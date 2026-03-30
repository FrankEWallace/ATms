<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class MessageController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        try {
            $siteId = $request->query('site_id') ?? $request->header('X-Site-Id');
            $query  = Message::with('sender');

            if ($siteId) {
                $query->where('site_id', $siteId);
            }

            if ($request->filled('channel')) {
                $query->where('channel', $request->query('channel'));
            }

            if ($request->filled('after')) {
                $query->where('created_at', '>', $request->query('after'));
            }

            $limit = (int) ($request->query('limit', 50));
            $limit = min($limit, 200);

            return $this->success(
                $query->orderBy('created_at', 'desc')->limit($limit)->get()->reverse()->values()
            );
        } catch (\Throwable $e) {
            return $this->error('Failed to fetch messages: ' . $e->getMessage(), 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'site_id' => 'required|uuid|exists:sites,id',
                'content' => 'required|string|max:5000',
                'channel' => 'nullable|in:general,safety,operations',
            ]);
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422);
        }

        try {
            $validated['sender_id'] = auth()->id();
            $message = Message::create($validated);
            return $this->created($message->load('sender'));
        } catch (\Throwable $e) {
            return $this->error('Failed to send message: ' . $e->getMessage(), 500);
        }
    }
}
