<?php

namespace App\Filament\Resources\DietTemplates\Pages;

use App\Filament\Resources\DietTemplates\DietTemplateResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Alignment;

class CreateDietTemplate extends CreateRecord
{
    protected static string $resource = DietTemplateResource::class;

    protected array $dietChartDays = [];

    public function getFormActionsAlignment(): Alignment|string
    {
        return Alignment::End;
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->dietChartDays = DietTemplateResource::decodeDietChartPayload($data['diet_chart_payload'] ?? $this->data['diet_chart_payload'] ?? null);
        unset($data['days'], $data['diet_chart_payload']);

        return $data;
    }

    protected function afterCreate(): void
    {
        DietTemplateResource::syncDietChart($this->record, $this->dietChartDays);
    }
}
