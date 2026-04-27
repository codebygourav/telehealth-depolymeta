<?php

namespace App\Filament\Resources\DoctorReplacements\Pages;

use App\Filament\Resources\DoctorReplacements\DoctorReplacementResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions\Action;

class ViewDoctorReplacement extends ViewRecord
{
    protected static string $resource = DoctorReplacementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('revert')
                ->label('Revert Replacement')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Revert Replacement')
                ->modalDescription('Are you sure you want to revert this replacement? All affected appointments will be restored to the original doctor.')
                ->action(function () {
                    \Illuminate\Support\Facades\DB::transaction(function () {
                        // Get all appointments that were replaced by this replacement
                        // Find appointments where doctor_id matches replacement_doctor_id and falls within date range
                        $query = \App\Models\Appointment::where('doctor_id', $this->record->replacement_doctor_id)
                            ->whereNotIn('status', ['cancelled', 'completed']);

                        if ($this->record->start_date) {
                            $query->whereDate('appointment_date', '>=', $this->record->start_date);
                        }
                        if ($this->record->end_date) {
                            $query->whereDate('appointment_date', '<=', $this->record->end_date);
                        }

                        $appointments = $query->get();

                        // Revert appointments back to original doctor
                        foreach ($appointments as $appointment) {
                            // Check if this appointment should be reverted (within replacement period)
                            $shouldRevert = true;
                            if ($this->record->start_date && $appointment->appointment_date < $this->record->start_date) {
                                $shouldRevert = false;
                            }
                            if ($this->record->end_date && $appointment->appointment_date > $this->record->end_date) {
                                $shouldRevert = false;
                            }

                            if ($shouldRevert) {
                                $appointment->update([
                                    'doctor_id' => $this->record->original_doctor_id,
                                ]);
                            }
                        }

                        // Deactivate replacement
                        $this->record->update(['is_active' => false]);
                    });

                    \Filament\Notifications\Notification::make()
                        ->title('Replacement Reverted')
                        ->success()
                        ->send();
                    $this->redirect(static::getResource()::getUrl('index'));
                })
                ->visible(fn() => $this->record->is_active),
        ];
    }
}
