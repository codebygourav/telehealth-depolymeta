<?php

namespace App\Services;

use App\Enums\AuthStatus;
use App\Models\Patient;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class PatientAuthAccountService
{
    public const DEFAULT_PASSWORD = 'Patient@123';

    public function provision(array $patientData, ?Patient $patient = null, ?string $plainPassword = null, ?User $user = null): array
    {
        $patient ??= new Patient();

        $email = $this->normalizeEmail($patientData['user_email'] ?? $patientData['email'] ?? $patient->email ?? null);
        $phone = $this->normalizePhone($patientData['user_phone'] ?? $patientData['mobile_no'] ?? $patient->mobile_no ?? $user?->phone ?? null);

        if (! $email) {
            throw ValidationException::withMessages([
                'data.email' => 'Email is required for patient login.',
            ]);
        }

        $user = $this->resolveUser($patient, $email, $user);
        $isNewUser = ! $user;

        if (! $user) {
            $user = new User();
        } elseif ($user->trashed()) {
            $user->restore();
        }

        $name = trim(($patientData['first_name'] ?? $patient->first_name ?? '') . ' ' . ($patientData['last_name'] ?? $patient->last_name ?? ''));
        $passwordToSet = filled($plainPassword) ? $plainPassword : ($isNewUser ? self::DEFAULT_PASSWORD : null);

        $userPayload = [
            'name' => $name !== '' ? $name : ($user->name ?? 'Patient'),
            'email' => $email,
            'phone' => $phone,
            'email_verified_at' => $user->email_verified_at ?: now(),
            'status' => AuthStatus::registered->value,
        ];

        if ($isNewUser) {
            $userPayload['slug'] = Str::slug(($name !== '' ? $name : 'patient') . '-' . Str::random(6));
        }

        if ($passwordToSet !== null) {
            $userPayload['password'] = Hash::make($passwordToSet);
        }

        $user->forceFill($userPayload);
        $user->save();

        $this->ensurePatientRole($user);
        $this->markRegistrationAsRegistered($email);

        $patientPayload = $patientData;
        unset($patientPayload['user_email'], $patientPayload['user_phone'], $patientPayload['user_password']);

        $patientPayload['user_id'] = $user->id;
        $patientPayload['email'] = $email;
        $patientPayload['mobile_no'] = $phone ?: ($patientPayload['mobile_no'] ?? $patient->mobile_no);
        $patientPayload['create_user_account'] = true;
        $patientPayload['source'] = $patientPayload['source'] ?? ($patient->source ?: 'internal');

        if ($patient->exists) {
            $patient->fill($patientPayload);
            $patient->save();
        } else {
            $patient = Patient::create($patientPayload);
        }

        return [
            'user' => $user->fresh(),
            'patient' => $patient->fresh(),
            'generated_password' => $isNewUser && ! filled($plainPassword) ? self::DEFAULT_PASSWORD : null,
        ];
    }

    public function ensurePatientProfileForUser(User $user): Patient
    {
        if ($user->trashed()) {
            $user->restore();
        }

        $patient = Patient::withTrashed()
            ->where('user_id', $user->id)
            ->first();

        if (! $patient && filled($user->email)) {
            $patient = Patient::withTrashed()
                ->whereRaw('LOWER(email) = ?', [strtolower($user->email)])
                ->first();

            if ($patient && $patient->user_id && $patient->user_id !== $user->id) {
                throw ValidationException::withMessages([
                    'data.email' => 'This email is already linked to another patient account.',
                ]);
            }
        }

        if ($patient?->trashed()) {
            $patient->restore();
        }

        [$firstName, $lastName] = $this->splitName($user->name);

        return $this->provision([
            'first_name' => $firstName !== '' ? $firstName : ($patient?->first_name ?? null),
            'last_name' => $lastName !== '' ? $lastName : ($patient?->last_name ?? null),
            'email' => $user->email,
            'mobile_no' => $user->phone,
            'source' => $patient?->source ?: 'internal',
            'create_user_account' => true,
        ], $patient, null, $user)['patient'];
    }

    private function resolveUser(Patient $patient, string $email, ?User $user = null): ?User
    {
        if ($user) {
            $conflict = User::withTrashed()
                ->whereRaw('LOWER(email) = ?', [$email])
                ->whereKeyNot($user->id)
                ->first();

            if ($conflict) {
                throw ValidationException::withMessages([
                    'data.email' => 'This email is already used by another user account.',
                ]);
            }

            return $user;
        }

        if ($patient->user_id) {
            $linkedUser = User::withTrashed()->find($patient->user_id);

            if (! $linkedUser) {
                throw ValidationException::withMessages([
                    'data.email' => 'The linked user account was not found. Please use another email.',
                ]);
            }

            $conflict = User::withTrashed()
                ->whereRaw('LOWER(email) = ?', [$email])
                ->whereKeyNot($linkedUser->id)
                ->first();

            if ($conflict) {
                throw ValidationException::withMessages([
                    'data.email' => 'This email is already used by another user account.',
                ]);
            }

            return $linkedUser;
        }

        $user = User::withTrashed()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        if (! $user) {
            return null;
        }

        $linkedPatient = Patient::withTrashed()
            ->where('user_id', $user->id)
            ->when($patient->exists, fn ($query) => $query->whereKeyNot($patient->getKey()))
            ->first();

        if ($linkedPatient) {
            throw ValidationException::withMessages([
                'data.email' => 'This user account is already linked to another patient. Select that patient or use another email.',
            ]);
        }

        return $user;
    }

    private function normalizeEmail(mixed $email): ?string
    {
        if (! is_string($email)) {
            return null;
        }

        $email = trim($email);

        return $email === '' ? null : strtolower($email);
    }

    private function normalizePhone(mixed $phone): ?string
    {
        if (! is_string($phone) && ! is_numeric($phone)) {
            return null;
        }

        $phone = preg_replace('/[\s\-()]/', '', (string) $phone);

        return $phone === '' ? null : $phone;
    }

    private function ensurePatientRole(User $user): void
    {
        if (method_exists($user, 'assignRole')) {
            if (! method_exists($user, 'hasRole') || ! $user->hasRole('patient')) {
                $user->assignRole('patient');
            }

            return;
        }

        if (class_exists(Role::class)) {
            $roleClass = Role::class;
            $patientRole = $roleClass::where('name', 'patient')->first();

            if ($patientRole && ! $user->roles->contains('id', $patientRole->id)) {
                $user->roles()->attach($patientRole);
            }
        }
    }

    private function markRegistrationAsRegistered(string $email): void
    {
        $registration = Registration::withTrashed()
            ->whereRaw('LOWER(email) = ?', [strtolower($email)])
            ->first();

        if ($registration) {
            if ($registration->trashed()) {
                $registration->restore();
            }

            $registration->update([
                'email_verified' => true,
                'status' => AuthStatus::registered->value,
            ]);

            return;
        }

        Registration::create([
            'email' => strtolower($email),
            'email_verified' => true,
            'status' => AuthStatus::registered->value,
        ]);
    }

    private function splitName(?string $name): array
    {
        $name = trim((string) $name);

        if ($name === '') {
            return ['Patient', ''];
        }

        $parts = preg_split('/\s+/', $name) ?: [];
        $firstName = array_shift($parts) ?: 'Patient';
        $lastName = implode(' ', $parts);

        return [$firstName, $lastName];
    }
}
