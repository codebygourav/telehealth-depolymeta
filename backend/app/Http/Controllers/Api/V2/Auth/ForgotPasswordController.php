<?php

namespace App\Http\Controllers\Api\V2\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ApiResponseService;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ForgotPasswordController extends Controller
{
    protected $otpService;

    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }

    /**
     * Send OTP for password reset
     */
    public function sendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return ApiResponseService::validationError($validator->errors());
        }

        $email = $request->email;

        // Send OTP via global service with 'forgot_password' context
        $this->otpService->sendOtp($email, 'forgot_password');

        return ApiResponseService::success(
            'responses.verification_code_sent',
            [
                'message' => 'Password reset OTP sent to your email.'
            ],
            data: [
                'email' => $email,
            ],
            code: 'OTP_SENT'
        );
    }

    /**
     * Verify OTP for forgot password
     */
    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'otp' => 'required|string',
        ]);

        if ($validator->fails()) {
            return ApiResponseService::validationError($validator->errors());
        }

        $isValid = $this->otpService->verifyOtp($request->email, $request->otp, 'forgot_password');

        if (!$isValid) {
            return ApiResponseService::error('responses.invalid_token', [
                'message' => 'Invalid or expired verification code.'
            ], 422, null, 'INVALID_OTP');
        }

        // Generate a short‑lived reset token after successful OTP verification
        $resetToken = (string) \Illuminate\Support\Str::uuid();
        // Store token in cache for 10 minutes (adjust if needed)
        \Illuminate\Support\Facades\Cache::put('pwd_reset_' . $request->email, $resetToken, now()->addMinutes(10));

        return ApiResponseService::success(
            'responses.otp_verified',
            [
                'message' => 'OTP verified successfully. You can now reset your password.',
            ],
            data: [
                'email' => $request->email,
                'reset_token' => $resetToken,
            ],
            code: 'OTP_VERIFIED'
        );
    }
}
