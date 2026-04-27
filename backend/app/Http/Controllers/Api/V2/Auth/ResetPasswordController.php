<?php

namespace App\Http\Controllers\Api\V2\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ApiResponseService;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class ResetPasswordController extends Controller
{
    protected OtpService $otpService;

    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }

    /**
     * Reset Password using OTP
     */
    public function reset(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'reset_token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            // Make sure to return the actual validation error messages array/collection, not the Validator instance itself
            return ApiResponseService::validationError($validator->errors()->toArray(), null, 'VALIDATION_ERROR');
        }

        // 1. Validate reset token (issued after OTP verification)
        $cachedToken = Cache::pull('pwd_reset_' . $request->email);
        if (! $cachedToken || $cachedToken !== $request->reset_token) {
            return ApiResponseService::error('responses.invalid_token', [
                'message' => 'Invalid or expired reset token.'
            ], 422, null, 'INVALID_RESET_TOKEN');
        }

        // 2. Update Password
        $user = User::where('email', $request->email)->first();
        $user->update([
            'password' => Hash::make($request->password)
        ]);

        // 3. Clear OTP
        $this->otpService->deleteOtp($request->email, 'forgot_password');

        return ApiResponseService::success(
            'responses.password_reset_success',
            [
                'message' => 'Your password has been reset successfully. Login with your new password.'
            ],
            data: [
                'email' => $request->email,
            ],
            code: 'PASSWORD_RESET_SUCCESS'
        );
    }
}
