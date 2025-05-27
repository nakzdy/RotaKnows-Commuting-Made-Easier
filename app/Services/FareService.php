<?php

namespace App\Services;

use App\Models\Fare; // Assuming you have a Fare model
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Throwable;

class FareService
{
    protected $apiKey;
    protected $baseUrl = 'https://api.foursquare.com/v3/places/search';

    public function __construct()
    {
        $this->apiKey = config('services.foursquare.api_key');
    }

    /**
     * Calculate fare based on origin and destination addresses.
     *
     * @param string $originAddress
     * @param string $destinationAddress
     * @return array
     */
    public function calculateFare(string $originAddress, string $destinationAddress): array
    {
        // Validate inputs
        $validator = Validator::make([
            'origin_address' => $originAddress,
            'destination_address' => $destinationAddress,
        ], [
            'origin_address' => 'required|string|min:5',
            'destination_address' => 'required|string|min:5',
        ]);

        if ($validator->fails()) {
            return [
                'status' => 'error',
                'message' => $validator->errors()->first(),
                'code' => 400
            ];
        }

        try {
            // Geocode addresses
            $origin = $this->geocodeAddress($originAddress);
            $destination = $this->geocodeAddress($destinationAddress);

            // Calculate distance using haversine formula
            $distance = $this->getDistance($origin, $destination);

            // Calculate fare (base fare + per km rate)
            $baseFare = 40; // Base fare in PHP
            $perKmRate = 13; // Rate per km in PHP
            $expectedFare = $baseFare + $distance * $perKmRate;

            return [
                'status' => 'success',
                'data' => [
                    'origin' => $originAddress,
                    'destination' => $destinationAddress,
                    'distance_km' => round($distance, 2),
                    'expected_fare' => round($expectedFare, 2),
                    'currency' => 'PHP'
                ]
            ];
        } catch (Throwable $e) {
            Log::error("API integration error in FareService: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            if (str_contains($e->getMessage(), 'Could not geocode')) {
                return [
                    'status' => 'error',
                    'message' => $e->getMessage() . " Try including the city or region (e.g., 'Pagatpat, Cagayan de Oro, Philippines').",
                    'code' => 400
                ];
            }
            return [
                'status' => 'error',
                'message' => 'An unexpected error occurred. Please try again later.',
                'code' => 500
            ];
        }
    }

    /**
     * Retrieve fare information from the database.
     *
     * @return array
     * @throws \Exception
     */
    public function getFareInfo(): array
    {
        try {
            $fares = Fare::all(); // Adjust query as needed
            return $fares->toArray();
        } catch (\Exception $e) {
            throw new \Exception('Error retrieving fare information: ' . $e->getMessage());
        }
    }

    /**
     * Update fare information in the database.
     *
     * @param array $data
     * @return Fare
     * @throws \Exception
     */
    public function updateFare(array $data): Fare
    {
        try {
            $fare = Fare::findOrFail($data['fare_id']);
            $fare->update([
                'amount' => $data['amount'],
                // Add other fields as needed
            ]);
            return $fare;
        } catch (\Exception $e) {
            throw new \Exception('Error updating fare: ' . $e->getMessage());
        }
    }

    /**
     * Geocode an address using Foursquare API.
     *
     * @param string $address
     * @return array
     * @throws \Exception
     */
    protected function geocodeAddress(string $address): array
    {
        $response = Http::withHeaders([
            'Authorization' => $this->apiKey,
            'Accept' => 'application/json'
        ])->get($this->baseUrl, [
            'query' => $address,
            'near' => 'Philippines',
            'limit' => 1,
            'fields' => 'geocodes,name,location'
        ]);

        if ($response->successful() && !empty($response->json()['results'])) {
            $geocodes = $response->json()['results'][0]['geocodes']['main'];
            return ['lat' => $geocodes['latitude'], 'lng' => $geocodes['longitude']];
        }

        throw new \Exception("Could not geocode address: {$address}");
    }

    /**
     * Calculate distance between two points using Haversine formula.
     *
     * @param array $origin
     * @param array $destination
     * @return float
     */
    protected function getDistance(array $origin, array $destination): float
    {
        $earthRadius = 6371; // Earth's radius in km

        $latFrom = deg2rad($origin['lat']);
        $lonFrom = deg2rad($origin['lng']);
        $latTo = deg2rad($destination['lat']);
        $lonTo = deg2rad($destination['lng']);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));

        return $angle * $earthRadius; // Distance in kilometers
    }
}