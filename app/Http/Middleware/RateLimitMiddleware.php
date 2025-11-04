<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Cache\RateLimiter;

class RateLimitMiddleware
{
    protected $limiter;

    public function __construct(RateLimiter $limiter)
    {
        $this->limiter = $limiter;
    }

    public function handle(Request $request, Closure $next)
    {
        $key = 'rate-limit:' . $request->ip();
        $limit = 60;
        $decay = 60;

        if ($this->limiter->tooManyAttempts($key, $limit)) {
            $retryAfter = $this->limiter->availableIn($key);
            return response()->json([
                'message' => 'Too many requests',
                'retry_after' => $retryAfter
            ], 429)->header('Retry-After', $retryAfter);
        }

        $this->limiter->hit($key, $decay);

        return $next($request);
    }
}
