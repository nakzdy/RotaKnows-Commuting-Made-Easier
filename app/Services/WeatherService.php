<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class WeatherService
{
    protected $client;
    protected $apiKey;

    public function __construct()
    {
        $this->client = new Client();
        $this->apiKey = env('OPENWEATHER_API_KEY');

        if (empty($this->apiKey)) {
            throw new \Exception('OpenWeather API key is not set in the .env file.');
        }
    }

    public function getWeather($city)
    {
        try {
            $response = $this->client->get('https://api.openweathermap.org/data/2.5/weather', [
                'query' => [
                    'q' => $city,
                    'appid' => $this->apiKey,
                    'units' => 'metric', // Celsius
                ]
            ]);

            return json_decode($response->getBody(), true);

        } catch (RequestException $e) {
            return [
                'error' => true,
                'message' => 'Unable to fetch weather data. Please check the city name or try again later.'
            ];
        }
    }
}
