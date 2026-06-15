<?php

namespace App\Filament\Resources\DietTemplates\Pages;

use App\Filament\Resources\DietTemplates\DietTemplateResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Alignment;

class CreateDietTemplate extends CreateRecord
{
    protected static string $resource = DietTemplateResource::class;

    public function getFormActionsAlignment(): Alignment|string
    {
        return Alignment::End;
    }
}
