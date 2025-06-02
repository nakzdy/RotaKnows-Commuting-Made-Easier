<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config; 

class PlacesService
{
    protected Client $client;
    protected string $apiKey;
    protected string $baseUrl;

    public function __construct()
    {
        $this->client = new Client();
        $this->apiKey = Config::get('services.foursquare.api_key');
        $this->baseUrl = Config::get('services.foursquare.base_url');

        if (empty($this->apiKey)) {
            Log::error('Foursquare API key is not set in the .env file or config/services.php.');
            throw new \Exception('Foursquare API key is not configured.');
        }
    }

    /**
     * Search nearby places using Foursquare Places API.
     *
     * @param float $lat Latitude of the location.
     * @param float $lon Longitude of the location.
     * @param string $query (Optional) Keyword to search for (e.g., 'restaurant', 'coffee shop').
     * @param int $radius (Optional) Radius in meters. Max 100000 (100km). Default 2000.
     * @param int $limit (Optional) Number of results to return. Default 10. Max 50.
     * @return array Returns an array of places or an error array.
     */
    public function getNearbyPlaces(
        float $lat,
        float $lon,
        string $query = 'restaurant',
        int $radius = 2000,
        int $limit = 10
    ): array {
        

        // Ensure radius and limit are within valid ranges
        $radius = max(1, min(100000, $radius));
        $limit = max(1, min(50, $limit));

        $queryParams = [
            'query' => $query,
            'll' => "{$lat},{$lon}", 
            'radius' => $radius,
            'limit' => $limit,
        ];
        Log::info("Foursquare Places Request Parameters: " . json_encode($queryParams));

        try {
            $response = $this->client->get($this->baseUrl . '/search', [
                'headers' => [
                    'Authorization' => $this->apiKey,
                    'Accept' => 'application/json',
                ],
                'query' => $queryParams,
            ]);

            // Read the response body once into a variable
            $responseBody = $response->getBody()->getContents();

            Log::info("Foursquare Places Response Status: " . $response->getStatusCode());

            
            if (Config::get('app.debug')) {
                Log::info("Foursquare Places Response Body: " . $responseBody);
            }

            if ($response->getStatusCode() === 200) {
                $data = json_decode($responseBody, true); // Use the already read body
                if (json_last_error() !== JSON_ERROR_NONE) {
                    Log::error("PlacesService: Failed to decode Foursquare JSON response for query '{$query}' at ({$lat},{$lon}).");
                    return ['error' => 'Invalid JSON response from Foursquare API.', 'status' => 500];
                }
                return $data;
            } else {
                // Use the already read response body for error messages
                $errorMessage = json_decode($responseBody, true)['message'] ?? 'Unknown error from Foursquare API';
                Log::error("PlacesService: Foursquare API returned status {$response->getStatusCode()} for query '{$query}' at ({$lat},{$lon}). Error: {$errorMessage}. Response Body: {$responseBody}");
                return [
                    'error' => true,
                    'message' => 'Foursquare API error: ' . $errorMessage,
                    'status' => $response->getStatusCode(),
                ];
            }
        } catch (RequestException $e) {
            $statusCode = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 500;
            // Get response body for error message if available, otherwise just exception message
            $errorMessage = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : $e->getMessage();
            Log::error("PlacesService: Guzzle RequestException for query '{$query}' at ({$lat},{$lon}): " . $errorMessage . "\n" . $e->getTraceAsString());
            return [
                'error' => true,
                'message' => 'Network or Foursquare API request failed: ' . $e->getMessage(),
                'status' => $statusCode,
            ];
        } catch (\Exception $e) {
            Log::error("PlacesService: General Exception for query '{$query}' at ({$lat},{$lon}): " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return [
                'error' => true,
                'message' => 'An unexpected error occurred in PlacesService: ' . $e->getMessage(),
                'status' => 500,
            ];
        }
    }
}