<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\PlacesService;

class PlacesController extends Controller
{
    protected $placesService;

    public function __construct(PlacesService $placesService)
    {
        $this->placesService = $placesService;
    }

    public function search(Request $request)
    {
        $query = $request->input('query', 'mall');
        $lat = $request->input('lat', '14.5995');    // default Manila latitude
        $lng = $request->input('lon', '120.9842');   // note: use 'lon' as per your query param

        $results = $this->placesService->searchPlaces($query, $lat, $lng);

        return response()->json($results);
    }
}
