<?php

namespace App\Http\Controllers;

use App\Services\TomTomService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class MapController extends Controller
{
    protected TomTomService $tomTomService;

    public function __construct(TomTomService $tomTomService)
    {
        $this->tomTomService = $tomTomService;
    }

    /**
     * Show the main map view.
     */
    public function index(): View
    {
        return view('map.index');
    }

    /**
     * Search places using TomTom API.
     */
    public function searchPlaces(Request $request): JsonResponse
    {
        $query = $request->input('query');
        $latitude = $request->input('latitude');
        $longitude = $request->input('longitude');

        $params = [];
        if ($latitude !== null && $longitude !== null) {
            $params = [
                'lat' => (float)$latitude,
                'lon' => (float)$longitude,
                'radius' => 50000,
                'limit' => 10,
            ];
        }

        $result = $this->tomTomService->search($query, $params);

        // If service returns an error array, respond with appropriate status
        if (isset($result['error'])) {
            return response()->json(['error' => $result['error']], $result['status'] ?? 500);
        }

        return response()->json($result);
    }

    /**
     * Get route from TomTom API.
     */
    public function getRoute(Request $request): JsonResponse
    {
        $originLat = $request->input('origin_lat');
        $originLon = $request->input('origin_lon');
        $destinationLat = $request->input('destination_lat');
        $destinationLon = $request->input('destination_lon');

        $coordinates = "{$originLat},{$originLon}:{$destinationLat},{$destinationLon}";

        $params = [
            'travelMode' => 'car',
            'instructionsType' => 'text',
            'traffic' => 'true',
        ];

        $result = $this->tomTomService->calculateRoute($coordinates, $params);

        if (isset($result['error'])) {
            return response()->json(['error' => $result['error']], $result['status'] ?? 500);
        }

        return response()->json($result);
    }
}
