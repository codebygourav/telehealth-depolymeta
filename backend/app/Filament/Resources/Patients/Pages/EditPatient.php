<?php

namespace App\Filament\Resources\Patients\Pages;

use App\Filament\Resources\Patients\PatientResource;
use App\Models\User;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Str;
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

        // Before creating or assigning a User, ensure the email is not already used by another User record.
        if (($source === 'app' || $createUserAccount) && empty($record->user_id)) {
            $email = $data['user_email'] ?? $data['email'] ?? null;
            $phone = $data['user_phone'] ?? $data['mobile_no'] ?? null;
            if (! empty($email)) {
                // Remove users with duplicate email except the current user's associated patient, when updating.
                $existingUserQuery = User::query()->where('email', $email);
                if (! empty($record->user_id)) {
                    $existingUserQuery->where('id', '<>', $record->user_id);
                }
                $existingUser = $existingUserQuery->first();

                if (! $existingUser) {
                    $password = ! empty($data['user_password'])
                        ? bcrypt($data['user_password'])
                        : bcrypt('Patient@123');

                    $user = User::create([
                        'name' => ($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? ''),
                        'slug' => Str::slug(($data['first_name'] ?? '') . '-' . ($data['last_name'] ?? '') . '-' . Str::random(6)),
                        'email' => $email,
                        'phone' => $phone,
                        'password' => $password,
                        'avatar' => $data['avatar'] ?? null,
                        'status' => \App\Enums\AuthStatus::registered->value,
                    ]);

                    // Assign the 'patient' role
                    if (method_exists($user, 'assignRole')) {
                        $user->assignRole('patient');
                    } elseif (class_exists(Role::class)) {
                        $roleClass = Role::class;
                        $patientRole = $roleClass::where('name', 'patient')->first();
                        if ($patientRole) {
                            $user->roles()->attach($patientRole);
                        }
                    }

                    $data['user_id'] = $user->id;

                    if (empty($data['email'])) {
                        $data['email'] = $email;
                    }
                    if (empty($data['mobile_no'])) {
                        $data['mobile_no'] = $phone;
                    }
                } else {
                    // If an existing user found, assign the patient to this user.
                    $data['user_id'] = $existingUser->id;
                }
            }
        } else {
            if ($record->user_id && ! $createUserAccount) {
                $data['user_id'] = null;
            }
        }

        unset($data['user_email'], $data['user_phone'], $data['user_password']);

        return $data;
    }

    protected function afterSave(): void
    {
        $record = $this->record;
        $source = $record->source ?? 'website';

        if (($source === 'app' || $record->create_user_account) && $record->user_id && $record->user) {
            $user = $record->user;
            $updateData = [
                'name' => ($record->first_name ?? '') . ' ' . ($record->last_name ?? ''),
                'email' => $record->email ?? $user->email,
                'phone' => $record->mobile_no ?? $user->phone,
            ];

            // Prevent updating email to another user's existing email
            $existingUser = User::where('email', $updateData['email'])
                ->where('id', '<>', $user->id)
                ->first();
            if ($existingUser) {
                unset($updateData['email']);
            }

            if (! empty($this->userPassword)) {
                $updateData['password'] = bcrypt($this->userPassword);
            }
            $user->update($updateData);

            if (method_exists($user, 'assignRole')) {
                if (! $user->hasRole('patient')) {
                    $user->assignRole('patient');
                }
            } elseif (class_exists(Role::class)) {
                $roleClass = Role::class;
                $patientRole = $roleClass::where('name', 'patient')->first();
                if ($patientRole && ! $user->roles->contains('id', $patientRole->id)) {
                    $user->roles()->attach($patientRole);
                }
            }
        } elseif ($source === 'website' && ! $record->create_user_account && $record->user_id) {
            $record->update(['user_id' => null]);
        }
    }
}
