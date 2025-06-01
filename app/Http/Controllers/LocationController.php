<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\LocationIQService;

class LocationController extends Controller
{
    protected LocationIQService $locationIQ;

    public function __construct(LocationIQService $locationIQ)
    {
        $this->locationIQ = $locationIQ;
    }

    /**
     * Geocode an address using LocationIQ.
     *
     * @param Request $request
     * @param string|null $address
     * @return JsonResponse
     */
    public function geocode(Request $request, string $address = null): JsonResponse
    {
        $address = $address
            ?? $request->query('address')
            ?? $request->input('address');

        return response()->json(
            $this->locationIQ->geocode($address)
        );
    }
}