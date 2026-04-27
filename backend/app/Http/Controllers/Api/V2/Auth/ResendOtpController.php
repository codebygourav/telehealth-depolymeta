<?php

namespace App\Http\Controllers\Api\V2\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Registration;
use App\Services\ApiResponseService;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ResendOtpController extends Controller
{
    protected $otpService;

    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }

    /**
     * Resend OTP for registration, forgot password, etc.
     */
    public function resendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'context' => 'required|string|in:registration,forgot_password',
        ]);

        if ($validator->fails()) {
            return ApiResponseService::validationError($validator->errors());
        }

        $email = $request->email;
        $context = $request->context;

        if ($context === 'registration') {
            if (User::where('email', $email)->exists()) {
                return ApiResponseService::error('responses.email_already_registered', [
                    'message' => 'This email is already registered. Please login.'
                ], 422, null, 'ALREADY_REGISTERED');
            }

            $registration = Registration::where('email', $email)->first();
            if ($registration && $registration->status === \App\Enums\AuthStatus::verified->value) {
                return ApiResponseService::error('responses.email_already_verified', [
                    'message' => 'Email is already verified. Please complete your profile.'
                ], 422, null, 'ALREADY_VERIFIED');
            }

            // If they haven't started registration, we can just allow them to start it by sending it anyway
            if (!$registration) {
                Registration::create([
                    'email' => $email,
                    'status' => \App\Enums\AuthStatus::new_register->value,
                ]);
            }
        } elseif ($context === 'forgot_password') {
            if (!User::where('email', $email)->exists()) {
                return ApiResponseService::error('responses.not_found', [
                    'message' => 'No account found with this email.'
                ], 404, null, 'USER_NOT_FOUND');
            }
        }

        // Send OTP via global OTP service
        $this->otpService->sendOtp($email, $context);

        return ApiResponseService::success(
            'responses.verification_code_sent',
            [
                'message' => 'OTP has been resent to your email.'
            ],
            data: [
                'email' => $email,
                'context' => $context
            ],
            code: 'OTP_RESENT'
        );
    }
}
