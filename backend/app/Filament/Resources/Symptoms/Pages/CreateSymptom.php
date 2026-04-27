<?php

namespace App\Filament\Resources\Symptoms\Pages;

use App\Filament\Resources\Symptoms\SymptomResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Alignment;

class CreateSymptom extends CreateRecord
{
    public function getFormActionsAlignment(): Alignment|string
    {
        // Return just the enum or a string class name as required by the typehint
        // Here, default to Alignment::End which matches "right alignment"
        return Alignment::End;
    }
    protected static string $resource = SymptomResource::class;
}
