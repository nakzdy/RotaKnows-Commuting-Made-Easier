<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\Request;

class PlacesService
{
    protected Client $client;
    protected string $apiKey;
    protected string $baseUrl;

    public function __construct()
    {
        $this->client = new Client();
        $this->apiKey = config('services.foursquare.api_key');
        $this->baseUrl = config('services.foursquare.base_url');
    }

    /**
     * Search places using Foursquare Places API.
     * Extracts input params from the request with defaults.
     *
     * @param Request $request
     * @return array
     */
    public function searchPlaces(Request $request): array
    {
        $query = $request->input('query', 'mall');
        $lat = $request->input('lat', '14.5995');    // Manila latitude
        $lng = $request->input('lon', '120.9842');   // Manila longitude ('lon')

        try {
            $response = $this->client->get($this->baseUrl . '/search', [
                'headers' => [
                    'Authorization' => $this->apiKey,
                    'Accept' => 'application/json',
                ],
                'query' => [
                    'query' => $query,
                    'll' => "{$lat},{$lng}",
                    'limit' => 10,
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            return [
                'error' => true,
                'message' => 'Failed to fetch Places content: ' . $e->getMessage(),
            ];
        }
    }
}
