<?php

namespace App\Filament\Resources\Doctors\Pages;

use App\Filament\Resources\Doctors\DoctorResource;
use App\Filament\Resources\Doctors\Schemas\DoctorAiTrainingForm;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Schema;

class ManageDoctorAiTraining extends EditRecord
{
    protected static string $resource = DoctorResource::class;

    protected static ?string $title = 'AI Speech Training';

    public function getHeading(): string
    {
        return 'AI Speech Training';
    }

    public function getSubheading(): ?string
    {
        return 'Configure pronunciation rules, shortcuts, diagnoses, instructions, and procedures for this doctor.';
    }

    public function form(Schema $schema): Schema
    {
        return DoctorAiTrainingForm::configure($schema);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $profile = $data['ai_training_profile'] ?? [];

        $profile['common_diagnoses'] = collect($profile['common_diagnoses'] ?? [])
            ->map(fn($item) => is_array($item) ? $item : ['value' => (string) $item])
            ->values()
            ->all();

        $profile['frequently_used_instructions'] = collect($profile['frequently_used_instructions'] ?? [])
            ->map(fn($item) => is_array($item) ? $item : ['value' => (string) $item])
            ->values()
            ->all();

        $profile['procedures_investigations'] = collect($profile['procedures_investigations'] ?? [])
            ->map(fn($item) => is_array($item) ? $item : ['value' => (string) $item])
            ->values()
            ->all();

        $data['ai_training_profile'] = $profile;

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $profile = $data['ai_training_profile'] ?? [];

        $profile['common_diagnoses'] = collect($profile['common_diagnoses'] ?? [])
            ->map(fn($item) => trim((string) (is_array($item) ? ($item['value'] ?? '') : $item)))
            ->filter()
            ->values()
            ->all();

        $profile['frequently_used_instructions'] = collect($profile['frequently_used_instructions'] ?? [])
            ->map(fn($item) => trim((string) (is_array($item) ? ($item['value'] ?? '') : $item)))
            ->filter()
            ->values()
            ->all();

        $profile['procedures_investigations'] = collect($profile['procedures_investigations'] ?? [])
            ->map(fn($item) => trim((string) (is_array($item) ? ($item['value'] ?? '') : $item)))
            ->filter()
            ->values()
            ->all();

        $profile['pronunciation_dictionary'] = collect($profile['pronunciation_dictionary'] ?? [])
            ->map(function ($item) {
                return [
                    'doctor_says' => trim((string) ($item['doctor_says'] ?? '')),
                    'ai_converts_to' => trim((string) ($item['ai_converts_to'] ?? '')),
                ];
            })
            ->filter(fn($item) => $item['doctor_says'] !== '' && $item['ai_converts_to'] !== '')
            ->values()
            ->all();

        $profile['medicine_shortcuts'] = collect($profile['medicine_shortcuts'] ?? [])
            ->map(function ($item) {
                return [
                    'medicine' => trim((string) ($item['medicine'] ?? '')),
                    'shortcut' => trim((string) ($item['shortcut'] ?? '')),
                    'priority' => max(1, min(5, (int) ($item['priority'] ?? 3))),
                ];
            })
            ->filter(fn($item) => $item['medicine'] !== '' && $item['shortcut'] !== '')
            ->values()
            ->all();

        $data['ai_training_profile'] = $profile;

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('backToDoctor')
                ->label('Back to Doctor Profile')
                ->icon('heroicon-o-arrow-left')
                ->url(fn() => DoctorResource::getUrl('edit', ['record' => $this->record])),
        ];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Doctor AI training profile saved successfully.';
    }
}
