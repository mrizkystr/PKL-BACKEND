<?php

namespace App\Http\Middleware;

use Closure;
use Tymon\JWTAuth\Facades\JWTAuth;
use Exception;

class JwtMiddleware
{
    public function handle($request, Closure $next)
    {
        try {
            $token = session('jwt_token');
            if (!$token) {
                return redirect()->route('login');
            }
            
            JWTAuth::setToken($token);
            $user = JWTAuth::authenticate();
            if (!$user) {
                return redirect()->route('login');
            }
            
        } catch (Exception $e) {
            return redirect()->route('login');
        }

        return $next($request);
    }
}