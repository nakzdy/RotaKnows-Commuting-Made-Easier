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

    /**
     * Get fare and info (GET request - your existing method)
     * This is for ad-hoc calculation without saving to a database.
     * Route: GET /api/fare-info?origin_address=...&destination_address=...
     */
    public function getFareAndInfo(Request $request)
    {
        $request->validate([
            'origin_address' => 'required|string',
            'destination_address' => 'required|string',
        ]);

        try {
            $tripDetails = $this->fareCalculationService->calculateTripDetails(
                $request->input('origin_address'),
                $request->input('destination_address')
            );

            return response()->json($tripDetails);

        } catch (Throwable $e) {
            \Log::error("API integration error in LocationFareController (GET): " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            if (str_contains($e->getMessage(), 'Could not geocode')) {
                return response()->json(['error' => $e->getMessage()], 400);
            }
            return response()->json(['error' => 'An unexpected error occurred. Please try again later.'], 500);
        }
    }

    /**
     * Calculate fare (POST request - your existing method)
     * This is also for ad-hoc calculation, typically with data in the request body.
     * Route: POST /api/calculate-fare (with JSON body)
     */
    public function calculateFare(Request $request)
    {
        $request->validate([
            'origin_address' => 'required|string',
            'destination_address' => 'required|string',
        ]);

        try {
            $tripDetails = $this->fareCalculationService->calculateTripDetails(
                $request->input('origin_address'),
                $request->input('destination_address')
            );

            return response()->json($tripDetails);

        } catch (Throwable $e) {
            \Log::error("API integration error in LocationFareController (POST): " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            if (str_contains($e->getMessage(), 'Could not geocode')) {
                return response()->json(['error' => $e->getMessage()], 400);
            }
            return response()->json(['error' => 'An unexpected error occurred. Please try again later.'], 500);
        }
    }

    // --- NEW METHODS FOR PUT/PATCH/DELETE WITH ORIGIN/DESTINATION AS "IDENTIFIERS" ---

    /**
     * Handles PUT/PATCH requests to conceptually "update" a specific fare calculation.
     * Since there's no database ID for a specific calculation, we use origin/destination
     * from the request body as a conceptual identifier for what we're "updating."
     *
     * This method would typically be used for updating a *persisted* trip, but here
     * it simulates processing an update for a fare calculation request.
     *
     * Route: PUT/PATCH /api/fare-update
     * Expected Body: { "origin_address": "...", "destination_address": "...", "new_fare_parameter": "..." }
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateFare(Request $request)
    {
        // For PUT/PATCH, the data to update would come from the request body
        $request->validate([
            'origin_address' => 'required|string',
            'destination_address' => 'required|string',
            'surcharge_amount' => 'numeric|min:0|nullable', // Example of a parameter you might "update"
            'fare_modifier' => 'numeric|nullable', // Another example
        ]);

        $originAddress = $request->input('origin_address');
        $destinationAddress = $request->input('destination_address');
        $surchargeAmount = $request->input('surcharge_amount', 0); // Default to 0 if not provided

        // In a real scenario, you might:
        // 1. Look up a stored trip based on origin/destination (if you had a DB of trips).
        // 2. Apply the update (e.g., add surcharge to the expected fare).
        // 3. Recalculate fare with new parameters or update stored parameters.

        // For this demonstration, we'll just acknowledge the request and show the data.
        // We can even do a fresh calculation to show how changes *might* affect it.
        try {
            $tripDetails = $this->fareCalculationService->calculateTripDetails(
                $originAddress,
                $destinationAddress
            );

            // Apply a conceptual update, e.g., add a surcharge
            $updatedFare = $tripDetails['expected_fare'] + $surchargeAmount;
            $tripDetails['expected_fare_with_surcharge'] = round($updatedFare, 0);

            return response()->json([
                'message' => "Fare for '{$originAddress}' to '{$destinationAddress}' conceptually updated via " . $request->method() . ".",
                'received_data' => $request->all(),
                'calculated_details' => $tripDetails,
                'note' => 'This is a placeholder. No actual fare calculations or settings are persisted without a database or config mechanism.'
            ], 200); // 200 OK

        } catch (Throwable $e) {
            \Log::error("API integration error in LocationFareController (UPDATE): " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            if (str_contains($e->getMessage(), 'Could not geocode')) {
                return response()->json(['error' => $e->getMessage()], 400);
            }
            return response()->json(['error' => 'An unexpected error occurred. Please try again later.'], 500);
        }
    }

    /**
     * Handles DELETE requests to conceptually "delete" or "reset" a fare calculation.
     * Since there's no database ID, we use origin/destination from the request body
     * or query parameters to identify what to "delete."
     *
     * Route: DELETE /api/fare-delete
     * Expected Body: { "origin_address": "...", "destination_address": "..." }
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteFare(Request $request)
    {
        // For DELETE, data can come from body or query parameters.
        // Using request()->all() for simplicity to get both.
        $request->validate([
            'origin_address' => 'required|string',
            'destination_address' => 'required|string',
        ]);

        $originAddress = $request->input('origin_address');
        $destinationAddress = $request->input('destination_address');

        // In a real scenario, you might:
        // 1. Delete a specific trip record from the database based on origin/destination.
        // 2. Clear a cache entry for a specific route.

        // For this demonstration, we'll just acknowledge the request.
        \Log::info("DELETE request received for fare calculation: Origin '{$originAddress}', Destination '{$destinationAddress}'");

        return response()->json([
            'message' => "Fare calculation for '{$originAddress}' to '{$destinationAddress}' conceptually deleted/reset.",
            'note' => 'This is a placeholder. No actual calculations or settings are deleted from persistence without a database or cache mechanism.'
        ], 204); // 204 No Content - common for successful DELETE with no response body
    }
}