<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LogApiRequests
{
    public function handle(Request $request, Closure $next)
    {
        $start = microtime(true);

        $response = $next($request);

        $duration = round((microtime(true) - $start) * 1000); // ms
        $method = $request->method();
        $uri = $request->getRequestUri();
        $timestamp = now()->format('Y-m-d H:i:s');

        Log::info("{$timestamp} {$method} {$uri} .......... ~ {$duration}ms");

        return $response;
    }
}