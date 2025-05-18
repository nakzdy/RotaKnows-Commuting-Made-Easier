<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Exception;

class WeatherService
{
    protected $client;
    protected $apiKey;
    protected $baseUrl;

    public function __construct()
    {
        $this->client = new Client();

        // Use config helper to load from config/services.php, fallback to env if needed
        $this->apiKey = config('services.openweather.api_key') ?? env('OPENWEATHER_API_KEY');
        $this->baseUrl = config('services.openweather.base_url') ?? 'https://api.openweathermap.org/data/2.5/weather';

        if (empty($this->apiKey)) {
            throw new Exception('OpenWeather API key is not set in the .env file.');
        }
    }

    public function getWeather(string $city)
    {
        try {
            $response = $this->client->get($this->baseUrl, [
                'query' => [
                    'q' => $city,
                    'appid' => $this->apiKey,
                    'units' => 'metric',  // Celsius
                ],
            ]);

            return json_decode($response->getBody(), true);

        } catch (RequestException $e) {
            return [
                'error' => true,
                'message' => 'Unable to fetch weather data. Please check the city name or try again later.',
            ];
        }
    }
}
