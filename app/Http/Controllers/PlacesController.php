<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\PlacesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class PlacesController extends Controller
{
    protected PlacesService $placesService;

    public function __construct(PlacesService $placesService)
    {
        $this->placesService = $placesService;
    }

    /**
     * Search places using Foursquare API, explicitly requiring latitude and longitude.
     *
     * @param Request $request
     * @param string|null $query (Optional) The primary search term from the route segment.
     * @return JsonResponse
     */
    public function search(Request $request, ?string $query = null): JsonResponse
    {
        try {
            // Validate required parameters for location-based search
            $request->validate([
                'lat' => 'required|numeric',
                'lon' => 'required|numeric',
                'q' => 'nullable|string',
                'radius' => 'nullable|integer|min:1|max:100000',
                'limit' => 'nullable|integer|min:1|max:50',
            ]);

            // Get latitude and longitude from request query parameters
            $lat = (float) $request->query('lat');
            $lon = (float) $request->query('lon');

            $searchQuery = $query ?? $request->query('q');


            $radius = (int) $request->query('radius', 2000);
            $limit = (int) $request->query('limit', 10);

            // Log the parameters being sent to the service
            Log::info('PlacesController: Calling getNearbyPlaces with:', [
                'query' => $searchQuery, 
                'lat' => $lat,
                'lon' => $lon,
                'radius' => $radius,
                'limit' => $limit,
            ]);

            // Call the PlacesService's getNearbyPlaces method
            $results = $this->placesService->getNearbyPlaces(
                $lat,
                $lon,
                $searchQuery, 
                $radius,
                $limit
            );

            // Check if the service returned an error
            if (isset($results['error'])) {
                return response()->json([
                    'success' => false,
                    'message' => $results['message'],
                ], $results['status'] ?? 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Foursquare search results retrieved successfully.',
                'data' => $results // This should contain the actual Foursquare data
            ], 200);

        } catch (ValidationException $e) {
            // Handle Laravel's validation exceptions (e.g., missing lat/lon)
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . $e->getMessage(),
                'errors' => $e->errors(),
            ], 422); // 422 Unprocessable Entity
        } catch (\Exception $e) {
            Log::error("Error in PlacesController::search: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Failed to search places: An internal error occurred.',
            ], 500);
        }
    }
}