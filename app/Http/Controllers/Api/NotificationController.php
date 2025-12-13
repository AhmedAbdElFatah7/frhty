<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Get User Notifications
     * 
     * Returns all notifications for the authenticated user.
     * 
     * @authenticated
     * 
     * @queryParam unread_only boolean Optional. Return only unread notifications. Example: true
     * @queryParam limit integer Optional. Number of notifications to return (default: 50). Example: 20
     * 
     * @response 200 scenario="success" {
     *   "success": true,
     *   "data": {
     *     "notifications": [
     *       {
     *         "id": 1,
     *         "type": "contest_winner",
     *         "title": "🎉 مبروك! لقد فزت في المسابقة",
     *         "message": "تهانينا! لقد أجبت على جميع الأسئلة...",
     *         "data": {...},
     *         "is_read": false,
     *         "read_at": null,
     *         "created_at": "2025-12-13 14:30:00"
     *       }
     *     ],
     *     "unread_count": 5,
     *     "total": 10
     *   }
     * }
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $unreadOnly = $request->query('unread_only', false);
            $limit = $request->query('limit', 50);

            $notifications = $this->notificationService->getUserNotifications($user, $limit, $unreadOnly);
            $unreadCount = $this->notificationService->getUnreadCount($user);

            return response()->json([
                'success' => true,
                'data' => [
                    'notifications' => $notifications->map(function ($notification) {
                        return [
                            'id' => $notification->id,
                            'type' => $notification->type,
                            'title' => $notification->title,
                            'message' => $notification->message,
                            'data' => $notification->data,
                            'is_read' => $notification->is_read,
                            'read_at' => $notification->read_at?->format('Y-m-d H:i:s'),
                            'created_at' => $notification->created_at->format('Y-m-d H:i:s'),
                        ];
                    }),
                    'unread_count' => $unreadCount,
                    'total' => $notifications->count(),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب الإشعارات',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mark Notification as Read
     * 
     * Marks a specific notification as read.
     * 
     * @authenticated
     * 
     * @urlParam id integer required The notification ID. Example: 1
     * 
     * @response 200 scenario="success" {
     *   "success": true,
     *   "message": "تم تعليم الإشعار كمقروء"
     * }
     * 
     * @response 404 scenario="not found" {
     *   "success": false,
     *   "message": "الإشعار غير موجود"
     * }
     */
    public function markAsRead(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->user();

            $result = $this->notificationService->markAsRead($id, $user);

            if (!$result) {
                return response()->json([
                    'success' => false,
                    'message' => 'الإشعار غير موجود',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'تم تعليم الإشعار كمقروء',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث الإشعار',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mark All Notifications as Read
     * 
     * Marks all user's notifications as read.
     * 
     * @authenticated
     * 
     * @response 200 scenario="success" {
     *   "success": true,
     *   "message": "تم تعليم جميع الإشعارات كمقروءة",
     *   "data": {
     *     "updated_count": 5
     *   }
     * }
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $updatedCount = $this->notificationService->markAllAsRead($user);

            return response()->json([
                'success' => true,
                'message' => 'تم تعليم جميع الإشعارات كمقروءة',
                'data' => [
                    'updated_count' => $updatedCount,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث الإشعارات',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get Unread Count
     * 
     * Returns the count of unread notifications.
     * 
     * @authenticated
     * 
     * @response 200 scenario="success" {
     *   "success": true,
     *   "data": {
     *     "unread_count": 5
     *   }
     * }
     */
    public function unreadCount(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $unreadCount = $this->notificationService->getUnreadCount($user);

            return response()->json([
                'success' => true,
                'data' => [
                    'unread_count' => $unreadCount,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب عدد الإشعارات',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
