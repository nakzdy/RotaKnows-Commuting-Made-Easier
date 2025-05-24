<?php

namespace App\Services;

use App\Services\LocationIQService;
use App\Services\TomTomService;
use App\Services\WeatherService;
use App\Services\GNewsService;
use Throwable; 

class FareCalculationService
{
    protected $locationIQService;
    protected $tomTomService;
    protected $weatherService;
    protected $gnewsService;

    public function __construct(
        LocationIQService $locationIQService,
        TomTomService $tomTomService,
        WeatherService $weatherService,
        GNewsService $gnewsService
    ) {
        $this->locationIQService = $locationIQService;
        $this->tomTomService = $tomTomService;
        $this->weatherService = $weatherService;
        $this->gnewsService = $gnewsService;
    }

    /**
     * Calculates all trip-related information (fare, distance, weather, news).
     *
     * @param string $originAddress
     * @param string $destinationAddress
     * @return array Returns an associative array of all trip details.
     * @throws \Exception if geocoding fails or critical data is missing.
     */
    public function calculateTripDetails(string $originAddress, string $destinationAddress): array
    {
        // 1. Geocode Origin and Destination using LocationIQ
        $originGeocode = $this->locationIQService->geocode($originAddress);
        $destinationGeocode = $this->locationIQService->geocode($destinationAddress);

        // This service should throw exceptions if core dependencies fail,
        // allowing the calling controller/job to catch and handle.
        if (empty($originGeocode) || !isset($originGeocode['latitude']) || !isset($originGeocode['longitude'])) {
            throw new \Exception('Could not geocode origin address: ' . $originAddress);
        }
        if (empty($destinationGeocode) || !isset($destinationGeocode['latitude']) || !isset($destinationGeocode['longitude'])) {
            throw new \Exception('Could not geocode destination address: ' . $destinationAddress);
        }

        $originCoords = [
            'lat' => $originGeocode['latitude'],
            'lon' => $originGeocode['longitude'],
        ];
        $destinationCoords = [
            'lat' => $destinationGeocode['latitude'],
            'lon' => $destinationGeocode['longitude'],
        ];

        // 2. Get Route and Distance using TomTom (or fallback to LocationIQ)
        $tomTomCoordinatesString = "{$originCoords['lat']},{$originCoords['lon']}:{$destinationCoords['lat']},{$destinationCoords['lon']}";
        $tomTomRoute = $this->tomTomService->calculateRoute(
            $tomTomCoordinatesString,
            ['travelMode' => 'car', 'traffic' => 'true', 'departAt' => 'now']
        );

        $distanceInMeters = 0;
        $travelTimeInSeconds = 0;

        if (isset($tomTomRoute['routes'][0]['summary'])) {
            $distanceInMeters = $tomTomRoute['routes'][0]['summary']['lengthInMeters'];
            $travelTimeInSeconds = $tomTomRoute['routes'][0]['summary']['travelTimeInSeconds'];
        } else {
            $locationIQDirections = $this->locationIQService->getDirections($originCoords, $destinationCoords);
            if (isset($locationIQDirections['routes'][0]['distance'])) {
                $distanceInMeters = $locationIQDirections['routes'][0]['distance'];
                $travelTimeInSeconds = $locationIQDirections['routes'][0]['duration'];
            } else {
                throw new \Exception('Could not determine route and distance from both TomTom and LocationIQ.');
            }
        }

        $distanceInKm = $distanceInMeters / 1000;
        $travelTimeInMinutes = round($travelTimeInSeconds / 60);

        // 3. Calculate Fare (using your public transport heuristic logic)
        $expectedFare = 0;
        $originLower = strtolower($originAddress);
        $destinationLower = strtolower($destinationAddress);
        $jeepneyFare = 15;
        $provincialBusFarePerKm = 3;
        $minProvincialBusFare = 80;
        $cdoKeywords = ['cagayan de oro', 'cdo', 'divisoria', 'carmen', 'agora', 'lapasan'];

        $isProvincialBusTrip = false;
        if ($distanceInKm > 30 || str_contains($destinationLower, 'balingasag') || str_contains($destinationLower, 'gingoog') || str_contains($destinationLower, 'claveria')) {
            $isProvincialBusTrip = true;
        }

        if ($isProvincialBusTrip) {
            $calculatedProvincialFare = max($minProvincialBusFare, $distanceInKm * $provincialBusFarePerKm);
            $firstLegLocalFare = 0;
            foreach ($cdoKeywords as $keyword) {
                if (str_contains($originLower, $keyword)) {
                    $firstLegLocalFare = $jeepneyFare;
                    break;
                }
            }
            $expectedFare = $firstLegLocalFare + $calculatedProvincialFare;
        } else {
            $expectedFare = $jeepneyFare * 2;
        }
        $expectedFare = round($expectedFare, 0);

        // 4. Get Weather at Destination
        $weather = $this->weatherService->getWeather($destinationAddress);
        $weatherDescription = $weather['weather'][0]['description'] ?? 'N/A';
        $temperature = $weather['main']['temp'] ?? 'N/A';

        // 5. Get News related to Destination
        $gnews = $this->gnewsService->searchNews($destinationAddress);
        $newsArticles = $gnews['articles'] ?? [];

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
        ];
    }
}