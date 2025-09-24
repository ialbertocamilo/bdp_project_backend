<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PerformanceMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        $response = $next($request);

        $endTime = microtime(true);
        $endMemory = memory_get_usage();

        $executionTime = round(($endTime - $startTime) * 1000, 2);
        $memoryUsage = round(($endMemory - $startMemory) / 1024 / 1024, 2);

        $response->headers->set('X-Execution-Time', $executionTime . 'ms');
        $response->headers->set('X-Memory-Usage', $memoryUsage . 'MB');
        $response->headers->set('X-Memory-Peak', round(memory_get_peak_usage() / 1024 / 1024, 2) . 'MB');

        if ($executionTime > 1000) {
            Log::warning('Slow query detected', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'execution_time' => $executionTime,
                'memory_usage' => $memoryUsage
            ]);
        }

        return $response;
    }
}