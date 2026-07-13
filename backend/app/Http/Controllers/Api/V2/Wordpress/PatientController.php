<?php

namespace App\Http\Controllers\Api\V2\Wordpress;

use App\Http\Controllers\Controller;
use App\Mail\PatientCredentialsMail;
use App\Models\EmailLog;
use App\Models\Patient;
use App\Models\User;
use App\Services\PatientAuthAccountService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PatientController extends Controller
{
    public function store(Request $request)
    {
        $email = $this->normalizeEmail($request->input('email'));

        if ($email) {
            $request->merge(['email' => $email]);

            $patient = $this->findPatientByEmail($email);
            $user = $this->findUserByEmail($email);

            if ($patient && $user) {
                $provisioned = app(PatientAuthAccountService::class)->provision(
                    patientData: [
                        'first_name' => $patient->first_name,
                        'last_name' => $patient->last_name,
                        'gender' => $patient->gender,
                        'age' => $patient->age,
                        'marital_status' => $patient->marital_status,
                        'father_name' => $patient->father_name,
                        'wife_name' => $patient->wife_name,
                        'husband_name' => $patient->husband_name,
                        'mobile_no' => $patient->mobile_no,
                        'email' => $email,
                        'address' => $patient->address,
                        'is_existing_patient' => $patient->is_existing_patient,
                        'existing_patient_id' => $patient->existing_patient_id,
                        'source' => $patient->source ?: 'website',
                    ],
                    patient: $patient,
                    user: $user,
                );

                return response()->json([
                    'message' => 'Patient created successfully',
                    'patient' => $provisioned['patient'],
                    'user' => $provisioned['user'],
                ]);
            }
        }

        $rules = [
            'first_name'          => 'required|string|max:255',
            'last_name'           => 'required|string|max:255',
            'gender'              => 'required|in:male,female,other',
            'age'                 => 'required|integer|min:1|max:120',
            'marital_status'      => 'required|in:single,married',

            'father_name' => [
                'nullable',
                'string',
                'max:255',
                function ($attribute, $value, $fail) use ($request) {
                    if (
                        (
                            $request->marital_status === 'single' ||
                            (
                                $request->gender === 'male' &&
                                $request->marital_status === 'married'
                            )
                        ) &&
                        empty($value)
                    ) {
                        $fail('The father name field is required.');
                    }
                },
            ],


            'husband_name' => [
                'nullable',
                'string',
                'max:255',
                function ($attribute, $value, $fail) use ($request) {
                    if (
                        $request->gender === 'female' &&
                        $request->marital_status === 'married' &&
                        empty($value)
                    ) {
                        $fail('The husband name field is required.');
                    }
                },
            ],

            'mobile'              => 'required|string|digits:10',
            'alternate_mobile'    => 'nullable|string|digits:10',
            'email'               => 'required|email|max:255',
            'address'             => 'required|string|max:1000',
            'is_existing_patient' => 'nullable|boolean',
            'existing_patient_id' => 'nullable|string|max:255',
            'source'              => 'nullable|string|max:50',
        ];

        $messages = [
            'first_name.required'          => 'The first name field is required.',
            'last_name.required'           => 'The last name field is required.',
            'gender.required'              => 'The gender field is required.',
            'gender.in'                    => 'The gender must be either male, female, or other.',
            'age.required'                 => 'The age field is required.',
            'age.integer'                  => 'The age must be a valid whole number.',
            'age.min'                      => 'The age must be at least 1.',
            'age.max'                      => 'The age cannot be greater than 120.',
            'marital_status.required'      => 'The marital status field is required.',
            'marital_status.in'            => 'The marital status must be single or married.',
            'mobile.required'              => 'The mobile number field is required.',
            'mobile.digits'                => 'The mobile number must be exactly 10 digits.',
            'email.required'               => 'The email address field is required.',
            'email.email'                  => 'Please provide a valid email address.',
            'address.required'             => 'The address field is required.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        $validated['email'] = $this->normalizeEmail($validated['email']);

        $patient = $this->findPatientByEmail($validated['email']);
        $user = $this->findUserByEmail($validated['email']);

        $rawPassword = null;

        if (! $user) {
            $rawPassword = Str::random(10);
        }

        try {
            $provisioned = app(PatientAuthAccountService::class)->provision(
                patientData: [
                    'first_name' => $validated['first_name'],
                    'last_name' => $validated['last_name'],
                    'gender' => $validated['gender'],
                    'age' => $validated['age'],
                    'marital_status' => $validated['marital_status'],
                    'father_name' => $validated['father_name'] ?? null,
                    'husband_name' => $validated['husband_name'] ?? null,
                    'mobile_no' => $validated['mobile'],
                    'email' => $validated['email'],
                    'address' => $validated['address'],
                    'is_existing_patient' => (bool) ($validated['is_existing_patient'] ?? false),
                    'existing_patient_id' => $validated['existing_patient_id'] ?? null,
                    'source' => $validated['source'] ?? 'website',
                ],
                patient: $patient,
                plainPassword: $rawPassword,
                user: $user,
            );
        } catch (UniqueConstraintViolationException $e) {
            return $this->existingPatientResponse($validated['email']);
        }

        if ($rawPassword !== null) {
            $mailable = new PatientCredentialsMail(
                trim($validated['first_name'] . ' ' . $validated['last_name']),
                $validated['email'],
                $rawPassword
            );
            $subject = 'Your Account Credentials - ' . config('app.name');
            $htmlBody = null;

            try {
                $htmlBody = $mailable->render();
            } catch (\Throwable $e) {
                // Ignore render errors for logging
            }

            try {
                Mail::to($validated['email'])->send($mailable);

                EmailLog::recordSent(
                    type: PatientCredentialsMail::class,
                    toEmail: $validated['email'],
                    subject: $subject,
                    patientId: $provisioned['patient']->id ?? null,
                    htmlBody: $htmlBody
                );
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error(
                    'Failed to send registration credentials email to patient: ' . $e->getMessage()
                );

                EmailLog::recordFailed(
                    type: PatientCredentialsMail::class,
                    toEmail: $validated['email'],
                    subject: $subject,
                    errorMessage: $e->getMessage(),
                    patientId: $provisioned['patient']->id ?? null,
                    htmlBody: $htmlBody
                );
            }
        }

        return response()->json([
            'message' => 'Patient created successfully',
            'patient' => $provisioned['patient'],
            'user' => $provisioned['user'],
        ]);
    }

    private function normalizeEmail(mixed $email): ?string
    {
        if (! is_string($email)) {
            return null;
        }

        $email = trim($email);

        return $email === '' ? null : strtolower($email);
    }

    private function findPatientByEmail(string $email): ?Patient
    {
        return Patient::withTrashed()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();
    }

    private function findUserByEmail(string $email): ?User
    {
        return User::withTrashed()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();
    }

    private function existingPatientResponse(string $email): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'message' => 'Patient created successfully',
            'patient' => $this->findPatientByEmail($email),
            'user' => $this->findUserByEmail($email),
        ]);
    }
}
