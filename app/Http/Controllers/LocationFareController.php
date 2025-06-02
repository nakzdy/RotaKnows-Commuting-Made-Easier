<?php

namespace App\Http\Controllers;

use App\Services\FareService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log; 

class LocationFareController extends Controller
{
    protected FareService $fareService;

    public function __construct(FareService $fareService)
    {
        $this->fareService = $fareService;
    }

    /**
     * Get fare information and other trip details.
     * Arguments for this method are passed to the service.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getFareAndInfo(Request $request): JsonResponse
    {
        try {
            // Pass the entire request data to the service for processing
            $fareInfo = $this->fareService->calculateTripDetails($request->all());

            // Check if the service explicitly returned an error (e.g., for API issues)
            if (isset($fareInfo['error'])) {
                return response()->json([
                    'success' => false,
                    'message' => $fareInfo['error'],
                ], $fareInfo['status'] ?? 500);
            }

            return response()->json([
                'success' => true,
                'data' => $fareInfo,
            ], 200);

        } catch (ValidationException $e) {
            // Handle Laravel's validation exceptions specifically
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . $e->getMessage(),
                'errors' => $e->errors(),
            ], 422); // Unprocessable Entity

        } catch (\Exception $e) {
            // Catch any other general exceptions thrown by the service
            Log::error("Error in LocationFareController::getFareAndInfo: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve fare information: An internal error occurred.',
            ], 500);
        }
    }

    /**
     * Store a newly created fare record in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeFare(Request $request): JsonResponse
    {
        try {
            $fare = $this->fareService->createFare($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Fare created successfully.',
                'data' => $fare->toArray(),
            ], 201); // 201 Created

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error("Error creating fare: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create fare: An internal error occurred.',
            ], 500);
        }
    }

    /**
     * Update fare information.
     *
     * @param Request $request
     * @param int $id // Assuming the route parameter for fare_id
     * @return JsonResponse
     */
    public function updateFare(Request $request, int $id): JsonResponse
    {
        try {
            // Merge the route parameter into the request data for validation in service
            $data = array_merge($request->all(), ['fare_id' => $id]);


            Log::info('Data received in LocationFareController for update:', $data);


            $updatedFare = $this->fareService->updateFare($data);

            if (isset($updatedFare['error'])) {
                return response()->json([
                    'success' => false,
                    'message' => $updatedFare['error'],
                ], $updatedFare['status'] ?? 500);
            }

            return response()->json([
                'success' => true,
                'data' => $updatedFare,
                'message' => 'Fare updated successfully',
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error("Error in LocationFareController::updateFare: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update fare: An internal error occurred.',
            ], 500);
        }
    }

    /**
     * Delete fare information.
     *
     * @param int $id // The fare ID from the route
     * @return JsonResponse
     */
    public function deleteFare(int $id): JsonResponse
    {
        try {
            $deleted = $this->fareService->deleteFare($id);

            if (!$deleted) {
                return response()->json([
                    'success' => false,
                    'message' => 'Fare not found or could not be deleted.',
                ], 404); // Not Found
            }

            return response()->json([
                'success' => true,
                'message' => 'Fare deleted successfully.',
            ], 200);

        } catch (\Exception $e) {
            Log::error("Error in LocationFareController::deleteFare: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete fare: An internal error occurred.',
            ], 500);
        }
    }
}