<?php

namespace App\helpers;

if (! function_exists('OkResponse')) {
    function OkResponse(mixed $data ,string $message='Saved successfully', int $status = 200): \Illuminate\Http\JsonResponse
    {
        return response()->json(compact('message', 'status', 'data'));
    }
}
