<?php

namespace App\Filament\Resources\MedicalReports\Pages;

use App\Filament\Resources\MedicalReports\MedicalReportResource;
use Filament\Resources\Pages\ViewRecord;

class ViewMedicalReport extends ViewRecord
{
    protected static string $resource = MedicalReportResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function deleteReportAction(): \Filament\Actions\Action
    {
        return \Filament\Actions\Action::make('deleteReport')
            ->requiresConfirmation()
            ->modalHeading('Delete Medical Report')
            ->modalDescription('Are you sure you want to delete this medical report? This will also soft delete any linked module documents.')
            ->modalSubmitActionLabel('Yes, delete it')
            ->color('danger')
            ->action(function (array $arguments) {
                $reportId = $arguments['id'] ?? null;
                $report = \App\Models\MedicalReport::find($reportId);
                
                if (!$report) {
                    \Filament\Notifications\Notification::make()
                        ->title('Report not found')
                        ->danger()
                        ->send();
                    return;
                }

                // Clean up module documents as soft delete
                foreach ($report->moduleDocuments as $doc) {
                    $doc->delete(); // Soft delete
                }

                $report->delete(); // Soft delete

                \Filament\Notifications\Notification::make()
                    ->title('Medical report removed (soft deleted)')
                    ->success()
                    ->send();

                if ($this->record->id == $reportId) {
                    return redirect($this->getResource()::getUrl('index'));
                }

                $this->refreshFormData(['report_details']);
            });
    }

    public function deleteReport($reportId)
    {
        $this->mountAction('deleteReport', ['id' => $reportId]);
    }
}
