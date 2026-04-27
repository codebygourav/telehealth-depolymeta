<?php

namespace App\Filament\Resources\Symptoms\Pages;

use App\Filament\Resources\Symptoms\SymptomResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Alignment;

class EditSymptom extends EditRecord
{

    public function getFormActionsAlignment(): Alignment|string
    {
        // Return just the enum or a string class name as required by the typehint
        // Here, default to Alignment::End which matches "right alignment"
        return Alignment::End;
    }
    protected static string $resource = SymptomResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
