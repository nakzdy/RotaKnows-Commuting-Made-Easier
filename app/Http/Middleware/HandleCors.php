<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class HandleCors
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Allow your frontend origin here:
        $allowedOrigins = [
            'http://127.0.0.1:5500',
            'http://127.0.0.1:5501',
            'https://rota-knows-frontend.vercel.app/',
            'http://localhost/RotaKnows-Frontend'
        ];

        $origin = $request->headers->get('Origin');

        if (in_array($origin, $allowedOrigins)) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
        }

        if ($request->getMethod() === "OPTIONS") {
            // Respond with 200 for preflight requests
            return response('', 200)
                ->withHeaders($response->headers->all());
        }

        return $response;
    }
}