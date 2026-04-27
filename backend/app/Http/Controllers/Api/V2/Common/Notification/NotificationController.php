<?php

namespace App\Http\Controllers\Api\V2\Common\Notification;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Http\Resources\NotificationIndexResource;
use App\Services\ApiResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $query = $request->user()
            ->notifications()
            ->where('is_archived', $request->boolean('archived', false))
            ->latest();

        $group = $request->get('group', $request->get('category'));
        if ($group && strtolower($group) !== 'all') {
            $query->where('category', $group);
        }

        if ($request->event_type) {
            $query->where('event_type', $request->event_type);
        }

        if ($request->unread) {
            $query->whereNull('read_at');
        }

        $notifications = $query->paginate($request->get('per_page', 20));

        $unreadCount = $request->user()
        ->notifications()
        ->whereNull('read_at')
        ->count();


        $notifications->setCollection(
            NotificationIndexResource::collection($notifications->getCollection())->collection
        );

        $paginator = $notifications->toArray();
        $paginator['meta']['unread_count'] = $unreadCount;

        return ApiResponseService::paginated(
            paginated: $notifications,
            responseKey: 'responses.success',
            extra: [
                'unread_count' => $unreadCount,
            ]
        );

        return ApiResponseService::paginated(
            paginated: $notifications,
            responseKey: 'responses.success',
        );
    }

    public function show(Request $request, string $notificationId): JsonResponse
    {
        $user = $request->user();

        $notification = $user->notifications()->where('id', $notificationId)->first();

        if (! $notification) {
            return ApiResponseService::notFound(resource: 'Notification');
        }

        if ($notification->read_at === null) {
            $notification->markAsRead();
        }

        return ApiResponseService::success(
            responseKey: 'responses.success',
            data: new NotificationResource($notification->fresh())
        );
    }


    public function unreadCount(Request $request): JsonResponse
    {
        // Count unread notifications based on read_at column in notifications table
        $count = $request->user()
            ->notifications()
            ->whereNull('read_at')
            ->count();

        return ApiResponseService::success(
            responseKey: 'responses.success',
            data: ['unread_count' => $count]
        );
    }

    public function markAsRead(Request $request, string $notificationId): JsonResponse
    {
        $user = $request->user();

        $notification = $user->notifications()->where('id', $notificationId)->first();

        if (! $notification) {
            return ApiResponseService::notFound(resource: 'Notification');
        }

        if ($notification->read_at === null) {
            $notification->markAsRead();
        }

        return ApiResponseService::success(
            responseKey: 'responses.success',
            data: new NotificationResource($notification->fresh())
        );
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->unreadNotifications()->update(['read_at' => now()]);



        return ApiResponseService::success(
            responseKey: 'responses.success',
            data: ['unread_count' => 0]
        );
    }

    public function archive(Request $request, string $notificationId): JsonResponse
    {
        $user = $request->user();
        $notification = $user->notifications()->where('id', $notificationId)->first();

        if (!$notification) {
            return ApiResponseService::notFound(resource: 'Notification');
        }

        $notification->update(['is_archived' => true]);

        return ApiResponseService::success(
            responseKey: 'responses.success',
            data: ['message' => 'Notification archived successfully']
        );
    }

    public function unarchive(Request $request, string $notificationId): JsonResponse
    {
        $user = $request->user();
        $notification = $user->notifications()->where('id', $notificationId)->first();

        if (!$notification) {
            return ApiResponseService::notFound(resource: 'Notification');
        }

        $notification->update(['is_archived' => false]);

        return ApiResponseService::success(
            responseKey: 'responses.success',
            data: ['message' => 'Notification unarchived successfully']
        );
    }

    public function archiveAll(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->notifications()->where('is_archived', false)->update(['is_archived' => true]);

        return ApiResponseService::success(
            responseKey: 'responses.success',
            data: ['message' => 'All notifications archived successfully']
        );
    }
}
