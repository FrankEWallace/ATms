<?php

namespace App\Traits;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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

    protected function paginated(LengthAwarePaginator $paginator): JsonResponse
    {
        return response()->json([
            'data'  => $paginator->items(),
            'meta'  => [
                'current_page' => $paginator->currentPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'last_page'    => $paginator->lastPage(),
            ],
            'error' => null,
        ]);
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
