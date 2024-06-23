<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Firebase\Auth\Token\Exception\InvalidToken;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Auth;

class VerifyAuth0Token
{
    protected $auth;

    public function __construct()
    {
        $this->auth = (new Factory)
            ->withServiceAccount(base_path('path/to/serviceAccountKey.json'))
            ->createAuth();
    }

    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();
        if (!$token) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        try {
            $verifiedToken = $this->auth->verifyIdToken($token);
            $request->auth = $verifiedToken;
        } catch (InvalidToken $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
