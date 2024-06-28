<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Config;

class HandleCors
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        $corsConfig = Config::get('cors');

        $allowedOrigins = implode(', ', $corsConfig['allowed_origins']);
        $allowedMethods = implode(', ', $corsConfig['allowed_methods']);
        $allowedHeaders = implode(', ', $corsConfig['allowed_headers']);
        $exposedHeaders = implode(', ', $corsConfig['exposed_headers']);
        $supportsCredentials = $corsConfig['supports_credentials'] ? 'true' : 'false';
        $maxAge = $corsConfig['max_age'];

        $response->headers->set('Access-Control-Allow-Origin', $allowedOrigins);
        $response->headers->set('Access-Control-Allow-Methods', $allowedMethods);
        $response->headers->set('Access-Control-Allow-Headers', $allowedHeaders);
        $response->headers->set('Access-Control-Expose-Headers', $exposedHeaders);
        $response->headers->set('Access-Control-Allow-Credentials', $supportsCredentials);
        $response->headers->set('Access-Control-Max-Age', $maxAge);

        if ($request->getMethod() === "OPTIONS") {
            $response->headers->set('Access-Control-Allow-Origin', $allowedOrigins);
            $response->headers->set('Access-Control-Allow-Methods', $allowedMethods);
            $response->headers->set('Access-Control-Allow-Headers', $allowedHeaders);
            $response->headers->set('Access-Control-Expose-Headers', $exposedHeaders);
            $response->headers->set('Access-Control-Allow-Credentials', $supportsCredentials);
            $response->headers->set('Access-Control-Max-Age', $maxAge);
            $response->setStatusCode(200);
        }

        return $response;
    }
}
