<?php

namespace App\Filament\Resources\Patients\Pages;

use App\Filament\Resources\Patients\PatientResource;
use App\Models\Patient;
use App\Models\Registration;
use App\Models\User;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class EditPatient extends EditRecord
{
    protected static string $resource = PatientResource::class;

    protected ?string $userPassword = null;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                ViewAction::make()
                    ->icon('heroicon-o-eye'),
                DeleteAction::make()
                    ->icon('heroicon-o-trash')
                    ->requiresConfirmation(),
            ])
                ->icon('heroicon-o-ellipsis-vertical')
                ->label('Actions')
                ->color('gray')
                ->button(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->record;

        if ($record->create_user_account && $record->user_id && $record->user) {
            $data['user_email'] = $record->user->email ?? $record->email;
            $data['user_phone'] = $record->user->phone ?? $record->mobile_no;
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $record = $this->record;
        $source = $data['source'] ?? $record->source ?? 'website';
        $createUserAccount = $data['create_user_account'] ?? false;

        if ($source === 'app') {
            $data['create_user_account'] = true;
            $createUserAccount = true;
        }

        if (! empty($data['user_password'])) {
            $this->userPassword = $data['user_password'];
        }

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return DB::transaction(function () use ($record, $data) {
            $source = $data['source'] ?? $record->source ?? 'website';
            $createUserAccount = $source === 'app' || (bool) ($data['create_user_account'] ?? false);

            if ($source === 'app') {
                $data['create_user_account'] = true;
            }

            if ($createUserAccount) {
                $user = $this->resolveLoginUserForPatient($record, $data);
                $phone = $this->normalizePhone($data['user_phone'] ?? $data['mobile_no'] ?? null);

                if ($user->trashed()) {
                    $user->restore();
                }

                $userData = [
                    'name' => trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? '')),
                    'phone' => $phone ?: ($data['mobile_no'] ?? $user->phone),
                    'email_verified_at' => $user->email_verified_at ?: now(),
                    'status' => \App\Enums\AuthStatus::registered->value,
                ];

                if (! empty($this->userPassword)) {
                    $userData['password'] = Hash::make($this->userPassword);
                }

                $user->update($userData);
                $this->ensurePatientRole($user);
                $this->markRegistrationAsRegistered($user->email);

                $data['user_id'] = $user->id;
                $data['email'] = $user->email;
                $data['mobile_no'] = $phone ?: ($data['mobile_no'] ?? null);
            } else {
                $data['user_id'] = null;
            }

            unset($data['user_email'], $data['user_phone'], $data['user_password']);

            $record->update($data);

            return $record;
        });
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

    private function resolveLoginUserForPatient(Model $record, array $data): User
    {
        if ($record->user_id) {
            $user = User::withTrashed()->find($record->user_id);

            if (! $user) {
                throw ValidationException::withMessages([
                    'data.user_email' => 'The linked user account was not found. Remove login access or use another email.',
                ]);
            }

            return $user;
        }

        $email = $this->normalizeEmail($data['user_email'] ?? $data['email'] ?? null);
        $phone = $this->normalizePhone($data['user_phone'] ?? $data['mobile_no'] ?? null);

        if (! $email) {
            throw ValidationException::withMessages([
                'data.user_email' => 'Email is required to create a login account.',
            ]);
        }

        $user = $this->findUserByEmail($email);

        if (! $user) {
            return User::create([
                'name' => trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? '')),
                'slug' => Str::slug(($data['first_name'] ?? '') . '-' . ($data['last_name'] ?? '') . '-' . Str::random(6)),
                'email' => $email,
                'phone' => $phone,
                'password' => ! empty($this->userPassword)
                    ? Hash::make($this->userPassword)
                    : Hash::make('Patient@123'),
                'email_verified_at' => now(),
                'status' => \App\Enums\AuthStatus::registered->value,
            ]);
        }

        $linkedPatient = Patient::withTrashed()
            ->where('user_id', $user->id)
            ->whereKeyNot($record->getKey())
            ->first();

        if ($linkedPatient) {
            throw ValidationException::withMessages([
                'data.user_email' => 'This user account is already linked to another patient. Select that patient or use another email.',
            ]);
        }

        return $user;
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
