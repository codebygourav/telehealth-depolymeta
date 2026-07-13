<?php

namespace App\Http\Controllers\Api\V2\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Registration;
use App\Services\ApiResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Enums\DoctorStatus;

class LoginController extends Controller
{
    /**
     * Patient Login
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
            'expo_push_token' => 'nullable|string',
            'device_type' => 'nullable|string',
            'device_name' => 'nullable|string',
            'app_version' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return ApiResponseService::validationError($validator->errors());
        }

        $email = $request->email;

        $user = User::where('email', $email)->first();
        $isDoctorLogin = $user?->hasRole('doctor') ?? false;
        // 0. Registration Status Check — patient-only registration flow
        $registration = Registration::where('email', $email)->first();

        if (! $isDoctorLogin && $registration && $registration->status === \App\Enums\AuthStatus::verified->value) {
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

        if (! $isDoctorLogin && $registration && $registration->status === \App\Enums\AuthStatus::new_register->value) {
            return ApiResponseService::error(
                'responses.email_not_verified',
                [
                    'email' => $email,
                    'status' => \App\Enums\AuthStatus::new_register->value,
                    'message' => 'Your email is not yet verified. Please verify your email first.'
                ],
                422,
                null,
                'EMAIL_NOT_VERIFIED'
            );
        }

        // 1. Basic Auth Check
        if (! $user || ! Hash::check($request->password, $user->password)) {
            return ApiResponseService::error('responses.invalid_credentials', [
                'message' => 'Invalid email or password'
            ], 401, null, 'INVALID_CREDENTIALS');
        }

        // Save expo_push_token and device info if provided
        if ($request->has('expo_push_token') && !empty($request->expo_push_token)) {
            // Remove the token from any other users to prevent the 1062 duplicate entry error
            // since push_token has a unique constraint in the database.
            \App\Models\UserDevice::where('push_token', $request->expo_push_token)
                ->where('user_id', '!=', $user->id)
                ->delete();

            \App\Models\UserDevice::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'push_token' => $request->expo_push_token
                ],
                [
                    'device_type' => $request->device_type,
                    'device_name' => $request->device_name,
                    'app_version' => $request->app_version,
                    'is_active' => true,
                    'last_used_at' => now(),
                ]
            );
        }


        // 3. Email Verification Check
        if (! $isDoctorLogin && ! $user->email_verified_at) {
            return ApiResponseService::error('responses.email_not_verified', [
                'message' => 'Please verify your email to continue.'
            ], 403, null, 'EMAIL_NOT_VERIFIED');
        }

        // 4. Role Specific Profile Check
        if ($user->hasRole('doctor')) {
            $doctor = $user->doctor()->first();
            if (! $doctor) {
                return ApiResponseService::error('responses.doctor_profile_missing', [
                    'message' => 'Doctor profile not found. Please contact administration.'
                ], 403, null, 'DOCTOR_PROFILE_MISSING');
            }

            $doctorStatusValue = $doctor->status instanceof DoctorStatus
                ? $doctor->status->value
                : $doctor->status;

            if ($doctorStatusValue !== DoctorStatus::ACTIVE->value) {
                return ApiResponseService::error('responses.doctor_not_active', [
                    'message' => 'Doctor account is not active. Please contact administration.'
                ], 403, null, 'DOCTOR_NOT_ACTIVE');
            }
        } elseif ($user->hasRole('patient')) {
            if (! $user->patient()->exists()) {
                // Create temporary token for profile setup with shorter expiration (24 hours)
                $setupTokenExpiration = now()->addHours(24);
                $setupToken = $user->createToken(
                    'patient-app-setup',
                    ['*'],
                    $setupTokenExpiration
                )->plainTextToken;

                return ApiResponseService::error('responses.profile_incomplete', [
                    'message' => 'Please complete your patient profile.',
                    'token' => $setupToken,
                ], 403);
            }


            if ($user->status instanceof \App\Enums\AuthStatus) {
                $userStatusValue = $user->status->value;
            } else {
                $userStatusValue = $user->status;
            }

            if ($userStatusValue !== \App\Enums\AuthStatus::registered->value) {
                return ApiResponseService::error('responses.patient_not_registered', [
                    'message' => 'Patient account is not fully registered.'
                ], 403, null, 'PATIENT_NOT_REGISTERED');
            }
        }

        if ($user->hasRole('doctor')) {
            $tokenName = 'doctor-app';
        } elseif ($user->hasRole('patient')) {
            $tokenName = 'patient-app';
        } else {
            $tokenName = 'user-app';
        }

        $expirationMinutes = config('sanctum.expiration');
        // Cast to int to handle string values from env, handle null case
        $expiresAt = $expirationMinutes ? now()->addMinutes((int) $expirationMinutes) : null;

        $token = $user->createToken(
            $tokenName,
            ['*'],
            $expiresAt
        )->plainTextToken;

        // Get user role
        $role = $user->getRoleNames()->first() ?? 'user';

        // Eagerly load doctor or patient relationship to prevent null property issues
        if ($user->hasRole('doctor')) {
            $doctor = $user->doctor()->first();
            $firstname = $doctor?->first_name ?? $user->name;
            $lastname = $doctor?->last_name ?? '';
            $gender = $doctor?->gender ?? '';
            $dob = $doctor?->dob ?? '';
            $email = $doctor?->email ?? $user->email;
            $avatar = storage_url($doctor?->avatar ?? null);
            $phone = $doctor?->mobile_no ?? $user->phone ?? '';
            $address = [
                'address' => $doctor?->address ?? '',
                'pincode' => $doctor?->pincode ?? '',
                'area' => $doctor?->area ?? '',
                'city' => $doctor?->city ?? '',
                'state' => $doctor?->state ?? '',
                'landmark' => $doctor?->landmark ?? '',
                'nationality' => $doctor?->nationality ?? '',
            ];
            $profileIdKey = 'doctor_id';
            $status = $doctor?->status ?? null;
            $profileId = $doctor?->id;
        } elseif ($user->hasRole('patient')) {
            $patient = $user->patient()->first();
            $firstname = $patient?->first_name ?? $user->name;
            $lastname = $patient?->last_name ?? '';
            $gender = $patient?->gender ?? '';
            $dob = $patient?->date_of_birth ?? '';
            $email = $patient?->email ?? $user->email;
            $avatar = storage_url($patient?->avatar) ?? null;
            $phone = $patient?->mobile_no ?? $user->phone ?? '';
            $bio = $patient?->bio ?? '';
            $address = [
                'address' => $patient?->address ?? '',
                'pincode' => $patient?->pincode ?? '',
                'area' => $patient?->area ?? '',
                'city' => $patient?->city ?? '',
                'state' => $patient?->state ?? '',
                'landmark' => $patient?->landmark ?? '',
                'nationality' => $patient?->nationality ?? '',
                'bio' => $patient?->bio ?? '',
            ];
            $profileIdKey = 'patient_id';
            $profileId = $patient?->id;
            $status = $user->status ?? null;
            $is_existing_patient = $patient?->is_existing_patient ?? 0;
            $existing_patient_id = $patient?->existing_patient_id ?? '';
        }

        return ApiResponseService::success(
            'responses.login_success',
            [
                'message' => 'Login successful',
                'token' => $token,
            ],
            [
                'id' => $user->id,
                'first_name' => $firstname,
                'last_name' => $lastname,
                'email' => $email,
                'role' => $role,
                'gender' => $gender,
                'phone' => $phone,
                'date_of_birth' => $dob,
                'address' => $address,
                'avatar' => $avatar,
                $profileIdKey => $profileId,
                'status' => $status,
                // Only return patient keys if patient, not doctor
                ...($role === 'patient' ? [
                    'is_existing_patient' => $is_existing_patient,
                    'existing_patient_id' => $existing_patient_id,
                ] : []),
        
            ],
            code: 'LOGIN_SUCCESS'
        );
    }

    /**
     * Logout
     */
    public function logout(Request $request)
    {
        \App\Models\UserDevice::where('user_id', $request->user()->id)
            ->update(['is_active' => false]);

        $request->user()->currentAccessToken()->delete();

        return ApiResponseService::success('responses.logout_success', [], null, 'LOGOUT_SUCCESS');
    }
}
