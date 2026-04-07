<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SupportController extends Controller
{
    use ApiResponse;

    public function message(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name'    => 'required|string|max:255',
                'email'   => 'required|email|max:255',
                'subject' => 'required|string|max:255',
                'message' => 'required|string|max:5000',
            ]);
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422);
        }

        try {
            // Log to Laravel's default log — replace with Mail::to(...)->send() once SMTP is configured
            \Illuminate\Support\Facades\Log::info('Support message received', $validated);

            return $this->success(['message' => 'Support message received']);
        } catch (\Throwable $e) {
            return $this->error('Failed to send support message: ' . $e->getMessage(), 500);
        }
    }
}
