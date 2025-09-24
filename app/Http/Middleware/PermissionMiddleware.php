<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PermissionMiddleware
{
    public function handle(Request $request, Closure $next, $permission)
    {
        if (!auth()->check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user = auth()->user();
        
        if (!$user->hasPermission($permission)) {
            return response()->json([
                'error' => 'Insufficient permissions',
                'required_permission' => $permission
            ], 403);
        }

        return $next($request);
    }
}