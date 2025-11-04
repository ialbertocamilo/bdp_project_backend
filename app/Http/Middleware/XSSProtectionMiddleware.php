<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class XSSProtectionMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        $response->header('X-Content-Type-Options', 'nosniff');
        $response->header('X-Frame-Options', 'DENY');
        $response->header('X-XSS-Protection', '1; mode=block');
        $response->header('Strict-Transport-Security', 'max-age=31536000');
        $response->header('Content-Security-Policy', "default-src 'self'");

        return $response;
    }
}
