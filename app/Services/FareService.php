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
        // 1. Validate incoming data
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
        $vehicleType = strtolower($data['vehicle_type']); // Get and normalize vehicle type
        $foursquareQuery = $data['foursquare_query'] ?? 'restaurant';

        // 2. Geocode Origin and Destination using LocationIQ
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

        // 3. Get Route and Distance using TomTom (or fallback to LocationIQ)
        $distanceInMeters = 0;
        $travelTimeInSeconds = 0;
        try {
            $tomTomCoordinatesString = "{$originCoords['lat']},{$originCoords['lon']}:{$destinationCoords['lat']},{$destinationCoords['lon']}";
            $tomTomRoute = $this->tomTomService->calculateRoute(
                $tomTomCoordinatesString,
                ['travelMode' => 'car', 'traffic' => 'true', 'departAt' => 'now'] // Assuming 'car' for general routing
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

        // 4. Calculate Fare based on Vehicle Type and Distance (your heuristic logic)
        $expectedFare = 0;
        $originLower = strtolower($originAddress);
        $destinationLower = strtolower($destinationAddress);

        // Define base rates (adjust these as needed for your specific context)
        $jeepneyBaseFare = 10; // Example local jeepney base fare
        $jeepneyPerKmRate = 1.5; // Example jeepney per km rate for longer trips
        $busBaseFare = 20; // Example local bus base fare (if applicable)
        $provincialBusPerKmRate = 1; // Example provincial bus per km rate
        $minProvincialBusFare = 70; // Minimum fare for provincial bus trips
        $taxiFlagDown = 40; // Example taxi flag-down
        $taxiPerKmRate = 13.5; // Example taxi per km rate

        // Keywords to identify provincial destinations for specific bus logic
        $provincialKeywords = ['balingasag', 'gingoog', 'claveria', 'iligan', 'butuan']; // Add more as needed
        $isGeographicallyProvincial = false;
        foreach ($provincialKeywords as $keyword) {
            if (str_contains($destinationLower, $keyword)) {
                $isGeographicallyProvincial = true;
                break;
            }
        }
        // Also consider distance for provincial trips
        if ($distanceInKm > 30) { // Arbitrary threshold for provincial distance
             $isGeographicallyProvincial = true;
        }


        switch ($vehicleType) {
            case 'jeepney':
                if ($isGeographicallyProvincial) {
                    // For provincial jeepney trips, apply a higher per-km rate or a flat minimum
                    $expectedFare = max($jeepneyBaseFare, $distanceInKm * $jeepneyPerKmRate * 1.5); // Example: 1.5x regular jeepney rate
                    // Could also combine with provincial bus minimum if relevant
                    $expectedFare = max($expectedFare, $minProvincialBusFare);
                } else {
                    // Local jeepney fare (e.g., within CDO)
                    $expectedFare = $jeepneyBaseFare * 2; // Simple heuristic for short trips, adjust as needed
                }
                break;

            case 'bus':
                // Bus fare logic, primarily for provincial routes
                if ($isGeographicallyProvincial) {
                    $calculatedProvincialFare = max($minProvincialBusFare, $distanceInKm * $provincialBusPerKmRate);
                    // Add a hypothetical first leg local fare if originating within CDO to a bus terminal
                    $firstLegLocalFare = 0;
                    $cdoKeywords = ['cagayan de oro', 'cdo', 'divisoria', 'carmen', 'agora', 'lapasan'];
                    foreach ($cdoKeywords as $keyword) {
                        if (str_contains($originLower, $keyword)) {
                            $firstLegLocalFare = $jeepneyBaseFare; // Assuming local leg to terminal is jeepney
                            break;
                        }
                    }
                    $expectedFare = $firstLegLocalFare + $calculatedProvincialFare;
                } else {
                    // If bus is chosen for a non-provincial (local) route
                    // This scenario might not typically happen with "provincial bus" type vehicles
                    // You might need a specific local bus fare or consider it an unsupported combination
                    Log::warning("FareService: Bus selected for non-provincial trip ('{$originAddress}' to '{$destinationAddress}'). Using basic fare.");
                    $expectedFare = $busBaseFare + ($distanceInKm * 0.5); // Example basic local bus fare
                }
                break;

            case 'taxi':
            case 'private_car': // Assuming 'private_car' uses similar calculation to taxi for now
                $expectedFare = $taxiFlagDown + ($distanceInKm * $taxiPerKmRate);
                // Add considerations for traffic, time, surcharges if applicable
                break;

            default:
                Log::warning("FareService: Unsupported vehicle type '{$vehicleType}' provided. Using default jeepney fare.");
                $expectedFare = $jeepneyBaseFare * 2; // Default to local jeepney fare
                break;
        }
        $expectedFare = round($expectedFare, 0);


        // 5. Get Weather at Destination
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
            // Continue even if weather fails
        }


        // 6. Get News related to Destination
        $newsArticles = [];
        try {
            $gnews = $this->gnewsService->searchNews($destinationAddress);
            $newsArticles = $gnews['articles'] ?? [];
        } catch (\Exception $e) {
            Log::warning("FareService (GNews): Could not get news for {$destinationAddress}: " . $e->getMessage());
            // Continue even if news fails
        }


        // 7. Get Nearby Places using Foursquare
        $nearbyPlaces = [];
        try {
            if (isset($destinationCoords['lat']) && isset($destinationCoords['lon'])) {
                $placesResult = $this->placesService->getNearbyPlaces(
                    $destinationCoords['lat'],
                    $destinationCoords['lon'],
                    $foursquareQuery,
                    1500, // Radius in meters
                    5    // Limit of results
                );

                if (!isset($placesResult['error'])) {
                    $nearbyPlaces = $placesResult['results'] ?? [];
                } else {
                    Log::warning("Foursquare Places API returned an error: " . ($placesResult['message'] ?? 'Unknown error'));
                }
            }
        } catch (\Exception $e) {
            Log::warning("FareService (Foursquare Places): " . $e->getMessage());
            // Continue even if places fails
        }


        // Return all calculated data
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
            'vehicle_type' => $vehicleType, // Include vehicle type in response
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
        // Define validation rules for creating a fare.
        // ADDED/MODIFIED: base_fare and distance_rate_per_km are now required.
        $validator = Validator::make($data, [
            'origin_address' => 'required|string|max:255',
            'destination_address' => 'required|string|max:255',
            'vehicle_type' => 'required|string|max:50',
            'distance_km' => 'required|numeric|min:0',
            'travel_time_minutes' => 'required|integer|min:0',
            'expected_fare' => 'required|numeric|min:0',
            'base_fare' => 'required|numeric|min:0', // <--- ADDED THIS LINE
            'distance_rate_per_km' => 'required|numeric|min:0', // <--- ADDED THIS LINE
            // Add any other fields from your 'fares' table you want to save
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // Create and save the new fare record
        $fare = Fare::create($data); // This assumes your Fare model has fillable properties set

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
        // Implement validation for updateFare here, matching your table columns
        $validator = Validator::make($data, [
            'fare_id' => 'required|integer|exists:fares,id', // 'exists:fares,id' checks if ID exists in 'fares' table
            'vehicle_type' => 'nullable|string|max:50',
            'base_fare' => 'nullable|numeric|min:0',
            'distance_rate_per_km' => 'nullable|numeric|min:0',
            'origin_address' => 'nullable|string|max:255',
            'destination_address' => 'nullable|string|max:255',
            // ADDED/MODIFIED: Allow these to be updated as well
            'distance_km' => 'nullable|numeric|min:0',
            'travel_time_minutes' => 'nullable|integer|min:0',
            'expected_fare' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        try {
            $fare = Fare::findOrFail($data['fare_id']);

            // Update only the fields that are present in the request data
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
            // ADDED: Explicitly update these fields if present in the data
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
            // Find the fare by ID and delete it
            $deleted = Fare::destroy($id);
            return (bool) $deleted;

        } catch (\Exception $e) {
            Log::error("FareService: Failed to delete fare ID {$id}: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return false;
        }
    }

}