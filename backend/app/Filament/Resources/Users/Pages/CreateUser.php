<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Services\PatientAuthAccountService;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Alignment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    public function getFormActionsAlignment(): Alignment|string
    {
        // Return just the enum or a string class name as required by the typehint
        // Here, default to Alignment::End which matches "right alignment"
        return Alignment::End;
    }
    protected function getFormValidationRules(): array
    {
        $rules = parent::getFormValidationRules();

        if ($this instanceof CreateRecord) {
            $rules['password'][] = 'required';
        }

        return $rules;
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (!empty($data['roles'])) {
            // Save the selected role ID into 'role' column
            $data['role'] = is_array($data['roles']) ? $data['roles'][0] : $data['roles'];
        }

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        unset($data['email_verified_at']); // optional

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            $user = $this->getModel()::create($data);

            if (! empty($user->role)) {
                $user->syncRoles([$user->role]);
            }

            if ($user->hasRole('patient')) {
                app(PatientAuthAccountService::class)->ensurePatientProfileForUser($user);
            }

            return $user;
        });
    }
}
