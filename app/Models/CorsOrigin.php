<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\CorsOrigin;

class HandleCors
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Get allowed origins from the database
        $allowedOrigins = CorsOrigin::pluck('origin')->toArray();

        $origin = $request->headers->get('Origin');

        if (in_array($origin, $allowedOrigins)) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
        }

        if ($request->getMethod() === 'OPTIONS') {
            return response('', 200)
                ->withHeaders($response->headers->all());
        }

        return $response;
    }
}
