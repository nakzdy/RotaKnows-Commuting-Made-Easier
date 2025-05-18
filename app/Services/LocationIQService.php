<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use Exception;

class LocationIQService
{
    protected $client;
    protected $apiKey;
    protected $baseUrl;

    public function __construct()
    {
        $this->client = new Client();
        $this->apiKey = config('services.locationiq.api_key');    // load from config
        $this->baseUrl = config('services.locationiq.base_url');  // load from config

        if (empty($this->apiKey)) {
            throw new Exception('LocationIQ API key is not set.');
        }
    }

    public function geocode(string $address)
    {
        $url = $this->baseUrl . '/search.php';

        try {
            $response = $this->client->request('GET', $url, [
                'query' => [
                    'key' => $this->apiKey,
                    'q' => $address,
                    'format' => 'json',
                ],
            ]);

            $data = json_decode($response->getBody(), true);

            if (!empty($data)) {
                return [
                    'latitude' => $data[0]['lat'],
                    'longitude' => $data[0]['lon'],
                    'display_name' => $data[0]['display_name'],
                ];
            } else {
                return null;
            }
        } catch (ClientException $e) {
            $responseBody = $e->getResponse()->getBody(true);
            $errorData = json_decode($responseBody, true) ?? ['message' => $responseBody->getContents()];
            throw new Exception('LocationIQ Client Error: ' . json_encode($errorData), $e->getCode());
        } catch (ServerException $e) {
            $responseBody = $e->getResponse()->getBody(true);
            $errorData = json_decode($responseBody, true) ?? ['message' => $responseBody->getContents()];
            throw new Exception('LocationIQ Server Error: ' . json_encode($errorData), $e->getCode());
        } catch (Exception $e) {
            throw new Exception('Failed to connect to LocationIQ API: ' . $e->getMessage(), 500);
        }
    }
}
