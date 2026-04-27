<?php

namespace App\Http\Controllers\Api\V2\Auth;

use App\Http\Controllers\Controller;
use App\Models\Registration;
use App\Models\User;
use App\Services\ApiResponseService;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class RegisterController extends Controller
{
    protected $otpService;

    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }

    /**
     * Submit email for registration
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return ApiResponseService::validationError($validator->errors());
        }

        $email = $request->email;

        // 1. Check if user already exists in main table
        if (User::where('email', $email)->exists()) {
            return ApiResponseService::error('responses.email_already_registered', [
                'message' => 'This email is already registered. Please login.'
            ], 422, null, 'ALREADY_REGISTERED');
        }

        // 2. Check registration status
        $registration = Registration::where('email', $email)->first();

        if ($registration && $registration->status === \App\Enums\AuthStatus::verified->value) {
            return ApiResponseService::error(
                'responses.email_already_verified',
                [
                    'email' => $email,
                    'status' => \App\Enums\AuthStatus::verified->value,
                    'message' => 'Email is already verified. Please complete your profile.'
                ],
                422,
                null,
                'ALREADY_VERIFIED'
            );
        }

        // 3. Create/Update registration record
        if (!$registration) {
            $registration = Registration::create([
                'email' => $email,
                'status' => \App\Enums\AuthStatus::new_register->value,
            ]);
        } else {
            $registration->update(['status' => \App\Enums\AuthStatus::new_register->value]);
        }

        // 4. Send OTP via global OtpService
        $this->otpService->sendOtp($email, 'registration');

        return ApiResponseService::success(
            'responses.verification_code_sent',
            [
                'message' => 'Verification code sent to your email.'
            ],
            data: [
                'email' => $email,
            ],
            code: 'VERIFICATION_SENT'
        );
    }
}
