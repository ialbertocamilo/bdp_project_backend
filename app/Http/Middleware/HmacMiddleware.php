<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class HmacMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $signature = $request->header('X-Signature');

        if (!$signature) {
            return response()->json(['message' => 'Missing X-Signature header'], 401);
        }

        $secret = config('app.hmac_secret', env('HMAC_SECRET', 'default-secret'));
        $payload = $request->getContent();
        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        if (!hash_equals($expectedSignature, $signature)) {
            return response()->json(['message' => 'Invalid signature'], 401);
        }

        return $next($request);
    }
}
