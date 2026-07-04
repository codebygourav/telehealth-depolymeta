<?php

namespace App\Filament\Resources\DoctorAdvertisements\Pages;

use App\Filament\Resources\DoctorAdvertisements\DoctorAdvertisementResource;
use App\Models\DisplayEvent;
use Filament\Actions\CreateAction;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListDoctorAdvertisements extends ListRecords
{
    protected static string $resource = DoctorAdvertisementResource::class;

    protected ?string $heading = 'Display Content Manager';

    protected string $view = 'filament.resources.doctor-advertisements.pages.list-doctor-advertisements';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('open_display')
                ->label('Open OPD Token')
                ->icon('heroicon-o-tv')
                ->url(route('opd-token.display'))
                ->openUrlInNewTab(),
            CreateAction::make()
                ->label('Add Display Content')
                ->slideOver()
                ->modalWidth('5xl'),
        ];
    }

    public function getPreviewRecord(): ?DisplayEvent
    {
        return DisplayEvent::query()
            ->with(['doctors.user', 'creator', 'updater'])
            ->orderByDesc('is_active')
            ->orderBy('display_order')
            ->orderByDesc('updated_at')
            ->first();
    }

    public function getPreviewStats(): array
    {
        $record = $this->getPreviewRecord();

        if (! $record) {
            return [
                'title' => 'No content yet',
                'description' => 'Create a display item to see a preview here.',
                'category' => 'Display Content',
                'schedule' => 'No active schedule',
                'media_type' => 'Not set',
                'doctors' => 'All doctors',
            ];
        }

        return [
            'title' => $record->title,
            'description' => strip_tags((string) $record->description ?: 'Preview of the selected display content.'),
            'category' => $record->category_label,
            'schedule' => $this->formatSchedule($record),
            'media_type' => ucfirst((string) ($record->media_type ?: 'image')),
            'doctors' => $record->doctors->isNotEmpty()
                ? $record->doctors->map(fn($doctor) => trim('Dr. ' . trim(($doctor->first_name ?? '') . ' ' . ($doctor->last_name ?? ''))))->implode(', ')
                : 'All doctors',
        ];
    }

    protected function formatSchedule(DisplayEvent $record): string
    {
        if ($record->starts_at && $record->ends_at) {
            return $record->starts_at->format('M d, h:i A') . ' to ' . $record->ends_at->format('M d, h:i A');
        }

        if ($record->starts_at) {
            return 'Starts ' . $record->starts_at->format('M d, h:i A');
        }

        if ($record->ends_at) {
            return 'Ends ' . $record->ends_at->format('M d, h:i A');
        }

        return 'Always visible when active';
    }
}
