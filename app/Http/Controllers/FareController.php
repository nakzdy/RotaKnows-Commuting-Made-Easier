<?php

namespace App\Http\Controllers;

use App\Services\FareService;
use Illuminate\Http\Request;

class FareController extends Controller
{
    protected $fareService;

    public function __construct(FareService $fareService)
    {
        $this->fareService = $fareService;
    }

    public function calculate(Request $request)
    {
        return response()->json(
            $this->fareService->calculateFare(
                $request->query('origin_address', ''),
                $request->query('destination_address', '')
            )
        );
    }
}
