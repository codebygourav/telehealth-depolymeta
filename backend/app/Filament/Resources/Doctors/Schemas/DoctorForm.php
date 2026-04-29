<?php

namespace App\Filament\Resources\Doctors\Schemas;

use App\Enums\{BloodGroupOption, DayOfWeek, DepartmentRole, GenderOption, LanguageOption, MaritalStatus};
use App\Models\{Department, DepartmentDoctor, Doctor};
use App\Services\DoctorAvailabilityValidationService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\{Checkbox, DatePicker, FileUpload, Hidden, Placeholder, Repeater, Select, Textarea, TextInput, TimePicker, Toggle};
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
                        ->image()
                        ->avatar()
                        ->imageEditor()
                        ->imageEditorAspectRatios([
                            '1:1',
                        ])
                        ->columns(3),
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
                    TextInput::make('last_name')->label('Last Name')->columnSpan(1),

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

                    TextInput::make('years_experience')
                        ->label('Years of Experience')
                        ->required()
                        ->numeric(),

                    TextInput::make('medical_license_number')
                        ->label('Medical License Number')
                        ->placeholder('Enter license number'),

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
                        ->helperText('Hide this doctor from patient browse/search. Booked patients can still access the doctor from their appointment.')
                        ->default(false),
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
                            TextInput::make('career_start')
                                ->label('Career Start (Post-PG)')
                                ->placeholder('e.g., 2004')
                                ->helperText('Enter year')
                                ->mask('9999')                  // allows ONLY 4 digits while typing
                                ->numeric()
                                ->rules(['digits:4'])
                                ->minLength(4)
                                ->maxLength(4),

                            TextInput::make('past_associations')
                                ->label('Past Associations')
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
                    Textarea::make('special_interests')
                        ->label('Special Interests')
                        ->placeholder('e.g., FACC, FRCS, Fellowship in Cardiology'),

                    Textarea::make('availability_info')
                        ->label('Availability')
                        ->placeholder('e.g., Cleveland Clinic, AIIMS'),

                    Textarea::make('memberships_info')
                        ->label('Memberships ')
                        ->placeholder('e.g., 2015'),
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

    /**
     * Generate simple, professional slot card HTML for the Existing Slots tab.
     */
    private static function generateSlotCardHtml(
        string $startTime,
        string $endTime,
        bool $isAvailable,
        bool $isRecurring,
        ?string $date,
        ?string $recurringEndDate,
        string $consultationType,
        string $opdType,
        float $fee,
        int $capacity,
        ?string $doctorRoom = null
    ): string {
        $statusText = $isAvailable ? 'Active' : 'Inactive';
        $typeText = $isRecurring ? 'Recurring' : 'One-Time';

        $dateDisplay = '-';
        if (! $isRecurring && $date) {
            try {
                $dateDisplay = \Carbon\Carbon::parse($date)->format('d M Y');
            } catch (\Exception $e) {
            }
        } elseif ($isRecurring && $recurringEndDate) {
            try {
                $dateDisplay = 'Until ' . \Carbon\Carbon::parse($recurringEndDate)->format('d M Y');
            } catch (\Exception $e) {
            }
        }

        $modeText = $consultationType === 'video' ? 'Video' : 'In-Person';
        $categoryText = $consultationType === 'video' ? '' : ' · ' . ucfirst($opdType);

        $startFormatted = $startTime !== '--:--'
            ? \Carbon\Carbon::parse($startTime)->format('h:i A')
            : '--:--';
        $endFormatted = $endTime !== '--:--'
            ? \Carbon\Carbon::parse($endTime)->format('h:i A')
            : '--:--';

        $feeDisplay = '₹' . number_format($fee, 0);
        $capacityLabel = $capacity . ' patient' . ($capacity > 1 ? 's' : '');

        $statusClasses = $isAvailable
            ? 'inline-flex items-center rounded-xl border px-2 py-0.5 text-[11px] font-medium border-emerald-200 bg-emerald-50 text-emerald-700'
            : 'inline-flex items-center rounded-xl border px-2 py-0.5 text-[11px] font-medium border-rose-200 bg-rose-50 text-rose-700';

        // Tailwind-style classes so it looks like native Filament UI, but keep layout very simple.
        $html = '<div class="px-4 py-3 space-y-1 bg-white border border-gray-200 rounded-lg">';

        // Row 1: time + status
        $html .= '<div class="flex items-center justify-between gap-3">';
        $html .= '<div class="text-sm font-semibold text-gray-900">' . $startFormatted . ' – ' . $endFormatted . '</div>';
        $html .= '<span class="' . $statusClasses . '">' . $statusText . '</span>';
        $html .= '</div>';

        // Row 2: type + date
        $html .= '<div class="flex flex-wrap items-center gap-1 text-xs text-gray-500">';
        $html .= '<span>' . $typeText . '</span>';
        $html .= '<span>•</span>';
        $html .= '<span>' . $dateDisplay . '</span>';
        $html .= '</div>';

        // Row 3: mode + fee + capacity
        $html .= '<div class="flex flex-wrap items-center gap-1 text-xs text-gray-500">';
        $html .= '<span>' . $modeText . $categoryText . '</span>';
        $html .= '<span>•</span>';
        $html .= '<span>' . $feeDisplay . '</span>';
        $html .= '<span>•</span>';
        $html .= '<span>' . $capacityLabel . '</span>';
        if ($doctorRoom) {
            $html .= '<span>•</span>';
            $html .= '<span>Room: ' . htmlspecialchars($doctorRoom) . '</span>';
        }
        $html .= '</div>';

        $html .= '</div>';

        return $html;
    }

    public static function availabilityTabsForSlideOver(): array
    {
        $dayLabels = DayOfWeek::labels();
        $dayKeys = array_keys($dayLabels);

        $listingSections = [];

        foreach ($dayKeys as $dayKey) {
            $dayKeyLower = strtolower($dayKey);

            $listingSections[] = Section::make($dayKeyLower)
                ->heading(function ($get) use ($dayLabels, $dayKey, $dayKeyLower) {
                    $slots = $get("slots_{$dayKeyLower}") ?? [];
                    $total = is_array($slots) ? count($slots) : 0;
                    $selected = is_array($slots)
                        ? collect($slots)->filter(fn($slot) => is_array($slot) && ! empty($slot['is_selected']))->count()
                        : 0;

                    $selectedPart = $selected > 0 ? " • {$selected} selected" : '';
                    return $dayLabels[$dayKey] . " ({$total}{$selectedPart})";
                })
                ->description(fn($get) => count($get("slots_{$dayKeyLower}") ?? []) > 0
                    ? "Consultation slots for {$dayLabels[$dayKey]}"
                    : 'No slots scheduled')
                // Fix: use closure to check if any slot in this section is being edited. If so, make collapsible.
                ->key(function ($get) use ($dayKeyLower) {
                    $slots = collect($get("slots_{$dayKeyLower}") ?? []);
                    $isEditing = $slots->contains(fn($slot) => !empty($slot['is_editing']));
                    return $dayKeyLower . '-' . ($isEditing ? 'editing' : 'view');
                })

                ->icon('heroicon-o-clock')
                ->iconColor(fn($get) => count($get("slots_{$dayKeyLower}") ?? []) > 0 ? 'success' : 'gray')
                ->visible(function ($get) use ($dayKeyLower) {
                    $selectedDay = strtolower((string) ($get('overview_day_filter') ?? strtolower(now()->format('l'))));
                    $matchesFilter = $selectedDay === 'all' || $selectedDay === $dayKeyLower;

                    return $matchesFilter && count($get("slots_{$dayKeyLower}") ?? []) > 0;
                })
                ->extraAttributes([
                    'class' => 'border border-gray-300 rounded-xl bg-white',
                ])
                ->schema([
                    Repeater::make("slots_{$dayKeyLower}")
                        ->hiddenLabel()
                        ->default([])
                        ->defaultItems(0)
                        ->addable(false)
                        ->reorderable(false)
                        ->grid(1)
                        ->collapsible()
                        ->collapsed(function ($get) use ($dayKeyLower) {
                            $slots = collect($get("slots_{$dayKeyLower}") ?? []);
                            $isEditing = $slots->contains(fn($slot) => !empty($slot['is_editing']));
                            return !$isEditing; // collapse only when nothing is editing
                        })
                        ->itemLabel(function ($state) {
                            $startTime = $state['start_time'] ?? null;
                            $endTime = $state['end_time'] ?? null;
                            $date = $state['date'] ?? null;
                            $isRecurring = $state['is_recurring'] ?? false;
                            $recurringStartDate = $state['recurring_start_date'] ?? null;
                            $recurringEndDate = $state['recurring_end_date'] ?? null;
                            $isAvailable = (bool) ($state['is_available'] ?? true);
                            $consultationType = strtolower((string) ($state['consultation_type'] ?? 'in-person'));
                            $labelParts = [];

                            if ($isRecurring) {
                                // Show the actual day name of recurring_start_date
                                if ($recurringStartDate) {
                                    try {
                                        $recurringStart = \Carbon\Carbon::parse($recurringStartDate);
                                        // Get English day name (e.g. "Tuesday") based on DB value
                                        $dayLabel = $recurringStart->englishDayOfWeek;
                                        $rangeText = $recurringStart->format('M d, Y');
                                        if ($recurringEndDate) {
                                            try {
                                                $recurringEnd = \Carbon\Carbon::parse($recurringEndDate);
                                                $rangeText .= ' - ' . $recurringEnd->format('M d, Y');
                                            } catch (\Exception $e) {
                                                // Ignore, just use the start date
                                            }
                                        }
                                        $labelParts[] = "{$dayLabel} ({$rangeText})";
                                    } catch (\Exception $e) {
                                        $labelParts[] = 'Recurring (Invalid start date)';
                                    }
                                } else {
                                    $labelParts[] = 'Recurring (No start date)';
                                }
                            } elseif ($date) {
                                try {
                                    $labelParts[] = \Carbon\Carbon::parse($date)->format('M d, Y');
                                } catch (\Exception $e) {
                                    $labelParts[] = $date;
                                }
                            }

                            // Always show the time range, if available
                            if ($startTime && $endTime) {
                                try {
                                    // Accept both H:i:s and H:i
                                    $start = \Carbon\Carbon::createFromFormat(strlen($startTime) === 5 ? 'H:i' : 'H:i:s', $startTime);
                                } catch (\Exception $e) {
                                    $start = null;
                                }
                                try {
                                    $end = \Carbon\Carbon::createFromFormat(strlen($endTime) === 5 ? 'H:i' : 'H:i:s', $endTime);
                                } catch (\Exception $e) {
                                    $end = null;
                                }
                                if ($start && $end) {
                                    $labelParts[] = $start->format('h:i A') . ' – ' . $end->format('h:i A');
                                } elseif ($start) {
                                    $labelParts[] = $start->format('h:i A');
                                } elseif ($end) {
                                    $labelParts[] = $end->format('h:i A');
                                }
                            }

                            $labelParts[] = $isAvailable ? '[Active]' : '[Inactive]';
                            $labelParts[] = $consultationType === 'video' ? '[Video]' : '[In-Person]';

                            return implode(' | ', $labelParts);
                        })
                        ->deleteAction(function (\Filament\Actions\Action $action) {
                            $action->label('Remove')->icon('heroicon-m-trash')->color('danger')->requiresConfirmation()
                                ->modalIcon('heroicon-o-exclamation-triangle')
                                ->modalIconColor('danger')
                                ->modalHeading('Remove Availability Slot')
                                ->modalDescription('This will permanently delete this consultation slot. Any pending appointments for this slot may be affected. This action cannot be undone.')
                                ->modalSubmitActionLabel('Yes, Remove Slot')
                                ->action(function (array $arguments, \Filament\Forms\Components\Repeater $component) {
                                    $itemKey = $arguments['item'] ?? null;
                                    if ($itemKey) {
                                        $state = $component->getState();
                                        $id = $state[$itemKey]['id'] ?? null;

                                        if ($id) {
                                            $record = \App\Models\DoctorAvailability::find($id);
                                            if ($record) {
                                                $record->delete();
                                                $record->forceDelete();
                                                \Filament\Notifications\Notification::make()
                                                    ->title('Availability Removed')
                                                    ->body('The consultation slot has been removed from the schedule.')
                                                    ->success()
                                                    ->send();
                                            }
                                        }

                                        unset($state[$itemKey]);
                                        $component->state($state);
                                    }
                                });
                        })
                        ->extraItemActions([
                            Action::make('toggle_select_slot')
                                ->label(function (array $arguments, Repeater $component) {
                                    $itemKey = $arguments['item'] ?? null;
                                    if (! $itemKey) {
                                        return 'Select';
                                    }

                                    $state = $component->getState();
                                    return ! empty($state[$itemKey]['is_selected']) ? 'Unselect' : 'Select';
                                })
                                ->icon(function (array $arguments, Repeater $component) {
                                    $itemKey = $arguments['item'] ?? null;
                                    if (! $itemKey) {
                                        return 'heroicon-m-check-circle';
                                    }

                                    $state = $component->getState();
                                    return ! empty($state[$itemKey]['is_selected'])
                                        ? 'heroicon-m-check-circle'
                                        : 'heroicon-m-plus-circle';
                                })
                                ->iconButton()
                                ->color(function (array $arguments, Repeater $component) {
                                    $itemKey = $arguments['item'] ?? null;
                                    if (! $itemKey) {
                                        return 'gray';
                                    }

                                    $state = $component->getState();
                                    return ! empty($state[$itemKey]['is_selected']) ? 'success' : 'gray';
                                })
                                ->action(function (array $arguments, Repeater $component) {
                                    $itemKey = $arguments['item'] ?? null;
                                    if (! $itemKey) return;

                                    $state = $component->getState();
                                    $state[$itemKey]['is_selected'] = ! (bool) ($state[$itemKey]['is_selected'] ?? false);
                                    $component->state($state);
                                }),

                            Action::make('edit_details')
                                ->icon('heroicon-m-pencil-square')
                                ->iconButton()
                                ->color('gray')
                                ->visible(function (array $arguments, Repeater $component) {

                                    $itemKey = $arguments['item'] ?? null;
                                    if (! $itemKey) return true;

                                    $state = $component->getState();

                                    return empty($state[$itemKey]['is_editing']);
                                })
                                ->action(function (array $arguments, Repeater $component) {

                                    $itemKey = $arguments['item'] ?? null;
                                    if (! $itemKey) return;

                                    $state = $component->getState();
                                    $state[$itemKey]['is_editing'] = true;

                                    $component->state($state);
                                }),

                            Action::make('save_edit')
                                ->icon('heroicon-m-check')
                                ->color('success')
                                ->visible(function (array $arguments, Repeater $component) {

                                    $itemKey = $arguments['item'] ?? null;
                                    if (! $itemKey) return false;

                                    $state = $component->getState();

                                    return ! empty($state[$itemKey]['is_editing']);
                                })
                                ->action(function (array $arguments, Repeater $component) {

                                    $itemKey = $arguments['item'] ?? null;
                                    if (! $itemKey) return;

                                    $state = $component->getState();

                                    // Just exit edit mode
                                    $state[$itemKey]['is_editing'] = false;

                                    $component->state($state);
                                }),
                            Action::make('cancel_edit')
                                ->icon('heroicon-m-x-mark')
                                ->color('gray')
                                ->visible(function (array $arguments, Repeater $component) {

                                    $itemKey = $arguments['item'] ?? null;
                                    if (! $itemKey) return false;

                                    $state = $component->getState();

                                    return ! empty($state[$itemKey]['is_editing']);
                                })
                                ->action(function (array $arguments, Repeater $component) {

                                    $itemKey = $arguments['item'] ?? null;
                                    if (! $itemKey) return;

                                    $state = $component->getState();
                                    $state[$itemKey]['is_editing'] = false;

                                    $component->state($state);
                                }),

                        ])
                        ->extraAttributes([
                            'style' => 'gap: 12px;',
                        ])
                        ->schema([
                            \Filament\Forms\Components\Hidden::make('id'),
                            \Filament\Forms\Components\Hidden::make('is_editing')->live(),
                            Hidden::make('is_selected')->default(false),
                            Hidden::make('start_time')->dehydrated(),
                            Hidden::make('end_time')->dehydrated(),
                            Hidden::make('date')->dehydrated(),
                            Hidden::make('is_recurring')->dehydrated(),
                            Hidden::make('is_available')->dehydrated(),
                            Hidden::make('capacity')->dehydrated(),
                            Hidden::make('consultation_type')->dehydrated(),
                            Hidden::make('opd_type')->dehydrated(),
                            Hidden::make('consultation_fee')->dehydrated(),
                            Hidden::make('recurring_start_date')->dehydrated(),
                            Hidden::make('recurring_months')->dehydrated(),
                            Hidden::make('recurring_end_date')->dehydrated(),
                            // DO NOT USE .dehydrated() here -- allow the form to initialize value for doctor_room
                            Hidden::make('doctor_room'),

                            // CARD VIEW (Read-Only) - Redesigned
                            Grid::make(12)
                                ->visible(fn($get) => ! (bool) $get('is_editing'))
                                ->schema([
                                    Placeholder::make('slot_card_view')
                                        ->label(null)
                                        ->hiddenLabel()
                                        ->columnSpan(12)
                                        ->content(function ($get) {
                                            return new \Illuminate\Support\HtmlString(
                                                self::generateSlotCardHtml(
                                                    $get('start_time') ?? '--:--',
                                                    $get('end_time') ?? '--:--',
                                                    (bool) ($get('is_available') ?? true),
                                                    (bool) ($get('is_recurring') ?? false),
                                                    $get('date'),
                                                    $get('recurring_end_date'),
                                                    $get('consultation_type') ?? 'in-person',
                                                    $get('opd_type') ?? 'general',
                                                    (float) ($get('consultation_fee') ?? 0),
                                                    (int) ($get('capacity') ?? 1),
                                                    $get('doctor_room') ?? null,
                                                )
                                            );
                                        }),
                                ]),

                            // EDIT MODE - Simplified
                            Section::make('Edit Slot')
                                ->visible(fn($get) => (bool) $get('is_editing'))
                                ->icon('heroicon-o-pencil-square')
                                ->compact()
                                ->schema([
                                    Grid::make(4)
                                        ->schema([
                                            Toggle::make('is_available')->label('Active')->onColor('success')->offColor('danger')->default(true)->dehydrated(),
                                            Toggle::make('is_recurring')->label('Recurring')->onColor('success')->offColor('danger')->live()->dehydrated(),
                                            Select::make('recurring_months')->label('Duration')->options([3 => '3 Month(s)', 6 => '6 Month(s)', 12 => '12 Month(s)'])->visible(fn($get) => $get('is_recurring'))->dehydrated(),
                                            TextInput::make('capacity')->label('Capacity')->numeric()->default(1)->dehydrated(),
                                        ]),
                                    Grid::make(5)
                                        ->schema([
                                            Select::make('consultation_type')
                                                ->label('Mode')
                                                ->options(['in-person' => 'In-Person', 'video' => 'Video'])
                                                ->default('in-person')
                                                ->live()
                                                ->dehydrated()
                                                ->afterStateUpdated(function ($state, $set, $get) {
                                                    if ($state === 'video') {
                                                        $set('opd_type', null);
                                                    }
                                                }),
                                            Select::make('opd_type')
                                                ->label('OPD')
                                                ->options(['general' => 'General', 'private' => 'Private'])
                                                ->default('general')
                                                ->visible(fn($get) => $get('consultation_type') === 'in-person')
                                                ->dehydrateStateUsing(function ($state, $get) {
                                                    // Only save opd_type for in-person; if video, save null
                                                    return $get('consultation_type') === 'in-person' ? $state : null;
                                                }),
                                            TextInput::make('consultation_fee')->label('Fee (₹)')->numeric()->default(0)->dehydrated(),
                                            TextInput::make('doctor_room')
                                                ->label('Doctor Room')
                                                ->placeholder('e.g., Room 101')
                                                ->dehydrated(),

                                            DatePicker::make('date')->label('Date')->visible(fn($get) => ! $get('is_recurring'))->native(false)->displayFormat('d M Y')->dehydrated(),
                                        ]),
                                    Grid::make(2)
                                        ->schema([
                                            TimePicker::make('start_time')->label('Start')->dehydrated(),
                                            TimePicker::make('end_time')->label('End')->dehydrated(),
                                        ]),
                                ]),
                        ]),
                ]);
        }

        return [
            Tabs::make('active_tab')
                ->live(true)
                ->contained(false)
                ->tabs([
                    Tab::make('overview')
                        ->label('Existing Slots')
                        ->icon('heroicon-m-calendar-days')
                        ->badge(function ($get) use ($dayKeys) {
                            $total = 0;
                            foreach ($dayKeys as $day) {
                                $total += count($get('slots_' . strtolower($day)) ?? []);
                            }

                            return $total > 0 ? $total : null;
                        })
                        ->schema([
                            Section::make('Slots Toolbar')
                                ->compact()
                                ->schema([
                                    Grid::make(4)
                                        ->schema([

                                            Select::make('overview_day_filter')
                                                ->label('Show Slots For')
                                                ->options(function () use ($dayLabels) {
                                                    $options = ['all' => 'All Days'];
                                                    foreach ($dayLabels as $key => $label) {
                                                        $options[strtolower($key)] = $label;
                                                    }

                                                    return $options;
                                                })
                                                ->default(strtolower(now()->format('l')))
                                                ->native(false)
                                                ->searchable()
                                                ->live()
                                                ->columnSpan(1),
                                            \Filament\Schemas\Components\Actions::make([
                                                Action::make('clear_selected_slots_topbar')
                                                    ->label(function ($get) use ($dayKeys) {
                                                        $scope = strtolower((string) ($get('overview_day_filter') ?? 'all'));
                                                        $targetDays = $scope === 'all' ? array_map('strtolower', $dayKeys) : [$scope];
                                                        $selectedCount = 0;

                                                        foreach ($targetDays as $day) {
                                                            $slots = $get("slots_{$day}") ?? [];
                                                            if (! is_array($slots)) {
                                                                continue;
                                                            }

                                                            $selectedCount += collect($slots)
                                                                ->filter(fn($slot) => is_array($slot) && ! empty($slot['is_selected']))
                                                                ->count();
                                                        }

                                                        return "Clear Selection ({$selectedCount})";
                                                    })
                                                    ->icon('heroicon-m-x-circle')
                                                    ->color('gray')
                                                    ->size('sm')
                                                    ->visible(function ($get) use ($dayKeys) {
                                                        $scope = strtolower((string) ($get('overview_day_filter') ?? 'all'));
                                                        $targetDays = $scope === 'all' ? array_map('strtolower', $dayKeys) : [$scope];

                                                        foreach ($targetDays as $day) {
                                                            $slots = $get("slots_{$day}") ?? [];
                                                            if (! is_array($slots)) {
                                                                continue;
                                                            }

                                                            $hasSelected = collect($slots)
                                                                ->contains(fn($slot) => is_array($slot) && ! empty($slot['is_selected']));

                                                            if ($hasSelected) {
                                                                return true;
                                                            }
                                                        }

                                                        return false;
                                                    })
                                                    ->action(function ($get, $set) use ($dayKeys) {
                                                        $scope = strtolower((string) ($get('overview_day_filter') ?? 'all'));
                                                        $targetDays = $scope === 'all' ? array_map('strtolower', $dayKeys) : [$scope];

                                                        foreach ($targetDays as $day) {
                                                            $slots = $get("slots_{$day}") ?? [];
                                                            if (! is_array($slots)) {
                                                                continue;
                                                            }

                                                            foreach ($slots as &$slot) {
                                                                if (is_array($slot)) {
                                                                    $slot['is_selected'] = false;
                                                                }
                                                            }

                                                            $set("slots_{$day}", $slots);
                                                        }
                                                    }),
                                                ActionGroup::make([
                                                    Action::make('bulk_select_all_slots')
                                                        ->label('Select All Slots')
                                                        ->icon('heroicon-m-check-badge')
                                                        ->action(function ($get, $set) use ($dayKeys) {
                                                            $scope = strtolower((string) ($get('overview_day_filter') ?? 'all'));
                                                            $targetDays = $scope === 'all' ? array_map('strtolower', $dayKeys) : [$scope];

                                                            foreach ($targetDays as $day) {
                                                                $slots = $get("slots_{$day}") ?? [];
                                                                if (! is_array($slots)) {
                                                                    continue;
                                                                }

                                                                foreach ($slots as &$slot) {
                                                                    if (is_array($slot)) {
                                                                        $slot['is_selected'] = true;
                                                                    }
                                                                }

                                                                $set("slots_{$day}", $slots);
                                                            }
                                                        }),
                                                    Action::make('bulk_clear_selected_slots')
                                                        ->label('Clear Selection')
                                                        ->icon('heroicon-m-x-circle')
                                                        ->action(function ($get, $set) use ($dayKeys) {
                                                            $scope = strtolower((string) ($get('overview_day_filter') ?? 'all'));
                                                            $targetDays = $scope === 'all' ? array_map('strtolower', $dayKeys) : [$scope];

                                                            foreach ($targetDays as $day) {
                                                                $slots = $get("slots_{$day}") ?? [];
                                                                if (! is_array($slots)) {
                                                                    continue;
                                                                }

                                                                foreach ($slots as &$slot) {
                                                                    if (is_array($slot)) {
                                                                        $slot['is_selected'] = false;
                                                                    }
                                                                }

                                                                $set("slots_{$day}", $slots);
                                                            }
                                                        }),
                                                    Action::make('bulk_activate_selected_slots')
                                                        ->label('Bulk Activate')
                                                        ->icon('heroicon-m-check-circle')
                                                        ->color('success')
                                                        ->action(function ($get, $set) use ($dayKeys) {
                                                            $scope = strtolower((string) ($get('overview_day_filter') ?? 'all'));
                                                            $targetDays = $scope === 'all' ? array_map('strtolower', $dayKeys) : [$scope];
                                                            $updated = 0;

                                                            foreach ($targetDays as $day) {
                                                                $slots = $get("slots_{$day}") ?? [];
                                                                if (! is_array($slots)) {
                                                                    continue;
                                                                }

                                                                foreach ($slots as &$slot) {
                                                                    if (is_array($slot) && ! empty($slot['is_selected'])) {
                                                                        $slot['is_available'] = true;
                                                                        $updated++;
                                                                    }
                                                                }

                                                                $set("slots_{$day}", $slots);
                                                            }

                                                            if ($updated > 0) {
                                                                Notification::make()
                                                                    ->title('Bulk Update Applied')
                                                                    ->body("Marked {$updated} selected slot(s) as active. Click Save Schedule to persist.")
                                                                    ->success()
                                                                    ->send();
                                                            }
                                                        }),
                                                    Action::make('bulk_deactivate_selected_slots')
                                                        ->label('Bulk Deactivate')
                                                        ->icon('heroicon-m-minus-circle')
                                                        ->color('warning')
                                                        ->action(function ($get, $set) use ($dayKeys) {
                                                            $scope = strtolower((string) ($get('overview_day_filter') ?? 'all'));
                                                            $targetDays = $scope === 'all' ? array_map('strtolower', $dayKeys) : [$scope];
                                                            $updated = 0;

                                                            foreach ($targetDays as $day) {
                                                                $slots = $get("slots_{$day}") ?? [];
                                                                if (! is_array($slots)) {
                                                                    continue;
                                                                }

                                                                foreach ($slots as &$slot) {
                                                                    if (is_array($slot) && ! empty($slot['is_selected'])) {
                                                                        $slot['is_available'] = false;
                                                                        $updated++;
                                                                    }
                                                                }

                                                                $set("slots_{$day}", $slots);
                                                            }

                                                            if ($updated > 0) {
                                                                Notification::make()
                                                                    ->title('Bulk Update Applied')
                                                                    ->body("Marked {$updated} selected slot(s) as inactive. Click Save Schedule to persist.")
                                                                    ->success()
                                                                    ->send();
                                                            }
                                                        }),
                                                    Action::make('bulk_delete_selected_slots')
                                                        ->label('Bulk Delete')
                                                        ->icon('heroicon-m-trash')
                                                        ->color('danger')
                                                        ->requiresConfirmation()
                                                        ->modalHeading('Delete selected slots?')
                                                        ->modalDescription('Selected slots will be removed from the chosen day scope. This action cannot be undone once saved.')
                                                        ->action(function ($get, $set) use ($dayKeys, $dayLabels) {
                                                            $scope = strtolower((string) ($get('overview_day_filter') ?? 'all'));
                                                            $targetDays = $scope === 'all' ? array_map('strtolower', $dayKeys) : [$scope];
                                                            $deletedCount = 0;

                                                            foreach ($targetDays as $day) {
                                                                $slots = $get("slots_{$day}") ?? [];
                                                                if (! is_array($slots)) {
                                                                    continue;
                                                                }

                                                                $remaining = [];
                                                                foreach ($slots as $slot) {
                                                                    if (! is_array($slot) || empty($slot['is_selected'])) {
                                                                        $remaining[] = $slot;
                                                                        continue;
                                                                    }

                                                                    $deletedCount++;
                                                                    if (! empty($slot['id'])) {
                                                                        $record = \App\Models\DoctorAvailability::find($slot['id']);
                                                                        if ($record) {
                                                                            $record->delete();
                                                                            $record->forceDelete();
                                                                        }
                                                                    }
                                                                }

                                                                $set("slots_{$day}", array_values($remaining));
                                                            }

                                                            if ($deletedCount > 0) {
                                                                $scopeLabel = $scope === 'all'
                                                                    ? 'all days'
                                                                    : ($dayLabels[ucfirst($scope)] ?? ucfirst($scope));

                                                                Notification::make()
                                                                    ->title('Selected Slots Deleted')
                                                                    ->body("Removed {$deletedCount} slot(s) from {$scopeLabel}.")
                                                                    ->success()
                                                                    ->send();
                                                            } else {
                                                                Notification::make()
                                                                    ->title('Nothing Selected')
                                                                    ->body('Select one or more slots first, then run bulk delete.')
                                                                    ->warning()
                                                                    ->send();
                                                            }
                                                        }),
                                                ])
                                                    ->label(function ($get) use ($dayKeys) {
                                                        $scope = strtolower((string) ($get('overview_day_filter') ?? 'all'));
                                                        $targetDays = $scope === 'all' ? array_map('strtolower', $dayKeys) : [$scope];
                                                        $selectedCount = 0;

                                                        foreach ($targetDays as $day) {
                                                            $slots = $get("slots_{$day}") ?? [];
                                                            if (! is_array($slots)) {
                                                                continue;
                                                            }

                                                            $selectedCount += collect($slots)
                                                                ->filter(fn($slot) => is_array($slot) && ! empty($slot['is_selected']))
                                                                ->count();
                                                        }

                                                        return $selectedCount > 0
                                                            ? "Bulk Actions - {$selectedCount} selected"
                                                            : 'Bulk Actions';
                                                    })
                                                    ->icon('heroicon-m-chevron-down')
                                                    ->button()
                                                    ->color('primary')
                                                    ->size('sm'),
                                            ])
                                            ->extraAttributes(['class' => 'flex items-start justify-end'])
                                            ->alignStart()
                                            ->columnSpan(3),
                                            ])

                                ])

                                ->visible(function ($get) use ($dayKeys) {
                                    foreach ($dayKeys as $day) {
                                        if (count($get('slots_' . strtolower($day)) ?? []) > 0) {
                                            return true;
                                        }
                                    }

                                    return false;
                                }),

                            // Empty state - simple
                            Placeholder::make('no_slots_message')
                                ->label(null)
                                ->content(function ($get) use ($dayLabels, $dayKeys) {
                                    $selectedDay = strtolower((string) ($get('overview_day_filter') ?? strtolower(now()->format('l'))));

                                    if ($selectedDay !== 'all') {
                                        $dayLabel = $dayLabels[ucfirst($selectedDay)] ?? ucfirst($selectedDay);
                                        return "No slots found for {$dayLabel}. Choose another day from the dropdown or switch to All Days.";
                                    }

                                    return 'No availability slots scheduled yet. Use "Add New Slot" to create them. Same date/time is supported for different consultation modes.';
                                })
                                ->visible(function ($get) use ($dayKeys) {
                                    $selectedDay = strtolower((string) ($get('overview_day_filter') ?? strtolower(now()->format('l'))));

                                    if ($selectedDay !== 'all') {
                                        return count($get("slots_{$selectedDay}") ?? []) === 0;
                                    }

                                    foreach ($dayKeys as $day) {
                                        if (count($get('slots_' . strtolower($day)) ?? []) > 0) {
                                            return false;
                                        }
                                    }

                                    return true;
                                })
                                ->extraAttributes([
                                    'class' => 'text-center text-gray-500 py-8',
                                ]),

                            // Day sections
                            ...$listingSections,

                            \Filament\Forms\Components\Placeholder::make('overview_actions')
                                ->hiddenLabel()
                                ->content(function () {
                                    return new \Illuminate\Support\HtmlString(\Illuminate\Support\Facades\Blade::render(<<<'BLADE'
                                        <div class="flex items-center pt-6 mt-6 border-t border-gray-200 gap-x-3 dark:border-white/10">
                                            <x-filament::button type="submit" color="primary">
                                                Save Schedule
                                            </x-filament::button>
                                            <x-filament::button type="button" color="gray" x-on:click="$el.closest('.fi-modal').querySelector('.fi-modal-close-btn').click()">
                                                Close
                                            </x-filament::button>
                                        </div>
                                    BLADE));
                                }),
                        ]),
                    Tab::make('add-slot')
                        ->label('Add New Slot')
                        ->icon('heroicon-m-plus-circle')
                        ->schema([
                            // Schedule Type Section
                            Section::make('Schedule Type')
                                ->icon('heroicon-o-calendar')
                                ->compact()
                                ->schema([
                                    Grid::make(4)
                                        ->schema([
                                            Select::make('temp_day')
                                                ->label('Day')
                                                ->options($dayLabels)
                                                ->default(strtolower(now()->format('l')))
                                                ->live()
                                                ->afterStateUpdated(fn($set) => $set('temp_date', null)),
                                            Toggle::make('temp_rec')
                                                ->label('Recurring?')
                                                ->reactive()
                                                ->onColor('success')
                                                ->onIcon('heroicon-m-check-circle')
                                                ->offIcon('heroicon-m-x-circle')
                                                ->offColor('danger')
                                                ->live()
                                                ->inline(false),
                                            Select::make('temp_months')
                                                ->label('Duration')
                                                ->options([
                                                    3 => '3 month(s) (Quarterly)',
                                                    6 => '6 month(s) (Bi-Annual)',
                                                    12 => '12 month(s) (Yearly)',
                                                ])
                                                ->default(3)
                                                ->visible(fn($get) => (bool) $get('temp_rec')),
                                            Toggle::make('temp_active')
                                                ->label('Active')
                                                ->onColor('success')
                                                ->offColor('danger')
                                                ->onIcon('heroicon-m-check-circle')
                                                ->offIcon('heroicon-m-x-circle')
                                                ->default(true)
                                                ->inline(false),
                                        ]),
                                ]),

                            // Consultation Details Section
                            Section::make('Consultation Details')
                                ->icon('heroicon-o-clipboard-document-list')
                                ->compact()
                                ->schema([
                                    Grid::make(5)
                                        ->schema([
                                            Select::make('temp_cons')
                                                ->label('Mode')
                                                ->options([
                                                    'in-person' => 'In-Person',
                                                    'video' => 'Video',
                                                ])
                                                ->default('in-person')
                                                ->live()
                                                ->afterStateUpdated(function ($state, $set, $get) {
                                                    if ($state === 'video') {
                                                        $set('temp_opd', null);
                                                    }
                                                }),
                                            Select::make('temp_opd')
                                                ->label('OPD Type')
                                                ->options([
                                                    'general' => 'General',
                                                    'private' => 'Private',
                                                ])
                                                ->default('general')
                                                ->visible(fn($get) => $get('temp_cons') === 'in-person')
                                                ->dehydrateStateUsing(function ($state, $get) {
                                                    // Only save if consultation is in-person
                                                    return $get('temp_cons') === 'in-person' ? $state : null;
                                                }),
                                            TextInput::make('temp_fee')
                                                ->label('Fee (₹)')
                                                ->numeric()
                                                ->default(0),
                                            TextInput::make('temp_room')
                                                ->label('Doctor Room')
                                                ->placeholder('e.g., Room 101')
                                                // Show the DB value as the initial default if available
                                                ->default(fn($get, $state) => $state !== null && $state !== '' ? $state : ($get('temp_room') ?? null)),
                                            TextInput::make('temp_cap')
                                                ->label('Capacity')
                                                ->numeric()
                                                ->default(1)
                                                ->minValue(1),
                                        ]),
                                ]),

                            // Date & Time Section
                            Section::make('Date & Time')
                                ->icon('heroicon-o-clock')
                                ->compact()
                                ->schema([
                                    Grid::make(3)
                                        ->schema([
                                            DatePicker::make('temp_date')
                                                ->label('Date')
                                                ->visible(fn($get) => ! $get('temp_rec'))
                                                ->live()
                                                ->native(false)
                                                ->displayFormat('d M Y')
                                                ->minDate(now()->startOfDay())
                                                ->prefixAction(
                                                    Action::make('prev_date')
                                                        ->icon('heroicon-m-chevron-left')
                                                        ->action(function ($state, $set) {
                                                            if (! $state) {
                                                                return;
                                                            }
                                                            $current = \Carbon\Carbon::parse($state);
                                                            $newDate = $current->subWeek();
                                                            if ($newDate->lt(now()->startOfDay())) {
                                                                return;
                                                            }
                                                            $set('temp_date', $newDate->format('Y-m-d'));
                                                        })
                                                        ->disabled(fn($state) => ! $state || \Carbon\Carbon::parse($state)->subWeek()->lt(now()->startOfDay()))
                                                )
                                                ->suffixAction(
                                                    Action::make('next_date')
                                                        ->icon('heroicon-m-chevron-right')
                                                        ->action(function ($state, $set, $get) {
                                                            if (! $state) {
                                                                $day = $get('temp_day');
                                                                if (! $day) {
                                                                    return;
                                                                }
                                                                $set('temp_date', \Carbon\Carbon::parse("next $day")->format('Y-m-d'));

                                                                return;
                                                            }
                                                            $current = \Carbon\Carbon::parse($state);
                                                            $set('temp_date', $current->addWeek()->format('Y-m-d'));
                                                        })
                                                )
                                                ->afterStateUpdated(function ($state, $set, $get) {
                                                    if (! $state) {
                                                        return;
                                                    }
                                                    $date = \Carbon\Carbon::parse($state);
                                                    $day = strtolower($get('temp_day') ?? '');
                                                    if ($day && strtolower($date->format('l')) !== $day) {
                                                        $set('temp_date', null);
                                                        Notification::make()
                                                            ->title('Day Mismatch')
                                                            ->body('Date must fall on ' . ucfirst($day))
                                                            ->danger()
                                                            ->send();
                                                    }
                                                }),
                                            TimePicker::make('temp_start')
                                                ->label('Start Time')
                                                ->live()
                                                ->afterStateUpdated(function ($state, $get, $livewire) {
                                                    if (! $state || ! $get('temp_end')) {
                                                        return;
                                                    }
                                                    $validationService = app(DoctorAvailabilityValidationService::class);
                                                    $timeErrors = $validationService->validateTimeRange($state, $get('temp_end'));
                                                    if (! empty($timeErrors)) {
                                                        Notification::make()->title('Invalid Time')->body($timeErrors[0])->danger()->send();
                                                    }
                                                }),
                                            TimePicker::make('temp_end')
                                                ->label('End Time')
                                                ->live()
                                                ->afterStateUpdated(function ($state, $get, $livewire) {
                                                    if (! $state || ! $get('temp_start')) {
                                                        return;
                                                    }
                                                    $validationService = app(DoctorAvailabilityValidationService::class);
                                                    $timeErrors = $validationService->validateTimeRange($get('temp_start'), $state);
                                                    if (! empty($timeErrors)) {
                                                        Notification::make()->title('Invalid Time')->body($timeErrors[0])->danger()->send();
                                                    }
                                                }),
                                        ]),
                                ]),

                            // Add button
                            \Filament\Schemas\Components\Actions::make([
                                \Filament\Actions\Action::make('global_add_slot')
                                    ->label('Add Consultation Slot')
                                    ->color('primary')
                                    ->size('md')
                                    ->icon('heroicon-m-plus-circle')
                                    ->action(function ($get, $set, $livewire) {
                                        $validationService = app(DoctorAvailabilityValidationService::class);
                                        $day = strtolower($get('temp_day') ?? '');
                                        $newStart = $get('temp_start');
                                        $newEnd = $get('temp_end');
                                        $newDate = $get('temp_date');
                                        $isRec = (bool) $get('temp_rec');

                                        // Validation: Required fields
                                        if (! $day || ! $newStart || ! $newEnd) {
                                            Notification::make()
                                                ->title('Missing Information')
                                                ->body('Please select a day and set both start and end times for the consultation slot.')
                                                ->warning()
                                                ->send();

                                            return;
                                        }

                                        // Collect all existing slots for validation
                                        $allSlots = [];
                                        $dayLabels = DayOfWeek::labels();
                                        foreach (array_keys($dayLabels) as $dayKey) {
                                            $dayKeyLower = strtolower($dayKey);
                                            $daySlots = $get("slots_{$dayKeyLower}") ?? [];
                                            if (is_array($daySlots)) {
                                                foreach ($daySlots as $slot) {
                                                    $slot['day_of_week'] = $dayKeyLower; // ✅ inject day
                                                    $allSlots[] = $slot;
                                                }
                                            }
                                        }

                                        // Validate using centralized service
                                        $doctorId = $livewire->record?->id;
                                        $consultationType = $get('temp_cons') ?? 'in-person';
                                        $errors = $validationService->validateSlot(
                                            $doctorId,
                                            $newDate,
                                            $newStart,
                                            $newEnd,
                                            $consultationType,
                                            $isRec,
                                            null,
                                            $allSlots,
                                            $day // ✅ VERY IMPORTANT

                                        );

                                        if (! empty($errors)) {
                                            Notification::make()
                                                ->title('Schedule Conflict')
                                                ->body($errors[0])
                                                ->danger()
                                                ->send();

                                            return;
                                        }

                                        // Normalize times
                                        $normalizedStart = is_array($newStart)
                                            ? sprintf('%02d:%02d:00', $newStart['hour'] ?? 0, $newStart['minute'] ?? 0)
                                            : Carbon::parse($newStart)->format('H:i:00');
                                        $normalizedEnd = is_array($newEnd)
                                            ? sprintf('%02d:%02d:00', $newEnd['hour'] ?? 0, $newEnd['minute'] ?? 0)
                                            : Carbon::parse($newEnd)->format('H:i:00');

                                        // Normalize date
                                        $normalizedDate = null;
                                        if (! $isRec && $newDate) {
                                            try {
                                                $normalizedDate = Carbon::parse($newDate)->format('Y-m-d');
                                            } catch (\Exception $e) {
                                            }
                                        }

                                        // Figure out correct opd_type
                                        $opdType = $consultationType === 'video' ? null : ($get('temp_opd') ?? 'general');

                                        // Prepare slot data
                                        $slotData = [
                                            'start_time' => substr($normalizedStart, 0, 5),
                                            'end_time' => substr($normalizedEnd, 0, 5),
                                            'opd_type' => $opdType,
                                            'consultation_type' => $consultationType,
                                            'consultation_fee' => $get('temp_fee') ?? 0,
                                            'capacity' => $get('temp_cap') ?? 1,
                                            'doctor_room' => $get('temp_room') ?? null,
                                            'is_recurring' => $isRec,
                                            'date' => $normalizedDate,
                                            'recurring_months' => $get('temp_months') ?? 3,
                                            'is_available' => $get('temp_active') ?? true,
                                            'is_editing' => false,
                                        ];

                                        // Calculate recurring dates for immediate display
                                        if ($isRec) {
                                            $months = (int) ($get('temp_months') ?? 3);

                                            $startDate = \Carbon\Carbon::now();

                                            $selectedDow = \Carbon\Carbon::parse($day)->dayOfWeek;
                                            $todayDow = $startDate->dayOfWeek;

                                            $daysToAdd = ($selectedDow - $todayDow + 7) % 7;

                                            if ($daysToAdd === 0) {
                                                $startTime = \Carbon\Carbon::parse($normalizedStart);
                                                if ($startDate->gt($startTime)) {
                                                    $daysToAdd = 7;
                                                }
                                            }

                                            $startDate = $startDate->copy()->addDays($daysToAdd);

                                            $slotData['recurring_start_date'] = $startDate->format('Y-m-d');
                                            $slotData['recurring_end_date'] = $startDate->copy()->addMonths($months)->format('Y-m-d');
                                        } else {
                                            $slotData['recurring_start_date'] = null;
                                            $slotData['recurring_end_date'] = null;
                                        }

                                        // Save to database if doctor exists
                                        if ($doctorId) {
                                            try {
                                                $dbData = array_merge($slotData, [
                                                    'doctor_id' => $doctorId,
                                                    'day_of_week' => $day,
                                                    'start_time' => $normalizedStart,
                                                    'end_time' => $normalizedEnd,
                                                ]);

                                                if ($isRec) {
                                                    $startDate = \Carbon\Carbon::parse($slotData['recurring_start_date']);

                                                    $dbData['recurring_start_date'] = $startDate->format('Y-m-d');
                                                    $dbData['recurring_end_date'] = $startDate->copy()
                                                        ->addMonths((int) $slotData['recurring_months'])
                                                        ->format('Y-m-d');

                                                    $dbData['date'] = null;
                                                } else {
                                                    $dbData['date'] = $normalizedDate;
                                                    $dbData['recurring_start_date'] = null;
                                                    $dbData['recurring_end_date'] = null;
                                                }

                                                // Again, ensure opd_type is null for video
                                                if (($dbData['consultation_type'] ?? '') === 'video') {
                                                    $dbData['opd_type'] = null;
                                                }

                                                $newRecord = \App\Models\DoctorAvailability::create($dbData);
                                                $slotData['id'] = $newRecord->id;

                                                \App\Services\NotificationService::notifyAvailabilityCreated($livewire->record, [$dbData]);

                                                Notification::make()
                                                    ->title('Consultation Slot Added')
                                                    ->body('The availability has been saved. Patients can now book appointments for this time slot.')
                                                    ->success()
                                                    ->send();
                                            } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
                                                Notification::make()
                                                    ->title('Duplicate Schedule')
                                                    ->body('This slot already exists. Please change the time, date, or consultation mode and try again.')
                                                    ->danger()
                                                    ->send();

                                                return;
                                            } catch (\Throwable $e) {
                                                Notification::make()
                                                    ->title('Error')
                                                    ->body('Could not save the consultation slot: ' . $e->getMessage())
                                                    ->danger()
                                                    ->send();

                                                return;
                                            }
                                        } else {
                                            // New doctor - staged for creation
                                            Notification::make()
                                                ->title('Slot Staged')
                                                ->body('Consultation slot added to the schedule. It will be saved when you create the doctor profile.')
                                                ->success()
                                                ->send();
                                        }

                                        // Update UI state
                                        $currentSlotsForDay = $get("slots_{$day}") ?? [];
                                        if (! is_array($currentSlotsForDay)) {
                                            $currentSlotsForDay = [];
                                        }
                                        $currentSlotsForDay[] = $slotData;
                                        $set("slots_{$day}", $currentSlotsForDay);

                                        // Reset form fields
                                        $set('temp_start', null);
                                        $set('temp_end', null);
                                        $set('temp_date', null);
                                        $set('temp_rec', false);
                                        $set('temp_fee', 0);
                                    }),
                            ])->columns(4)->extraAttributes(['class' => 'ms-auto fi-align-end']),

                        ]),
                ])->columnSpanFull(),
        ];
    }
}
