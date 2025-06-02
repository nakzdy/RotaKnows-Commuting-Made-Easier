<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LocationIQService
{
    /**
     * Geocode an address using LocationIQ API.
     *
     * @param string|null $address
     * @return array Returns an array with 'latitude', 'longitude', 'display_name', or 'error' and 'status'.
     */
    public function geocode(?string $address): array
    {
        if (empty($address)) {
            Log::warning("LocationIQService: Geocode request received with empty address.");
            return [
                'status' => 400,
                'error' => 'Address is required for geocoding.',
            ];
        }

        $apiKey = config('services.locationiq.api_key'); 
        $baseUrl = config('services.locationiq.base_url'); 

        if (empty($apiKey)) {
            Log::error("LocationIQService: LocationIQ API key is not configured in .env or services.php.");
            return [
                'status' => 500,
                'error' => 'LocationIQ API key is missing.',
            ];
        }

        try {
            $response = Http::get("{$baseUrl}/search.php", [
                'key' => $apiKey,
                'q' => $address,
                'format' => 'json',
                'limit' => 1 // Limit to 1 result for simplicity
            ]);

            // Log the request URL and response for debugging
            Log::info("LocationIQ Geocode Request URL: " . $response->effectiveUri());
            Log::info("LocationIQ Geocode Response Status: " . $response->status());
            Log::info("LocationIQ Geocode Response Body: " . $response->body());

            if ($response->successful()) {
                $data = $response->json();

                // Check if data is not empty and contains at least one result
                if (!empty($data) && isset($data[0])) {
                    return [
                        'latitude' => (float) $data[0]['lat'], // Cast to float for consistency
                        'longitude' => (float) $data[0]['lon'],
                        'display_name' => $data[0]['display_name'] ?? $address, // Use display_name if available
                    ];
                } else {
                    // LocationIQ API returned successful status but no geocoding results
                    Log::warning("LocationIQService: No geocoding results found for address '{$address}'.");
                    return [
                        'status' => 404,
                        'error' => 'No geocoding results found for the given address.',
                    ];
                }
            } else {
                // LocationIQ API returned an error status (e.g., 401, 403, 429, 500)
                $errorMessage = $response->json('error', 'Unknown LocationIQ API error');
                Log::error("LocationIQService: API call failed for '{$address}'. Status: {$response->status()}. Error: {$errorMessage}");
                return [
                    'status' => $response->status(),
                    'error' => "LocationIQ API error: {$errorMessage}",
                ];
            }

        } catch (\Exception $e) {
            // Catch network errors, cURL errors, etc.
            Log::error("LocationIQService: Exception during geocoding for '{$address}': " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return [
                'status' => 500,
                'error' => 'An unexpected error occurred while contacting the geocoding service: ' . $e->getMessage(),
            ];
        }
    }
}