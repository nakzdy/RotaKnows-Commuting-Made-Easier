<?php

namespace App\Http\Controllers;

use App\Services\FareCalculationService; 
use Illuminate\Http\Request;
use Throwable;

class LocationFareController extends Controller
{
    protected $fareCalculationService; 

    public function __construct(FareCalculationService $fareCalculationService)
    {
        $this->fareCalculationService = $fareCalculationService;
    }

    public function getFareAndInfo(Request $request)
    {
        $request->validate([
            'origin_address' => 'required|string',
            'destination_address' => 'required|string',
        ]);

        try {
            // Call the service to get all the calculated details
            $tripDetails = $this->fareCalculationService->calculateTripDetails(
                $request->input('origin_address'),
                $request->input('destination_address')
            );

            return response()->json($tripDetails);

        } catch (Throwable $e) {
            \Log::error("API integration error in LocationFareController: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            // Return a more specific error if the service threw a clear exception
            if (str_contains($e->getMessage(), 'Could not geocode')) {
                return response()->json(['error' => $e->getMessage()], 400);
            }
            return response()->json(['error' => 'An unexpected error occurred. Please try again later.'], 500);
        }
    }
}