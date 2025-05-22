<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class TomTomService
{
    protected $apiKey;
    protected $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.tomtom.key');
        // Now getting the base URL from the config file
        $this->baseUrl = config('services.tomtom.base_url'); 
    }

    /**
     * Performs a TomTom Search API request.
     * * @param string $query The search query (e.g., "coffee shop")
     * @param array $params Additional parameters for the API request (e.g., ['lat' => 40.7128, 'lon' => -74.0060])
     * @param string $endpoint The specific endpoint to hit (e.g., '/search/2/search/')
     * @return array|null The API response data or null on failure.
     */
    public function search(string $query, array $params = [], string $endpoint = '/search/2/search/')
    {
        // Concatenate base URL with the specific endpoint and query
        $url = "{$this->baseUrl}{$endpoint}" . urlencode($query) . ".json";

        try {
            $response = Http::get($url, array_merge([
                'key' => $this->apiKey,
            ], $params));

            if ($response->successful()) {
                return $response->json();
            } else {
                \Log::error("TomTom API Error ({$endpoint}): " . $response->status() . " - " . $response->body());
                return null;
            }
        } catch (\Exception $e) {
            \Log::error("TomTom API Exception ({$endpoint}): " . $e->getMessage());
            return null;
        }
    }

    /**
     * Example for a different TomTom API, e.g., Routing.
     * You would add more methods here for other TomTom APIs you need.
     * * @param string $coordinates An array of origin and destination coordinates (e.g., ['40.7128,-74.0060:40.7580,-73.9855'])
     * @param array $params Additional parameters for the API request
     * @return array|null The API response data or null on failure.
     */
    public function calculateRoute(string $coordinates, array $params = [], string $endpoint = '/routing/1/calculateRoute/')
    {
        // Concatenate base URL with the specific endpoint and coordinates
        $url = "{$this->baseUrl}{$endpoint}" . $coordinates . "/json";

        try {
            $response = Http::get($url, array_merge([
                'key' => $this->apiKey,
            ], $params));

            if ($response->successful()) {
                return $response->json();
            } else {
                \Log::error("TomTom API Error ({$endpoint}): " . $response->status() . " - " . $response->body());
                return null;
            }
        } catch (\Exception $e) {
            \Log::error("TomTom API Exception ({$endpoint}): " . $e->getMessage());
            return null;
        }
    }

}