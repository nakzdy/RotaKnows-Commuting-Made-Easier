<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;

class LocationController extends Controller
{
    public function geocode(Request $request, string $address = null)
    {
        // Handle GET request with address in the URL path (/api/geocode/{address})
        if ($request->isMethod('get') && $address) {
        }
        // Handle GET request with address as a query parameter (/api/geocode?address=...)
        elseif ($request->isMethod('get')) {
            $address = $request->query('address');
            if (!$address) {
                return response()->json(['error' => 'Address is required'], 400);
            }
        }
        // Handle POST request with address in the request body (/api/geocode)
        elseif ($request->isMethod('post')) {
            $request->validate([
                'address' => 'required|string',
            ]);
            $address = $request->input('address');
        }
        else {
            return response()->json(['error' => 'Invalid request method'], 405);
        }

        $apiKey = env('LOCATIONIQ_API_KEY');

        $client = new Client();
        $url = "https://us1.locationiq.com/v1/search.php";

        try {
            // Make the API request to LocationIQ
            $response = $client->request('GET', $url, [
                'query' => [
                    'key' => $apiKey,
                    'q' => $address,
                    'format' => 'json',
                ],
            ]);

            // Decode the JSON response
            $data = json_decode($response->getBody(), true);

            // Check if data is not empty and return the result
            if (!empty($data)) {
                $result = [
                    'latitude' => $data[0]['lat'],
                    'longitude' => $data[0]['lon'],
                    'display_name' => $data[0]['display_name']
                ];
                return response()->json($result);
            } else {
                return response()->json(['error' => 'No results found'], 404);
            }
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            // Handle client errors (4xx)
            return response()->json(['error' => 'Client error', 'details' => $e->getMessage()], $e->getCode());
        } catch (\GuzzleHttp\Exception\ServerException $e) {
            // Handle server errors (5xx)
            return response()->json(['error' => 'Server error', 'details' => $e->getMessage()], $e->getCode());
        } catch (\Exception $e) {
            // Handle any other exceptions
            return response()->json(['error' => 'Failed to connect to LocationIQ API', 'details' => $e->getMessage()], 500);
        }
    }

}
//ANYTHING ABOVE HERE DI HILABTAN except lang anang 'use' kay mag add man gyud mo ana - XAN