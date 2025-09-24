<?php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;

class NotificationController extends Controller
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();

        $notifications = $user->notifications()
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->through(function ($notification) {
                return [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'title' => $notification->data['title'] ?? 'Notification',
                    'message' => $notification->data['message'] ?? '',
                    'data' => $notification->data,
                    'read_at' => $notification->read_at,
                    'created_at' => $notification->created_at,
                    'is_read' => $notification->read_at !== null,
                ];
            });

        return Inertia::render('Notifications/Index', [
            'notifications' => $notifications,
            'unread_count' => $this->notificationService->getUnreadCount($user),
        ]);
    }

    public function api(Request $request): JsonResponse
    {
        $user = $request->user();

        $notifications = $user->notifications()
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get()
            ->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'title' => $notification->data['title'] ?? 'Notification',
                    'message' => $notification->data['message'] ?? '',
                    'data' => $notification->data,
                    'read_at' => $notification->read_at,
                    'created_at' => $notification->created_at,
                    'is_read' => $notification->read_at !== null,
                ];
            });

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $this->notificationService->getUnreadCount($user),
        ]);
    }

    public function markAsRead(Request $request, string $notificationId): JsonResponse
    {
        $user = $request->user();

        if ($this->notificationService->markAsRead($user, $notificationId)) {
            return response()->json([
                'success' => true,
                'message' => 'Notification marked as read.',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to mark notification as read.',
        ], 500);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($this->notificationService->markAllAsRead($user)) {
            return response()->json([
                'success' => true,
                'message' => 'All notifications marked as read.',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to mark notifications as read.',
        ], 500);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'unread_count' => $this->notificationService->getUnreadCount($user),
        ]);
    }

    public function destroy(Request $request, string $notificationId): JsonResponse
    {
        $user = $request->user();

        try {
            $notification = $user->notifications()->findOrFail($notificationId);
            $notification->delete();

            return response()->json([
                'success' => true,
                'message' => 'Notification deleted successfully.',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete notification.',
            ], 500);
        }
    }

    public function test(Request $request): JsonResponse
    {
        $user = $request->user();

        $this->notificationService->sendToUser(
            $user,
            'test',
            'Test Notification',
            'This is a test notification to verify the system is working.',
            ['test_data' => 'example']
        );

        return response()->json([
            'success' => true,
            'message' => 'Test notification sent.',
        ]);
    }
}
