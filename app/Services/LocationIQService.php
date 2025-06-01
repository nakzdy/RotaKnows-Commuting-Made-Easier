<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class LocationIQService
{
    /**
     * Geocode an address using LocationIQ API.
     *
     * @param string|null $address
     * @return array
     */
    public function geocode(?string $address): array
    {
        if (empty($address)) {
            return [
                'status' => 400,
                'error' => 'Address is required',
            ];
        }

        try {
            $response = Http::get('https://us1.locationiq.com/v1/search.php', [
                'key' => env('LOCATIONIQ_API_KEY'),
                'q' => $address,
                'format' => 'json',
            ]);

            if ($response->successful() && !empty($response->json())) {
                return $response->json(); // Laravel auto-transforms arrays into JSON
            }

            return [
                'status' => 404,
                'error' => 'No results found',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 500,
                'error' => 'Server error: ' . $e->getMessage(),
            ];
        }
    }
}