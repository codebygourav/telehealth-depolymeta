<?php

namespace App\Filament\Resources\Doctors\Schemas;

use App\Enums\{BloodGroupOption, DayOfWeek, DepartmentRole, GenderOption, LanguageOption, MaritalStatus};
use App\Models\{Department, DepartmentDoctor, Doctor};
use App\Services\DoctorAvailabilityValidationService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\{Checkbox, DatePicker, FileUpload, Hidden, Placeholder, Repeater, RichEditor, Select, Textarea, TextInput, TimePicker, Toggle};
use Filament\Notifications\Notification;
use Filament\Schemas\Components\{Grid, Section, Tabs, Tabs\Tab};
use Filament\Schemas\Schema;

class DoctorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Profile Photo & Credentials')
                ->description('Upload profile photo and set basic credentials.')
                ->schema([
                    FileUpload::make('avatar')
                        ->label('Profile Photo')
                        ->disk('public')
                        ->directory('user_avatar')
                        ->visibility('public')
                        ->image()
                        ->avatar()
                        ->openable()
                        ->maxSize(2048)
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                        ->columns(3), // Remove imageEditor to stop forcing crop on upload


                    FileUpload::make('signature')
                        ->label('Signature')
                        ->disk('public')
                        ->directory('doctorSignatures')
                        ->image()
                        ->avatar()
                        ->imageEditor()
                        ->imageEditorAspectRatios([
                            '1:1',
                        ])
                        ->maxSize(2048) // 2MB max file size
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                        // Note: optimize() and imageResize methods don't exist in Filament v4
                        // Image editing is handled via imageEditor() above
                        ->columns(3),
                ])
                ->columns(3)
                ->columnSpanFull(),

            Section::make('Basic Information')
                ->description('General and identification details of the doctor.')
                ->columns(2)
                ->schema([
                    TextInput::make('first_name')->label('First Name')->required()->columnSpan(1),
                    TextInput::make('last_name')->label('Last Name')->required()->columnSpan(1),

                    TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->required()
                        ->afterStateHydrated(
                            fn($component, $state, $record) => $record?->user && $component->state($record->user->email)
                        )
                        ->unique(
                            table: 'users',
                            column: 'email',
                            ignorable: fn(?\Illuminate\Database\Eloquent\Model $record) => $record?->user
                        )->columnSpan(1),

                    Checkbox::make('update_password')
                        ->label('Update Password')
                        ->inline()
                        ->visible(fn($record) => $record !== null)
                        ->default(false)
                        ->live()
                        ->dehydrated(),

                    TextInput::make('password')
                        ->label(fn($record) => $record === null ? 'Password' : 'New Password')
                        ->password()
                        ->revealable()
                        ->autocomplete(fn($record) => $record === null ? 'new-password' : 'new-password')
                        ->visible(fn($get, $record) => $record === null ? true : (bool) $get('update_password'))
                        ->required(fn($get, $record) => $record === null ? true : (bool) $get('update_password'))
                        ->dehydrated()
                        ->columnStart(1)
                        ->columnSpan(1),

                    TextInput::make('phone')
                        ->label('Phone')
                        ->tel()
                        ->maxLength(20)
                        ->afterStateHydrated(
                            fn($component, $state, $record) => $record?->user && $component->state($record->user->phone)
                        )
                        ->dehydrateStateUsing(fn($state) => preg_replace('/[\s\-()]/', '', $state))
                        ->unique(
                            table: 'users',
                            column: 'phone',
                            ignorable: fn(?\Illuminate\Database\Eloquent\Model $record) => $record?->user
                        ),

                    DatePicker::make('dob')->label('Date of Birth'),
                    Select::make('career_start_year')
                        ->label('Career Start Year After PG')
                        ->required()
                        ->options(
                            function () {
                                $currentYear = now()->year;
                                $startYear = $currentYear;
                                $endYear = max(1950, $currentYear - 50);
                                $years = [];
                                for ($year = $startYear; $year >= $endYear; $year--) {
                                    $years[$year] = (string) $year;
                                }
                                return $years;
                            }
                        )
                        ->searchable(true) // Filament sometimes requires explicit true or omitting param
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set) {
                            if ($state) {
                                $set('years_experience', now()->year - (int) $state);
                            }
                        }),




                    Placeholder::make('experience_display')
                        ->label('Years Experience')
                        ->content(function ($get) {
                            return $get('years_experience')
                                ? $get('years_experience') . ' Years'
                                : '0 Years';
                        }),
                    TextInput::make('medical_license_number')
                        ->label('Medical License Number')
                        ->placeholder('Enter license number'),

                    TextInput::make('google_sheet_doctor_id')
                        ->label('Google Sheet Doctor ID')
                        ->placeholder('Doctor ID from client sheet')
                        ->helperText('Used to map manually uploaded external booking sheets to this doctor.')
                        ->numeric()
                        ->maxValue(PHP_INT_MAX),


                    Select::make('blood_group')
                        ->label('Blood Group')
                        ->options(BloodGroupOption::labels())
                        ->searchable()
                        ->dehydrateStateUsing(fn($state) => $state instanceof \BackedEnum ? $state->value : $state)
                        ->afterStateHydrated(fn($component, $state) => $component->state($state instanceof \BackedEnum ? $state->value : $state)),

                    Select::make('gender')
                        ->label('Gender')
                        ->options(GenderOption::labels())
                        ->native(false)
                        ->searchable()
                        ->dehydrateStateUsing(fn($state) => $state instanceof \BackedEnum ? $state->value : $state)
                        ->afterStateHydrated(fn($component, $state) => $component->state($state instanceof \BackedEnum ? $state->value : $state)),

                    Select::make('marital_status')
                        ->label('Marital Status')
                        ->options(MaritalStatus::labels())
                        ->native(false)
                        ->searchable()
                        ->dehydrateStateUsing(fn($state) => $state instanceof \BackedEnum ? $state->value : $state)
                        ->afterStateHydrated(fn($component, $state) => $component->state($state instanceof \BackedEnum ? $state->value : $state)),

                    Select::make('languages_known')
                        ->label('Languages Known')
                        ->multiple()
                        ->options(LanguageOption::labels())
                        ->dehydrateStateUsing(fn($state) => $state instanceof \BackedEnum ? $state->value : $state)
                        ->afterStateHydrated(fn($component, $state) => $component->state($state instanceof \BackedEnum ? $state->value : $state))
                        ->searchable(),

                    Textarea::make('bio')
                        ->label('Short Bio')
                        ->placeholder('Brief professional introduction')
                        ->columnSpanFull(),

                    Textarea::make('description')
                        ->label('Detailed Description')
                        ->placeholder('Background, specializations, and more')
                        ->columnSpanFull(),
                ])
                ->columns(3)
                ->columnSpanFull(),

            Section::make('Availability')
                ->description('Availability details for the doctor.')
                ->schema([
                    Select::make('status')
                        ->label('Status')
                        ->options(\App\Enums\DoctorStatus::values())
                        ->required()
                        ->native(false)
                        ->searchable()
                        ->dehydrateStateUsing(fn($state) => $state instanceof \BackedEnum ? $state->value : $state)
                        ->afterStateHydrated(fn($component, $state) => $component->state($state instanceof \BackedEnum ? $state->value : $state)),

                    Toggle::make('hide_from_mobile_app')
                        ->label('Hide from mobile app')
                        ->onColor('success')
                        ->offColor('danger')
                        ->default(false)
                        ->inline()
                        ->helperText('Hide this doctor from mobile browse/search only. Admin booking can still use this doctor, and booked patients can still see appointment details.')
                        ->default(false),

                    Toggle::make('hide_from_wordpress_api')
                        ->label('Hide from WordPress APIs')
                        ->onColor('success')
                        ->offColor('danger')
                        ->default(false)
                        ->inline()
                        ->helperText('Hide this doctor from WordPress/public website APIs only. Mobile app visibility is controlled separately.'),

                    Toggle::make('is_test_doctor')
                        ->label('Test doctor')
                        ->onColor('warning')
                        ->offColor('gray')
                        ->default(false)
                        ->inline()
                        ->helperText('Hidden from normal APIs, OPD calendar, reports, and admin appointment lists. Available only from WordPress test doctor APIs.'),
                ])
                ->columns(3)
                ->columnSpanFull(),

            Section::make('Address & Contact')
                ->description('Permanent and communication address details.')
                ->schema([
                    Textarea::make('address_line1')
                        ->label('Address Line 1'),

                    Textarea::make('address_line2')
                        ->label('Address Line 2'),

                    TextInput::make('country')
                        ->label('Country'),

                    TextInput::make('state')
                        ->label('State'),

                    TextInput::make('city')
                        ->label('City'),

                    TextInput::make('pincode')
                        ->label('Pincode'),
                    TextInput::make('area')
                        ->label('Area'),
                ])
                ->columns(3)
                ->columnSpanFull(),

            Section::make('Departments')
                ->description('Assign doctor to one or more departments with role and order.')
                ->schema([
                    Repeater::make('departments')
                        ->schema([
                            Select::make('id')
                                ->label('Department')
                                ->options(fn() => Department::where('status', 'active')->pluck('name', 'id'))
                                ->required()
                                ->reactive()
                                ->afterStateUpdated(function ($state, $set, $get, $record, $component) {
                                    if ($state && $record) {
                                        // Prevent duplicate department selections in the repeater
                                        $all = $component->getState();
                                        $values = collect($all)->pluck('id')->filter()->values();
                                        $duplicates = $values->countBy();
                                        if ($state && $duplicates->get($state, 0) > 1) {
                                            $set('id', null);
                                            Notification::make()
                                                ->danger()
                                                ->title('This department is already selected.')
                                                ->send();
                                            return;
                                        }

                                        // Continue with pivot setup
                                        $currentItem = $get();
                                        if (! is_array($currentItem)) {
                                            $currentItem = [];
                                        }
                                        $pivot = $currentItem['pivot'] ?? [];
                                        $pivot['order'] = $pivot['order'] ?? (DepartmentDoctor::where('doctor_id', $record->id)->max('order') + 1);
                                        $pivot['role'] = $pivot['role'] ?? null;
                                        // $set(array_merge($currentItem, ['pivot' => $pivot]));
                                    }
                                }),

                            Select::make('pivot.role')
                                ->label('Role in Department')
                                ->options(DepartmentRole::labels())
                                ->required(),

                            TextInput::make('pivot.order')
                                ->label('Order')
                                ->numeric()
                                ->disabled(true)
                                ->helperText('Order is controlled by drag-and-drop'),
                        ])
                        ->columns(3)
                        ->orderable('pivot.order')
                        // Removed ->uniqueItems as it is not a valid method.
                        ->afterStateUpdated(function ($state, $set, $get, $record, $component) {
                            // Prevent duplicate department entries at Repeater level
                            if ($state && is_array($state)) {
                                $ids = collect($state)->pluck('id')->filter();
                                $duplicates = $ids->countBy()->filter(fn($count) => $count > 1);
                                if ($duplicates->isNotEmpty()) {
                                    // Find the duplicated index and clear it
                                    foreach ($state as $index => $item) {
                                        if ($item['id'] && $duplicates->has($item['id'])) {
                                            $state[$index]['id'] = null;
                                            Notification::make()
                                                ->danger()
                                                ->title('Duplicate department detected and cleared.')
                                                ->send();
                                            break;
                                        }
                                    }
                                    $component->state($state);
                                }
                            }
                        })
                        ->afterStateHydrated(function ($component, $state, $record) {
                            if (! $record) {
                                return;
                            }

                            $currentState = $component->getState();
                            if (is_array($currentState) && count($currentState) && is_array($currentState[0] ?? null)) {
                                return; // already hydrated
                            }

                            $pivotDepartments = DepartmentDoctor::where('doctor_id', $record->id)
                                ->orderBy('order')
                                ->get()
                                ->map(fn($dept) => [
                                    'id' => $dept->department_id,
                                    'pivot' => [
                                        'role' => $dept->role ?? null,
                                        'order' => $dept->order ?? 1,
                                    ],
                                    '_pivot_id' => $dept->id, // for tracking unique pivot row
                                ])
                                ->values()
                                ->toArray();

                            $component->state($pivotDepartments ?: []);
                        })
                        ->addActionLabel('Add Department')
                        ->addAction(fn($action) => $action->extraAttributes(['class' => 'ms-auto']))
                        ->deleteAction(
                            fn($action) => $action
                                ->requiresConfirmation()
                                ->after(function ($state, $get, $set, $record, $component, $key) {
                                    // Remove department doctor pivot row when department is deleted in the form
                                    if ($record && isset($state['_pivot_id'])) {
                                        $pivotId = $state['_pivot_id'];
                                        $pivot = DepartmentDoctor::find($pivotId);
                                        if ($pivot) {
                                            $pivot->forceDelete();
                                        }
                                    }
                                })
                        ),
                ])
                ->columnSpanFull(),

            Section::make('Education Info')
                ->description('Degrees and educational background.')
                ->schema([
                    Repeater::make('education_info')
                        ->label('Education Info')
                        ->schema([
                            TextInput::make('degree')
                                ->label('Degree')
                                ->maxLength(255),
                            TextInput::make('institution')
                                ->label('Institution')
                                ->maxLength(255),
                            DatePicker::make('start_date')
                                ->label('Start Date'),
                            DatePicker::make('end_date')
                                ->label('End Date'),
                        ])
                        ->addActionLabel('Add Education')
                        ->addAction(fn($action) => $action->extraAttributes(['class' => 'ms-auto']))
                        ->columns(2)
                        ->columnSpanFull(),
                ])
                ->columnSpanFull(),

            Section::make('Awards Info')
                ->description('Awards, honors, and recognitions.')
                ->schema([
                    Repeater::make('awards_info')
                        ->label('Awards Info')
                        ->schema([
                            TextInput::make('title')
                                ->label('Title')
                                ->maxLength(255),
                            TextInput::make('organization')
                                ->label('Organization')
                                ->maxLength(255),
                            TextInput::make('year')
                                ->label('Year')
                                ->maxLength(4)
                                ->numeric()
                                ->placeholder('e.g., 2020'),
                            Textarea::make('description')
                                ->label('Description')
                                ->maxLength(1000)
                                ->columnSpanFull(),
                            FileUpload::make('award_image')
                                ->label('Award Image')
                                ->disk('public')
                                ->directory('doctorDocument')
                                ->image()
                                ->imageEditor()
                                ->maxSize(2048)
                                ->helperText('Upload an image of the award (optional, max 2MB)')
                                ->columnSpanFull(),
                        ])
                        ->addActionLabel('Add Award')
                        ->addAction(fn($action) => $action->extraAttributes(['class' => 'ms-auto']))
                        ->columns(3)
                        ->columnSpanFull(),
                ])
                ->columnSpanFull(),

            Section::make('Certifications Info')
                ->description('Certifications and professional courses.')
                ->schema([
                    Repeater::make('certifications_info')
                        ->label('Certifications Info')
                        ->schema([
                            TextInput::make('name')
                                ->label('Certificate Name')
                                ->maxLength(255),
                            TextInput::make('organization')
                                ->label('Organization')
                                ->maxLength(255),
                            DatePicker::make('issue_date')
                                ->label('Issue Date'),
                            DatePicker::make('expiry_date')
                                ->label('Expiry Date'),
                            FileUpload::make('certification_image')
                                ->label('Certificate Image')
                                ->disk('public')
                                ->directory('doctorDocument')
                                ->image()
                                ->imageEditor()
                                ->maxSize(2048)
                                ->helperText('Upload an image of the certificate (optional, max 2MB)')
                                ->columnSpanFull(),
                        ])
                        ->addActionLabel('Add Certification')
                        ->addAction(fn($action) => $action->extraAttributes(['class' => 'ms-auto']))
                        ->columns(2)
                        ->columnSpanFull(),
                ])
                ->columnSpanFull(),

            Section::make('Professional Experience')
                ->description('Career milestones and past professional associations after completing post-graduation.')
                ->schema([
                    Repeater::make('professional_experience_info')
                        ->label('Professional Experience')
                        ->schema([
                            DatePicker::make('start_date')
                                ->label('Start Date')
                                ->placeholder('Select start date')
                                ->helperText('Select the start date of the experience'),
                            DatePicker::make('end_date')
                                ->label('End Date')
                                ->placeholder('Select end date')
                                ->helperText('Select the end date of the experience'),


                            TextInput::make('association')
                                ->label('Associations')
                                ->placeholder('e.g., AIIMS Bathinda, SR at GMCH Chandigarh, Fortis Hospital Mohali')
                                ->helperText('List notable hospitals, institutions, residencies, or past experience'),
                        ])
                        ->addActionLabel('Add Experience')
                        ->addAction(fn($action) => $action->extraAttributes(['class' => 'ms-auto']))
                        ->columns(2),
                ])
                ->columnSpanFull(),

            Section::make('Areas of Expertise')
                ->description('Core clinical specialties, subspecialties, procedures, and additional areas of professional expertise.')
                ->schema([
                    TextArea::make('specializations_info')
                        ->label('Specializations / Subspecialties')
                        ->placeholder('e.g., Clinical Nephrology – Glomerular Disease, Peritoneal Dialysis')
                        ->helperText('List subspecialties separated by commas'),

                    TextArea::make('key_procedures_info')
                        ->label('Key Procedures / Skills')
                        ->placeholder('e.g., Angioplasty, Knee Replacement, IVF')
                        ->helperText('List primary procedures or technical skills'),

                    TextArea::make('expertise_info')
                        ->label('Expertise')
                        ->placeholder('e.g., Transplant Medicine, Pediatric Nephrology'),
                ])
                ->columnSpanFull(),

            Section::make('Fellowships / Training')
                ->description('Post-graduate fellowships, advanced training, certifications.')
                ->schema([
                    Repeater::make('fellowships_info')
                        ->label('Fellowship / Training')
                        ->schema([
                            TextInput::make('title')
                                ->label('Title')
                                ->placeholder('e.g., FACC, FRCS, Fellowship in Cardiology'),

                            TextInput::make('institution')
                                ->label('Institution / Organization')
                                ->placeholder('e.g., Cleveland Clinic, AIIMS'),

                            TextInput::make('year_started')
                                ->label('Year Started')
                                ->placeholder('e.g., 2015')
                                ->mask('9999')
                                ->numeric()
                                ->rules(['digits:4'])
                                ->minLength(4)
                                ->maxLength(4),

                            Textarea::make('description')
                                ->label('Description')
                                ->placeholder('Short details about this fellowship or advanced training')
                                ->columnSpanFull(),
                        ])
                        ->columns(3)
                        ->itemLabel(fn(array $state): ?string => $state['title'] ?? null)
                        ->addActionLabel('Add Fellowship')
                        ->addAction(fn($action) => $action->extraAttributes(['class' => 'ms-auto'])),
                ])
                ->columnSpanFull(),

            Section::make('Additional Information')
                ->description('Other relevant details about the doctor.')
                ->schema([
                    ...self::additionalInformationHtmlField(
                        'special_interests',
                        'Special Interests',
                        'e.g., FACC, FRCS, Fellowship in Cardiology'
                    ),

                    ...self::additionalInformationHtmlField(
                        'availability_info',
                        'Availability',
                        'e.g., Cleveland Clinic, AIIMS'
                    ),

                    ...self::additionalInformationHtmlField(
                        'memberships_info',
                        'Memberships',
                        'e.g., 2015'
                    ),
                ])
                ->columnSpanFull(),

            // 🌐 SOCIAL LINKS
            Section::make('Social Media')
                ->description('Links to doctor’s social profiles.')
                ->schema([
                    TextInput::make('social_links.facebook')
                        ->label('Facebook')
                        ->prefixIcon('heroicon-m-globe-alt')
                        ->url(),

                    TextInput::make('social_links.twitter')
                        ->label('Twitter / X')
                        ->prefixIcon('heroicon-m-globe-alt')
                        ->url(),

                    TextInput::make('social_links.linkedin')
                        ->label('LinkedIn')
                        ->prefixIcon('heroicon-m-globe-alt')
                        ->url(),

                    TextInput::make('social_links.instagram')
                        ->label('Instagram')
                        ->prefixIcon('heroicon-m-globe-alt')
                        ->url(),

                    TextInput::make('social_links.website')
                        ->label('Personal Website')
                        ->prefixIcon('heroicon-m-globe-alt')
                        ->url(),
                ])
                ->columns(2)
                ->columnSpanFull(),

        ]);
    }

    private static function additionalInformationHtmlField(string $name, string $label, string $placeholder): array
    {
        return [
            RichEditor::make($name)
                ->label($label)
                ->placeholder($placeholder)
                ->helperText('Format content visually. HTML is saved in the same field.')
                ->columnSpanFull()
                ->dehydrated(),
        ];
    }
}
