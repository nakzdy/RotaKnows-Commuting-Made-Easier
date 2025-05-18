<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class GNewsService
{
    protected $client;
    protected $apiKey;
    protected $baseUrl;

    public function __construct()
    {
        $this->client = new Client();
        $this->apiKey = config('services.gnews.api_key');
        $this->baseUrl = config('services.gnews.base_url');
    }

    public function searchNews($query = 'traffic Philippines')
    {
        try {
            $response = $this->client->get($this->baseUrl, [
                'query' => [
                    'q' => $query,
                    'lang' => 'en',
                    'country' => 'ph',
                    'token' => $this->apiKey,
                    'max' => 10
                ]
            ]);

            return json_decode($response->getBody(), true);

        } catch (RequestException $e) {
            return [
                'error' => true,
                'message' => 'Failed to fetch GNews content.'
            ];
        }
    }
}
