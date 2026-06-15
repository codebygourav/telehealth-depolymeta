<?php

namespace App\Http\Controllers\Api\V2\Wordpress;

use App\Http\Controllers\Controller;
use App\Mail\PatientCredentialsMail;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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

            if ($patient || $user) {
                return response()->json([
                    'message' => 'Patient created successfully',
                    'patient' => $patient,
                    'user' => $user,
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

        if ($patient || $user) {
            return response()->json([
                'message' => 'Patient created successfully',
                'patient' => $patient,
                'user' => $user,
            ]);
        }

        $rawPassword = Str::random(10);
        $hashedPassword = Hash::make($rawPassword);

        $patientData = [
            'first_name'          => $validated['first_name'],
            'last_name'           => $validated['last_name'],
            'gender'              => $validated['gender'],
            'age'                 => $validated['age'],
            'marital_status'      => $validated['marital_status'],
            'father_name'         => $validated['father_name'] ?? null,
            'husband_name'        => $validated['husband_name'] ?? null,
            'mobile_no'           => $validated['mobile'],
            'email'               => $validated['email'],
            'password'            => $hashedPassword,
            'address'             => $validated['address'],
            'is_existing_patient' => (bool) ($validated['is_existing_patient'] ?? false),
            'existing_patient_id' => $validated['existing_patient_id'] ?? null,
            'source'              => $validated['source'] ?? 'website',
        ];

        $user = null;

        $userData = [
            'name'              => trim($validated['first_name'] . ' ' . $validated['last_name']),
            'email'             => $validated['email'],
            'password'          => $hashedPassword,
            'mobile'            => $validated['mobile'],
            'phone'             => $validated['mobile'],
            'email_verified_at' => now(),
            'status'            => \App\Enums\AuthStatus::registered->value,
        ];

        try {
            $user = User::create($userData);
        } catch (UniqueConstraintViolationException $e) {
            return $this->existingPatientResponse($validated['email']);
        }

        if (method_exists($user, 'assignRole')) {
            $user->assignRole('patient');
        }

        if (array_key_exists('user_id', (new Patient())->getFillable())) {
            $patientData['user_id'] = $user->id;
        }

        if (class_exists('\App\Models\Registration')) {
            \App\Models\Registration::updateOrCreate(
                ['email' => $validated['email']],
                [
                    'email_verified' => true,
                    'status' => \App\Enums\AuthStatus::registered->value,
                ]
            );
        }

        try {
            $patient = Patient::create($patientData);
        } catch (UniqueConstraintViolationException $e) {
            return $this->existingPatientResponse($validated['email']);
        }

        try {
            Mail::to($validated['email'])->send(
                new PatientCredentialsMail(
                    trim($validated['first_name'] . ' ' . $validated['last_name']),
                    $validated['email'],
                    $rawPassword
                )
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error(
                'Failed to send registration credentials email to patient: ' . $e->getMessage()
            );
        }

        return response()->json([
            'message' => 'Patient created successfully',
            'patient' => $patient,
            'user' => $user,
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
