<?php

namespace App\Http\Controllers\Api\V2\Common\Device;

use App\Http\Controllers\Controller;
use App\Models\UserDevice;
use App\Services\ApiResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserDeviceController extends Controller
{
    /**
     * Register or refresh a push token
     */
    public function refresh(Request $request): JsonResponse
    {
        $request->validate([
            'push_token' => 'required|string',
            'device_type' => 'nullable|string',
            'device_name' => 'nullable|string',
            'app_version' => 'nullable|string',
        ]);

        $user = $request->user();

        $device = UserDevice::updateOrCreate(
            ['push_token' => $request->push_token],
            [
                'user_id' => $user->id,
                'device_type' => $request->device_type,
                'device_name' => $request->device_name,
                'app_version' => $request->app_version,
                'is_active' => true,
                'last_used_at' => now(),
            ]
        );

        return ApiResponseService::success(
            responseKey: 'responses.success',
            data: [
                'push_token' => $device->push_token,
                'is_active' => $device->is_active
            ]
        );
    }

    /**
     * Deactivate a push token (on logout)
     */
    public function deactivate(Request $request): JsonResponse
    {
        $request->validate([
            'push_token' => 'required|string',
        ]);

        UserDevice::where('push_token', $request->push_token)
            ->update(['is_active' => false]);

        return ApiResponseService::success(
            responseKey: 'responses.success',
            data: ['message' => 'Device deactivated successfully']
        );
    }
}
