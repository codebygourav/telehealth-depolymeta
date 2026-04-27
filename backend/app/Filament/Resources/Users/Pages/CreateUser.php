<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Alignment;

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

        unset($data['email_verified_at']); // optional

        return $data;
    }

    protected function afterCreate(): void
    {
        $user = $this->record;
        if (!empty($user->roles)) {
            // Sync roles using Spatie
            $user->syncRoles([$user->role]);
        }
    }
}
