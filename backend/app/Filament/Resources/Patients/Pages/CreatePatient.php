<?php

namespace App\Filament\Resources\Patients\Pages;

use App\Filament\Resources\Patients\PatientResource;
use App\Models\Patient;
use App\Models\Registration;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Alignment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class CreatePatient extends CreateRecord
{
    public function getFormActionsAlignment(): Alignment|string
    {
        // Return just the enum or a string class name as required by the typehint
        // Here, default to Alignment::End which matches "right alignment"
        return Alignment::End;
    }

    protected static string $resource = PatientResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $source = $data['source'] ?? 'website';

        // For app source, always set create_user_account to true
        if ($source === 'app') {
            $data['create_user_account'] = true;
        }

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($data) {
            $source = $data['source'] ?? 'website';
            $createUserAccount = $data['create_user_account'] ?? false;

            if (($source === 'app' || $createUserAccount)) {
                $email = $this->normalizeEmail($data['user_email'] ?? $data['email'] ?? null);
                $phone = $this->normalizePhone($data['user_phone'] ?? $data['mobile_no'] ?? null);

                if (! empty($email)) {
                    $user = $this->findUserByEmail($email);

                    if (! $user) {
                        $password = ! empty($data['user_password'])
                            ? Hash::make($data['user_password'])
                            : Hash::make('Patient@123');

                        $user = User::create([
                            'name' => ($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? ''),
                            'slug' => Str::slug(($data['first_name'] ?? '') . '-' . ($data['last_name'] ?? '') . '-' . Str::random(6)),
                            'email' => $email,
                            'phone' => $phone,
                            'password' => $password,
                            'email_verified_at' => now(),
                            'status' => \App\Enums\AuthStatus::registered->value,
                            'avatar' => $data['avatar'] ?? null,
                        ]);
                    } else {
                        if ($user->trashed()) {
                            $user->restore();
                        }

                        $linkedPatient = Patient::withTrashed()
                            ->where('user_id', $user->id)
                            ->first();

                        if ($linkedPatient) {
                            throw ValidationException::withMessages([
                                'data.user_email' => 'This user account is already linked to another patient. Select that patient or use another email.',
                            ]);
                        }

                        $user->forceFill([
                            'email_verified_at' => $user->email_verified_at ?: now(),
                            'status' => \App\Enums\AuthStatus::registered->value,
                        ])->save();
                    }

                    $this->ensurePatientRole($user);
                    $this->markRegistrationAsRegistered($email);
                    $data['user_id'] = $user->id;

                    $data['email'] = $email;
                    $data['mobile_no'] = $phone ?: ($data['mobile_no'] ?? null);
                }
            }

            // Remove temporary user fields from data
            unset($data['user_email'], $data['user_phone'], $data['user_password']);

            // Create the Patient record
            return $this->getModel()::create($data);
        });
    }

    protected function afterCreate(): void
    {
        // Logic moved to handleRecordCreation for transaction safety
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

    private function findUserByEmail(string $email): ?User
    {
        return User::withTrashed()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();
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
                'status' => \App\Enums\AuthStatus::registered->value,
            ]);

            return;
        }

        Registration::create([
            'email' => strtolower($email),
            'email_verified' => true,
            'status' => \App\Enums\AuthStatus::registered->value,
        ]);
    }
}
