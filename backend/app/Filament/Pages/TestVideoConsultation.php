<?php

namespace App\Filament\Pages;

use App\Models\Appointment;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Log;

use App\Traits\HasCustomSidebar;

class TestVideoConsultation extends Page implements HasForms
{
    use InteractsWithForms;
    use HasCustomSidebar;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedVideoCamera;
    protected string $view = 'filament.pages.test-video-consultation';
    protected static ?string $slug = 'test-video-consultation';
    protected static ?string $navigationLabel = 'Test Video Consultation';
    protected static ?string $title = 'Test Video Consultation';
    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool
    {
        $module = static::$slug ?? strtolower(class_basename(static::class));
        return check_permission(["{$module}.view", "{$module}.view_any", "{$module}.manage_own"]);
    }

    public static function getSidebarOptions(): array
    {
        return [
            'label' => 'Test Video Consultation',
            'icon'  => 'heroicon-o-video-camera',
            'sort'  => 1000,
            'group' => 'Test Booking Functionality',
        ];
    }

    public array $data = [];

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Select::make('appointment_id')
                    ->label('Select Appointment')
                    ->options(function () {
                        return Appointment::where('consultation_type', 'video')
                            ->with(['patient', 'doctor'])
                            ->orderBy('appointment_date', 'desc')
                            ->get()
                            ->mapWithKeys(function ($appointment) {
                                $patientName = $appointment->patient
                                    ? $appointment->patient->first_name . ' ' . $appointment->patient->last_name
                                    : 'N/A';
                                $doctorName = $appointment->doctor
                                    ? $appointment->doctor->first_name . ' ' . $appointment->doctor->last_name
                                    : 'N/A';
                                $date = $appointment->appointment_date
                                    ? \Carbon\Carbon::parse($appointment->appointment_date)->format('M d, Y')
                                    : 'N/A';

                                return [$appointment->id => "{$patientName} with {$doctorName} - {$date}"];
                            });
                            
                    })
                    ->searchable()
                    ->required()
                    ->placeholder('Select an appointment')
                    ->helperText('Select a video consultation appointment to test API endpoints')
                    ->live(),
            ])
            ->statePath('data');
    }

    public function mount(): void
    {
        $this->form->fill();
    }
}