<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\PlacesService;

class PlacesController extends Controller
{
    protected PlacesService $placesService;

    public function __construct(PlacesService $placesService)
    {
        $this->placesService = $placesService;
    }

    public function search(Request $request)
    {
        $results = $this->placesService->searchPlaces($request);

        return response()->json($results);
    }
}
