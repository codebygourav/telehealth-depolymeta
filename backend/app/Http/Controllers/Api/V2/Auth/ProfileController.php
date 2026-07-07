<?php

namespace App\Http\Controllers\Api\V2\Auth;

use App\Enums\BloodGroupOption;
use App\Enums\MaritalStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Doctor\DoctorAvailabilityResource;
use App\Mail\PatientRegistrationCompleteMail;
use App\Models\{Department, Doctor, Patient, Registration, User, UserDevice};
use App\Services\ApiResponseService;
use App\Services\DoctorAvailabilityService;
use App\Services\RegistrationBookingService;
use App\Services\SettingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    /**
     * Registration wizard: departments for visit-details dropdown (public).
     */
    public function registrationDepartments()
    {
        $departments = Department::query()
            ->select('id', 'name')
            ->orderBy('name')
            ->get()
            ->map(fn ($d) => ['id' => $d->id, 'name' => $d->name])
            ->values();

        return ApiResponseService::success(
            responseKey: 'responses.success',
            data: ['departments' => $departments],
        );
    }

    /**
     * Doctors in a department for registration booking (public).
     */
    public function registrationDoctors(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'department_id' => ['required', 'uuid', 'exists:departments,id'],
        ]);

        if ($validator->fails()) {
            return ApiResponseService::validationError($validator->errors(), null, 'VALIDATION_ERROR');
        }

        $doctors = Doctor::query()
            ->active()
            ->visibleInMobileApp()
            ->whereHas('departments', fn ($q) => $q->where('departments.id', $request->department_id))
            ->with(['departments:id,name'])
            ->orderBy('first_name')
            ->get()
            ->map(function (Doctor $doctor) {
                return [
                    'id' => $doctor->id,
                    'first_name' => $doctor->first_name,
                    'last_name' => $doctor->last_name,
                    'name' => trim(($doctor->first_name ?? '') . ' ' . ($doctor->last_name ?? '')),
                    'departments' => $doctor->departments->map(fn ($d) => [
                        'id' => $d->id,
                        'name' => $d->name,
                    ])->values(),
                ];
            })
            ->values();

        return ApiResponseService::success(
            responseKey: 'responses.success',
            data: ['doctors' => $doctors],
        );
    }

    /**
     * Expanded availability slots for a doctor (date, time, consultation type, fee) — public, for registration UI.
     */
    public function registrationDoctorAvailability(Request $request, DoctorAvailabilityService $availabilityService)
    {
        $validator = Validator::make($request->all(), [
            'doctor_id' => ['required', 'uuid', 'exists:doctors,id'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
        ]);

        if ($validator->fails()) {
            return ApiResponseService::validationError($validator->errors(), null, 'VALIDATION_ERROR');
        }

        $doctor = Doctor::query()
            ->active()
            ->visibleInMobileApp()
            ->whereKey($request->doctor_id)
            ->first();

        if (! $doctor) {
            return ApiResponseService::notFound(resource: 'Doctor', module: 'patient');
        }

        $start = $request->filled('from_date')
            ? Carbon::parse($request->from_date)->startOfDay()
            : Carbon::today();
        $end = $request->filled('to_date')
            ? Carbon::parse($request->to_date)->endOfDay()
            : Carbon::today()->copy()->addDays(30);

        $slots = $availabilityService->expandSlots(
            $doctor->availabilities()->where('is_available', true)->with('overrides')->get(),
            $start,
            $end
        );

        $grouped = $availabilityService->groupSlotsByDate($slots)->map(fn ($group) => [
            'date' => $group['date'],
            'slots' => DoctorAvailabilityResource::collection($group['slots'])->resolve($request),
        ]);

        return ApiResponseService::success(
            responseKey: 'responses.success',
            data: [
                'doctor_id' => $doctor->id,
                'availability_by_date' => $grouped->values(),
            ],
        );
    }

    /**
     * Check email registration / verification status (public).
     */
    public function checkStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
        ]);

        if ($validator->fails()) {
            return ApiResponseService::validationError($validator->errors(), null, 'VALIDATION_ERROR');
        }

        $email = $request->email;

        if (User::where('email', $email)->exists()) {
            return ApiResponseService::success(
                responseKey: 'responses.success',
                data: [
                    'status' => 'registered',
                    'message' => 'Account exists. Please log in.',
                ],
            );
        }

        $registration = Registration::where('email', $email)->first();

        if (! $registration) {
            return ApiResponseService::success(
                responseKey: 'responses.success',
                data: [
                    'status' => 'not_registered',
                    'message' => 'Start registration with this email first.',
                ],
            );
        }

        $statusValue = $registration->status instanceof \App\Enums\AuthStatus
            ? $registration->status->value
            : $registration->status;

        return ApiResponseService::success(
            responseKey: 'responses.success',
            data: [
                'status' => $statusValue,
                'email_verified' => (bool) $registration->email_verified,
            ],
        );
    }

    /**
     * Complete profile and create user/patient account; optional appointment + Razorpay order in one flow.
     */
    public function complete(Request $request, RegistrationBookingService $registrationBookingService)
    {
        $bloodValues = array_column(BloodGroupOption::cases(), 'value');
        $maritalValues = array_map(fn ($c) => $c->value, MaritalStatus::cases());

        $rules = [
            'email' => 'required|email',
            'password' => 'required|string|min:8',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'gender' => 'required|string|in:male,female,other',
            'date_of_birth' => 'required|date',
            'mobile_no' => 'required|string|max:20',
            'blood_group' => ['nullable', 'string', Rule::in($bloodValues)],
            'marital_status' => ['nullable', 'string', Rule::in($maritalValues)],
            'is_existing_patient' => ['nullable', 'boolean'],
            'existing_patient_id' => [
                'nullable',
                'string',
                'max:255',
                Rule::requiredIf(fn () => $request->boolean('is_existing_patient')),
            ],
            'alternate_no' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:2000',
            'allergies' => 'nullable|string|max:2000',
            'existing_conditions' => 'nullable|string|max:2000',
            'current_medications' => 'nullable|string|max:5000',
            'past_medical_history' => 'nullable|string|max:5000',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_relationship' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:32',
            'insurance_provider' => 'nullable|string|max:255',
            'insurance_policy_number' => 'nullable|string|max:255',
            'insurance_policy_expiry' => 'nullable|date',
            'insurance_tpa_details' => 'nullable|string|max:500',
            'treatment_consent_accepted' => 'nullable|boolean',
            'book_appointment' => 'nullable|boolean',
            'department_id' => ['nullable', 'uuid', 'exists:departments,id'],
            'doctor_id' => ['nullable', 'uuid', 'exists:doctors,id'],
            'availability_id' => ['nullable', 'uuid', 'exists:availabilities,id'],
            'appointment_date' => 'nullable|date',
            'appointment_time' => 'nullable|string',
            'consultation_type' => ['nullable', 'string', 'in:in-person,video'],
            'opd_type' => ['nullable', 'string', 'in:general,private'],
            'visit_reason' => 'nullable|string|max:5000',
            'expo_push_token' => 'nullable|string',
            'device_type' => 'nullable|string',
            'device_name' => 'nullable|string',
            'app_version' => 'nullable|string',
        ];

        $validator = Validator::make($request->all(), $rules);

        $validator->after(function ($validator) use ($request) {
            if ($request->boolean('book_appointment')) {
                foreach ([
                    'doctor_id' => 'Doctor is required to book an appointment.',
                    'availability_id' => 'Availability slot is required.',
                    'appointment_date' => 'Appointment date is required.',
                    'appointment_time' => 'Appointment time is required.',
                    'consultation_type' => 'Consultation type is required.',
                ] as $field => $message) {
                    if (! $request->filled($field)) {
                        $validator->errors()->add($field, $message);
                    }
                }
                if ($request->input('consultation_type') === 'in-person' && ! $request->filled('opd_type')) {
                    $validator->errors()->add('opd_type', 'OPD type is required for in-person visits.');
                }
            }
        });

        if ($validator->fails()) {
            return ApiResponseService::validationError($validator->errors(), null, 'VALIDATION_ERROR');
        }

        $email = $request->email;

        if (User::where('email', $email)->exists()) {
            return ApiResponseService::error('responses.email_already_registered', [
                'message' => 'This account is already fully registered. Please login.',
            ], 422, null, 'ALREADY_REGISTERED');
        }

        $registration = Registration::where('email', $email)->first();

        if (! $registration) {
            return ApiResponseService::error('responses.email_not_registered', [
                'message' => 'Email not found in registration. Please start registration.',
            ], 422, null, 'NOT_REGISTERED');
        }

        $statusValue = $registration->status instanceof \App\Enums\AuthStatus
            ? $registration->status->value
            : $registration->status;

        if ($statusValue !== \App\Enums\AuthStatus::verified->value) {
            return ApiResponseService::error('responses.email_not_verified', [
                'message' => 'Please verify your email before completing profile.',
            ], 422, null, 'NOT_VERIFIED');
        }

        if ($request->has('treatment_consent_accepted') && ! $request->boolean('treatment_consent_accepted')) {
            return ApiResponseService::validationError([
                'treatment_consent_accepted' => ['You must agree to treatment and data usage to continue.'],
            ], null, 'VALIDATION_ERROR');
        }

        if ($request->boolean('book_appointment') && $request->filled('department_id')) {
            $inDept = Doctor::query()
                ->whereKey($request->doctor_id)
                ->whereHas('departments', fn ($q) => $q->where('departments.id', $request->department_id))
                ->exists();
            if (! $inDept) {
                return ApiResponseService::validationError([
                    'doctor_id' => ['The selected doctor does not belong to this department.'],
                ], null, 'VALIDATION_ERROR');
            }
        }

        $plainPassword = $request->password;
        $useMockPayment = SettingService::isAppointmentMockPaymentEnabled();

        try {
            $result = DB::transaction(function () use ($request, $registration, $registrationBookingService, $plainPassword, $useMockPayment) {
                $user = User::create([
                    'name' => $request->first_name . ' ' . $request->last_name,
                    'email' => $registration->email,
                    'password' => Hash::make($plainPassword),
                    'phone' => $request->mobile_no,
                    'email_verified_at' => now(),
                    'status' => \App\Enums\AuthStatus::registered->value,
                ]);

                $user->assignRole('patient');

                $patient = Patient::create([
                    'user_id' => $user->id,
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'email' => $registration->email,
                    'gender' => $request->gender,
                    'date_of_birth' => $request->date_of_birth,
                    'mobile_no' => $request->mobile_no,
                    'alternate_no' => $request->alternate_no,
                    'address' => $request->address,
                    'age' => Carbon::parse($request->date_of_birth)->age,
                    'blood_group' => $request->blood_group,
                    'marital_status' => $request->marital_status,
                    'allergies' => $request->allergies,
                    'existing_conditions' => $request->existing_conditions,
                    'current_medications' => $request->current_medications,
                    'past_medical_history' => $request->past_medical_history,
                    'emergency_contact_name' => $request->emergency_contact_name,
                    'emergency_contact_relationship' => $request->emergency_contact_relationship,
                    'emergency_contact_phone' => $request->emergency_contact_phone,
                    'insurance_provider' => $request->insurance_provider,
                    'insurance_policy_number' => $request->insurance_policy_number,
                    'insurance_policy_expiry' => $request->insurance_policy_expiry,
                    'insurance_tpa_details' => $request->insurance_tpa_details,
                    'treatment_consent_accepted' => $request->boolean('treatment_consent_accepted') ? true : ($request->has('treatment_consent_accepted') ? false : null),
                    'is_existing_patient' => $request->boolean('is_existing_patient'),
                    'existing_patient_id' => $request->boolean('is_existing_patient')
                        ? $request->existing_patient_id
                        : null,
                    'source' => 'app',
                    'create_user_account' => true,
                ]);

                if ($request->has('expo_push_token') && ! empty($request->expo_push_token)) {
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

                $registration->update(['status' => \App\Enums\AuthStatus::registered->value]);

                $bookingPayload = null;
                $appointment = null;

                if ($request->boolean('book_appointment')) {
                    $booking = $registrationBookingService->book($patient, [
                        'doctor_id' => $request->doctor_id,
                        'availability_id' => $request->availability_id,
                        'appointment_date' => $request->appointment_date,
                        'appointment_time' => $request->appointment_time,
                        'consultation_type' => $request->consultation_type,
                        'opd_type' => $request->opd_type,
                        'visit_reason' => $request->visit_reason,
                    ], $useMockPayment);

                    $appointment = $booking['appointment'];
                    $bookingPayload = $this->formatRegistrationBookingPaymentResponse(
                        $booking['payment'],
                        $booking['payment_order']
                    );
                }

                $token = $user->createToken('patient_auth_token')->plainTextToken;
                $avatar = storage_url($patient?->avatar) ?? null;

                $responseData = [
                    'id' => $user->id,
                    'name' => $user->name,
                    'first_name' => $user->patient->first_name,
                    'last_name' => $user->patient->last_name,
                    'is_existing_patient' => $patient->is_existing_patient,
                    'existing_patient_id' => $patient->existing_patient_id,
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

                if ($bookingPayload !== null && $appointment) {
                    $responseData['appointment'] = [
                        'id' => $appointment->id,
                        'slug' => $appointment->slug,
                        'date' => $appointment->appointment_date,
                        'time' => $appointment->appointment_time,
                        'status' => $appointment->status,
                    ];
                    $responseData['booking_payment'] = $bookingPayload;
                }

                return [
                    'user' => $user,
                    'patient' => $patient,
                    'token' => $token,
                    'responseData' => $responseData,
                    'appointment' => $appointment,
                    'bookingPayload' => $bookingPayload,
                ];
            });
        } catch (ValidationException $e) {
            return ApiResponseService::validationError($e->errors(), null, 'VALIDATION_ERROR');
        } catch (\Exception $e) {
            Log::error('Profile completion error: ' . $e->getMessage());

            return ApiResponseService::error('responses.profile_completion_failed', [
                'message' => $e->getMessage(),
            ], 500, null, 'SERVER_ERROR');
        }

        try {
            $appointmentSummary = null;
            if ($result['appointment']) {
                $result['appointment']->loadMissing(['doctor', 'availability', 'payment']);
                $a = $result['appointment'];
                $doctorLabel = $a->doctor
                    ? trim(($a->doctor->first_name ?? '') . ' ' . ($a->doctor->last_name ?? ''))
                    : null;
                $consultationLabel = $a->consultation_type === 'video' ? 'Online (video)' : 'In-person';
                $pay = $a->payment;
                $paymentLabel = 'No payment required';
                if ($pay) {
                    $st = $pay->status instanceof PaymentStatus ? $pay->status->value : $pay->status;
                    $paymentLabel = ucfirst((string) $st);
                    if ($st === PaymentStatus::PENDING->value && $pay->razorpay_order_id) {
                        $paymentLabel = 'Pending — complete payment in the app (Razorpay)';
                    }
                }
                $appointmentSummary = [
                    'Doctor' => $doctorLabel,
                    'Date' => $a->appointment_date ? Carbon::parse($a->appointment_date)->format('l, d M Y') : null,
                    'Time window' => ($a->availability && $a->availability->start_time && $a->availability->end_time)
                        ? Carbon::parse($a->availability->start_time)->format('g:i A') . ' – ' . Carbon::parse($a->availability->end_time)->format('g:i A')
                        : null,
                    'Consultation type' => $consultationLabel,
                    'Payment' => $paymentLabel,
                ];
            }

            Mail::to($result['user']->email)->send(new PatientRegistrationCompleteMail(
                patientName: $result['user']->name,
                email: $result['user']->email,
                passwordNote: 'Use the same password you just entered in the registration form. For security, avoid sharing it. You can change it anytime from the app after logging in.',
                appointmentSummary: $appointmentSummary,
            ));
        } catch (\Exception $e) {
            Log::warning('Registration welcome email failed: ' . $e->getMessage());
        }

        return ApiResponseService::success(
            'responses.profile_completed',
            [
                'message' => 'Profile completed and account created successfully.',
                'token' => $result['token'],
            ],
            data: $result['responseData'],
            code: 'PROFILE_COMPLETED'
        );
    }

    /**
     * @param  array<string, mixed>|null  $paymentOrderResult
     * @return array<string, mixed>
     */
    protected function formatRegistrationBookingPaymentResponse($payment, ?array $paymentOrderResult): array
    {
        $paymentData = null;

        if ($payment) {
            $paymentData = [
                'status' => $payment->status instanceof PaymentStatus ? $payment->status->value : ($payment->status ?? PaymentStatus::PENDING->value),
                'order_id' => $payment->razorpay_order_id ?? null,
                'payment_id' => $payment->razorpay_payment_id ?? null,
                'amount' => $payment->amount ?? null,
                'amount_paise' => (int) round(($payment->amount ?? 0) * 100),
                'razorpay_key_id' => config('services.razorpay.key_id', env('RAZORPAY_KEY_ID')),
                'payment_required' => false,
                'mock_payment' => $payment->payment_method === 'mock',
            ];
        } elseif ($paymentOrderResult) {
            $paymentData = [
                'status' => PaymentStatus::PENDING->value,
                'order_id' => $paymentOrderResult['order']['id'] ?? null,
                'amount_rupees' => $paymentOrderResult['amount_rupees'] ?? 0,
                'amount_paise' => $paymentOrderResult['amount_paise'] ?? 0,
                'razorpay_key_id' => $paymentOrderResult['key_id'] ?? null,
                'payment_required' => ($paymentOrderResult['amount_rupees'] ?? 0) > 0,
            ];
        }

        return $paymentData ?? [];
    }
}