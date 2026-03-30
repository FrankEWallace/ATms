<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    protected function success(mixed $data, int $code = 200): JsonResponse
    {
        return response()->json([
            'data'  => $data,
            'error' => null,
        ], $code);
    }

    protected function created(mixed $data): JsonResponse
    {
        return response()->json([
            'data'  => $data,
            'error' => null,
        ], 201);
    }

    protected function error(string $message, int $code = 400): JsonResponse
    {
        return response()->json([
            'data'  => null,
            'error' => $message,
        ], $code);
    }
}
