<?php

namespace App\Http\Controllers\Api\V2\Auth;

use App\Http\Controllers\Controller;
use App\Models\{Patient, UserDevice, User, Registration};
use App\Services\ApiResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    /**
     * Complete profile and create user/patient account
     */
    public function complete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:8',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'gender' => 'required|string|in:male,female,other',
            'date_of_birth' => 'required|date',
            'mobile_no' => 'required|string|max:20',
            'expo_push_token' => 'nullable|string',
            'device_type' => 'nullable|string',
            'device_name' => 'nullable|string',
            'app_version' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return ApiResponseService::validationError($validator->errors(), null, 'VALIDATION_ERROR');
        }

        $email = $request->email;

        // 1. Check if user already exists (completely registered)
        if (User::where('email', $email)->exists()) {
            return ApiResponseService::error('responses.email_already_registered', [
                'message' => 'This account is already fully registered. Please login.'
            ], 422, null, 'ALREADY_REGISTERED');
        }

        // 2. Check registration status in temporary table
        $registration = Registration::where('email', $email)->first();

        if (!$registration) {
            return ApiResponseService::error('responses.email_not_registered', [
                'message' => 'Email not found in registration. Please start registration.'
            ], 422, null, 'NOT_REGISTERED');
        }

        $statusValue = $registration->status instanceof \App\Enums\AuthStatus
            ? $registration->status->value
            : $registration->status;

        if ($statusValue !== \App\Enums\AuthStatus::verified->value) {
            return ApiResponseService::error('responses.email_not_verified', [
                'message' => 'Please verify your email before completing profile.'
            ], 422, null, 'NOT_VERIFIED');
        }

        try {
            return DB::transaction(function () use ($request, $registration) {
                // 1. Create User
                $user = User::create([
                    'name' => $request->first_name . ' ' . $request->last_name,
                    'email' => $registration->email,
                    'password' => Hash::make($request->password),
                    'phone' => $request->mobile_no,
                    'email_verified_at' => now(),
                    'status' => \App\Enums\AuthStatus::registered->value,
                ]);

                // 2. Assign Patient Role
                $user->assignRole('patient');

                // 3. Create Patient
                $patient = Patient::create([
                    'user_id' => $user->id,
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'email' => $registration->email,
                    'gender' => $request->gender,
                    'date_of_birth' => $request->date_of_birth,
                    'mobile_no' => $request->mobile_no,
                    'age' => \Carbon\Carbon::parse($request->date_of_birth)->age,
                    'source' => 'app',
                    'create_user_account' => true,
                ]);

                if ($request->has('expo_push_token') && !empty($request->expo_push_token)) {
                    // Remove the token from any other users to prevent the 1062 duplicate entry error
                    // since push_token has a unique constraint in the database.
                    UserDevice::where('push_token', $request->expo_push_token)
                        ->where('user_id', '!=', $user->id)
                        ->delete();

                    UserDevice::updateOrCreate(
                        ['user_id' => $user->id, 'push_token' => $request->expo_push_token],
                        [
                            'device_type' => $request->device_type,
                            'device_name' => $request->device_name,
                            'app_version' => $request->app_version,
                            'is_active' => true,
                            'last_used_at' => now(),
                        ]
                    );
                }

                // 3. Update registration status instead of deleting it
                $registration->update(['status' => \App\Enums\AuthStatus::registered->value]);

                // 4. Generate token for immediate login
                $token = $user->createToken('patient_auth_token')->plainTextToken;
                $avatar = storage_url($patient?->avatar) ?? null;
                $responseData = [
                    'id' => $user->id,
                    'name' => $user->name,
                    'first_name' => $user->patient->first_name,
                    'last_name' => $user->patient->last_name,
                    'email' => $user->email,
                    'gender' => $user->patient ? $user->patient->gender : null,
                    'phone' => $user->patient ? $user->patient->mobile_no : null,
                    'date_of_birth' => $user->patient ? $user->patient->date_of_birth : null,
                    'address' => [
                        'address' => $user->patient ? $user->patient->address : null,
                        'pincode' => $user->patient ? $user->patient->pincode : null,
                        'area' => $user->patient ? $user->patient->area : null,
                        'city' => $user->patient ? $user->patient->city : null,
                        'landmark' => $user->patient ? $user->patient->landmark : null,
                        'state' => $user->patient ? $user->patient->state : null,
                    ],
                    'avatar' => $avatar,
                    'status' => $user->status,
                    'patient_id' => $user->patient ? $user->patient->id : null,
                ];
                return ApiResponseService::success(
                    'responses.profile_completed',
                    [
                        'message' => 'Profile completed and account created successfully.',
                        'token' => $token
                    ],
                    data: $responseData,
                    code: 'PROFILE_COMPLETED'
                );
            });
        } catch (\Exception $e) {
            Log::error('Profile completion error: ' . $e->getMessage());
            return ApiResponseService::error('responses.profile_completion_failed', [
                'message' => $e->getMessage()
            ], 500, null, 'SERVER_ERROR');
        }
    }
}
