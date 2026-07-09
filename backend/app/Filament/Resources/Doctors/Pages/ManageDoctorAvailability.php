<?php

namespace App\Filament\Resources\Doctors\Pages;

use App\Enums\AppointmentStatus;
use App\Enums\DayOfWeek;
use App\Filament\Resources\Doctors\DoctorResource;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\DoctorAvailability;
use App\Models\DoctorAvailabilityOverride;
use App\Models\Setting;
use App\Services\DoctorAvailabilityService;
use App\Services\DoctorAvailabilityValidationService;
use App\Services\SettingService;
use App\Services\SlotBookingCutoffService;
use App\Services\SlotCapacityService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Url;

class ManageDoctorAvailability extends Page
{
    use InteractsWithRecord {
        afterActionCalled as afterRecordActionCalled;
        getBreadcrumbs as getRecordBreadcrumbs;
        getMountedActionSchemaModel as getRecordMountedActionSchemaModel;
        getRecordTitle as getRecordTitleForRecord;
        getSubNavigation as getRecordSubNavigation;
        getSubNavigationParameters as getRecordSubNavigationParameters;
        getWidgetData as getRecordWidgetData;
        mountCanAuthorizeAccess as mountCanAuthorizeRecordAccess;
    }

    protected static string $resource = DoctorResource::class;

    protected string $view = 'filament.resources.doctors.pages.manage-doctor-availability';

    protected static ?string $title = 'Manage Availability';

    public ?string $availabilityFilter = null;

    #[Url(as: 'doctor')]
    public ?string $doctorFilter = null;

    public string $statusFilter = 'all';

    public string $scheduleTypeFilter = 'all';

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public ?string $dayFilter = null;

    public array $selectedRows = [];

    public bool $showFilters = false;

    public bool $allDaysSelected = false;

    public bool $doctorScopedFromUrl = false;

    public ?string $activeDate = null;

    public bool $hasOpenDatesState = false;

    // Track which date-groups are open to avoid accordion collapsing on Livewire updates
    public array $openDates = [];

    public bool $allDatesExpanded = false;

    public string $slotView = 'upcoming';

    public int|string|null $childAge = null;

    public int $availabilityRefreshVersion = 0;

    public function mount(int|string|null $record = null): void
    {
        if ($record) {
            $this->record = $this->resolveRecord($record);
            $this->doctorFilter = $this->getRecord()->id;
            $this->dateTo = now()->addMonths(3)->toDateString();
        } else {
            $this->record = null;
            $this->doctorScopedFromUrl = filled($this->doctorFilter);
            $this->dateTo = now()->toDateString();
        }

        $this->dateFrom = now()->toDateString();
        $this->dayFilter = '';
        $this->childAge = $this->globalChildAgeLimit();
    }

    public function defaultDayKey(): string
    {
        return strtolower(now()->format('l'));
    }

    public function isDayFilterApplied(): bool
    {
        return filled($this->dayFilter);
    }

    public function updatedDateFrom(?string $value): void
    {
        if (! $value) {
            return;
        }

        $this->selectedRows = [];
    }

    public function updatedAvailabilityFilter(?string $value): void
    {
        if (! $value) {
            return;
        }

        $availability = $this->baseAvailabilityQuery()->where('id', $value)->first();

        if (! $availability) {
            return;
        }

        if ($this->isRecurringTemplate($availability)) {
            $this->dayFilter = app(DoctorAvailabilityService::class)->recurringDayOfWeek($availability, now());
            $this->allDaysSelected = false;

            return;
        }

        if ($availability->date) {
            $date = Carbon::parse($availability->date);
            $this->dateFrom = $date->toDateString();
            $this->dateTo = $date->toDateString();
            $this->dayFilter = strtolower($date->format('l'));
            $this->allDaysSelected = false;
        }
    }

    public function updatedDoctorFilter(?string $value): void
    {
        $this->availabilityFilter = null;
        $this->selectedRows = [];
    }

    public function clearFilters(): void
    {
        $this->availabilityFilter = null;
        $this->doctorFilter = $this->hasDoctorRecord()
            ? $this->getRecord()->id
            : ($this->doctorScopedFromUrl ? $this->doctorFilter : null);
        $this->statusFilter = 'all';
        $this->scheduleTypeFilter = 'all';
        $this->dateFrom = $this->slotView === 'passed' ? now()->subMonths(3)->toDateString() : now()->toDateString();
        $this->dateTo = $this->slotView === 'passed' || ! $this->hasDoctorRecord()
            ? now()->toDateString()
            : now()->addMonths(3)->toDateString();
        $this->dayFilter = '';
        $this->allDaysSelected = false;
        $this->selectedRows = [];
    }

    public function filterByDay(?string $day = null): void
    {
        $this->dayFilter = $day ?? '';
        $this->allDaysSelected = $day === null;

        if ($this->isGlobalManager() && $this->slotView === 'upcoming') {
            $this->dateFrom = now()->toDateString();
            $this->dateTo = now()->addMonths(3)->toDateString();
        }

        $this->selectedRows = [];
    }

    public function toggleFilters(): void
    {
        $this->showFilters = ! $this->showFilters;
    }

    public function setSlotView(string $view): void
    {
        $this->slotView = in_array($view, ['upcoming', 'passed'], true) ? $view : 'upcoming';
        $this->selectedRows = [];
        $this->allDaysSelected = false;

        if ($this->slotView === 'passed') {
            $this->dateFrom = now()->subMonths(3)->toDateString();
            $this->dateTo = now()->toDateString();

            return;
        }

        $this->dateFrom = now()->toDateString();
        $this->dateTo = $this->hasDoctorRecord() ? now()->addMonths(3)->toDateString() : now()->toDateString();
    }

    public function getTitle(): string
    {
        if (! $this->hasDoctorRecord()) {
            return 'Manage Doctor Slots';
        }

        $name = $this->getRecord()->user?->name
            ?: trim(($this->getRecord()->first_name ?? '') . ' ' . ($this->getRecord()->last_name ?? ''));

        return trim("Manage Availability - {$name}", ' -');
    }

    public function isGlobalManager(): bool
    {
        return ! $this->hasDoctorRecord();
    }

    public function isAllDoctorsManager(): bool
    {
        return $this->isGlobalManager() && ! $this->doctorScopedFromUrl;
    }

    public function mountCanAuthorizeAccess(): void
    {
        if ($this->isGlobalManager()) {
            abort_unless(static::canAccess(), 403);

            return;
        }

        $this->mountCanAuthorizeRecordAccess();
    }

    public function getBreadcrumbs(): array
    {
        if ($this->isGlobalManager()) {
            return [];
        }

        return $this->getRecordBreadcrumbs();
    }

    public function hasRecord(): bool
    {
        return $this->hasDoctorRecord();
    }

    public function getRecordTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        if ($this->isGlobalManager()) {
            return 'Doctor Availabilities';
        }

