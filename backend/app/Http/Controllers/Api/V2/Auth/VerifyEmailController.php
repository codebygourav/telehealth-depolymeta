<?php

namespace App\Http\Controllers\Api\V2\Auth;

use App\Http\Controllers\Controller;
use App\Models\Registration;
use App\Services\ApiResponseService;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class VerifyEmailController extends Controller
{
    protected $otpService;

    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }

    /**
     * Verify email with token
     */
    public function verify(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'otp' => 'required|string',
        ]);

        if ($validator->fails()) {
            return ApiResponseService::validationError($validator->errors());
        }

        $email = $request->email;
        $token = $request->otp;

        // 1. Verify OTP using global service
        $isValid = $this->otpService->verifyOtp($email, $token, 'registration');

        if (!$isValid) {
            return ApiResponseService::error('responses.invalid_token', [
                'message' => 'Invalid or expired verification code.'
            ], 422, null, 'INVALID_OTP');
        }

        // 2. Update registration status
        $registration = Registration::where('email', $email)->first();

        if ($registration) {
            $registration->update([
                'email_verified' => true,
                'status' => \App\Enums\AuthStatus::verified->value,
            ]);
        }

        // 3. Delete OTP after successful verification
        $this->otpService->deleteOtp($email, 'registration');

        return ApiResponseService::success(
            'responses.email_verified_successfully',
            [
                'message' => 'Email verified successfully. You can now complete your profile.',
            ],
            data: [
                'email' => $email,
                'status' => \App\Enums\AuthStatus::verified->value,
            ],
            code: 'EMAIL_VERIFIED'
        );
    }
}
