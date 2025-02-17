<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Pastikan user login dan memiliki peran customer
        if (Auth::guard('api')->check() && Auth::guard('api')->user()->role === 'customer') {
            return $next($request);
        }

        return response()->json(['error' => 'Unauthorized'], 403);
    }
}
