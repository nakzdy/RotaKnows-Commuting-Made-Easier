<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TomTomService
{
    protected string $apiKey;
    protected string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.tomtom.key');
        $this->baseUrl = rtrim(config('services.tomtom.base_url'), '/');
    }

    public function search(string $query, array $params = []): array
    {
        if (empty($query)) {
            return ['error' => 'Search query is required', 'status' => 400];
        }

        $endpoint = '/search/2/search/';
        $url = "{$this->baseUrl}{$endpoint}" . urlencode($query) . ".json";

        try {
            $response = Http::get($url, array_merge(['key' => $this->apiKey], $params));

            if ($response->successful()) {
                return $response->json();
            }

            Log::error("TomTom API Error (Search): HTTP {$response->status()} - {$response->body()}");
            return ['error' => 'Failed to fetch search results', 'status' => $response->status()];
        } catch (\Exception $e) {
            Log::error("TomTom API Exception (Search): {$e->getMessage()}");
            return ['error' => 'Exception occurred during search', 'status' => 500];
        }
    }

    public function calculateRoute(string $coordinates, array $params = []): array
    {
        if (empty($coordinates)) {
            return ['error' => 'Coordinates are required', 'status' => 400];
        }

        $endpoint = '/routing/1/calculateRoute/';
        $url = "{$this->baseUrl}{$endpoint}{$coordinates}/json";

        try {
            $response = Http::get($url, array_merge(['key' => $this->apiKey], $params));

            if ($response->successful()) {
                return $response->json();
            }

            Log::error("TomTom API Error (Routing): HTTP {$response->status()} - {$response->body()}");
            return ['error' => 'Failed to calculate route', 'status' => $response->status()];
        } catch (\Exception $e) {
            Log::error("TomTom API Exception (Routing): {$e->getMessage()}");
            return ['error' => 'Exception occurred during routing', 'status' => 500];
        }
    }
}