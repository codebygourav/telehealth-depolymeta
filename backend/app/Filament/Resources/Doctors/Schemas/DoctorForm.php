<?php

namespace App\Filament\Resources\Doctors\Schemas;

use App\Enums\{BloodGroupOption, DayOfWeek, DepartmentRole, GenderOption, LanguageOption, MaritalStatus};
use App\Models\{Department, DepartmentDoctor, Doctor};
use App\Services\DoctorAvailabilityValidationService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\{Checkbox, DatePicker, FileUpload, Hidden, Placeholder, Radio, Repeater, RichEditor, Select, Textarea, TextInput, TimePicker, Toggle};
use Filament\Notifications\Notification;
use Filament\Schemas\Components\{Grid, Section, Tabs, Tabs\Tab, Wizard, Wizard\Step};
use Filament\Schemas\Schema;

class DoctorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Wizard::make([
                // 1. Basic & Sheet Data
                Step::make('Basic Info')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('first_name')->label('First Name')->required(),
                                TextInput::make('last_name')->label('Last Name')->required(),
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
                                    ),
                            ]),

                        Grid::make(3)
                            ->schema([
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
                                TextInput::make('medical_license_number')
                                    ->label('PMC Registration Number / License Number')
                                    ->required()
                                    ->placeholder('Enter registration number'),
                                Select::make('career_start_year')
                                    ->label('Year of start of Senior Residency / Career Start Year')
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
                                    ->searchable()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        if ($state) {
                                            $set('years_experience', now()->year - (int) $state);
                                        }
                                    }),
                            ]),

                        Grid::make(3)
                            ->schema([
                                Placeholder::make('experience_display')
                                    ->label('Years Experience')
                                    ->content(function ($get) {
                                        return $get('years_experience')
                                            ? $get('years_experience') . ' Years'
                                            : '0 Years';
                                    }),
                                TextInput::make('google_sheet_doctor_id')
                                    ->label('Sync External Appointment Doctor ID')
                                    ->placeholder('Doctor ID from client sheet')
                                    ->numeric()
                                    ->maxValue(PHP_INT_MAX),
                                Select::make('languages_known')
                                    ->label('Languages Familiar')
                                    ->multiple()
                                    ->options(LanguageOption::labels())
                                    ->dehydrateStateUsing(fn($state) => $state instanceof \BackedEnum ? $state->value : $state)
                                    ->afterStateHydrated(fn($component, $state) => $component->state($state instanceof \BackedEnum ? $state->value : $state))
                                    ->searchable()
                                    ->required(),
                            ]),

                        Section::make('Profile Photo & Signature')
                            ->schema([
                                FileUpload::make('avatar')
                                    ->label('Profile Photo / Doctor Image')
                                    ->disk('public')
                                    ->directory('user_avatar')
                                    ->visibility('public')
                                    ->image()
                                    ->avatar()
                                    ->imageEditor()
                                    ->imageEditorAspectRatios(['1:1'])
                                    ->imageCropAspectRatio('1:1')
                                    ->openable()
                                    ->maxSize(2048)
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                    ->helperText('Upload professional headshot. Square (1:1 ratio) is recommended.'),

                                FileUpload::make('signature')
                                    ->label('Signature')
                                    ->disk('public')
                                    ->directory('doctorSignatures')
                                    ->image()
                                    ->imageEditor()
                                    ->imageEditorAspectRatios(['3:1', '4:1', '1:1'])
                                    ->imageCropAspectRatio('3:1')
                                    ->maxSize(2048)
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                    ->helperText('Upload signature image. Recommended ratio: 3:1 (e.g., 600x200px).'),
                            ])
                            ->columns(2),

                        Section::make('Departments Assignment')
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

                                                    $currentItem = $get();
                                                    if (! is_array($currentItem)) {
                                                        $currentItem = [];
                                                    }
                                                    $pivot = $currentItem['pivot'] ?? [];
                                                    $pivot['order'] = $pivot['order'] ?? (DepartmentDoctor::where('doctor_id', $record->id)->max('order') + 1);
                                                    $pivot['role'] = $pivot['role'] ?? null;
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
                                    ->afterStateUpdated(function ($state, $set, $get, $record, $component) {
                                        if ($state && is_array($state)) {
                                            $ids = collect($state)->pluck('id')->filter();
                                            $duplicates = $ids->countBy()->filter(fn($count) => $count > 1);
                                            if ($duplicates->isNotEmpty()) {
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
                                            return;
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
                                                '_pivot_id' => $dept->id,
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
                                                if ($record && isset($state['_pivot_id'])) {
                                                    $pivotId = $state['_pivot_id'];
                                                    $pivot = DepartmentDoctor::find($pivotId);
                                                    if ($pivot) {
                                                        $pivot->forceDelete();
                                                    }
                                                }
                                            })
                                    ),
                            ]),

                        Section::make('Account Credentials (Visible for new/updating passwords)')
                            ->schema([
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
                                    ->dehydrated(),
                            ])
                            ->columns(2),
                    ]),

                // 2. Availability
                Step::make('Availability')
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
                            ->helperText('Hide this doctor from mobile browse/search only.'),

                        Toggle::make('hide_from_wordpress_api')
                            ->label('Hide from WordPress APIs')
                            ->onColor('success')
                            ->offColor('danger')
                            ->default(false)
                            ->inline()
                            ->helperText('Hide this doctor from WordPress/public website APIs only.'),

                        Toggle::make('is_test_doctor')
                            ->label('Test doctor')
                            ->onColor('warning')
                            ->offColor('gray')
                            ->default(false)
                            ->inline()
                            ->helperText('Hidden from normal APIs, OPD calendar, reports, and admin lists.'),

                        RichEditor::make('availability_info')
                            ->label('Availability Info From Sheet')
                            ->placeholder('e.g., General OPD: Tuesday and Friday (10:00am to 2:00 pm)...')
                            ->helperText('Format availability schedule info. Saved directly in HTML.')
                            ->columnSpanFull(),
                    ]),

                // 3. Professional Bio
                Step::make('Professional Bio')
                    ->schema([
                        TextInput::make('sub_title')
                            ->label('Sub Title')
                            ->placeholder('Sub Title')
                            ->columnSpanFull(),
                        RichEditor::make('bio')
                            ->label('Short Bio (A brief on Professional Experience)')
                            ->placeholder('Brief professional introduction (max 200 words recommended)')
                            ->required()
                            ->columnSpanFull(),

                        RichEditor::make('description')
                            ->label('Detailed Description')
                            ->placeholder('Background, specializations, and more')
                            ->columnSpanFull(),
                    ]),

                // 4. Education
                Step::make('Education')
                    ->schema([
                        Radio::make('education_info_mode')
                            ->label('Entry Method')
                            ->options([
                                'repeater' => 'Structured List (Degrees, Institutions, Years)',
                                'editor' => 'Free-text Editor (Write custom text / HTML)',
                            ])
                            ->default('repeater')
                            ->inline()
                            ->live()
                            ->afterStateHydrated(function ($component, $state, $record) {
                                if ($record && is_array($record->education_info)) {
                                    $info = $record->education_info;
                                    if (isset($info[0]['is_free_text']) && $info[0]['is_free_text']) {
                                        $component->state('editor');
                                    } else {
                                        $component->state('repeater');
                                    }
                                }
                            }),

                        Repeater::make('education_info_repeater')
                            ->label('Education Entries')
                            ->schema([
                                TextInput::make('degree')
                                    ->label('Degree')
                                    ->maxLength(255),
                                TextInput::make('institution')
                                    ->label('Institution')
                                    ->maxLength(255),
                                TextInput::make('completion_year')
                                    ->label('Completion Year')
                                    ->maxLength(255),
                            ])
                            ->columns(3)
                            ->visible(fn($get) => $get('education_info_mode') === 'repeater')
                            ->afterStateHydrated(function ($component, $state, $record) {
                                if ($record && is_array($record->education_info)) {
                                    $info = $record->education_info;
                                    if (!(isset($info[0]['is_free_text']) && $info[0]['is_free_text'])) {
                                        $component->state($info);
                                    }
                                }
                            }),

                        RichEditor::make('education_info_editor')
                            ->label('Education Info (Free-text Editor)')
                            ->visible(fn($get) => $get('education_info_mode') === 'editor')
                            ->afterStateHydrated(function ($component, $state, $record) {
                                if ($record && is_array($record->education_info)) {
                                    $info = $record->education_info;
                                    if (isset($info[0]['is_free_text']) && $info[0]['is_free_text']) {
                                        $html = $info[0]['html'] ?? null;
                                        $component->state(blank($html) ? null : $html);
                                    }
                                }
                            })
                            ->columnSpanFull(),
                    ]),

                // 5. Awards / Roles / Recognition
                Step::make('Awards')
                    ->schema([
                        Radio::make('awards_info_mode')
                            ->label('Entry Method')
                            ->options([
                                'repeater' => 'Structured List (Titles, Organizations, Years)',
                                'editor' => 'Free-text Editor (Write custom text / HTML)',
                            ])
                            ->default('repeater')
                            ->inline()
                            ->live()
                            ->afterStateHydrated(function ($component, $state, $record) {
                                if ($record && is_array($record->awards_info)) {
                                    $info = $record->awards_info;
                                    if (isset($info[0]['is_free_text']) && $info[0]['is_free_text']) {
                                        $component->state('editor');
                                    } else {
                                        $component->state('repeater');
                                    }
                                }
                            }),

                        Repeater::make('awards_info_repeater')
                            ->label('Award Entries')
                            ->schema([
                                TextInput::make('title')
                                    ->label('Title / Role')
                                    ->maxLength(255),
                                TextInput::make('year')
                                    ->label('Year')
                                    ->maxLength(4)
                                    ->numeric()
                                    ->placeholder('e.g., 2026'),
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
                                    ->helperText('Upload image of the award (max 2MB)')
                                    ->columnSpanFull(),
                            ])
                            ->columns(3)
                            ->visible(fn($get) => $get('awards_info_mode') === 'repeater')
                            ->afterStateHydrated(function ($component, $state, $record) {
                                if ($record && is_array($record->awards_info)) {
                                    $info = $record->awards_info;
                                    if (!(isset($info[0]['is_free_text']) && $info[0]['is_free_text'])) {
                                        $component->state($info);
                                    }
                                }
                            }),

                        RichEditor::make('awards_info_editor')
                            ->label('Awards / Roles Info (Free-text Editor)')
                            ->visible(fn($get) => $get('awards_info_mode') === 'editor')
                            ->afterStateHydrated(function ($component, $state, $record) {
                                if ($record && is_array($record->awards_info)) {
                                    $info = $record->awards_info;
                                    if (isset($info[0]['is_free_text']) && $info[0]['is_free_text']) {
                                        $html = $info[0]['html'] ?? null;
                                        $component->state(blank($html) ? null : $html);
                                    }
                                }
                            })
                            ->columnSpanFull(),
                    ]),

                // 6. Certificates
                Step::make('Certificates')
                    ->schema([
                        Radio::make('certifications_info_mode')
                            ->label('Entry Method')
                            ->options([
                                'repeater' => 'Structured List (Names, Organizations, Dates)',
                                'editor' => 'Free-text Editor (Write custom text / HTML)',
                            ])
                            ->default('repeater')
                            ->inline()
                            ->live()
                            ->afterStateHydrated(function ($component, $state, $record) {
                                if ($record && is_array($record->certifications_info)) {
                                    $info = $record->certifications_info;
                                    if (isset($info[0]['is_free_text']) && $info[0]['is_free_text']) {
                                        $component->state('editor');
                                    } else {
                                        $component->state('repeater');
                                    }
                                }
                            }),

                        Repeater::make('certifications_info_repeater')
                            ->label('Certificate Entries')
                            ->schema([
                                TextInput::make('name')
                                    ->label('Certificate / Degree Name')
                                    ->maxLength(255),
                                TextInput::make('organization')
                                    ->label('Organization / Institution')
                                    ->maxLength(255),
                                Textarea::make('description')
                                    ->label('Description')
                                    ->maxLength(1000)
                                    ->columnSpanFull(),
                                FileUpload::make('certification_image')
                                    ->label('Certificate Image')
                                    ->disk('public')
                                    ->directory('doctorDocument')
                                    ->image()
                                    ->imageEditor()
                                    ->maxSize(2048)
                                    ->helperText('Upload image of the certificate (max 2MB)')
                                    ->columnSpanFull(),
                            ])
                            ->columns(2)
                            ->visible(fn($get) => $get('certifications_info_mode') === 'repeater')
                            ->afterStateHydrated(function ($component, $state, $record) {
                                if ($record && is_array($record->certifications_info)) {
                                    $info = $record->certifications_info;
                                    if (!(isset($info[0]['is_free_text']) && $info[0]['is_free_text'])) {
                                        $component->state($info);
                                    }
                                }
                            }),

                        RichEditor::make('certifications_info_editor')
                            ->label('Certifications Info (Free-text Editor)')
                            ->visible(fn($get) => $get('certifications_info_mode') === 'editor')
                            ->afterStateHydrated(function ($component, $state, $record) {
                                if ($record && is_array($record->certifications_info)) {
                                    $info = $record->certifications_info;
                                    if (isset($info[0]['is_free_text']) && $info[0]['is_free_text']) {
                                        $html = $info[0]['html'] ?? null;
                                        $component->state(blank($html) ? null : $html);
                                    }
                                }
                            })
                            ->columnSpanFull(),
                    ]),

                // 7. Experience
                Step::make('Experience')
                    ->schema([
                        Radio::make('professional_experience_info_mode')
                            ->label('Entry Method')
                            ->options([
                                'repeater' => 'Structured List (Associations, Dates, Descriptions)',
                                'editor' => 'Free-text Editor (Write custom text / HTML)',
                            ])
                            ->default('repeater')
                            ->inline()
                            ->live()
                            ->afterStateHydrated(function ($component, $state, $record) {
                                if ($record && is_array($record->professional_experience_info)) {
                                    $info = $record->professional_experience_info;
                                    if (isset($info[0]['is_free_text']) && $info[0]['is_free_text']) {
                                        $component->state('editor');
                                    } else {
                                        $component->state('repeater');
                                    }
                                }
                            }),

                        Repeater::make('professional_experience_info_repeater')
                            ->label('Working Experience Entries')
                            ->schema([
                                TextInput::make('association')
                                    ->label('Association / Hospital')
                                    ->placeholder('e.g., CMC Ludhiana')
                                    ->maxLength(255),
                                Textarea::make('description')
                                    ->label('Description')
                                    ->columnSpanFull(),
                            ])
                            ->columns(1)
                            ->visible(fn($get) => $get('professional_experience_info_mode') === 'repeater')
                            ->afterStateHydrated(function ($component, $state, $record) {
                                if ($record && is_array($record->professional_experience_info)) {
                                    $info = $record->professional_experience_info;
                                    if (!(isset($info[0]['is_free_text']) && $info[0]['is_free_text'])) {
                                        $component->state($info);
                                    }
                                }
                            }),

                        RichEditor::make('professional_experience_info_editor')
                            ->label('Working Experience (Free-text Editor)')
                            ->visible(fn($get) => $get('professional_experience_info_mode') === 'editor')
                            ->afterStateHydrated(function ($component, $state, $record) {
                                if ($record && is_array($record->professional_experience_info)) {
                                    $info = $record->professional_experience_info;
                                    if (isset($info[0]['is_free_text']) && $info[0]['is_free_text']) {
                                        $html = $info[0]['html'] ?? null;
                                        $component->state(blank($html) ? null : $html);
                                    }
                                }
                            })
                            ->columnSpanFull(),
                    ]),

                // 8. Fellowships / Training
                Step::make('Fellowships')
                    ->schema([
                        Radio::make('fellowships_info_mode')
                            ->label('Entry Method')
                            ->options([
                                'repeater' => 'Structured List (Titles, Institutions, Years)',
                                'editor' => 'Free-text Editor (Write custom text / HTML)',
                            ])
                            ->default('repeater')
                            ->inline()
                            ->live()
                            ->afterStateHydrated(function ($component, $state, $record) {
                                if ($record && is_array($record->fellowships_info)) {
                                    $info = $record->fellowships_info;
                                    if (isset($info[0]['is_free_text']) && $info[0]['is_free_text']) {
                                        $component->state('editor');
                                    } else {
                                        $component->state('repeater');
                                    }
                                }
                            }),

                        Repeater::make('fellowships_info_repeater')
                            ->label('Fellowship Entries')
                            ->schema([
                                TextInput::make('title')
                                    ->label('Title')
                                    ->placeholder('e.g., Fellowship in Comprehensive Hematology Oncology'),
                                TextInput::make('institution')
                                    ->label('Institution')
                                    ->placeholder('e.g., Cleveland Clinic'),
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
                                    ->columnSpanFull(),
                            ])
                            ->columns(3)
                            ->visible(fn($get) => $get('fellowships_info_mode') === 'repeater')
                            ->afterStateHydrated(function ($component, $state, $record) {
                                if ($record && is_array($record->fellowships_info)) {
                                    $info = $record->fellowships_info;
                                    if (!(isset($info[0]['is_free_text']) && $info[0]['is_free_text'])) {
                                        $component->state($info);
                                    }
                                }
                            }),

                        RichEditor::make('fellowships_info_editor')
                            ->label('Fellowships Info (Free-text Editor)')
                            ->visible(fn($get) => $get('fellowships_info_mode') === 'editor')
                            ->afterStateHydrated(function ($component, $state, $record) {
                                if ($record && is_array($record->fellowships_info)) {
                                    $info = $record->fellowships_info;
                                    if (isset($info[0]['is_free_text']) && $info[0]['is_free_text']) {
                                        $html = $info[0]['html'] ?? null;
                                        $component->state(blank($html) ? null : $html);
                                    }
                                }
                            })
                            ->columnSpanFull(),
                    ]),

                // 9. Extra Info
                Step::make('Additional Information')
                    ->schema([
                        RichEditor::make('specializations_info')
                            ->label('Specializations / Subspecialties')
                            ->placeholder('e.g., MD Pediatrics, fellowship in Comprehensive Hematology Oncology')
                            ->columnSpanFull(),

                        RichEditor::make('key_procedures_info')
                            ->label('Key Procedures')
                            ->placeholder('e.g., Bone marrow, Chemotherapy, Immunotherapy')
                            ->columnSpanFull(),

                        RichEditor::make('memberships_info')
                            ->label('Memberships')
                            ->placeholder('Use editor for memberships or committee roles.')
                            ->columnSpanFull(),

                        RichEditor::make('special_interests')
                            ->label('Special Interests')
                            ->placeholder('e.g., Thalassemia, supportive care, survivorship...')
                            ->columnSpanFull(),

                        RichEditor::make('expertise_info')
                            ->label('Expertise Info')
                            ->placeholder('e.g., Complex hematological disorders, childhood malignancies...')
                            ->columnSpanFull(),

                        Section::make('Voice & Speech Settings')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        Select::make('speech_locale')
                                            ->label('Speech Recognition Accent / Locale')
                                            ->options([
                                                'en-IN' => 'English (India)',
                                                'en-US' => 'English (US)',
                                                'hi-IN' => 'Hindi',
                                                'pa-IN' => 'Punjabi',
                                            ])
                                            ->default('en-IN'),
                                        Select::make('voice_name')
                                            ->label('Text-to-Speech Voice Name')
                                            ->options([
                                                'system_default' => 'Default System Voice',
                                                'Google US English' => 'Google US English (en-US)',
                                                'Google UK English Female' => 'Google UK English Female (en-GB)',
                                                'Google UK English Male' => 'Google UK English Male (en-GB)',
                                                'Google India English' => 'Google India English (en-IN)',
                                                'Samantha' => 'Apple Samantha (en-US)',
                                            ])
                                            ->searchable()
                                            ->placeholder('Select voice')
                                            ->helperText('Choose a preferred voice for prescription synthesis. Fallback defaults are used if not available.')
                                            ->nullable(),
                                    ]),
                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('speech_rate')
                                            ->label('Speech Rate (Speed)')
                                            ->numeric()
                                            ->default(1.0)
                                            ->step(0.1)
                                            ->minValue(0.5)
                                            ->maxValue(2.0)
                                            ->helperText('Spoken speed multiplier. 1.0 is standard, 0.5 is slow, 2.0 is fast.'),
                                        TextInput::make('speech_pitch')
                                            ->label('Speech Pitch')
                                            ->numeric()
                                            ->default(1.0)
                                            ->step(0.1)
                                            ->minValue(0.5)
                                            ->maxValue(2.0)
                                            ->helperText('Voice pitch frequency. 1.0 is standard, 0.5 is deep, 2.0 is high.'),
                                    ]),
                            ])
                            ->columns(1),
                    ]),

                // 10. Contact
                Step::make('Contact')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Textarea::make('address_line1')
                                    ->label('Address Line 1')
                                    ->columnSpan(2),
                                Textarea::make('address_line2')
                                    ->label('Address Line 2')
                                    ->columnSpan(1),
                            ]),

                        Grid::make(3)
                            ->schema([
                                TextInput::make('country')->label('Country')->default('India'),
                                TextInput::make('state')->label('State')->default('Punjab'),
                                TextInput::make('city')->label('City')->default('Ludhiana'),
                            ]),

                        Grid::make(3)
                            ->schema([
                                TextInput::make('pincode')->label('Pincode'),
                                TextInput::make('area')->label('Area'),
                                TextInput::make('landmark')->label('Landmark'),
                            ]),

                        Section::make('Social Media')
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
                            ->columns(2),
                    ]),
            ])
                ->skippable()
                ->columnSpanFull(),
        ]);
    }

    public static function mutateFormDataBeforeFill(array $data): array
    {
        $fields = [
            'education_info',
            'awards_info',
            'certifications_info',
            'professional_experience_info',
            'fellowships_info',
        ];

        foreach ($fields as $field) {
            $modeKey = "{$field}_mode";
            $repeaterKey = "{$field}_repeater";
            $editorKey = "{$field}_editor";

            $info = $data[$field] ?? null;

            if (is_array($info) && count($info) > 0) {
                if (isset($info[0]['is_free_text']) && $info[0]['is_free_text']) {
                    $data[$modeKey] = 'editor';
                    $data[$editorKey] = $info[0]['html'] ?? '';
                    $data[$repeaterKey] = [];
                } else {
                    $data[$modeKey] = 'repeater';
                    $data[$repeaterKey] = $info;
                    $data[$editorKey] = '';
                }
            } else {
                $data[$modeKey] = 'repeater';
                $data[$repeaterKey] = [];
                $data[$editorKey] = '';
            }
        }

        return $data;
    }

    public static function mutateFormDataBeforeSave(array $data): array
    {
        $fields = [
            'education_info',
            'awards_info',
            'certifications_info',
            'professional_experience_info',
            'fellowships_info',
        ];

        foreach ($fields as $field) {
            $modeKey = "{$field}_mode";
            $repeaterKey = "{$field}_repeater";
            $editorKey = "{$field}_editor";

            if (isset($data[$modeKey])) {
                if ($data[$modeKey] === 'editor') {
                    $data[$field] = [
                        [
                            'is_free_text' => true,
                            'html' => $data[$editorKey] ?? '',
                        ]
                    ];
                } else {
                    $data[$field] = $data[$repeaterKey] ?? [];
                }
            }

            unset($data[$modeKey], $data[$repeaterKey], $data[$editorKey]);
        }

        return $data;
    }
}
