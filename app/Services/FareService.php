<?php

namespace App\Services;

use App\Services\LocationIQService;
use App\Services\TomTomService;
use App\Services\WeatherService;
use App\Services\GNewsService;
use App\Services\PlacesService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Models\Fare;
use Illuminate\Support\Facades\Log;

class FareService
{
    protected LocationIQService $locationIQService;
    protected TomTomService $tomTomService;
    protected WeatherService $weatherService;
    protected GNewsService $gnewsService;
    protected PlacesService $placesService;

    public function __construct(
        LocationIQService $locationIQService,
        TomTomService $tomTomService,
        WeatherService $weatherService,
        GNewsService $gnewsService,
        PlacesService $placesService
    ) {
        $this->locationIQService = $locationIQService;
        $this->tomTomService = $tomTomService;
        $this->weatherService = $weatherService;
        $this->gnewsService = $gnewsService;
        $this->placesService = $placesService;
    }

    /**
     * Calculates all trip-related information (fare, distance, weather, news, and nearby places).
     * All arguments are expected within the $data array.
     *
     * @param array $data Expected keys: 'originAddress', 'destinationAddress', 'vehicle_type' (required).
     * @return array Returns an associative array of all trip details.
     * @throws \Illuminate\Validation\ValidationException if required arguments are missing.
     * @throws \Exception if geocoding fails or critical data is missing from external APIs.
     */
    public function calculateTripDetails(array $data): array
    {
        $validator = Validator::make($data, [
            'originAddress' => 'required|string|min:3',
            'destinationAddress' => 'required|string|min:3',
            'vehicle_type' => 'required|string|in:jeepney,bus,private_car,taxi',
            'foursquare_query' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $originAddress = $data['originAddress'];
        $destinationAddress = $data['destinationAddress'];
        $vehicleType = strtolower($data['vehicle_type']);
        $foursquareQuery = $data['foursquare_query'] ?? 'restaurant';

        try {
            $originGeocode = $this->locationIQService->geocode($originAddress);
            $destinationGeocode = $this->locationIQService->geocode($destinationAddress);

            if (isset($originGeocode['error']) || empty($originGeocode) || !isset($originGeocode['latitude']) || !isset($originGeocode['longitude'])) {
                Log::error("FareService: Could not geocode origin address '{$originAddress}'. Error: " . ($originGeocode['error'] ?? 'Unknown.'));
                return ['error' => 'Could not geocode origin address.', 'status' => 400];
            }
            if (isset($destinationGeocode['error']) || empty($destinationGeocode) || !isset($destinationGeocode['latitude']) || !isset($destinationGeocode['longitude'])) {
                Log::error("FareService: Could not geocode destination address '{$destinationAddress}'. Error: " . ($destinationGeocode['error'] ?? 'Unknown.'));
                return ['error' => 'Could not geocode destination address.', 'status' => 400];
            }
        } catch (\Exception $e) {
            Log::error("FareService (Geocoding): " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return ['error' => 'Geocoding service error: ' . $e->getMessage(), 'status' => 503];
        }

        $originCoords = [
            'lat' => $originGeocode['latitude'],
            'lon' => $originGeocode['longitude'],
        ];
        $destinationCoords = [
            'lat' => $destinationGeocode['latitude'],
            'lon' => $destinationGeocode['longitude'],
        ];

        $distanceInMeters = 0;
        $travelTimeInSeconds = 0;
        try {
            $tomTomCoordinatesString = "{$originCoords['lat']},{$originCoords['lon']}:{$destinationCoords['lat']},{$destinationCoords['lon']}";
            $tomTomRoute = $this->tomTomService->calculateRoute(
                $tomTomCoordinatesString,
                ['travelMode' => 'car', 'traffic' => 'true', 'departAt' => 'now']
            );

            if (isset($tomTomRoute['routes'][0]['summary'])) {
                $distanceInMeters = $tomTomRoute['routes'][0]['summary']['lengthInMeters'];
                $travelTimeInSeconds = $tomTomRoute['routes'][0]['summary']['travelTimeInSeconds'];
            } else {
                Log::warning("FareService: TomTom route failed. Attempting LocationIQ directions.");
                $locationIQDirections = $this->locationIQService->getDirections($originCoords, $destinationCoords);
                if (isset($locationIQDirections['routes'][0]['distance'])) {
                    $distanceInMeters = $locationIQDirections['routes'][0]['distance'];
                    $travelTimeInSeconds = $locationIQDirections['routes'][0]['duration'];
                } else {
                    Log::error("FareService: Could not determine route and distance from both TomTom and LocationIQ.");
                    return ['error' => 'Could not determine route and distance.', 'status' => 500];
                }
            }
        } catch (\Exception $e) {
            Log::error("FareService (Routing): " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return ['error' => 'Routing service error: ' . $e->getMessage(), 'status' => 503];
        }

        $distanceInKm = $distanceInMeters / 1000;
        $travelTimeInMinutes = round($travelTimeInSeconds / 60);

        $expectedFare = 0;
        $originLower = strtolower($originAddress);
        $destinationLower = strtolower($destinationAddress);

        $jeepneyBaseFare = 10;
        $jeepneyPerKmRate = 1.5;
        $busBaseFare = 20;
        $provincialBusPerKmRate = 1;
        $minProvincialBusFare = 70;
        $taxiFlagDown = 40;
        $taxiPerKmRate = 13.5;

        $provincialKeywords = ['balingasag', 'gingoog', 'claveria', 'iligan', 'butuan'];
        $isGeographicallyProvincial = false;
        foreach ($provincialKeywords as $keyword) {
            if (str_contains($destinationLower, $keyword)) {
                $isGeographicallyProvincial = true;
                break;
            }
        }

        if ($distanceInKm > 30) {
            $isGeographicallyProvincial = true;
        }

        switch ($vehicleType) {
            case 'jeepney':
                if ($isGeographicallyProvincial) {
                    $expectedFare = max($jeepneyBaseFare, $distanceInKm * $jeepneyPerKmRate * 1.5);
                    $expectedFare = max($expectedFare, $minProvincialBusFare);
                } else {
                    $expectedFare = $jeepneyBaseFare * 2;
                }
                break;

            case 'bus':
                if ($isGeographicallyProvincial) {
                    $calculatedProvincialFare = max($minProvincialBusFare, $distanceInKm * $provincialBusPerKmRate);
                    $firstLegLocalFare = 0;
                    $cdoKeywords = ['cagayan de oro', 'cdo', 'divisoria', 'carmen', 'agora', 'lapasan'];
                    foreach ($cdoKeywords as $keyword) {
                        if (str_contains($originLower, $keyword)) {
                            $firstLegLocalFare = $jeepneyBaseFare;
                            break;
                        }
                    }
                    $expectedFare = $firstLegLocalFare + $calculatedProvincialFare;
                } else {
                    Log::warning("FareService: Bus selected for non-provincial trip ('{$originAddress}' to '{$destinationAddress}'). Using basic fare.");
                    $expectedFare = $busBaseFare + ($distanceInKm * 0.5);
                }
                break;

            case 'taxi':
            case 'private_car':
                $expectedFare = $taxiFlagDown + ($distanceInKm * $taxiPerKmRate);
                break;

            default:
                Log::warning("FareService: Unsupported vehicle type '{$vehicleType}' provided. Using default jeepney fare.");
                $expectedFare = $jeepneyBaseFare * 2;
                break;
        }
        $expectedFare = round($expectedFare, 0);

        $weatherDescription = 'N/A';
        $temperature = 'N/A';
        try {
            $weatherResult = $this->weatherService->getWeather($destinationAddress);

            if (isset($weatherResult['error']) && $weatherResult['error'] === true) {
                Log::warning("FareService (Weather): WeatherService reported an error for {$destinationAddress}: " . $weatherResult['message']);
            } else {
                $openWeatherMapData = $weatherResult['weather'] ?? [];

                $weatherDescription = $openWeatherMapData['weather'][0]['description'] ?? 'N/A';
                $temperature = $openWeatherMapData['main']['temp'] ?? 'N/A';
            }
        } catch (\Exception $e) {
            Log::warning("FareService (Weather): Could not get weather for {$destinationAddress} due to an exception: " . $e->getMessage());
        }

        $newsArticles = [];
        try {
            $gnews = $this->gnewsService->searchNews($destinationAddress);
            $newsArticles = $gnews['articles'] ?? [];
        } catch (\Exception $e) {
            Log::warning("FareService (GNews): Could not get news for {$destinationAddress}: " . $e->getMessage());
        }

        $nearbyPlaces = [];
        try {
            if (isset($destinationCoords['lat']) && isset($destinationCoords['lon'])) {
                $placesResult = $this->placesService->getNearbyPlaces(
                    $destinationCoords['lat'],
                    $destinationCoords['lon'],
                    $foursquareQuery,
                    1500,
                    5
                );

                if (!isset($placesResult['error'])) {
                    $nearbyPlaces = $placesResult['results'] ?? [];
                } else {
                    Log::warning("Foursquare Places API returned an error: " . ($placesResult['message'] ?? 'Unknown error'));
                }
            }
        } catch (\Exception $e) {
            Log::warning("FareService (Foursquare Places): " . $e->getMessage());
        }

        return [
            'origin' => $originAddress,
            'destination' => $destinationAddress,
            'origin_coordinates' => $originCoords,
            'destination_coordinates' => $destinationCoords,
            'distance_km' => round($distanceInKm, 2),
            'travel_time_minutes' => $travelTimeInMinutes,
            'expected_fare' => $expectedFare,
            'weather' => [
                'description' => $weatherDescription,
                'temperature_celsius' => $temperature,
            ],
            'news' => $newsArticles,
            'nearby_places' => $nearbyPlaces,
            'vehicle_type' => $vehicleType,
        ];
    }

    /**
     * Creates a new fare record in the database.
     *
     * @param array $data Expected keys for fare details.
     * @return Fare The created Fare model instance.
     * @throws \Illuminate\Validation\ValidationException
     */
    public function createFare(array $data): Fare
    {
        $validator = Validator::make($data, [
            'origin_address' => 'required|string|max:255',
            'destination_address' => 'required|string|max:255',
            'vehicle_type' => 'required|string|max:50',
            'distance_km' => 'required|numeric|min:0',
            'travel_time_minutes' => 'required|integer|min:0',
            'expected_fare' => 'required|numeric|min:0',
            'base_fare' => 'required|numeric|min:0',
            'distance_rate_per_km' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $fare = Fare::create($data);

        return $fare;
    }

    /**
     * Updates an existing fare record in the database.
     * Arguments are expected within the $data array.
     *
     * @param array $data Expected keys: 'fare_id', 'vehicle_type', 'base_fare', 'distance_rate_per_km', etc.
     * @return array The updated fare data or an error array.
     * @throws \Illuminate\Validation\ValidationException if required arguments are missing or invalid.
     */
    public function updateFare(array $data): array
    {
        $validator = Validator::make($data, [
            'fare_id' => 'required|integer|exists:fares,id',
            'vehicle_type' => 'nullable|string|max:50',
            'base_fare' => 'nullable|numeric|min:0',
            'distance_rate_per_km' => 'nullable|numeric|min:0',
            'origin_address' => 'nullable|string|max:255',
            'destination_address' => 'nullable|string|max:255',
            'distance_km' => 'nullable|numeric|min:0',
            'travel_time_minutes' => 'nullable|integer|min:0',
            'expected_fare' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        try {
            $fare = Fare::findOrFail($data['fare_id']);

            if (isset($data['vehicle_type'])) {
                $fare->vehicle_type = $data['vehicle_type'];
            }
            if (isset($data['base_fare'])) {
                $fare->base_fare = $data['base_fare'];
            }
            if (isset($data['distance_rate_per_km'])) {
                $fare->distance_rate_per_km = $data['distance_rate_per_km'];
            }
            if (isset($data['origin_address'])) {
                $fare->origin_address = $data['origin_address'];
            }
            if (isset($data['destination_address'])) {
                $fare->destination_address = $data['destination_address'];
            }
            if (isset($data['distance_km'])) {
                $fare->distance_km = $data['distance_km'];
            }
            if (isset($data['travel_time_minutes'])) {
                $fare->travel_time_minutes = $data['travel_time_minutes'];
            }
            if (isset($data['expected_fare'])) {
                $fare->expected_fare = $data['expected_fare'];
            }

            $fare->save();

            return $fare->toArray();
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning("FareService: Fare ID {$data['fare_id']} not found for update.");
            return ['error' => 'Fare not found.', 'status' => 404];
        } catch (\Exception $e) {
            Log::error("FareService: Failed to update fare ID {$data['fare_id']}: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return ['error' => 'Failed to update fare due to an internal error.', 'status' => 500];
        }
    }

    /**
     * Deletes a fare record from the database.
     *
     * @param int $id The ID of the fare to delete.
     * @return bool True if deleted, false otherwise.
     */
    public function deleteFare(int $id): bool
    {
        try {
            $deleted = Fare::destroy($id);
            return (bool) $deleted;
        } catch (\Exception $e) {
            Log::error("FareService: Failed to delete fare ID {$id}: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return false;
        }
    }
}