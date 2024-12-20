<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;

class JWTAuthMiddleware
{
    public function handle($request, Closure $next)
    {
        try {
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return redirect()->route('login')->with('error', 'You need to log in first.');
            }
        } catch (\Exception $e) {
            return redirect()->route('login')->with('error', 'Invalid or expired token.');
        }

        return $next($request);
    }
}
