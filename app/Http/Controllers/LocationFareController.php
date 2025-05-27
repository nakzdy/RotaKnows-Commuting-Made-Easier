<?php

namespace App\Http\Controllers;

use App\Services\FareService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class LocationFareController extends Controller
{
    protected $fareService;

    public function __construct(FareService $fareService)
    {
        $this->fareService = $fareService;
    }

    /**
     * Get fare information.
     *
     * @return JsonResponse
     */
    public function getFareAndInfo(): JsonResponse
    {
        try {
            $fareInfo = $this->fareService->getFareInfo();
            return response()->json([
                'success' => true,
                'data' => $fareInfo,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve fare information: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update fare information.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateFare(Request $request): JsonResponse
    {
        try {
            // Validate request data
            $validated = $request->validate([
                'fare_id' => 'required|integer|exists:fares,id',
                'amount' => 'required|numeric|min:0',
                // Add other validation rules as needed
            ]);

            $updatedFare = $this->fareService->updateFare($validated);
            return response()->json([
                'success' => true,
                'data' => $updatedFare,
                'message' => 'Fare updated successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update fare: ' . $e->getMessage(),
            ], 500);
        }
    }
}