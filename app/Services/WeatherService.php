<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Exception;
use Illuminate\Support\Facades\Log; 

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
            Log::error('OpenWeather API key is not set in the .env file or config/services.php.');
            throw new Exception('OpenWeather API key is not configured.');
        }
    }

    /**
     * Get weather data for the specified city/address.
     *
     * @param string $city The city name or address for which to get weather.
     * @return array Returns weather data or an error array.
     */
    public function getWeather(string $city): array // <--- CHANGED THIS LINE
    {
        if (empty($city)) {
            Log::warning("WeatherService: Attempted to get weather for an empty city string.");
            return [
                'error' => true,
                'message' => 'City name cannot be empty for weather lookup.',
            ];
        }

        try {
            $response = $this->client->get($this->baseUrl, [
                'query' => [
                    'q' => $city,
                    'appid' => $this->apiKey,
                    'units' => 'metric', // Celsius
                ],
            ]);

            if ($response->getStatusCode() === 200) {
                $weatherData = json_decode($response->getBody()->getContents(), true);
                return [
                    'city' => $city,
                    'weather' => $weatherData, // Return the full weather data
                ];
            } else {
                // Handle non-200 responses from OpenWeatherMap API
                Log::error("WeatherService: OpenWeatherMap API returned status {$response->getStatusCode()} for city '{$city}'. Response: " . $response->getBody()->getContents());
                 return [
                    'error' => true,
                    'message' => 'Failed to fetch weather data from OpenWeatherMap API. Status: ' . $response->getStatusCode(),
                    'api_response' => $response->getBody()->getContents() // Include API response for debugging
                ];
            }


        } catch (RequestException $e) {
            // This catches Guzzle HTTP client errors (network issues, API errors handled by Guzzle)
            $statusCode = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 500;
            $errorMessage = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : $e->getMessage();

            Log::error("WeatherService: Guzzle RequestException for city '{$city}': " . $errorMessage . "\n" . $e->getTraceAsString());

            return [
                'error' => true,
                'message' => "Unable to fetch weather data: " . ($e->hasResponse() ? 'API error' : 'Network error') . ". Status: {$statusCode}. " . $e->getMessage(),
            ];
        } catch (Exception $e) {
            // Catch any other general exceptions
            Log::error("WeatherService: General Exception for city '{$city}': " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return [
                'error' => true,
                'message' => 'An unexpected error occurred while processing weather data: ' . $e->getMessage(),
            ];
        }
    }
}