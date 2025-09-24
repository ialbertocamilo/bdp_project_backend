<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class HmacMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $secretKey = config('app.hmac_secret', env('HMAC_SECRET'));
        
        if (!$secretKey) {
            return $next($request);
        }

        $signature = $request->header('X-Signature');
        $payload = $request->getContent();
        
        if (!$signature) {
            return response()->json(['error' => 'Missing signature'], Response::HTTP_UNAUTHORIZED);
        }

        $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $secretKey);

        if (!hash_equals($expectedSignature, $signature)) {
            return response()->json(['error' => 'Invalid signature'], Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}