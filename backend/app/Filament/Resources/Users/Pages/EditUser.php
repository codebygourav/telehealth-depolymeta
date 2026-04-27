<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Alignment;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    public function getFormActionsAlignment(): Alignment|string
    {
        // Return just the enum or a string class name as required by the typehint
        // Here, default to Alignment::End which matches "right alignment"
        return Alignment::End;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (!empty($data['roles'])) {
            $data['role'] = is_array($data['roles']) ? $data['roles'][0] : $data['roles'];
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $user = $this->record;
        if (!empty($user->roles)) {
            $user->syncRoles([$user->role]);
        }
    }


    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }
}
