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

    public function __construct()
    {
        $this->client = new Client();
        $this->apiKey = env('LOCATIONIQ_API_KEY');
    }

    public function geocode(string $address)
    {
        $url = "https://us1.locationiq.com/v1/search.php";

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
