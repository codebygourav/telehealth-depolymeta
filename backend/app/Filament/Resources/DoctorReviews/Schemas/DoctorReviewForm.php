<?php

namespace App\Filament\Resources\DoctorReviews\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\FakerPatient;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\RichEditor;

class DoctorReviewForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Review Type Selection
                Section::make('Review Type')
                    ->icon('heroicon-o-identification')
                    ->description('Select the type of review you want to create')
                    ->schema([
                        Select::make('review_type')
                            ->label('Review Type')
                            ->options(function (string $operation, $record) {
                                // For create operation, only allow fake reviews
                                if ($operation === 'create') {
                                    return [
                                        'fake' => 'Fake Review',
                                    ];
                                }
                                
                                // For edit/view, show what it is
                                return [
                                    'original' => 'Review by Original User',
                                    'fake' => 'Fake Review',
                                ];
                            })
                            ->default('fake')
                            ->disabled(function (string $operation, $record) {
                                // On edit, disable if it's an original review
                                return $operation === 'edit' && $record && $record->review_type === 'original';
                            })
                            ->dehydrated() // Ensure the value is still sent if disabled
                            ->required()
                            ->reactive()
                            ->live()
                            ->afterStateUpdated(function ($state, $set) {
                                if ($state === 'original') {
                                    $set('faker_name', null);
                                    $set('faker_age', null);
                                    $set('faker_address', null);
                                    $set('faker_avatar', null);
                                    $set('faker_patient_id', null);
                                } else {
                                    $set('patient_id', null);
                                }
                            })
                            ->helperText('Choose "Fake Review" to create a review with custom patient information (Original reviews can only be created by patients)')
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),

                // Doctor Selection - Always visible at top
                Section::make('Doctor Information')
                    ->icon('heroicon-o-user')
                    ->description('Select the doctor being reviewed')
                    ->schema([
                        Select::make('doctor_id')
                            ->label('Doctor')
                            ->relationship('doctor', 'first_name', fn($query) => $query->with('user'))
                            ->getOptionLabelFromRecordUsing(fn($record) => $record->first_name . ' ' . $record->last_name)
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText('Search and select the doctor who received this review')
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),

                // Original Patient Section
                Section::make('Patient Information')
                    ->icon('heroicon-o-user-circle')
                    ->description('Select the patient who wrote this review')
                    ->visible(fn($get) => $get('review_type') === 'original')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('patient_id')
                                    ->label('Patient')
                                    ->relationship('patient', 'first_name', fn($query) => $query->with('user'))
                                    ->getOptionLabelFromRecordUsing(fn($record) => $record->first_name . ' ' . $record->last_name)
                                    ->searchable()
                                    ->preload()
                                    ->reactive()
                                    ->live()
                                    ->required()
                                    ->helperText('Search and select the patient who wrote this review')
                                    ->columnSpan(1),

                                Placeholder::make('patient_avatar_preview')
                                    ->label('Patient Avatar')
                                    ->reactive()
                                    ->live()
                                    ->content(function ($get, $record) {
                                        $patientId = $get('patient_id') ?? $record?->patient_id;

                                        if (!$patientId) {
                                            return new HtmlString(
                                                '<div class="flex items-center justify-center py-3 bg-gray-50 dark:bg-gray-800 rounded-lg border border-dashed border-gray-300 dark:border-gray-600">' .
                                                    '<div class="text-center">' .
                                                    '<svg class="mx-auto h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">' .
                                                    '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />' .
                                                    '</svg>' .
                                                    '<p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Select a patient</p>' .
                                                    '</div>' .
                                                    '</div>'
                                            );
                                        }

                                        $patient = Patient::with('user')->find($patientId);

                                        if (!$patient) {
                                            return new HtmlString(
                                                '<div class="flex items-center justify-center py-3 bg-red-50 dark:bg-red-900/20 rounded-lg border border-dashed border-red-300 dark:border-red-600">' .
                                                    '<p class="text-xs text-red-600 dark:text-red-400">Patient not found</p>' .
                                                    '</div>'
                                            );
                                        }

                                        $avatarUrl = storage_url($patient->avatar) ?? asset('images/default-avatar.png');

                                        return new HtmlString(
                                            '<div class="flex items-center gap-3">' .
                                                '<img src="' . htmlspecialchars($avatarUrl) . '" alt="Patient Avatar" class="w-14 h-14 rounded-xl object-cover border-2 border-white dark:border-gray-600 shadow-sm">' .
                                                '<div class="flex-1 min-w-0">' .
                                                '<p class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate">' . htmlspecialchars($patient->first_name . ' ' . $patient->last_name) . '</p>' .
                                                '<p class="text-xs text-gray-600 dark:text-gray-400 mt-0.5">Patient Profile</p>' .
                                                '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-primary-100 text-primary-800 dark:bg-primary-900 dark:text-primary-200 mt-1">Original User</span>' .
                                                '</div>' .
                                                '</div>'
                                        );
                                    })
                                    ->columnSpan(1),
                            ]),
                    ])
                    ->columnSpanFull(),

                // Appointment Information Section
                Section::make('Appointment Information')
                    ->icon('heroicon-o-calendar-days')
                    ->description('Details of the appointment associated with this review')
                    ->visible(fn($get) => $get('review_type') === 'original')
                    ->schema([
                        Placeholder::make('appointment_details')
                            ->label('Linked Appointment')
                            ->content(function ($get, $record) {
                                $appointmentId = $get('appointment_id') ?? $record?->appointment_id;
                                if (!$appointmentId) return 'No appointment linked';
                                
                                $appointment = \App\Models\Appointment::with(['doctor', 'patient'])->find($appointmentId);
                                if (!$appointment) return 'Appointment not found';
                                
                                $date = $appointment->appointment_date instanceof \Carbon\Carbon ? $appointment->appointment_date->format('D, M d, Y') : (\Illuminate\Support\Carbon::parse($appointment->appointment_date)->format('D, M d, Y'));
                                $time = $appointment->appointment_time ?? 'N/A';
                                $status = $appointment->status instanceof \App\Enums\AppointmentStatus ? $appointment->status->label() : ($appointment->status->value ?? (string) $appointment->status);
                                $type = $appointment->consultation_type ?? 'N/A';
                                $doctorName = $appointment->doctor ? "{$appointment->doctor->first_name} {$appointment->doctor->last_name}" : 'N/A';
                                
                                return new HtmlString("
                                    <div class='p-4 bg-gray-50 dark:bg-gray-800/50 rounded-xl border border-gray-200 dark:border-gray-700'>
                                        <div class='grid grid-cols-2 gap-4'>
                                            <div>
                                                <p class='text-xs text-gray-500 uppercase font-semibold'>Date and Time</p>
                                                <p class='text-sm font-medium text-gray-900 dark:text-gray-100'>$date at $time</p>
                                            </div>
                                            <div>
                                                <p class='text-xs text-gray-500 uppercase font-semibold'>Status</p>
                                                <span class='inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300'>$status</span>
                                            </div>
                                            <div>
                                                <p class='text-xs text-gray-500 uppercase font-semibold'>Consultation Type</p>
                                                <p class='text-sm font-medium text-gray-900 dark:text-gray-100 capitalize'>$type</p>
                                            </div>
                                            <div>
                                                <p class='text-xs text-gray-500 uppercase font-semibold'>Doctor</p>
                                                <p class='text-sm font-medium text-gray-900 dark:text-gray-100'>$doctorName</p>
                                            </div>
                                        </div>
                                    </div>
                                ");
                            })
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),

                // Fake Patient Section
                Section::make('Fake Patient Information')
                    ->icon('heroicon-o-user-plus')
                    ->description('Enter details for the fake patient')
                    ->visible(fn($get) => $get('review_type') === 'fake')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('faker_name')
                                            ->label('Full Name')
                                            ->required()
                                            ->maxLength(255)
                                            ->placeholder('Enter patient name')
                                            ->helperText('Enter the full name of the fake patient')
                                            ->columnSpan(1),

                                        TextInput::make('faker_age')
                                            ->label('Age')
                                            ->required()
                                            ->numeric()
                                            ->minValue(1)
                                            ->maxValue(150)
                                            ->placeholder('Age')
                                            ->helperText('Patient age in years')
                                            ->columnSpan(1),

                                        Textarea::make('faker_address')
                                            ->label('Address')
                                            ->required()
                                            ->rows(3)
                                            ->placeholder('Enter complete address')
                                            ->helperText('Enter the address of the fake patient')
                                            ->columnSpanFull(),
                                    ])
                                    ->columnSpan(1),

                                Grid::make(1)
                                    ->schema([
                                        FileUpload::make('faker_avatar')
                                            ->label('Profile Photo')
                                            ->disk('public')
                                            ->directory('user_avatar')
                                            ->image()
                                            ->avatar()
                                            ->imageEditor()
                                            ->imageEditorAspectRatios(['1:1'])
                                            ->circleCropper()
                                            ->imagePreviewHeight('200')
                                            ->maxSize(2048)
                                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                            ->helperText('Upload a profile photo for the fake patient (optional)')
                                            ->columnSpanFull(),

                                    ])->columnSpan(1),
                            ]),
                    ])
                    ->columnSpanFull(),

                // Review Content Section
                Section::make('Review Content')
                    ->icon('heroicon-o-document-text')
                    ->description('Enter the review details')
                    ->schema([
                        TextInput::make('title')
                            ->label('Review Title')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., Excellent doctor, highly recommended')
                            ->helperText('Enter a concise and descriptive title for the review (max 255 characters)')
                            ->columnSpanFull(),

                        Textarea::make('content')
                            ->label('Review Content')
                            ->required()
                            ->placeholder('Share your experience and feedback about the doctor...')
                            ->helperText('Describe your experience in detail. You can format the text using the editor toolbar.')
                            ->columnSpanFull(),

                        Grid::make(2)
                            ->schema([
                                \Filament\Forms\Components\ViewField::make('rating')
                                    ->label('Rating')
                                    ->view('filament.forms.components.star-rating'),
                            ]),
                    ])
                    ->columnSpanFull(),

                // Settings Section
                Section::make('Review Settings')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->description('Configure review visibility and display options')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Toggle::make('is_active')
                                    ->label('Active Status')
                                    ->helperText('When enabled, this review will be visible to the public')
                                    ->onColor('success')
                                    ->offColor('danger')
                                    ->default(true)
                                    ->required()
                                    ->inline(false),

                                Toggle::make('is_featured')
                                    ->label('Featured Review')
                                    ->helperText('When enabled, this review will be highlighted on the homepage')
                                    ->onColor('success')
                                    ->offColor('gray')
                                    ->default(false)
                                    ->inline(false),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}