<?php

namespace App\Http\Controllers; 

use App\Services\TomTomService; 
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse; 
use Illuminate\View\View; 

class MapController extends Controller
{
    // Declare a protected property to hold the TomTomService instance
    protected TomTomService $tomTomService;

    /**
     * Constructor to inject the TomTomService.
     * Laravel's service container automatically provides an instance of TomTomService.
     */
    public function __construct(TomTomService $tomTomService)
    {
        $this->tomTomService = $tomTomService;
    }

    /**
     * Display the main map view.
     * This is typically for a web application where you render an HTML page with a map.
     *
     * @return View
     */
    public function index(): View
    {
        return view('map.index'); 
    }

    /**
     * Handles a TomTom Search API request.
     * Fetches points of interest or addresses based on a query.
     * This would typically be called via AJAX from a front-end.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function searchPlaces(Request $request): JsonResponse
    {
        // 1. Extract and Validate Input
        $query = $request->input('query');
        $latitude = $request->input('latitude');   
        $longitude = $request->input('longitude');  

        if (empty($query)) {
            // Return a 400 Bad Request if the query is missing
            return response()->json(['error' => 'Search query is required.'], 400);
        }

        // Prepare additional parameters for the TomTom API call
        $params = [];
        if ($latitude && $longitude) {
            // TomTom's Search API often uses 'lat' and 'lon' for biasing results.
            $params['lat'] = (float)$latitude;
            $params['lon'] = (float)$longitude;
            $params['radius'] = 50000; 
            $params['limit'] = 10;     
        }

        // 2. Delegate to the Service Layer
        $results = $this->tomTomService->search($query, $params, '/search/2/search/');

        // 3. Handle the Response from the Service & Prepare Response for Client
        if ($results === null) {
            //return a 500 Internal Server Error
            return response()->json(['error' => 'Could not retrieve results from TomTom Search API. Check application logs for details.'], 500);
        }


        return response()->json($results);
    }

    /**
     * Handles a TomTom Routing API request.
     * Calculates a route between an origin and destination.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getRoute(Request $request): JsonResponse
    {
        // 1. Extract and Validate Input
        $originLat = $request->input('origin_lat');
        $originLon = $request->input('origin_lon');
        $destinationLat = $request->input('destination_lat');
        $destinationLon = $request->input('destination_lon');

        if (empty($originLat) || empty($originLon) || empty($destinationLat) || empty($destinationLon)) {
            return response()->json(['error' => 'Origin and destination coordinates (latitude and longitude) are required.'], 400);
        }

        // TomTom Routing API expects coordinates in 'lat,lon:lat,lon' format
        $coordinates = "{$originLat},{$originLon}:{$destinationLat},{$destinationLon}";

        
        $params = [
            'travelMode' => 'car', 
            'instructionsType' => 'text', 
            'traffic' => 'true', 
        ];

        // 2. Delegate to the Service Layer
        $route = $this->tomTomService->calculateRoute($coordinates, $params, '/routing/1/calculateRoute/');

        // 3. Handle the Response from the Service & Prepare Response for Client
        if ($route === null) {
            return response()->json(['error' => 'Could not calculate route from TomTom Routing API. Check application logs for details.'], 500);
        }

        return response()->json($route);
    }

}