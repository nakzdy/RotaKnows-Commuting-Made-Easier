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
        $this->apiKey = config('services.foursquare.api_key');
        $this->baseUrl = config('services.foursquare.base_url');
    }

    public function searchPlaces($query = 'mall', $lat = '14.5995', $lng = '120.9842')
    {
        try {
            $response = $this->client->get($this->baseUrl . '/search', [
                'headers' => [
                    'Authorization' => $this->apiKey,
                    'Accept' => 'application/json',
                ],
                'query' => [
                    'query' => $query,
                    'll' => $lat . ',' . $lng,  // lat,lng format
                    'limit' => 10,
                ],
            ]);

            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            return [
                'error' => true,
                'message' => 'Failed to fetch Places content: ' . $e->getMessage(),
            ];
        }
    }
}
