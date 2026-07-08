<?php
 
namespace App\Http\Controllers\Api\V2\Common\Notification;

use App\Http\Controllers\Controller;
use App\Services\ApiResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PushSubscriptionController extends Controller
{
    /**
     * Store or update a push subscription for the authenticated user.
     * POST /api/v2/notifications/push-subscription
     */
    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'endpoint' => 'required|string',
            'keys' => 'required|array',
            'keys.p256dh' => 'required|string',
            'keys.auth' => 'required|string',
            'content_encoding' => 'nullable|string',
        ]);

        $user = $request->user();

        $user->updatePushSubscription(
            $request->input('endpoint'),
            $request->input('keys.p256dh'),
            $request->input('keys.auth'),
            $request->input('content_encoding', 'aes128gcm')
        );

        return ApiResponseService::success(
            responseKey: 'responses.success',
            data: ['message' => 'Push subscription saved successfully.']
        );
    }

    /**
     * Delete a push subscription for the authenticated user.
     * POST /api/v2/notifications/push-subscription/delete
     */
    public function destroy(Request $request): JsonResponse
    {
        $request->validate([
            'endpoint' => 'required|string',
        ]);

        $user = $request->user();

        $user->deletePushSubscription($request->input('endpoint'));

        return ApiResponseService::success(
            responseKey: 'responses.success',
            data: ['message' => 'Push subscription removed successfully.']
        );
    }
}