        return $this->getRecordTitleForRecord();
    }

    /**
     * @return array<string, mixed>
     */
    public function getSubNavigationParameters(): array
    {
        if ($this->isGlobalManager()) {
            return [];
        }

        return $this->getRecordSubNavigationParameters();
    }

    public function getSubNavigation(): array
    {
        if ($this->isGlobalManager()) {
            return [];
        }

        return $this->getRecordSubNavigation();
    }

    /**
     * @return array<string, mixed>
     */
    public function getWidgetData(): array
    {
        if ($this->isGlobalManager()) {
            return [];
        }

        return $this->getRecordWidgetData();
    }

    protected function getMountedActionSchemaModel(): \Illuminate\Database\Eloquent\Model|string|null
    {
        if ($this->isGlobalManager()) {
            return DoctorAvailability::class;
        }

        return $this->getRecordMountedActionSchemaModel();
    }

    protected function afterActionCalled(Action $action): void
    {
        if ($this->isGlobalManager()) {
            parent::afterActionCalled($action);

            return;
        }

        $this->afterRecordActionCalled($action);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('backToProfile')
                ->label('Doctor Profile')
                ->icon('heroicon-o-arrow-left')
                ->url(fn() => DoctorResource::getUrl('edit', ['record' => $this->getRecord()]))
                ->visible(fn(): bool => $this->hasDoctorRecord()),

            $this->createAvailabilityAction(),
        ];
    }

    public function createAvailabilityAction(): Action
    {
        return Action::make('createAvailability')
            ->label('Add Slot')
            ->icon('heroicon-o-plus')
            ->modalWidth('3xl')
            ->modalHeading('Add availability slot')
            ->extraModalWindowAttributes(['class' => 'availability-slot-modal-window'])
            ->mountUsing(function (Schema $schema, array $arguments): void {
                $date = $arguments['date'] ?? null;
                $this->activeDate = filled($date) ? Carbon::parse($date)->toDateString() : null;
                $isRecurring = isset($arguments['date']) ? '0' : '1';
                $dayOfWeek = $date ? strtolower(Carbon::parse($date)->format('l')) : strtolower(now()->format('l'));

                $schema->fill([
                    'is_recurring' => $isRecurring,
                    'date' => $date,
                    'days_of_week' => [$dayOfWeek],
                    'doctor_id' => $arguments['doctor_id'] ?? $this->doctorFilter,
                    'is_available' => true,
                ]);
            })
            ->form($this->availabilityFormSchema(includeStatus: true))
            ->modalSubmitActionLabel('Save slot')
            ->action(function (array $data): void {
                $availability = $this->saveParentAvailability($data);

                Notification::make()
                    ->success()
                    ->title('Slot added for ' . $this->doctorName($availability->doctor))
                    ->send();
            });
    }

    public function editOccurrenceAction(): Action
    {
        return Action::make('editOccurrence')
            ->label('Edit Date')
            ->icon('heroicon-o-calendar')
            ->modalWidth('3xl')
            ->modalHeading('Edit availability date')
            ->extraModalWindowAttributes(['class' => 'availability-slot-modal-window'])
            ->modalDescription(fn(array $arguments = []): string => $this->editOccurrenceModalDescription($arguments))
            ->mountUsing(function (Schema $schema, array $arguments): void {
                $availability = $this->findAvailability($arguments['availability'] ?? null);
                $date = Carbon::parse($arguments['date'] ?? $availability->date)->toDateString();
                $effective = app(DoctorAvailabilityService::class)->effectiveValuesForDate($availability, $date);

                $isRecurring = $this->isRecurringTemplate($availability);
                $usesParentCutoff = $isRecurring
                    ? ($effective['override']?->booking_cutoff_rules === null)
                    : ($availability->booking_cutoff_rules === null);

                $rules = $isRecurring
                    ? ($usesParentCutoff ? [] : ($effective['override']?->booking_cutoff_rules ?? []))
                    : ($availability->booking_cutoff_rules ?? []);

                $firstRule = is_array($rules) && count($rules) > 0 ? $rules[0] : null;
                $cutoffValue = $firstRule['value'] ?? null;
                $cutoffUnit = $firstRule['unit'] ?? 'hours';
                $inheritedCutoffLabel = $this->bookingCutoffLabel(
                    $effective['booking_cutoff_rules'] ?? [],
                    $effective['booking_cutoff_rules_source'] ?? 'app_default',
                );

                $schema->fill([
                    'availability_id' => $availability->id,
                    'is_recurring_edit' => $isRecurring,
                    'override_date' => $date,
                    'start_time' => $this->formatTime($effective['start_time']),
                    'end_time' => $this->formatTime($effective['end_time']),
                    'capacity' => $effective['capacity'],
                    'consultation_fee' => $effective['consultation_fee'],
                    'doctor_room' => $effective['doctor_room'],
                    'is_child_only' => (bool) $availability->is_child_only,
                    'status' => $effective['status'] === 'blocked' ? 'blocked' : 'active',
                    'note' => $effective['override']?->note,
                    'inherit_booking_cutoff_rules' => $usesParentCutoff,
                    'booking_cutoff_value' => $cutoffValue,
                    'booking_cutoff_unit' => $cutoffUnit,
                    'inherited_booking_cutoff_label' => $inheritedCutoffLabel,
                ]);
            })
            ->form($this->editOccurrenceFormSchema())
            ->action(function (array $data): void {
                $availability = $this->findAvailability($data['availability_id'] ?? null);
                $date = Carbon::parse($data['override_date'] ?? $availability->date)->toDateString();
                $this->activeDate = $date;

                if (! $this->isRecurringTemplate($availability)) {
                    $this->saveParentAvailability([
                        ...$data,
                        'is_recurring' => '0',
                        'date' => $data['override_date'],
                        'day_of_week' => strtolower(Carbon::parse($data['override_date'])->format('l')),
                        'consultation_type' => $availability->consultation_type,
                        'opd_type' => $availability->opd_type,
                        'is_child_only' => $availability->is_child_only,
                        'is_available' => $data['status'] === 'active' ? '1' : '0',
                        'booking_cutoff_rules' => $data['booking_cutoff_rules'] ?? null,
                    ], $availability);
                } else {
                    $this->updateChildOnlySettings($availability, $data);
                    $this->saveOverride($availability, $data['override_date'], $data);
                }

                if ($this->isRecurringTemplate($availability) && ! $availability->is_available && ($data['status'] ?? '') === 'active') {
                    Notification::make()
                        ->warning()
                        ->title('Blocked weekly series')
                        ->body('This date belongs to a blocked weekly series. Use Edit Weekly Series on the parent slot to make it available.')
                        ->send();
                }

                $this->openDates = [$date];
                $this->hasOpenDatesState = true;
                $this->refreshAvailabilityRows();

                Notification::make()
                    ->success()
                    ->title('Slot updated for ' . $this->doctorName($availability->doctor))
                    ->body('The selected availability date was updated successfully.')
                    ->send();
            });
    }

    public function editParentAvailabilityAction(): Action
    {
        return Action::make('editParentAvailability')
            ->label('Edit Series')
            ->icon('heroicon-o-cog-6-tooth')
            ->modalWidth('3xl')
            ->modalHeading('Edit weekly availability series')
            ->extraModalWindowAttributes(['class' => 'availability-slot-modal-window'])
            ->mountUsing(function (Schema $schema, array $arguments): void {
                $availability = $this->findAvailability($arguments['availability'] ?? null);

                $rules = $availability->booking_cutoff_rules ?? [];
                $firstRule = is_array($rules) && count($rules) > 0 ? $rules[0] : null;
                $cutoffValue = $firstRule['value'] ?? null;
                $cutoffUnit = $firstRule['unit'] ?? 'hours';

                $schema->fill([
                    'availability_id' => $availability->id,
                    'start_time' => $this->formatTime($availability->start_time),
                    'end_time' => $this->formatTime($availability->end_time),
                    'capacity' => $availability->capacity,
                    'consultation_type' => $availability->consultation_type ?? 'in-person',
                    'opd_type' => $availability->opd_type ?? 'general',
                    'status' => $availability->is_available ? 'active' : 'blocked',
                    'consultation_fee' => $availability->consultation_fee,
                    'doctor_room' => $availability->doctor_room,
                    'is_child_only' => (bool) $availability->is_child_only,
                    'is_auto_recurring' => (bool) $availability->is_auto_recurring,
                    'effective_date' => now()->toDateString(),
                    'inherit_booking_cutoff_rules' => ($availability->booking_cutoff_rules === null),
                    'booking_cutoff_value' => $cutoffValue,
                    'booking_cutoff_unit' => $cutoffUnit,
                ]);
            })
            ->form([
                Hidden::make('availability_id'),
                Section::make('Series settings')
                    ->columns(1)
                    ->schema([
                        Grid::make(3)->schema([
                            DatePicker::make('effective_date')
                                ->label('Effective From Date')
                                ->helperText('All dates before this date will be locked to their current times.')
                                ->required()
                                ->default(now()->toDateString()),
                            TimePicker::make('start_time')->label('Start Time')->seconds(false)->required(),
                            TimePicker::make('end_time')->label('End Time')->seconds(false)->required(),
                        ]),
                    ]),
                Section::make('Consultation type')
                    ->columns(1)
                    ->schema([
                        Grid::make(3)->schema([
                            Select::make('consultation_type')
                                ->label('Mode')
                                ->options(['in-person' => 'In-Person', 'video' => 'Video'])
                                ->default('in-person')
                                ->live()
                                ->afterStateUpdated(function ($state, $set): void {
                                    if ($state === 'video') {
                                        $set('opd_type', null);
                                        $set('doctor_room', null);
                                        $set('is_child_only', false);
                                    }
                                })
                                ->required(),
                            Select::make('opd_type')
                                ->label('OPD Type')
                                ->options(['general' => 'General', 'private' => 'Private'])
                                ->default('general')
                                ->visible(fn($get) => ($get('consultation_type') ?? 'in-person') === 'in-person')
                                ->required(fn($get) => ($get('consultation_type') ?? 'in-person') === 'in-person'),
                            Select::make('status')
                                ->label('Status')
                                ->options([
                                    'active' => 'Active',
                                    'blocked' => 'Blocked',
                                ])
                                ->default('active')
                                ->required(),
                        ]),
                    ]),
                Section::make('Capacity & details')
                    ->columns(1)
                    ->schema([
                        Grid::make(4)->schema([
                            TextInput::make('capacity')->numeric()->minValue(1)->required(),
                            TextInput::make('consultation_fee')->label('Consultation Fee')->numeric()->minValue(0),
                            TextInput::make('doctor_room')
                                ->label('Room')
                                ->visible(fn($get) => ($get('consultation_type') ?? 'in-person') === 'in-person'),
                            Toggle::make('is_child_only')
                                ->label('Child only')
                                ->inline(false)
                                ->onColor('primary')
                                ->offColor('gray')
                                ->visible(fn($get) => ($get('consultation_type') ?? 'in-person') === 'in-person'),
                        ]),
                        Grid::make(1)->schema([
                            Toggle::make('is_auto_recurring')
                                ->label('Auto Recur')
                                ->helperText('Automatically extend this weekly recurrence when it is close to the end date.')
                                ->onColor('primary')
                                ->offColor('gray')
                                ->inline(false),
                        ]),
                    ]),
                Section::make('Booking close time')
                    ->description('Optional rule for how long before the slot starts booking closes.')
                    ->columns(1)
                    ->schema([
                        ...$this->bookingCutoffRulesFormFields(forOverride: false),
                    ]),
            ])
            ->action(function (array $data): void {
                try {
                    $availability = $this->findAvailability($data['availability_id'] ?? null);
                    $data['consultation_type'] = $data['consultation_type'] ?? $availability->consultation_type;
                    $data['opd_type'] = $data['opd_type'] ?? $availability->opd_type;
                    $this->validateParentSeriesUpdate($availability, $data);
                    $this->updateParentAvailabilitySeries($availability, $data);
                } catch (\Illuminate\Validation\ValidationException $e) {
                    // `validateParentSeriesUpdate` already shows a notification; rethrow to surface the error
                    throw $e;
                } catch (\Exception $e) {
                    Notification::make()
                        ->danger()
                        ->title('Error updating series')
                        ->body($e->getMessage())
                        ->send();

                    throw $e;
                }
            });
    }

    public function editParentSeriesAction(): Action
    {
        return Action::make('editParentSeries')
            ->label('Edit Series')
            ->icon('heroicon-o-cog-6-tooth')
            ->modalWidth('3xl')
            ->modalHeading('Edit weekly availability series')
            ->extraModalWindowAttributes(['class' => 'availability-slot-modal-window'])
            ->form([
                Section::make('Select series')
                    ->columns(1)
                    ->schema([
                        Select::make('availability_id')
                            ->label('Weekly Series')
                            ->options(function () {
                                $query = DoctorAvailability::query()
                                    ->with('doctor.user')
                                    ->where(function ($query) {
                                        $query->where('is_recurring', true)
                                            ->orWhere(function ($query) {
                                                $query->where(function ($query) {
                                                    $query->whereNull('date')
                                                        ->orWhere('date', '');
                                                })
                                                    ->whereNotNull('day_of_week')
                                                    ->where('day_of_week', '<>', '');
                                            });
                                    });

                                if ($this->hasDoctorRecord()) {
                                    $query->where('doctor_id', $this->getRecord()->id);
                                } elseif ($this->doctorFilter) {
                                    $query->where('doctor_id', $this->doctorFilter);
                                }

                                return $query
                                    ->orderBy('doctor_id')
                                    ->orderBy('day_of_week')
                                    ->orderBy('start_time')
                                    ->get()
                                    ->mapWithKeys(function (DoctorAvailability $availability) {
                                        $startTime = \Carbon\Carbon::parse($availability->start_time)->format('h:i A');
                                        $endTime = \Carbon\Carbon::parse($availability->end_time)->format('h:i A');
                                        $type = ucfirst($availability->day_of_week);
                                        $doctorPrefix = $this->doctorName($availability->doctor) . ' - ';
                                        $label = "{$doctorPrefix}{$type} | {$startTime} - {$endTime} | " . ucfirst($availability->consultation_type);
                                        return [$availability->id => $label];
                                    });
                            })
                            ->searchable()
                            ->placeholder('Search doctor, day, or time')
                            ->live()
                            ->required()
                            ->afterStateUpdated(function ($state, $set): void {
                                if (!$state) return;
                                $availability = DoctorAvailability::find($state);
                                if ($availability) {
                                    $rules = $availability->booking_cutoff_rules ?? [];
                                    $firstRule = is_array($rules) && count($rules) > 0 ? $rules[0] : null;
                                    $cutoffValue = $firstRule['value'] ?? null;
                                    $cutoffUnit = $firstRule['unit'] ?? 'hours';

                                    $set('start_time', $this->formatTime($availability->start_time));
                                    $set('end_time', $this->formatTime($availability->end_time));
                                    $set('capacity', $availability->capacity);
                                    $set('consultation_type', $availability->consultation_type ?? 'in-person');
                                    $set('opd_type', $availability->opd_type ?? 'general');
                                    $set('consultation_fee', $availability->consultation_fee);
                                    $set('doctor_room', $availability->doctor_room);
                                    $set('is_child_only', (bool) $availability->is_child_only);
                                    $set('is_available', $availability->is_available);
                                    $set('status', $availability->is_available ? 'active' : 'blocked');
                                    $set('is_auto_recurring', (bool) $availability->is_auto_recurring);
                                    $set('inherit_booking_cutoff_rules', ($availability->booking_cutoff_rules === null));
                                    $set('booking_cutoff_value', $cutoffValue);
                                    $set('booking_cutoff_unit', $cutoffUnit);
                                }
                            }),
                    ]),
                Section::make('Series settings')
                    ->columns(1)
                    ->visible(fn($get) => filled($get('availability_id')))
                    ->schema([
                        Grid::make(3)->schema([
                            DatePicker::make('effective_date')
                                ->label('Effective From Date')
                                ->helperText('All dates before this date will be locked to their current times.')
                                ->required()
                                ->default(now()->toDateString()),
                            TimePicker::make('start_time')->label('Start Time')->seconds(false)->required(),
                            TimePicker::make('end_time')->label('End Time')->seconds(false)->required(),
                        ]),
                        Grid::make(2)->schema([
                            Select::make('status')
                                ->label('Status')
                                ->options([
                                    'active' => 'Active',
                                    'blocked' => 'Blocked',
                                ])
                                ->default('active')
                                ->afterStateUpdated(function ($state, $set): void {
                                    $set('is_available', $state === 'active' || $state === true || $state === '1');
                                })
                                ->required(),
                            Toggle::make('is_auto_recurring')
                                ->label('Auto Recur')
                                ->helperText('Automatically extend this weekly recurrence when it is close to the end date.')
                                ->onColor('primary')
                                ->offColor('gray')
                                ->inline(false),
                        ]),
                    ]),
                Section::make('Consultation type')
                    ->columns(1)
                    ->visible(fn($get) => filled($get('availability_id')))
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('consultation_type')
                                ->label('Mode')
                                ->options(['in-person' => 'In-Person', 'video' => 'Video'])
                                ->default('in-person')
                                ->live()
                                ->afterStateUpdated(function ($state, $set): void {
                                    if ($state === 'video') {
                                        $set('opd_type', null);
                                        $set('doctor_room', null);
                                        $set('is_child_only', false);
                                    }
                                })
                                ->required(),
                            Select::make('opd_type')
                                ->label('OPD Type')
                                ->options(['general' => 'General', 'private' => 'Private'])
                                ->default('general')
                                ->visible(fn($get) => ($get('consultation_type') ?? 'in-person') === 'in-person')
                                ->required(fn($get) => ($get('consultation_type') ?? 'in-person') === 'in-person'),
                        ]),
                    ]),
                Section::make('Capacity & details')
                    ->columns(1)
                    ->visible(fn($get) => filled($get('availability_id')))
                    ->schema([
                        Grid::make(4)->schema([
                            TextInput::make('capacity')->numeric()->minValue(1)->required(),
                            TextInput::make('consultation_fee')->label('Consultation Fee')->numeric()->minValue(0),
                            TextInput::make('doctor_room')
                                ->label('Room')
                                ->visible(fn($get) => ($get('consultation_type') ?? 'in-person') === 'in-person'),
                            Toggle::make('is_child_only')
                                ->label('Child only')
                                ->inline(false)
                                ->onColor('primary')
                                ->offColor('gray')
                                ->visible(fn($get) => ($get('consultation_type') ?? 'in-person') === 'in-person'),
                        ]),
                        Grid::make(2)->schema([
                            Toggle::make('is_auto_recurring')
                                ->label('Auto Recur')
                                ->helperText('Automatically extend this weekly recurrence when it is close to the end date.')
                                ->onColor('primary')
                                ->offColor('gray')
                                ->inline(false),
                        ]),
                    ]),
                Section::make('Booking close time')
                    ->description('Optional rule for how long before the slot starts booking closes.')
                    ->columns(1)
                    ->visible(fn($get) => filled($get('availability_id')))
                    ->schema([
                        ...$this->bookingCutoffRulesFormFields(forOverride: false),
                    ]),
            ])
            ->action(function (array $data): void {
                if (!filled($data['availability_id'])) {
                    Notification::make()
                        ->danger()
                        ->title('Validation Error')
                        ->body('Please select a weekly series to edit.')
                        ->send();
                    return;
                }

                $availability = $this->findAvailability($data['availability_id'] ?? null);
                $data['consultation_type'] = $data['consultation_type'] ?? $availability->consultation_type;
                $data['opd_type'] = $data['opd_type'] ?? $availability->opd_type;
                $this->validateParentSeriesUpdate($availability, $data);
                $this->updateParentAvailabilitySeries($availability, $data);
            });
    }

    private function validateParentSeriesUpdate(DoctorAvailability $availability, array $data): void
    {
        $timeService = app(DoctorAvailabilityValidationService::class);
        $originalStart = $timeService->normalizeTime($availability->start_time);
        $originalEnd = $timeService->normalizeTime($availability->end_time);
        $newStart = $timeService->normalizeTime($data['start_time'] ?? null);
        $newEnd = $timeService->normalizeTime($data['end_time'] ?? null);

        $timingChanged = ($originalStart !== $newStart || $originalEnd !== $newEnd);

        if ($timingChanged) {
            $effectiveDate = Carbon::parse($data['effective_date'])->startOfDay();
            $fromAfter = $effectiveDate->copy();
            $toAfter = $availability->recurring_end_date
                ? Carbon::parse($availability->recurring_end_date)->endOfDay()
                : now()->addYears(5)->endOfDay();

            $rowsAfter = $this->recurringRows($availability, $fromAfter, $toAfter);
            $hasBookings = false;
            foreach ($rowsAfter as $row) {
                if (($row['total_booked'] ?? $row['booked'] ?? 0) > 0) {
                    $hasBookings = true;
                    break;
                }
            }

            if ($hasBookings) {
                $nextWeekDate = Carbon::parse($data['effective_date'])->addWeek()->toDateString();
                $message = "This slot already has booked appointments. To change the timing, the Effective From Date must be from next week (on or after {$nextWeekDate}).";

                Notification::make()
                    ->danger()
                    ->title('Validation Error')
                    ->body($message)
                    ->send();

                throw ValidationException::withMessages([
                    'effective_date' => $message,
                ]);
            }
        }
    }

    private function updateParentAvailabilitySeries(DoctorAvailability $availability, array $data): void
    {
        $effectiveDate = Carbon::parse($data['effective_date'])->startOfDay();

        // 1. Lock original values for dates before effective_date using overrides
        $from = $availability->recurring_start_date ? Carbon::parse($availability->recurring_start_date)->startOfDay() : now()->subYears(5)->startOfDay();
        $to = $effectiveDate->copy()->subDay()->endOfDay();

        $rowsBefore = $this->recurringRows($availability, $from, $to);
        foreach ($rowsBefore as $row) {
            $date = $row['date'];
            $effective = app(DoctorAvailabilityService::class)->effectiveValuesForDate($availability, Carbon::parse($date));

            $this->saveOverride($availability, $date, [
                'start_time' => $this->formatTime($effective['start_time']),
                'end_time' => $this->formatTime($effective['end_time']),
                'capacity' => $effective['capacity'],
                'consultation_fee' => $effective['consultation_fee'],
                'doctor_room' => $effective['doctor_room'],
                'status' => $effective['status'] === 'blocked' ? 'blocked' : 'active',
                'inherit_booking_cutoff_rules' => ($effective['override']?->booking_cutoff_rules === null),
                'booking_cutoff_value' => is_array($effective['booking_cutoff_rules']) && count($effective['booking_cutoff_rules']) > 0 ? $effective['booking_cutoff_rules'][0]['value'] : null,
                'booking_cutoff_unit' => is_array($effective['booking_cutoff_rules']) && count($effective['booking_cutoff_rules']) > 0 ? $effective['booking_cutoff_rules'][0]['unit'] : 'hours',
            ]);
        }

        // 2. Lock future dates on/after effective_date that have booked appointments
        $fromAfter = $effectiveDate->copy();
        $toAfter = $availability->recurring_end_date ? Carbon::parse($availability->recurring_end_date)->endOfDay() : now()->addYears(5)->endOfDay();

        $rowsAfter = $this->recurringRows($availability, $fromAfter, $toAfter);
        $lockedDates = [];
        foreach ($rowsAfter as $row) {
            $date = $row['date'];
            $bookedCount = $row['booked'];
            if ($bookedCount > 0) {
                $effective = app(DoctorAvailabilityService::class)->effectiveValuesForDate($availability, Carbon::parse($date));

                $this->saveOverride($availability, $date, [
                    'start_time' => $this->formatTime($effective['start_time']),
                    'end_time' => $this->formatTime($effective['end_time']),
                    'capacity' => $effective['capacity'],
                    'consultation_fee' => $effective['consultation_fee'],
                    'doctor_room' => $effective['doctor_room'],
                    'status' => $effective['status'] === 'blocked' ? 'blocked' : 'active',
                    'inherit_booking_cutoff_rules' => ($effective['override']?->booking_cutoff_rules === null),
                    'booking_cutoff_value' => is_array($effective['booking_cutoff_rules']) && count($effective['booking_cutoff_rules']) > 0 ? $effective['booking_cutoff_rules'][0]['value'] : null,
                    'booking_cutoff_unit' => is_array($effective['booking_cutoff_rules']) && count($effective['booking_cutoff_rules']) > 0 ? $effective['booking_cutoff_rules'][0]['unit'] : 'hours',
                ]);
                $lockedDates[] = Carbon::parse($date)->format('d M Y');
            } else {
                // Delete override if it has no bookings to let it inherit new parent values
                DoctorAvailabilityOverride::query()
                    ->where('doctor_availability_id', $availability->id)
                    ->whereDate('override_date', $date)
                    ->delete();
            }
        }

        // 3. Save the new values to the parent series availability record
        $this->saveParentAvailability([
            ...$data,
            'is_recurring' => '1',
            'day_of_week' => $availability->day_of_week,
            'consultation_type' => $data['consultation_type'] ?? $availability->consultation_type,
            'opd_type' => $data['opd_type'] ?? $availability->opd_type,
            'is_available' => ($data['status'] ?? 'active') === 'active' ? '1' : '0',
        ], $availability);

        $body = 'Parent series updated successfully starting ' . $effectiveDate->format('d M Y') . '.';
        if (count($lockedDates) > 0) {
            $body .= ' Note: ' . count($lockedDates) . ' future dates (' . implode(', ', array_slice($lockedDates, 0, 3)) . (count($lockedDates) > 3 ? '...' : '') . ') had existing booked appointments and were locked to their original parameters.';
        }

        Notification::make()
            ->success()
            ->title('Weekly Series Updated')
            ->body($body)
            ->send();
    }

    public function extendSeriesAction(): Action
    {
        return Action::make('extendSeries')
            ->label('Extend Recurrence')
            ->icon('heroicon-o-arrow-path')
            ->modalHeading('Extend Weekly Recurrence')
            ->modalDescription('Extend the end date of this recurring slot series.')
            ->mountUsing(function (Schema $schema, array $arguments): void {
                $availability = $this->findAvailability($arguments['availability'] ?? null);
                $schema->fill([
                    'availability_id' => $availability->id,
                    'months' => 3,
                ]);
            })
            ->form([
                Hidden::make('availability_id'),
                Select::make('months')
                    ->label('Extend Duration By')
                    ->options([
                        3 => '3 months',
                        6 => '6 months',
                        12 => '12 months',
                    ])
                    ->default(3)
                    ->required(),
            ])
            ->action(function (array $data): void {
                $availability = $this->findAvailability($data['availability_id'] ?? null);
                if ($availability->recurring_end_date) {
                    $currentEnd = Carbon::parse($availability->recurring_end_date);
                    $newEnd = $currentEnd->copy()->addMonths((int)$data['months'])->toDateString();
                    $availability->update([
                        'recurring_end_date' => $newEnd,
                    ]);

                    Notification::make()
                        ->success()
                        ->title('Weekly Series Extended')
                        ->body('The series recurrence has been extended to ' . Carbon::parse($newEnd)->format('d M Y') . '.')
                        ->send();
                }
            });
    }

    public function blockOccurrenceAction(): Action
    {
        return Action::make('blockOccurrence')
            ->label('Block Date')
            ->icon('heroicon-o-no-symbol')
            ->color('warning')
            ->requiresConfirmation()
            ->modalDescription(fn(array $arguments = []): ?string => $this->appointmentWarningForArguments($arguments, 'block'))
            ->action(function (array $arguments): void {
                $availability = $this->findAvailability($arguments['availability'] ?? null);
                $date = Carbon::parse($arguments['date'] ?? $availability->date)->toDateString();
                $this->activeDate = $date;

                if ($this->isRecurringTemplate($availability)) {
                    $this->saveOverride($availability, $date, ['status' => 'blocked']);
                } else {
                    $availability->update(['is_available' => false]);
                }

                Notification::make()->warning()->title('Availability blocked')->send();
            });
    }

    public function restoreOccurrenceAction(): Action
    {
        return Action::make('restoreOccurrence')
            ->label('Restore')
            ->icon('heroicon-o-arrow-uturn-left')
            ->color('success')
            ->requiresConfirmation()
            ->modalDescription('This will restore the date to the recurring schedule (or re-activate a one-time slot).')
            ->action(function (array $arguments): void {
                $availability = $this->findAvailability($arguments['availability'] ?? null);
                $date = Carbon::parse($arguments['date'] ?? $availability->date)->toDateString();
                $this->activeDate = $date;

                if ($this->isRecurringTemplate($availability)) {
                    DoctorAvailabilityOverride::query()
                        ->where('doctor_availability_id', $availability->id)
                        ->whereDate('override_date', $date)
                        ->delete();
                } else {
                    if ($availability->trashed()) {
                        $availability->restore();
                    }

                    $availability->update(['is_available' => true]);
                }

                Notification::make()->success()->title('Slot restored')->send();
            });
    }

    public function unblockOccurrenceAction(): Action
    {
        return Action::make('unblockOccurrence')
            ->label('Unblock')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->requiresConfirmation()
            ->action(function (array $arguments): void {
                $availability = $this->findAvailability($arguments['availability'] ?? null);
                $date = Carbon::parse($arguments['date'] ?? $availability->date)->toDateString();
                $this->activeDate = $date;

                if ($this->isRecurringTemplate($availability)) {
                    $override = DoctorAvailabilityOverride::query()
                        ->where('doctor_availability_id', $availability->id)
                        ->whereDate('override_date', $date)
                        ->first();

                    if ($override && $override->status === 'blocked') {
                        if ($this->overrideOnlyBlocks($override)) {
                            $override->delete();
                        } else {
                            $override->update(['status' => 'active']);
                        }
                    }
                } else {
                    $availability->update(['is_available' => true]);
                }

                Notification::make()->success()->title('Slot unblocked')->send();
            });
    }


    public function resetOccurrenceAction(): Action
    {
        return Action::make('resetOccurrence')
            ->label('Reset Date')
            ->icon('heroicon-o-arrow-path')
            ->color('gray')
            ->requiresConfirmation()
            ->modalDescription(fn(array $arguments = []): ?string => $this->appointmentWarningForArguments($arguments, 'reset'))
            ->action(function (array $arguments): void {
                $availability = $this->findAvailability($arguments['availability'] ?? null);
                $date = Carbon::parse($arguments['date'] ?? null)->toDateString();
                $this->activeDate = $date;

                DoctorAvailabilityOverride::query()
                    ->where('doctor_availability_id', $availability->id)
                    ->whereDate('override_date', $date)
                    ->delete();

                Notification::make()->success()->title('Date reset to recurring rule')->send();
            });
    }

    public function deleteOccurrenceAction(): Action
    {
        return Action::make('deleteOccurrence')
            ->label('Delete Date')
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->requiresConfirmation()
            ->modalDescription(fn(array $arguments = []): string => ($this->appointmentWarningForArguments($arguments, 'delete') ?: 'For recurring slots, only this date is removed. For one-time slots, the slot is deleted. Existing appointments remain in appointment history.'))
            ->action(function (array $arguments): void {
                $availability = $this->findAvailability($arguments['availability'] ?? null);
                $date = Carbon::parse($arguments['date'] ?? $availability->date)->toDateString();
                $this->activeDate = $date;

                $this->deleteDate($availability, $date);

                Notification::make()->success()->title('Availability date deleted')->send();
            });
    }

    public function bulkEditSelectedAction(): Action
    {
        return Action::make('bulkEditSelected')
            ->label('Bulk Edit')
            ->icon('heroicon-o-pencil-square')
            ->modalWidth('2xl')
            ->modalDescription(fn(): string => $this->bulkEditSelectedModalDescription())
            ->form([
                Grid::make(2)->schema([
                    TimePicker::make('start_time')->label('Start Time')->seconds(false),
                    TimePicker::make('end_time')->label('End Time')->seconds(false),
                    TextInput::make('capacity')->numeric()->minValue(1),
                    TextInput::make('consultation_fee')->label('Fee')->numeric()->minValue(0),
                    TextInput::make('doctor_room')->label('Room'),
                    Select::make('status')
                        ->options([
                            'active' => 'Available',
                            'blocked' => 'Blocked',
                            'cancelled' => 'Deleted',
                        ])
                        ->placeholder('Do not change'),
                ]),
                ...$this->bookingCutoffRulesFormFields(forOverride: true, includeInheritToggle: false),
                Textarea::make('note')->rows(3)->placeholder('Optional note for overrides'),
            ])
            ->action(function (array $data): void {
                if (isset($data['status']) && $data['status'] === 'active' && $this->selectedRowsContainBlockedParentSeries()) {
                    Notification::make()
                        ->warning()
                        ->title('Blocked weekly series')
                        ->body('Some selected dates belong to a blocked weekly series. Use Edit Weekly Series on the parent slot to make the slot available.')
                        ->send();
                }

                $count = $this->bulkUpdateSelected($data);

                Notification::make()
                    ->success()
                    ->title("{$count} selected date(s) updated")
                    ->send();
            });
    }

    public function bulkDeleteSelected(): void
    {
        $warning = $this->bulkAppointmentWarning('delete');

        $count = $this->forEachSelectedRow(function (DoctorAvailability $availability, string $date): void {
            $this->deleteDate($availability, $date);
        });

        $this->selectedRows = [];

        Notification::make()
            ->success()
            ->title("{$count} selected date(s) deleted")
            ->body($warning)
            ->send();
    }

    public function bulkEditSelectedModalDescription(): string
    {
        $message = $this->bulkAppointmentWarning('edit');

        if ($this->selectedRowsContainBlockedParentSeries()) {
            $message = ($message ? $message . ' ' : '') . 'Some selected dates belong to a blocked weekly series. Use Edit Weekly Series on the parent slot to make the slot available before changing child dates.';
        }

        return $message ?: 'Only filled fields will be changed. Recurring dates are updated as date-specific overrides.';
    }

    public function selectedRowsContainBlockedParentSeries(): bool
    {
        foreach ($this->selectedRows as $rowKey) {
            [$availabilityId, $date] = array_pad(explode('|', (string) $rowKey, 2), 2, null);
            if (! $availabilityId || ! $date) {
                continue;
            }

            $availability = $this->baseAvailabilityQuery()->where('id', $availabilityId)->first();
            if (! $availability || ! $this->isRecurringTemplate($availability)) {
                continue;
            }

            if (! $availability->is_available) {
                return true;
            }
        }

        return false;
    }

    public function editOccurrenceModalDescription(array $arguments = []): string
    {
        $message = $this->appointmentWarningForArguments($arguments, 'edit') ?: '';
        $availability = $this->baseAvailabilityQuery()->where('id', $arguments['availability'] ?? null)->first();

        if ($availability && $this->isRecurringTemplate($availability) && ! $availability->is_available) {
            $note = 'This date belongs to a blocked weekly series. Use Edit Weekly Series on the parent slot to make it available.';
            $message = trim(($message ? $message . ' ' : '') . $note);
        }

        return $message ?: 'Only filled fields will be changed.';
    }

    public function bulkBlockSelected(): void
    {
        $warning = $this->bulkAppointmentWarning('block');

        $count = $this->forEachSelectedRow(function (DoctorAvailability $availability, string $date): void {
            if ($this->isRecurringTemplate($availability)) {
                $this->saveOverride($availability, $date, ['status' => 'blocked']);

                return;
            }

            $availability->update(['is_available' => false]);
        });

        $this->selectedRows = [];

        Notification::make()
            ->warning()
            ->title("{$count} selected date(s) blocked")
            ->body($warning)
            ->send();
    }

    public function clearSelection(): void
    {
        $this->selectedRows = [];
    }

    public function saveGlobalChildAge(): void
    {
        $age = (int) $this->childAge;

        if ($age < 1 || $age > 18) {
            throw ValidationException::withMessages([
                'childAge' => 'Child age must be between 1 and 18 years.',
            ]);
        }

        Setting::setValue(
            group: 'booking',
            key: 'child_age',
            value: $age,
            type: 'integer',
            description: 'Global age limit used by all child-only OPD slots.',
            isPublic: true,
        );

        $this->childAge = $age;

        Notification::make()
            ->success()
            ->title('Global child age updated')
            ->body("Child-only OPD slots now use age {$age} years.")
            ->send();
    }

    public function selectVisibleRows(): void
    {
        $this->selectedRows = $this->rows
            ->pluck('row_key')
            ->values()
            ->all();

        // keep the visible date groups open when selecting visible rows
        $this->openDates = $this->rows->groupBy('date')->keys()->all();
        $this->hasOpenDatesState = true;
    }

    public function toggleDate(string $date): void
    {
        $this->hasOpenDatesState = true;

        if (in_array($date, $this->openDates, true)) {
            $this->openDates = array_values(array_filter($this->openDates, fn($d) => $d !== $date));

            return;
        }

        $this->openDates[] = $date;
    }

    public function openAllDates(): void
    {
        $this->openDates = $this->rows->groupBy('date')->keys()->all();
        $this->allDatesExpanded = true;
        $this->hasOpenDatesState = true;
    }

    public function closeAllDates(): void
    {
        $this->openDates = [];
        $this->allDatesExpanded = false;
        $this->hasOpenDatesState = true;
    }

    public function toggleAllDates(): void
    {
        if ($this->allDatesExpanded) {
            $this->closeAllDates();
        } else {
            $this->openAllDates();
        }
    }

    #[\Livewire\Attributes\On('setOpenDates')]
    public function setOpenDates(array $dates = []): void
    {
        $this->openDates = $dates;
        $this->hasOpenDatesState = true;
        $this->allDatesExpanded = count($dates) === $this->rows->groupBy('date')->count();
    }

    public function toggleSelectAll(): void
    {
        $visibleKeys = $this->rows->pluck('row_key')->values()->all();

        // if all visible are selected, clear; otherwise select all visible and open date groups
        if (count(array_intersect($visibleKeys, $this->selectedRows)) === count($visibleKeys) && count($visibleKeys) > 0) {
            $this->selectedRows = [];
            $this->openDates = [];
            $this->hasOpenDatesState = true;

            return;
        }

        $this->selectedRows = $visibleKeys;
        $this->openDates = $this->rows->groupBy('date')->keys()->all();
        $this->hasOpenDatesState = true;
    }

    public function toggleSelectDay(string $date): void
    {
        $dayRowKeys = $this->rows->where('date', $date)->pluck('row_key')->toArray();

        if (empty($dayRowKeys)) {
            return;
        }

        // Check if all rows for this day are already selected
        $allDaySelected = count(array_intersect($dayRowKeys, $this->selectedRows)) === count($dayRowKeys);

        if ($allDaySelected) {
            // Deselect all rows for this day
            $this->selectedRows = array_diff($this->selectedRows, $dayRowKeys);
        } else {
            // Select all rows for this day
            $this->selectedRows = array_unique(array_merge($this->selectedRows, $dayRowKeys));
        }
    }

    public function getAvailabilityOptionsProperty(): array
    {
        return $this->baseAvailabilityQuery()
            ->with('doctor.user')
            ->orderByDesc('is_recurring')
            ->orderBy('day_of_week')
            ->orderBy('date')
            ->orderBy('start_time')
            ->get()
            ->mapWithKeys(fn(DoctorAvailability $availability) => [
                $availability->id => $this->availabilityLabel($availability),
            ])
            ->all();
    }

    public function getDoctorOptionsProperty(): array
    {
        return Doctor::query()
            ->with('user:id,name')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get()
            ->mapWithKeys(fn(Doctor $doctor) => [
                $doctor->id => $this->doctorName($doctor),
            ])
            ->all();
    }

    public function getSelectedDoctorProfileUrlProperty(): ?string
    {
        if (! $this->isAllDoctorsManager() || ! $this->doctorFilter) {
            return null;
        }

        $doctor = Doctor::query()->find($this->doctorFilter);

        return $doctor ? DoctorResource::getUrl('edit', ['record' => $doctor]) : null;
    }

    public function getRowsProperty(): Collection
    {
        $from = Carbon::parse($this->dateFrom ?: now())->startOfDay();
        $to = Carbon::parse($this->dateTo ?: now()->addMonths(3))->endOfDay();

        $rows = collect();

        $this->baseAvailabilityQuery()
            ->with('overrides')
            ->when($this->availabilityFilter, fn($query) => $query->where('id', $this->availabilityFilter))
            ->get()
            ->each(function (DoctorAvailability $availability) use ($from, $to, $rows): void {
                if ($availability->is_recurring && $availability->is_auto_recurring && $availability->recurring_end_date) {
                    $endDate = Carbon::parse($availability->recurring_end_date)->startOfDay();
                    $today = now()->startOfDay();
                    if ($endDate->diffInDays($today, false) >= -7) {
                        $months = $availability->recurring_months ?: 3;
                        $newEnd = $endDate->copy()->addMonths($months)->toDateString();
                        $availability->update([
                            'recurring_end_date' => $newEnd,
                        ]);
                        $availability->recurring_end_date = Carbon::parse($newEnd);
                    }
                }

                if ($this->isRecurringTemplate($availability)) {
                    $rows->push(...$this->recurringRows($availability, $from, $to));

                    return;
                }

                if (! $availability->date) {
                    return;
                }

                $date = Carbon::parse($availability->date);
                if ($date->betweenIncluded($from, $to)) {
                    $rows->push($this->rowForAvailability($availability, $date, null));
                }
            });

        return $rows
            ->filter(fn(array $row) => $this->slotView === 'passed' ? $row['is_passed'] : ! $row['is_passed'])
            ->filter(fn(array $row) => ! $this->isDayFilterApplied() || $row['day_key'] === $this->dayFilter)
            ->filter(fn(array $row) => match ($this->statusFilter) {
                'modified' => $row['source'] === 'override',
                'blocked' => $row['status'] === 'blocked',
                'deleted' => $row['status'] === 'cancelled',
                'available' => $row['status'] === 'active',
                default => true,
            })
            ->filter(fn(array $row) => match ($this->scheduleTypeFilter) {
                'recurring' => $row['is_recurring'],
                'one-time' => ! $row['is_recurring'],
                default => true,
            })
            ->sortBy([['date', 'asc'], ['start_time', 'asc']])
            ->values();
    }

    public function getGroupedRowsProperty(): Collection
    {
        return $this->rows->groupBy('date');
    }

    public function getSummaryProperty(): array
    {
        $rows = $this->rows;

        return [
            'total' => $rows->count(),
            'available' => $rows->where('status', 'active')->count(),
            'blocked' => $rows->where('status', 'blocked')->count(),
            'deleted' => $rows->where('status', 'cancelled')->count(),
            'modified' => $rows->where('source', 'override')->count(),
        ];
    }

    public function getActiveFilterCountProperty(): int
    {
        return collect([
            $this->availabilityFilter,
            ! $this->hasDoctorRecord() && $this->doctorFilter ? $this->doctorFilter : null,
            $this->statusFilter !== 'all' ? $this->statusFilter : null,
            $this->scheduleTypeFilter !== 'all' ? $this->scheduleTypeFilter : null,
            $this->isDayFilterApplied() ? $this->dayFilter : null,
        ])->filter()->count();
    }

    private function recurringRows(DoctorAvailability $availability, Carbon $from, Carbon $to): array
    {
        $start = $availability->recurring_start_date ? Carbon::parse($availability->recurring_start_date)->startOfDay() : $from->copy();
        $end = $availability->recurring_end_date ? Carbon::parse($availability->recurring_end_date)->endOfDay() : $to->copy();
        $rangeStart = $from->greaterThan($start) ? $from->copy() : $start;
        $rangeEnd = $to->lessThan($end) ? $to->copy() : $end;

        if ($rangeStart->gt($rangeEnd)) {
            return [];
        }

        $dayNumbers = [
            'sunday' => Carbon::SUNDAY,
            'monday' => Carbon::MONDAY,
            'tuesday' => Carbon::TUESDAY,
            'wednesday' => Carbon::WEDNESDAY,
            'thursday' => Carbon::THURSDAY,
            'friday' => Carbon::FRIDAY,
            'saturday' => Carbon::SATURDAY,
        ];
        $day = app(DoctorAvailabilityService::class)->recurringDayOfWeek($availability, $start);
        $targetDow = $dayNumbers[$day] ?? $start->dayOfWeek;
        $current = $rangeStart->copy();

        if ($current->dayOfWeek !== $targetDow) {
            $current->next($targetDow);
        }

        $overrides = $availability->overrides->keyBy(fn(DoctorAvailabilityOverride $override) => $override->override_date->format('Y-m-d'));
        $rows = [];

        while ($current->lte($rangeEnd)) {
            $date = $current->copy();
            $rows[] = $this->rowForAvailability($availability, $date, $overrides->get($date->toDateString()));
            $current->addWeek();
        }

        return $rows;
    }

    private function rowForAvailability(DoctorAvailability $availability, Carbon $date, ?DoctorAvailabilityOverride $override): array
    {
        $effective = app(DoctorAvailabilityService::class)->effectiveValuesForDate($availability, $date);
        $isRecurring = $this->isRecurringTemplate($availability);
        $status = $effective['status'] === 'cancelled'
            ? 'cancelled'
            : ($effective['status'] === 'blocked' || ! $availability->is_available ? 'blocked' : 'active');

        $bookedDetails = $this->bookedCountsDetail($availability, $date, $effective['start_time'], $availability->consultation_type);
        $cutoffSource = $effective['booking_cutoff_rules_source'] ?? 'app_default';

        $isEndingSoon = false;
        $endingSoonDate = null;
        if ($isRecurring && !$availability->is_auto_recurring && $availability->recurring_end_date) {
            $endDate = Carbon::parse($availability->recurring_end_date)->startOfDay();
            $today = now()->startOfDay();
            $diffDays = $today->diffInDays($endDate, false);
            if ($diffDays <= 14) {
                $isEndingSoon = true;
                $endingSoonDate = $endDate->format('d M Y');
            }
        }

        $normalizedConsultationType = trim(strtolower($availability->consultation_type ?? 'in-person'));
        $normalizedConsultationType = str_contains($normalizedConsultationType, 'video') ? 'video' : 'in-person';
        $normalizedOpdType = trim((string) ($availability->opd_type ?? ''));

        return [
            'row_key' => $availability->id . '|' . $date->toDateString(),
            'availability_id' => $availability->id,
            'date' => $date->toDateString(),
            'day' => $date->format('l'),
            'day_key' => strtolower($date->format('l')),
            'start_time' => $this->formatTime($effective['start_time']),
            'end_time' => $this->formatTime($effective['end_time']),
            'base_capacity' => $availability->capacity ?? 1,
            'capacity' => $effective['capacity'],
            'booked' => $this->bookedCount($availability, $date, $effective['start_time'], $normalizedConsultationType),
            'internal_booked' => $bookedDetails['internal'],
            'external_booked' => $bookedDetails['external'],
            'total_booked' => $bookedDetails['total'],
            'is_passed' => $this->isRowPassed($date, $effective['end_time']),
            'source' => $override ? 'override' : ($isRecurring ? 'recurring' : 'one-time'),
            'status' => $status,
            'blocked_parent' => $isRecurring && ! $availability->is_available,
            'is_recurring' => $isRecurring,
            'is_ending_soon' => $isEndingSoon,
            'ending_soon_date' => $endingSoonDate,
            'is_auto_recurring' => (bool) $availability->is_auto_recurring,
            'type' => $normalizedConsultationType,
            'consultation_type' => $normalizedConsultationType,
            'opd_type' => $normalizedOpdType,
            'is_child_only' => (bool) $availability->is_child_only,
            'child_age' => $availability->is_child_only ? $this->globalChildAgeLimit() : null,
            'fee' => $effective['consultation_fee'],
            'room' => $effective['doctor_room'],
            'label' => $this->availabilityLabel($availability, $override),
            'doctor_id' => $availability->doctor_id,
            'doctor_name' => $this->doctorName($availability->doctor),
            'doctor_profile_url' => $availability->doctor
                ? DoctorResource::getUrl('edit', ['record' => $availability->doctor])
                : null,
            'booking_cutoff_source' => $cutoffSource,
            'booking_cutoff_label' => $this->bookingCutoffLabel($effective['booking_cutoff_rules'] ?? [], $cutoffSource),
        ];
    }

    private function bookedCount(
        DoctorAvailability $availability,
        Carbon $date,
        mixed $startTime = null,
        ?string $consultationType = null
    ): int {
        return app(SlotCapacityService::class)->bookedCount(
            doctorId: $availability->doctor_id,
            date: $date,
            startTime: $startTime ?? $availability->start_time,
            availabilityId: $availability->id,
            consultationType: $consultationType ?? $availability->consultation_type,
        );
    }

    private function bookedCountsDetail(
        DoctorAvailability $availability,
        Carbon $date,
        mixed $startTime = null,
        ?string $consultationType = null
    ): array {
        return app(SlotCapacityService::class)->bookedCountsDetail(
            doctorId: $availability->doctor_id,
            date: $date,
            startTime: $startTime ?? $availability->start_time,
            availabilityId: $availability->id,
            consultationType: $consultationType ?? $availability->consultation_type,
        );
    }

    private function isRowPassed(Carbon $date, mixed $endTime): bool
    {
        if (! $endTime) {
            return $date->isBefore(now()->startOfDay());
        }

        return Carbon::parse($date->toDateString() . ' ' . Carbon::parse($endTime)->format('H:i:s'))->isPast();
    }

    private function appointmentWarningForArguments(array $arguments, string $action): ?string
    {
        $availability = $this->baseAvailabilityQuery()->where('id', $arguments['availability'] ?? null)->first();

        if (! $availability) {
            return null;
        }

        $date = Carbon::parse($arguments['date'] ?? $availability->date)->toDateString();
        $count = $this->bookedCount($availability, Carbon::parse($date));

        if ($count === 0) {
            return null;
        }

        return "Warning: this date already has {$count} appointment(s). If you {$action} this slot, existing appointments remain in history but the available schedule shown to users will change.";
    }

    private function bulkAppointmentWarning(string $action): ?string
    {
        $appointments = 0;

        $this->forEachSelectedRow(function (DoctorAvailability $availability, string $date) use (&$appointments): void {
            $appointments += $this->bookedCount($availability, Carbon::parse($date));
        });

        if ($appointments === 0) {
            return null;
        }

        return "Warning: selected dates include {$appointments} appointment(s). If you {$action} these slots, existing appointments remain in history but the available schedule shown to users will change.";
    }

    private function saveParentAvailability(array $data, ?DoctorAvailability $availability = null): DoctorAvailability
    {
        $doctorId = $data['doctor_id']
            ?? $availability?->doctor_id
            ?? $this->doctorFilter
            ?? ($this->hasDoctorRecord() ? $this->getRecord()->id : null);

        if (blank($doctorId)) {
            throw ValidationException::withMessages([
                'doctor_id' => 'Please select a doctor before adding this slot.',
            ]);
        }

        // Normalize days selection early so presence of days implies recurring
        $selectedDays = null;
        if (array_key_exists('days_of_week', $data)) {
            $raw = $data['days_of_week'];

            if (is_array($raw)) {
                $arr = $raw;
            } elseif (is_string($raw)) {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    $arr = $decoded;
                } else {
                    $arr = array_filter(array_map('trim', explode(',', $raw)));
                }
            } else {
                $arr = [$raw];
            }

            $arr = array_values(array_filter(array_map(fn($d) => strtolower((string) $d), $arr)));
            $selectedDays = count($arr) ? $arr : null;
        }

        $isRecurring = ($data['is_recurring'] ?? '0') === '1' || ($data['is_recurring'] ?? false) === true || (is_array($selectedDays) && count($selectedDays) > 0);
        $date = $isRecurring ? null : ($data['date'] ?? null);
        $day = strtolower($data['day_of_week'] ?? ($date ? Carbon::parse($date)->format('l') : ($selectedDays[0] ?? 'monday')));
        $consultationType = strtolower($data['consultation_type'] ?? 'in-person') === 'video' ? 'video' : 'in-person';
        $recurringStartDate = $isRecurring ? $this->nextDateForDay($day)->toDateString() : null;
        $recurringEndDate = $isRecurring
            ? Carbon::parse($recurringStartDate)->addMonths((int) ($data['recurring_months'] ?? 3))->subDay()->toDateString()
            : null;

        $cutoffRules = null;
        if (! ($data['inherit_booking_cutoff_rules'] ?? true)) {
            if (isset($data['booking_cutoff_value']) && isset($data['booking_cutoff_unit']) && (int)$data['booking_cutoff_value'] > 0) {
                $cutoffRules = [
                    [
                        'value' => (int) $data['booking_cutoff_value'],
                        'unit' => $data['booking_cutoff_unit'],
                    ]
                ];
            }
        }

        // Normalize days selection (support array, JSON string, or comma-separated string)
        $selectedDays = null;
        if ($isRecurring && array_key_exists('days_of_week', $data)) {
            $raw = $data['days_of_week'];

            if (is_array($raw)) {
                $arr = $raw;
            } elseif (is_string($raw)) {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    $arr = $decoded;
                } else {
                    $arr = array_filter(array_map('trim', explode(',', $raw)));
                }
            } else {
                $arr = [$raw];
            }

            $arr = array_values(array_filter(array_map(fn($d) => strtolower((string) $d), $arr)));
            $selectedDays = count($arr) ? $arr : null;
        }

        $payload = [
            'doctor_id' => $doctorId,
            'date' => $date,
            'day_of_week' => $day,
            'start_time' => Carbon::parse($data['start_time'])->format('H:i:00'),
            'end_time' => Carbon::parse($data['end_time'])->format('H:i:00'),
            'capacity' => (int) ($data['capacity'] ?? 1),
            'consultation_type' => $consultationType,
            'opd_type' => $consultationType === 'video' ? null : ($data['opd_type'] ?? 'general'),
            'doctor_room' => $data['doctor_room'] ?? null,
            'consultation_fee' => (float) ($data['consultation_fee'] ?? 0),
            'is_child_only' => $consultationType === 'in-person'
                ? (($data['is_child_only'] ?? $availability?->is_child_only ?? false) === true
                    || ($data['is_child_only'] ?? $availability?->is_child_only ?? false) === '1')
                : false,
            'is_recurring' => $isRecurring,
            'is_auto_recurring' => $isRecurring ? (bool) ($data['is_auto_recurring'] ?? false) : false,
            'recurring_start_date' => $recurringStartDate,
            'recurring_end_date' => $recurringEndDate,
            'booking_cutoff_rules' => $cutoffRules,
        ];

        if (array_key_exists('status', $data)) {
            $payload['is_available'] = ($data['status'] === 'active' || $data['status'] === true || $data['status'] === '1');
        } else {
            $payload['is_available'] = ($data['is_available'] ?? '1') === '1' || ($data['is_available'] ?? true) === true;
        }

        if ($isRecurring && ! empty($payload['recurring_start_date']) && ! empty($payload['recurring_end_date'])) {
            $payload['recurring_months'] = Carbon::parse($payload['recurring_start_date'])->diffInMonths(Carbon::parse($payload['recurring_end_date'])) ?: 1;
        }

        // If the form submitted a days_of_week array (single or multiple), ensure the payload has a primary day_of_week for single-entry cases
        if (is_array($selectedDays) && count($selectedDays) > 0) {
            $payload['day_of_week'] = $selectedDays[0];
        } elseif (isset($data['days_of_week']) && is_array($data['days_of_week']) && count($data['days_of_week']) > 0) {
            $payload['day_of_week'] = strtolower((string) $data['days_of_week'][0]);
        }

        // Log input for debugging multi-day creation issues
        Log::debug('[ManageDoctorAvailability] saveParentAvailability input', [
            'doctor_id' => $doctorId,
            'is_recurring' => $isRecurring,
            'raw_days' => $data['days_of_week'] ?? null,
            'selectedDays' => $selectedDays,
        ]);

        if ($availability) {
            $availability->update($payload);

            return $availability;
        }

        // If creating a new recurring availability for multiple days, create one availability per day
        if ($isRecurring && is_array($selectedDays) && count($selectedDays) > 1) {
            $created = [];

            DB::transaction(function () use ($selectedDays, $payload, $data, &$created) {
                foreach ($selectedDays as $d) {
                    $payloadPer = $payload;
                    $payloadPer['day_of_week'] = $d;
                    $payloadPer['recurring_start_date'] = $this->nextDateForDay($d)->toDateString();
                    $payloadPer['recurring_end_date'] = Carbon::parse($payloadPer['recurring_start_date'])->addMonths((int) ($data['recurring_months'] ?? 3))->subDay()->toDateString();

                    if (! empty($payloadPer['recurring_start_date']) && ! empty($payloadPer['recurring_end_date'])) {
                        $payloadPer['recurring_months'] = Carbon::parse($payloadPer['recurring_start_date'])->diffInMonths(Carbon::parse($payloadPer['recurring_end_date'])) ?: 1;
                    }

                    $created[] = DoctorAvailability::create($payloadPer);
                }
            });

            // return the first created availability for callers that expect a single object
            // log created ids
            $ids = array_map(fn($c) => $c->id, $created);
            Log::debug('[ManageDoctorAvailability] created availabilities', ['ids' => $ids]);

            return $created[0] ?? DoctorAvailability::create($payload);
        }

        return DoctorAvailability::create($payload);
    }

    private function saveOverride(DoctorAvailability $availability, string $date, array $data): DoctorAvailabilityOverride
    {
        $payload = [
            'doctor_id' => $availability->doctor_id,
        ];

        if (array_key_exists('start_time', $data)) {
            $payload['start_time'] = filled($data['start_time'] ?? null) ? Carbon::parse($data['start_time'])->format('H:i:00') : null;
        }

        if (array_key_exists('end_time', $data)) {
            $payload['end_time'] = filled($data['end_time'] ?? null) ? Carbon::parse($data['end_time'])->format('H:i:00') : null;
        }

        if (array_key_exists('capacity', $data)) {
            $payload['capacity'] = filled($data['capacity'] ?? null) ? (int) $data['capacity'] : null;
        }

        if (array_key_exists('consultation_fee', $data)) {
            $payload['consultation_fee'] = filled($data['consultation_fee'] ?? null) ? (float) $data['consultation_fee'] : null;
        }

        if (array_key_exists('doctor_room', $data)) {
            $payload['doctor_room'] = filled($data['doctor_room'] ?? null) ? $data['doctor_room'] : null;
        }

        if (array_key_exists('status', $data)) {
            $status = $data['status'] ?: 'active';
            if ($status === 'active' && $availability->is_recurring && ! $availability->is_available) {
                $status = 'blocked';
            }
            $payload['status'] = $status;
        }

        if (array_key_exists('note', $data)) {
            $payload['note'] = $data['note'] ?? null;
        }

        if (array_key_exists('inherit_booking_cutoff_rules', $data)) {
            $payload['booking_cutoff_rules'] = ($data['inherit_booking_cutoff_rules'] ?? true)
                ? null
                : (
                    (isset($data['booking_cutoff_value']) && isset($data['booking_cutoff_unit']) && (int)$data['booking_cutoff_value'] > 0)
                    ? [['value' => (int) $data['booking_cutoff_value'], 'unit' => $data['booking_cutoff_unit']]]
                    : null
                );
        } else {
            if (isset($data['booking_cutoff_value']) && isset($data['booking_cutoff_unit']) && (int)$data['booking_cutoff_value'] > 0) {
                $payload['booking_cutoff_rules'] = [['value' => (int) $data['booking_cutoff_value'], 'unit' => $data['booking_cutoff_unit']]];
            }
        }

        $overrideDate = Carbon::parse($date)->toDateString();
        $override = DoctorAvailabilityOverride::withTrashed()
            ->where('doctor_availability_id', $availability->id)
            ->whereDate('override_date', $overrideDate)
            ->first();

        if ($override) {
            if ($override->trashed()) {
                $override->restore();
            }

            $override->fill($payload);
            $override->override_date = $overrideDate;
            $override->save();

            return $override;
        }

        return DoctorAvailabilityOverride::create([
            'doctor_availability_id' => $availability->id,
            'override_date' => $overrideDate,
            ...$payload,
        ]);
    }

    private function updateChildOnlySettings(DoctorAvailability $availability, array $data): void
    {
        if (! array_key_exists('is_child_only', $data)) {
            return;
        }

        $isChildOnly = ($data['is_child_only'] ?? false) === true || ($data['is_child_only'] ?? false) === '1';

        $availability->update([
            'is_child_only' => $isChildOnly,
        ]);
    }

    private function deleteDate(DoctorAvailability $availability, string $date): void
    {
        if ($this->isRecurringTemplate($availability)) {
            $this->saveOverride($availability, $date, [
                'status' => 'cancelled',
                'note' => 'Deleted for this date from availability manager.',
            ]);

            return;
        }

        $availability->delete();
    }

    private function bulkUpdateSelected(array $data): int
    {
        $cutoffRules = null;
        if (isset($data['booking_cutoff_value']) && isset($data['booking_cutoff_unit']) && (int)$data['booking_cutoff_value'] > 0) {
            $cutoffRules = [
                [
                    'value' => (int) $data['booking_cutoff_value'],
                    'unit' => $data['booking_cutoff_unit'],
                ]
            ];
        }

        $payload = collect($data)
            ->filter(fn($value) => filled($value))
            ->all();

        if ($cutoffRules !== null) {
            $payload['booking_cutoff_rules'] = $cutoffRules;
        }

        if ($payload === []) {
            return 0;
        }

        return $this->forEachSelectedRow(function (DoctorAvailability $availability, string $date) use ($payload): void {
            if ($this->isRecurringTemplate($availability)) {
                $this->saveOverride($availability, $date, $payload);

                return;
            }

            $parentPayload = [];

            foreach (['start_time', 'end_time', 'capacity', 'consultation_fee', 'doctor_room', 'booking_cutoff_rules'] as $field) {
                if (array_key_exists($field, $payload)) {
                    $parentPayload[$field] = $payload[$field];
                }
            }

            if (array_key_exists('status', $payload)) {
                $parentPayload['is_available'] = $payload['status'] === 'active';
            }

            if ($parentPayload !== []) {
                $this->saveParentAvailability([
                    ...$availability->only([
                        'date',
                        'day_of_week',
                        'start_time',
                        'end_time',
                        'capacity',
                        'consultation_type',
                        'opd_type',
                        'is_child_only',
                        'doctor_room',
                        'consultation_fee',
                    ]),
                    ...$parentPayload,
                    'is_recurring' => '0',
                    'is_available' => ($parentPayload['is_available'] ?? $availability->is_available) ? '1' : '0',
                ], $availability);
            }
        });
    }

    private function forEachSelectedRow(callable $callback): int
    {
        $count = 0;

        foreach ($this->selectedRows as $rowKey) {
            [$availabilityId, $date] = array_pad(explode('|', (string) $rowKey, 2), 2, null);

            if (! $availabilityId || ! $date) {
                continue;
            }

            $availability = $this->baseAvailabilityQuery()->where('id', $availabilityId)->first();

            if (! $availability) {
                continue;
            }

            $callback($availability, Carbon::parse($date)->toDateString());
            $count++;
        }

        return $count;
    }

    private function nextDateForDay(string $day): Carbon
    {
        $today = now()->startOfDay();
        $dayNumbers = [
            'sunday' => Carbon::SUNDAY,
            'monday' => Carbon::MONDAY,
            'tuesday' => Carbon::TUESDAY,
            'wednesday' => Carbon::WEDNESDAY,
            'thursday' => Carbon::THURSDAY,
            'friday' => Carbon::FRIDAY,
            'saturday' => Carbon::SATURDAY,
        ];
        $targetDay = $dayNumbers[strtolower($day)] ?? $today->dayOfWeek;

        if ($today->dayOfWeek === $targetDay) {
            return $today;
        }

        return $today->copy()->next($targetDay);
    }

    private function availabilityFormSchema(bool $includeStatus = false, bool $includeId = false): array
    {
        return [
            ...($includeId ? [Hidden::make('availability_id')] : []),
            Section::make('Doctor')
                ->description('Select the doctor for this availability slot.')
                ->columns(1)
                ->schema(array_filter([
                    ...($this->isAllDoctorsManager() ? [
                        Select::make('doctor_id')
                            ->label('Doctor')
                            ->options(fn(): array => $this->doctorOptions)
                            ->searchable()
                            ->reactive()
                            ->required()
                            ->default(fn(): ?string => $this->doctorFilter)
                            ->helperText('Choose the doctor first. The rest of the availability fields will appear below.'),
                    ] : []),
                    ...($this->isGlobalManager() && ! $this->isAllDoctorsManager() && filled($this->doctorFilter) ? [
                        Hidden::make('doctor_id')
                            ->default(fn(): ?string => $this->doctorFilter)
                            ->dehydrated(),
                        Placeholder::make('selected_doctor')
                            ->label('Selected doctor')
                            ->content(fn(): string => $this->doctorName(Doctor::query()->find($this->doctorFilter)))
                            ->columnSpanFull(),
                    ] : []),
                ])),
            ...($includeStatus ? [
                Section::make('Availability Status')
                    ->description('Enable or disable the slot before filling in the rest of the details.')
                    ->columns(1)
                    ->schema([
                        Toggle::make('is_available')
                            ->label('Active')
                            ->default(true)
                            ->inline(false)
                            ->onColor('primary')
                            ->offColor('gray')
                            ->required(),
                    ]),
            ] : []),
            Section::make('Schedule')
                ->description('Choose recurring weekly or a one-time date, then set the time window.')
                ->icon('heroicon-o-calendar-days')
                ->columns(1)
                ->visible(fn($get) => filled($get('doctor_id')) || ($this->isGlobalManager() && filled($this->doctorFilter)))
                ->schema([
                    Grid::make(4)->schema([
                        Select::make('is_recurring')
                            ->label('Schedule Type')
                            ->options(['1' => 'Recurring weekly', '0' => 'One-time date'])
                            ->default('1')
                            ->live()
                            ->required(),
                        Select::make('days_of_week')
                            ->label('Days')
                            ->options(DayOfWeek::labels())
                            ->multiple()
                            ->reactive()
                            ->helperText('Select one or more days. When only one day is selected this acts like a single "Day" field.')
                            ->visible(fn($get) => (string) ($get('is_recurring') ?? '1') === '1')
                            ->required(fn($get) => (string) ($get('is_recurring') ?? '1') === '1'),
                        DatePicker::make('date')
                            ->label('Date')
                            ->visible(fn($get) => (string) ($get('is_recurring') ?? '1') === '0')
                            ->required(fn($get) => (string) ($get('is_recurring') ?? '1') === '0')
                            ->live()
                            ->afterStateUpdated(function ($state, $set): void {
                                if ($state) {
                                    $set('days_of_week', [strtolower(Carbon::parse($state)->format('l'))]);
                                }
                            }),
                        Select::make('recurring_months')
                            ->label('Duration')
                            ->options([
                                3 => '3 months',
                                6 => '6 months',
                                12 => '12 months',
                            ])
                            ->default(3)
                            ->visible(fn($get) => (string) ($get('is_recurring') ?? '1') === '1')
                            ->required(fn($get) => (string) ($get('is_recurring') ?? '1') === '1'),
                        Toggle::make('is_auto_recurring')
                            ->label('Auto Recur')
                            ->visible(fn($get) => (string) ($get('is_recurring') ?? '1') === '1')
                            ->default(false)
                            ->onColor('primary')
                            ->offColor('gray')
                            ->inline(false),
                    ]),
                    Grid::make(2)->schema([
                        TimePicker::make('start_time')->label('Start Time')->seconds(false)->required(),
                        TimePicker::make('end_time')->label('End Time')->seconds(false)->required(),
                    ]),
                ]),
            Section::make('Consultation & booking close time')
                ->description('Consultation details and optional rules for how long before the slot starts booking closes.')
                ->icon('heroicon-o-clipboard-document-list')
                ->columns(1)
                ->visible(fn($get) => filled($get('doctor_id')) || ($this->isGlobalManager() && filled($this->doctorFilter)))
                ->schema([
                    Grid::make(2)->schema([
                        Select::make('consultation_type')
                            ->label('Mode')
                            ->options(['in-person' => 'In-Person', 'video' => 'Video'])
                            ->default('in-person')
                            ->live()
                            ->afterStateUpdated(function ($state, $set): void {
                                if ($state === 'video') {
                                    $set('opd_type', null);
                                    $set('doctor_room', null);
                                    $set('is_child_only', false);
                                }
                            })
                            ->required(),
                        Select::make('opd_type')
                            ->label('OPD Type')
                            ->options(['general' => 'General', 'private' => 'Private'])
                            ->default('general')
                            ->visible(fn($get) => ($get('consultation_type') ?? 'in-person') === 'in-person')
                            ->required(fn($get) => ($get('consultation_type') ?? 'in-person') === 'in-person'),
                        TextInput::make('consultation_fee')->label('Fee')->numeric()->minValue(0)->default(0),
                        TextInput::make('capacity')->numeric()->minValue(1)->default(1)->required(),
                        Toggle::make('is_child_only')
                            ->label('Child only')
                            ->helperText('When enabled, this OPD slot is available only for patients up to the selected age.')
                            ->default(false)
                            ->inline(false)
                            ->onColor('primary')
                            ->offColor('gray')
                            ->live()
                            ->visible(fn($get) => ($get('consultation_type') ?? 'in-person') === 'in-person')
                            ->afterStateUpdated(function ($state, $set): void {
                                //
                            }),
                        Placeholder::make('global_child_age')
                            ->label('Global child age')
                            ->content(fn(): string => $this->globalChildAgeLimit() . ' years')
                            ->visible(fn($get) => ($get('consultation_type') ?? 'in-person') === 'in-person' && (bool) ($get('is_child_only') ?? false)),
                        TextInput::make('doctor_room')
                            ->label('Doctor Room')
                            ->placeholder('e.g., Room 101')
                            ->visible(fn($get) => ($get('consultation_type') ?? 'in-person') === 'in-person'),
                        ...$this->bookingCutoffRulesFormFields(forOverride: false),
                    ]),
                ]),
            Section::make('Review slot details')
                ->description('Confirm the selected doctor, time, and slot settings before saving.')
                ->columns(1)
                ->visible(fn($get) => (filled($get('doctor_id')) || ($this->isGlobalManager() && filled($this->doctorFilter))) && filled($get('start_time')) && filled($get('end_time')))
                ->schema([
                    Placeholder::make('slot_review')
                        ->label('Review details')
                        ->content(fn($get): HtmlString => new HtmlString($this->slotReviewHtml($get))),
                ]),
        ];
    }

    private function baseAvailabilityQuery()
    {
        return DoctorAvailability::query()
            ->with('doctor.user')
            ->when($this->hasDoctorRecord(), fn($query) => $query->where('doctor_id', $this->getRecord()->id))
            ->when(! $this->hasDoctorRecord() && $this->doctorFilter, fn($query) => $query->where('doctor_id', $this->doctorFilter));
    }

    private function findAvailability(?string $id): DoctorAvailability
    {
        return $this->baseAvailabilityQuery()->where('id', $id)->firstOrFail();
    }

    private function availabilityLabel(DoctorAvailability $availability, ?DoctorAvailabilityOverride $override = null): string
    {
        $startTime = $this->formatTime($override && $override->start_time !== null ? $override->start_time : $availability->start_time);
        $endTime = $this->formatTime($override && $override->end_time !== null ? $override->end_time : $availability->end_time);
        if ($startTime) {
            $startTime = \Carbon\Carbon::parse($startTime)->format('h:i A');
        }
        if ($endTime) {
            $endTime = \Carbon\Carbon::parse($endTime)->format('h:i A');
        }
        $time = $startTime . ' - ' . $endTime;

        $type = $this->isRecurringTemplate($availability)
            ? ucfirst($availability->day_of_week ?: 'Recurring')
            : ($availability->date ? Carbon::parse($availability->date)->format('d M Y') : 'One-time');

        return "{$type} | {$time} | " . ucfirst($availability->consultation_type);
    }

    private function doctorName(?Doctor $doctor): string
    {
        if (! $doctor) {
            return 'Unknown doctor';
        }

        return $doctor->user?->name
            ?: trim(($doctor->first_name ?? '') . ' ' . ($doctor->last_name ?? ''))
            ?: 'Doctor';
    }

    private function bookingCutoffLabel(array $rules, string $source): string
    {
        $ruleText = collect(app(SlotBookingCutoffService::class)->normalizeRules($rules))
            ->map(function (array $rule): string {
                $value = (int) $rule['value'];
                $unit = rtrim((string) $rule['unit'], 's');

                return $value . ' ' . $unit . ($value === 1 ? '' : 's');
            })
            ->implode(', ');

        $ruleText = $ruleText ?: 'default close time';

        return match ($source) {
            'override' => "Custom close time: {$ruleText}",
            'availability' => "Slot close time: {$ruleText}",
            default => "Using global booking close time: {$ruleText}",
        };
    }

    private function globalChildAgeLimit(): int
    {
        return SettingService::getChildAgeLimit();
    }

    public function setActiveDate(?string $date): void
    {
        $this->activeDate = $date;
    }

    private function slotReviewSummary($get): string
    {
        $doctor = null;
        if ($this->isAllDoctorsManager()) {
            $doctor = Doctor::query()->find($get('doctor_id'))?->user?->name;
        } elseif ($this->isGlobalManager() && filled($this->doctorFilter)) {
            $doctor = $this->doctorName(Doctor::query()->find($this->doctorFilter));
        }

        $scheduleType = (string) ($get('is_recurring') ?? '1') === '1' ? 'Recurring weekly' : 'One-time date';
        $date = $get('date') ? Carbon::parse($get('date'))->format('d M Y') : 'N/A';
        $time = $get('start_time') && $get('end_time') ? Carbon::parse($get('start_time'))->format('h:i A') . ' - ' . Carbon::parse($get('end_time'))->format('h:i A') : 'Not set';
        $status = ($get('is_available') ?? true) ? 'Active' : 'Blocked';
        $consultation = ucfirst($get('consultation_type') ?? 'in-person');
        $opd = ($get('opd_type') ?? 'general') === 'general' ? 'General' : 'Private';
        $childOnly = ($get('is_child_only') ?? false) ? 'Yes' : 'No';
        $fee = filled($get('consultation_fee')) ? number_format($get('consultation_fee'), 2) : '0.00';
        $capacity = $get('capacity') ?? '1';
        $room = $get('doctor_room') ?: 'N/A';
        $autoRecur = ($get('is_auto_recurring') ?? false) ? 'Enabled' : 'Disabled';

        return "Doctor: " . ($doctor ?? 'Please select a doctor') . "\n"
            . "Schedule: {$scheduleType}\n"
            . "Date: {$date}\n"
            . "Time: {$time}\n"
            . "Status: {$status}\n"
            . "Consultation mode: {$consultation}\n"
            . "OPD type: {$opd}\n"
            . "Child only: {$childOnly}\n"
            . "Capacity: {$capacity}\n"
            . "Fee: ₹{$fee}\n"
            . "Room: {$room}\n"
            . "Auto recur: {$autoRecur}";
    }

    private function slotReviewHtml($get)
    {
        $doctor = null;
        if ($this->isAllDoctorsManager()) {
            $doctor = Doctor::query()->find($get('doctor_id'))?->user?->name;
        } elseif ($this->isGlobalManager() && filled($this->doctorFilter)) {
            $doctor = $this->doctorName(Doctor::query()->find($this->doctorFilter));
        }

        $isRecurring = (string) ($get('is_recurring') ?? '1') === '1';
        $scheduleType = $isRecurring ? 'Recurring weekly' : 'One-time date';
        $date = $get('date') ? Carbon::parse($get('date'))->format('d M Y') : null;
        $start = $get('start_time') ? Carbon::parse($get('start_time'))->format('h:i A') : null;
        $end = $get('end_time') ? Carbon::parse($get('end_time'))->format('h:i A') : null;
        $time = ($start && $end) ? "{$start} - {$end}" : 'Not set';
        $status = ($get('is_available') ?? true) ? 'Active' : 'Blocked';
        $consultation = ucfirst($get('consultation_type') ?? 'in-person');
        $opd = ($get('opd_type') ?? 'general') === 'general' ? 'General' : 'Private';
        $childOnly = ($get('is_child_only') ?? false) ? 'Yes' : 'No';
        $fee = filled($get('consultation_fee')) ? number_format($get('consultation_fee'), 2) : '0.00';
        $capacity = $get('capacity') ?? '1';
        $room = $get('doctor_room') ?: 'N/A';
        $autoRecur = ($get('is_auto_recurring') ?? false) ? 'Enabled' : 'Disabled';

        $rows = [];
        $rows[] = ['label' => 'Doctor', 'value' => $doctor ?? 'Please select a doctor'];
        $rows[] = ['label' => 'Schedule', 'value' => $scheduleType];
        if (! $isRecurring) {
            $rows[] = ['label' => 'Date', 'value' => $date ?? 'N/A'];
        } else {
            $selected = $get('days_of_week') ?? ($get('day_of_week') ? [$get('day_of_week')] : [now()->format('l')]);
            $labels = is_array($selected) ? array_map(fn($d) => ucfirst($d), $selected) : [ucfirst((string)$selected)];
            $rows[] = ['label' => 'Days', 'value' => implode(', ', $labels)];
            $rows[] = ['label' => 'Duration', 'value' => ($get('recurring_months') ?? '') ? ($get('recurring_months') . ' months') : ''];
        }
        $rows[] = ['label' => 'Time', 'value' => $time];
        $rows[] = ['label' => 'Status', 'value' => $status];
        $rows[] = ['label' => 'Consultation mode', 'value' => $consultation];
        if ($consultation === 'In-person') {
            $rows[] = ['label' => 'OPD type', 'value' => $opd];
            $rows[] = ['label' => 'Child only', 'value' => $childOnly];
            $rows[] = ['label' => 'Room', 'value' => $room];
        }
        $rows[] = ['label' => 'Capacity', 'value' => $capacity];
        $rows[] = ['label' => 'Fee', 'value' => "₹{$fee}"];
        $rows[] = ['label' => 'Auto recur', 'value' => $autoRecur];

        return view('filament.resources.doctors.components.slot-review', ['rows' => $rows]);
    }

    private function hasDoctorRecord(): bool
    {
        return isset($this->record) && $this->record instanceof Doctor;
    }

    private function isRecurringTemplate(DoctorAvailability $availability): bool
    {
        return app(DoctorAvailabilityService::class)->isRecurringTemplate($availability);
    }

    private function formatTime($time): ?string
    {
        if (! $time) {
            return null;
        }

        return Carbon::parse($time)->format('H:i');
    }

    /**
     * @return array<int, \Filament\Schemas\Components\Component>
     */
    private function editOccurrenceFormSchema(): array
    {
        return [
            Hidden::make('availability_id'),
            Hidden::make('is_recurring_edit')->dehydrated(),
            Hidden::make('inherited_booking_cutoff_label'),
            Section::make('Date & time')
                ->columns(1)
                ->schema([
                    Grid::make(3)->schema([
                        DatePicker::make('override_date')->label('Date')->required()->disabled()->dehydrated(),
                        TimePicker::make('start_time')->label('Start Time')->seconds(false)->required(),
                        TimePicker::make('end_time')->label('End Time')->seconds(false)->required(),
                    ]),
                ]),
            Section::make('Capacity & consultation')
                ->columns(1)
                ->schema([
                    Grid::make(3)->schema([
                        TextInput::make('capacity')->numeric()->minValue(1)->required(),
                        TextInput::make('consultation_fee')->label('Consultation Fee')->numeric()->minValue(0),
                        TextInput::make('doctor_room')->label('Room'),
                        Toggle::make('is_child_only')
                            ->label('Child only')
                            ->helperText('For recurring slots, this applies to the full weekly slot series.')
                            ->inline(false)
                            ->onColor('primary')
                            ->offColor('gray')
                            ->live()
                            ->afterStateUpdated(function ($state, $set): void {
                                //
                            }),
                        Placeholder::make('global_child_age')
                            ->label('Global child age')
                            ->content(fn(): string => $this->globalChildAgeLimit() . ' years')
                            ->visible(fn($get) => (bool) ($get('is_child_only') ?? false)),
                        Select::make('status')->options([
                            'active' => 'Active',
                            'blocked' => 'Blocked',
                        ])->required(),
                    ]),
                ]),
            Section::make('Booking close time')
                ->description('Optional date-specific rule for how long before this occurrence starts booking closes.')
                ->columns(1)
                ->schema([
                    Placeholder::make('current_booking_close_time')
                        ->hiddenLabel()
                        ->content(fn($get): string => (string) ($get('inherited_booking_cutoff_label') ?: $this->bookingCutoffLabel(app(SlotBookingCutoffService::class)->defaultRules(), 'app_default')))
                        ->visible(fn($get): bool => (bool) ($get('inherit_booking_cutoff_rules') ?? true)),
                    ...$this->bookingCutoffRulesFormFields(forOverride: true),
                ]),
            Section::make('Notes')
                ->schema([
                    Textarea::make('note')->rows(3)->columnSpanFull(),
                ]),
        ];
    }

    private function bookingCutoffRulesFormFields(bool $forOverride = false, bool $includeInheritToggle = true): array
    {
        $fields = [];

        if ($includeInheritToggle) {
            $fields[] = Toggle::make('inherit_booking_cutoff_rules')
                ->label(
                    fn($get) => ($forOverride && ($get('is_recurring_edit') ?? false))
                        ? 'Use recurring weekly slot booking close time'
                        : 'Use global booking close time'
                )
                ->helperText(
                    function ($get) use ($forOverride) {
                        if ($forOverride && ($get('is_recurring_edit') ?? false)) {
                            $currentLabel = $get('inherited_booking_cutoff_label') ?: $this->bookingCutoffLabel(app(SlotBookingCutoffService::class)->defaultRules(), 'app_default');
                            return "When enabled, this date uses the weekly slot rule, or the global booking close time if the weekly slot has no custom rule.\nCurrent: **{$currentLabel}**";
                        } else {
                            $defaultLabel = $this->bookingCutoffLabel(
                                app(SlotBookingCutoffService::class)->defaultRules(),
                                'app_default'
                            );
                            return "On by default. This slot uses the global booking close time until you turn this off and set a slot-specific rule.\nCurrent: **{$defaultLabel}**";
                        }
                    }
                )




                ->default(true)
                ->inline(false)
                ->onColor('primary')
                ->offColor('gray')
                ->live()
                ->columnSpanFull();
        }

        $fields[] = Grid::make(2)
            ->schema([
                TextInput::make('booking_cutoff_value')
                    ->label('Booking closes before start')
                    ->numeric()
                    ->minValue(1)
                    ->placeholder('e.g., 4')
                    ->required(fn($get) => ! $includeInheritToggle
                        ? false
                        : ! ($get('inherit_booking_cutoff_rules') ?? true)),
                Select::make('booking_cutoff_unit')
                    ->label('Unit')
                    ->options([
                        'minutes' => 'Minutes',
                        'hours' => 'Hours',
                        'days' => 'Days',
                    ])
                    ->default('hours')
                    ->required(fn($get) => ! $includeInheritToggle
                        ? false
                        : ! ($get('inherit_booking_cutoff_rules') ?? true)),
            ])
            ->visible(fn($get) => ! $includeInheritToggle
                || ! ($get('inherit_booking_cutoff_rules') ?? true))
            ->columnSpanFull();

        return $fields;
    }

    /**
     * @param  mixed  $rules
     * @return array<int, array{value: int, unit: string}>|null
     */
    private function normalizeBookingCutoffRules(mixed $rules): ?array
    {
        if (! is_array($rules) || $rules === []) {
            return null;
        }

        return app(\App\Services\SlotBookingCutoffService::class)->normalizeRules($rules);
    }

    private function overrideOnlyBlocks(DoctorAvailabilityOverride $override): bool
    {
        return $override->start_time === null
            && $override->end_time === null
            && $override->capacity === null
            && $override->consultation_fee === null
            && $override->doctor_room === null
            && $override->booking_cutoff_rules === null
            && blank($override->note);
    }

    private function refreshAvailabilityRows(): void
    {
        $this->availabilityRefreshVersion++;
        unset($this->rows, $this->summary, $this->availabilityOptions, $this->doctorOptions);
        $this->dispatch('$refresh');
    }
}