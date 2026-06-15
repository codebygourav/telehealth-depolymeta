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
use Illuminate\Validation\ValidationException;
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
                $isRecurring = isset($arguments['date']) ? '0' : '1';
                $dayOfWeek = $date ? strtolower(Carbon::parse($date)->format('l')) : strtolower(now()->format('l'));

                $schema->fill([
                    'is_recurring' => $isRecurring,
                    'date' => $date,
                    'day_of_week' => $dayOfWeek,
                    'doctor_id' => $arguments['doctor_id'] ?? $this->doctorFilter,
                ]);
            })
            ->form($this->availabilityFormSchema(includeStatus: true))
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
            ->modalDescription(fn(array $arguments = []): ?string => $this->appointmentWarningForArguments($arguments, 'edit'))
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

                Notification::make()
                    ->success()
                    ->title('Slot updated for ' . $this->doctorName($availability->doctor))
                    ->body('The selected availability date was updated successfully.')
                    ->send();
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
            ->modalDescription(fn(): string => $this->bulkAppointmentWarning('edit') ?: 'Only filled fields will be changed. Recurring dates are updated as date-specific overrides.')
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
            'booked' => $this->bookedCount($availability, $date, $effective['start_time'], $availability->consultation_type),
            'internal_booked' => $bookedDetails['internal'],
            'external_booked' => $bookedDetails['external'],
            'total_booked' => $bookedDetails['total'],
            'is_passed' => $this->isRowPassed($date, $effective['end_time']),
            'source' => $override ? 'override' : ($isRecurring ? 'recurring' : 'one-time'),
            'status' => $status,
            'is_recurring' => $isRecurring,
            'type' => $availability->consultation_type,
            'opd_type' => $availability->opd_type,
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

        $isRecurring = ($data['is_recurring'] ?? '0') === '1' || ($data['is_recurring'] ?? false) === true;
        $date = $isRecurring ? null : ($data['date'] ?? null);
        $day = strtolower($data['day_of_week'] ?? ($date ? Carbon::parse($date)->format('l') : 'monday'));
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
            'recurring_start_date' => $recurringStartDate,
            'recurring_end_date' => $recurringEndDate,
            'is_available' => ($data['is_available'] ?? '1') === '1' || ($data['is_available'] ?? true) === true,
            'booking_cutoff_rules' => $cutoffRules,
        ];

        if ($isRecurring && ! empty($payload['recurring_start_date']) && ! empty($payload['recurring_end_date'])) {
            $payload['recurring_months'] = Carbon::parse($payload['recurring_start_date'])->diffInMonths(Carbon::parse($payload['recurring_end_date'])) ?: 1;
        }

        if ($availability) {
            $availability->update($payload);

            return $availability;
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
            $payload['status'] = $data['status'] ?: 'active';
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
            ...($this->isAllDoctorsManager() ? [
                Select::make('doctor_id')
                    ->label('Doctor')
                    ->options(fn(): array => $this->doctorOptions)
                    ->searchable()
                    ->required()
                    ->default(fn(): ?string => $this->doctorFilter),
            ] : []),
            ...($this->isGlobalManager() && ! $this->isAllDoctorsManager() && filled($this->doctorFilter) ? [
                Hidden::make('doctor_id')
                    ->default(fn(): ?string => $this->doctorFilter)
                    ->dehydrated(),
            ] : []),
            Section::make('Schedule')
                ->description('Choose recurring weekly or a single date, then set the time window.')
                ->icon('heroicon-o-calendar-days')
                ->columns(1)
                ->schema([
                    Grid::make(3)->schema([
                        Select::make('is_recurring')
                            ->label('Schedule Type')
                            ->options(['1' => 'Recurring weekly', '0' => 'One-time date'])
                            ->default('1')
                            ->live()
                            ->required(),
                        Select::make('day_of_week')
                            ->label('Day')
                            ->options(DayOfWeek::labels())
                            ->default(strtolower(now()->format('l')))
                            ->visible(fn($get) => (string) ($get('is_recurring') ?? '1') === '1')
                            ->required(fn($get) => (string) ($get('is_recurring') ?? '1') === '1'),
                        DatePicker::make('date')
                            ->label('Date')
                            ->visible(fn($get) => (string) ($get('is_recurring') ?? '1') === '0')
                            ->required(fn($get) => (string) ($get('is_recurring') ?? '1') === '0')
                            ->live()
                            ->afterStateUpdated(function ($state, $set): void {
                                if ($state) {
                                    $set('day_of_week', strtolower(Carbon::parse($state)->format('l')));
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
                        ...($includeStatus ? [
                            Toggle::make('is_available')
                                ->label('Active')
                                ->default(true)
                                ->inline(false)
                                ->onColor('primary')
                                ->offColor('gray'),
                        ] : []),
                    ]),
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
