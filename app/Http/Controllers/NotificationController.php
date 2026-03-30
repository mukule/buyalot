<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Return the authenticated user's recent notifications and unread count.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $notifications = $user->notifications()
            ->latest()
            ->take(20)
            ->get()
            ->map(fn ($n) => [
                'id'         => $n->id,
                'type'       => $n->data['type'] ?? null,
                'title'      => $n->data['title'] ?? '',
                'body'       => $n->data['body'] ?? '',
                'action_url' => $n->data['action_url'] ?? null,
                'read_at'    => $n->read_at?->toIso8601String(),
                'created_at' => $n->created_at->toIso8601String(),
            ]);

        return response()->json([
            'notifications' => $notifications,
            'unread_count'  => $user->unreadNotifications()->count(),
        ]);
    }

    /**
     * Mark a single notification as read.
     */
    public function markRead(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return response()->json(['ok' => true]);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json(['ok' => true]);
    }
}
