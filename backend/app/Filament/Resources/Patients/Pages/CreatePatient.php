<?php

namespace App\Filament\Resources\Patients\Pages;

use App\Filament\Resources\Patients\PatientResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Alignment;
use Illuminate\Support\Str;
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

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($data) {
            $source = $data['source'] ?? 'website';
            $createUserAccount = $data['create_user_account'] ?? false;

            if (($source === 'app' || $createUserAccount)) {
                $email = $data['user_email'] ?? $data['email'] ?? null;
                $phone = $data['user_phone'] ?? $data['mobile_no'] ?? null;

                if (! empty($email)) {
                    // Check if user already exists (e.g. if we are retrying after a soft-failure)
                    $user = \App\Models\User::where('email', $email)->first();

                    if (! $user) {
                        $password = ! empty($data['user_password'])
                            ? \Illuminate\Support\Facades\Hash::make($data['user_password'])
                            : \Illuminate\Support\Facades\Hash::make('Patient@123');

                        $user = \App\Models\User::create([
                            'name' => ($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? ''),
                            'slug' => Str::slug(($data['first_name'] ?? '') . '-' . ($data['last_name'] ?? '') . '-' . Str::random(6)),
                            'email' => $email,
                            'phone' => $phone,
                            'password' => $password,
                            'email_verified_at' => now(),
                            'status' => \App\Enums\AuthStatus::registered->value,
                            'avatar' => $data['avatar'] ?? null,
                        ]);
                        if (method_exists($user, 'assignRole')) {
                            $user->assignRole('patient');
                        } elseif (class_exists(Role::class)) {
                            $roleClass = Role::class;
                            $doctorRole = $roleClass::where('name', 'patient')->first();
                            if ($doctorRole) {
                                $user->roles()->attach($doctorRole);
                            }
                        }
                    }

                    $data['user_id'] = $user->id;

                    // Sync user email/phone to patient table only if patient fields are empty
                    if (empty($data['email'])) {
                        $data['email'] = $email;
                    }
                    if (empty($data['mobile_no'])) {
                        $data['mobile_no'] = $phone;
                    }
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
}
