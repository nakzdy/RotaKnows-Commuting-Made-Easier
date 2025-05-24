<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\PlacesService;

class PlacesController extends Controller
{
    protected $placesService;

    // Inject the PlacesService
    public function __construct(PlacesService $placesService)
    {
        $this->placesService = $placesService;
    }

    // GET /api/places?lat=...&lon=...&query=...
    public function search(Request $request)
    {
        $lat = $request->input('lat');
        $lon = $request->input('lon');
        $query = $request->input('query', '');
        $radius = $request->input('radius', 1500);
        $limit = $request->input('limit', 10);

        if (!$lat || !$lon) {
            return response()->json([
                'error' => true,
                'message' => 'Latitude and longitude are required.'
            ], 400);
        }

        $places = $this->placesService->getNearbyPlaces($lat, $lon, $query, $radius, $limit);

        return response()->json($places);
    }
}
