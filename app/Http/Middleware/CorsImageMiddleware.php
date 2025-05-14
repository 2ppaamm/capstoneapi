<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CorsImageMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Only add headers for image routes
        if (str_starts_with($request->path(), 'images/')) {
            $response->headers->set('Access-Control-Allow-Origin', '*');
            $response->headers->set('Access-Control-Allow-Methods', 'GET, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', '*');
        }

        return $response;
    }
}
