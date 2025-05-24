<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class PlacesService
{
    protected $client;
    protected $apiKey;
    protected $baseUrl;

    public function __construct()
    {
        $this->client = new Client();
        $this->apiKey = config('services.places.api_key');
        $this->baseUrl = config('services.places.base_url');
    }

    public function getNearbyPlaces($lat, $lon, $query = '', $radius = 1500, $limit = 10)
    {
        try {
            $response = $this->client->get($this->baseUrl, [
                'headers' => [
                    'Accept' => 'application/json',
                    'Authorization' => $this->apiKey,
                ],
                'query' => [
                    'll' => "$lat,$lon",
                    'query' => $query,
                    'radius' => $radius,
                    'limit' => $limit
                ]
            ]);

            return json_decode($response->getBody(), true);

        } catch (RequestException $e) {
            return [
                'error' => true,
                'message' => 'Failed to fetch Places data.'
            ];
        }
    }
}
