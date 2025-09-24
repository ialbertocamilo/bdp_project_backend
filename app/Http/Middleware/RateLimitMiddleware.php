<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Response;

class RateLimitMiddleware
{
    public function handle(Request $request, Closure $next, $maxAttempts = 60, $decayMinutes = 1)
    {
        $key = 'rate_limit:' . $request->ip();
        $attempts = Cache::get($key, 0);

        if ($attempts >= $maxAttempts) {
            return response()->json([
                'error' => 'Too many requests. Please try again later.',
                'retry_after' => $decayMinutes * 60
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }

        Cache::put($key, $attempts + 1, $decayMinutes * 60);

        return $next($request);
    }
}