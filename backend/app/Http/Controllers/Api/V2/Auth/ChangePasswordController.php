<?php

namespace App\Http\Controllers\Api\V2\Auth;

use App\Http\Controllers\Controller;
use App\Services\ApiResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ChangePasswordController extends Controller
{
    /**
     * Change Password (Authenticated)
     */
    public function change(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return ApiResponseService::validationError($validator->errors());
        }

        // 1. Verify Current Password
        if (!Hash::check($request->current_password, $user->password)) {
            return ApiResponseService::error('responses.invalid_current_password', [
                'message' => 'The current password you entered is incorrect.'
            ], 422, null, 'INVALID_CURRENT_PASSWORD');
        }

        // 2. Update Password
        $user->update([
            'password' => Hash::make($request->new_password)
        ]);

        return ApiResponseService::success(
            'responses.password_changed_successfully',
            [],
            data: [
                'email' => $user->email,
                'password' => $request->new_password,
            ],
            code: 'PASSWORD_CHANGED'
        );
    }
}
