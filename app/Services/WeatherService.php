<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Exception;
use Illuminate\Http\Request;

class WeatherService
{
    protected Client $client;
    protected string $apiKey;
    protected string $baseUrl;

    public function __construct()
    {
        $this->client = new Client();

        $this->apiKey = config('services.openweather.api_key');
        $this->baseUrl = config('services.openweather.base_url');

        if (empty($this->apiKey)) {
            throw new Exception('OpenWeather API key is not set in the .env file.');
        }
    }

    /**
     * Get weather data for the requested city.
     *
     * @param Request $request
     * @return array
     */
    public function getWeather(Request $request): array
    {
        $city = $request->input('city', 'Cagayan de Oro City');

        try {
            $response = $this->client->get($this->baseUrl, [
                'query' => [
                    'q' => $city,
                    'appid' => $this->apiKey,
                    'units' => 'metric', // Celsius
                ],
            ]);

            return [
                'city' => $city,
                'weather' => json_decode($response->getBody()->getContents(), true),
            ];

        } catch (RequestException $e) {
            return [
                'error' => true,
                'message' => 'Unable to fetch weather data. Please check the city name or try again later.',
            ];
        }
    }
}