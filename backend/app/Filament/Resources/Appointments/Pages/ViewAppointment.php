<?php

namespace App\Filament\Resources\Appointments\Pages;

use App\Filament\Resources\Appointments\AppointmentResource;
use App\Models\VideoConsultation;
use App\Services\WherebyService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Schema;

class ViewAppointment extends ViewRecord
{
    protected static string $resource = AppointmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generate_video_link')
                ->label('Generate Video Link')
                ->icon('heroicon-o-link')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Generate video link')
                ->modalDescription('Create a Whereby room with host and participant URLs for this appointment.')
                ->visible(fn (): bool => $this->record->consultation_type === 'video' && ! $this->hasCompleteVideoLinks())
                ->action(function (): void {
                    $this->generateVideoLink();
                }),

            Action::make('view_video_links')
                ->label('View Video Links')
                ->icon('heroicon-o-video-camera')
                ->color('success')
                ->visible(fn (): bool => $this->record->consultation_type === 'video' && $this->hasCompleteVideoLinks())
                ->modalHeading('Video consultation links')
                ->modalContent(fn () => view('filament.pages.video-consultation-urls', [
                    'videoConsultation' => $this->record->videoConsultation,
                ]))
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Close'),

            Action::make('back')
                ->label('Back')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(AppointmentResource::getUrl('index')),
            DeleteAction::make()
                ->requiresConfirmation(),
        ];
    }

    protected function hasCompleteVideoLinks(): bool
    {
        $this->record->loadMissing('videoConsultation');
        $videoConsultation = $this->record->videoConsultation;

        return $videoConsultation
            && filled($videoConsultation->host_url)
            && (filled($videoConsultation->participate_url) || filled($videoConsultation->room_url));
    }

    protected function generateVideoLink(): ?VideoConsultation
    {
        $wherebyService = app(WherebyService::class);

        if (! $wherebyService->isConfigured()) {
            Notification::make()
                ->title('Whereby API not configured')
                ->body('Add WHEREBY_API_KEY in Settings > Third Party API, then try again.')
                ->danger()
                ->send();

            return null;
        }

        $this->record->load('videoConsultation');

        $videoConsultation = $this->record->videoConsultation
            ? $wherebyService->regenerateUrls($this->record->videoConsultation)
            : $wherebyService->createVideoConsultation($this->record);

        if (! $videoConsultation) {
            Notification::make()
                ->title('Failed to generate video link')
                ->danger()
                ->send();

            return null;
        }

        if (Schema::hasColumn('appointments', 'whereby_room_url')) {
            $this->record->update([
                'whereby_room_url' => $videoConsultation->room_url,
                'whereby_room_id' => $videoConsultation->room_id,
            ]);
        }

        Notification::make()
            ->title('Video link generated')
            ->success()
            ->send();

        $this->record->refresh();

        return $videoConsultation;
    }
}
