<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserNotification;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        try {
            $userId = auth()->id();
            $query  = UserNotification::where('user_id', $userId);

            if ($request->boolean('unread_only', false)) {
                $query->where('read', false);
            }

            $notifications = $query->orderBy('created_at', 'desc')
                ->limit(100)
                ->get();

            return $this->success($notifications);
        } catch (\Throwable $e) {
            return $this->error('Failed to fetch notifications: ' . $e->getMessage(), 500);
        }
    }

    public function markRead(string $id): JsonResponse
    {
        try {
            $notification = UserNotification::where('user_id', auth()->id())
                ->findOrFail($id);

            $notification->update(['read' => true]);

            return $this->success($notification);
        } catch (\Throwable $e) {
            return $this->error('Notification not found', 404);
        }
    }

    public function markAllRead(): JsonResponse
    {
        try {
            UserNotification::where('user_id', auth()->id())
                ->where('read', false)
                ->update(['read' => true]);

            return $this->success(['message' => 'All notifications marked as read']);
        } catch (\Throwable $e) {
            return $this->error('Failed to mark notifications as read: ' . $e->getMessage(), 500);
        }
    }
}
