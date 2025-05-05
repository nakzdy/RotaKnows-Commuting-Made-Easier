<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\LocationIQService; 
use Exception;

class LocationController extends Controller
{
    protected $locationIQService;

    public function __construct(LocationIQService $locationIQService)
    {
        $this->locationIQService = $locationIQService;
    }

    public function geocode(Request $request, string $address = null)
    {
        // Handle GET request with address in the URL path (/api/geocode/{address})
        if ($request->isMethod('get') && $address) {
            // $address is already set
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
        } else {
            return response()->json(['error' => 'Invalid request method'], 405);
        }

        try {
            $result = $this->locationIQService->geocode($address); 

            if ($result) {
                return response()->json($result);
            } else {
                return response()->json(['error' => 'No results found'], 404);
            }
        } catch (Exception $e) {
            return response()->json(['error' => 'Geocoding failed', 'details' => $e->getMessage()], 500);
        }
    }
}
