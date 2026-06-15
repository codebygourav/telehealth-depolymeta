<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Appointments\AppointmentResource;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Payment;
use App\Traits\HasCustomSidebar;
use BackedEnum;
use Carbon\Carbon;
use Filament\Forms\Components\{DatePicker, Select};
use Filament\Forms\Concerns\{InteractsWithForms, InteractsWithTables};
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Filament\Actions\ExportAction;


class DoctorReport extends Page implements HasForms, HasTable
{
    use HasCustomSidebar, InteractsWithForms, InteractsWithTable;

    protected string $view = 'filament.pages.doctor-report';

    protected static ?string $title = 'Doctor Report';

    public ?array $data = [];

    public bool $isLoaded = false;
    public string $activePeriod = 'monthly';

    public function setPeriod($period): void
    {
        $this->activePeriod = $period;
        $newData = $this->data;
        $newData['reportPeriod'] = $period;
        $this->data = $newData;

        if (method_exists($this, 'resetTable')) {
            $this->resetTable();
        }
    }

    public static function shouldRegisterNavigation(): bool
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
$module = static::$slug ?? strtolower(class_basename(static::class));
        return $user && ($user->hasRole('super_admin') || check_permission(["{$module}.view", "{$module}.view_any"]));
    }

    public static function getSidebarOptions(): array
    {
        return [
            'label' => 'Doctor Reports',
            'icon' => 'heroicon-o-chart-bar',
            'group' => 'Reports',
            'sort' => 101,
        ];
    }

    public static function canAccess(): bool
    {
        return static::shouldRegisterNavigation();
    }

    protected function getForms(): array
    {
        return ['form'];
    }

    // protected function getHeaderActions(): array
    // {
    //     return [
    //         ExportAction::make('export_audit_excel')
    //             ->exporter(\App\Filament\Exports\AppointmentExporter::class)
    //             ->label('Export Report')
    //             ->color('primary')
    //             ->icon('heroicon-o-document-arrow-down'),
    //     ];
    // }

    /**
     * Called via wire:init to defer heavy data loading
     */
    public function loadData(): void
    {
        $this->isLoaded = true;
    }

    public function mount(): void
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $doctorId = 'all';

        if ($user && method_exists($user, 'hasRole') && $user->hasRole('doctor')) {
            $doctorId = Doctor::where('user_id', $user->id)->first()?->id;
        }

        $this->form->fill([
            'reportDoctor' => $doctorId,
        ]);

        $this->data = $this->form->getRawState();
        $this->data['reportPeriod'] = $this->activePeriod;
        $this->data['reportDate'] = now()->format('Y-m-d');
    }

    public function updated($propertyName): void
    {
        if (str_starts_with($propertyName, 'data.')) {
            $formData = $this->form->getRawState();
            $this->data = array_merge($this->data, $formData);
            $this->data['reportPeriod'] = $this->activePeriod;
            $this->data['reportDate'] = now()->format('Y-m-d');

            if (method_exists($this, 'resetTable')) {
                $this->resetTable();
            }
        }
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 1;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('reportDoctor')
                    ->hiddenLabel()
                    ->placeholder('Filter by Doctor...')
                    ->options(function () {
                        /** @var \App\Models\User|null $user */
                        $user = Auth::user();
                        $query = Doctor::query()
                            ->select('id', 'first_name', 'last_name')
                            ->orderBy('first_name');

                        $options = [];
                        if ($user && (! method_exists($user, 'hasRole') || ! $user->hasRole('doctor'))) {
                            $options['all'] = 'All Doctors';
                        }

                        return $options + $query->get()->mapWithKeys(fn($d) => [$d->id => "{$d->first_name} {$d->last_name}"])->toArray();
                    })
                    ->searchable()
                    ->required()
                    ->hidden(function () {
                        /** @var mixed $user */
                        $user = Auth::user();
                        return $user && method_exists($user, 'hasRole') && $user->hasRole('doctor');
                    })
                    ->live(),
            ])
            ->statePath('data')
            ->columns(1);
    }

    public function table(Table $table): Table
    {
        $doctorId = $this->data['reportDoctor'] ?? 'all';
        $period = $this->data['reportPeriod'] ?? 'monthly';
        $date = $this->data['reportDate'] ?? now()->format('Y-m-d');
        $parsedDate = Carbon::parse($date);

        return $table
            ->query(function () use ($doctorId, $period, $parsedDate) {
                $query = Appointment::query()
                    ->with(['doctor.user', 'patient.user', 'payment']);

                if ($doctorId && $doctorId !== 'all') {
                    $query->where('doctor_id', $doctorId);
                }

                if ($period === 'weekly') {
                    $query->whereDate('appointment_date', '>=', $parsedDate->copy()->startOfWeek()->format('Y-m-d'))
                        ->whereDate('appointment_date', '<=', $parsedDate->copy()->endOfWeek()->format('Y-m-d'));
                }

                if ($period === 'monthly') {
                    $query->whereMonth('appointment_date', $parsedDate->month)
                        ->whereYear('appointment_date', $parsedDate->year);
                }

                if ($period === 'yearly') {
                    $query->whereYear('appointment_date', $parsedDate->year);
                }

                return $query->latest();
            })
            ->columns([
                TextColumn::make('appointment_date')
                    ->label('Date')
                    ->date('D, d M Y')
                    ->sortable()
                    ->color('gray'),
                TextColumn::make('razorpay_payment_id')
                    ->label('Razorpay ID')
                    ->getStateUsing(function ($record) {
                        if (!empty($record->razorpay_payment_id)) {
                            return $record->razorpay_payment_id;
                        }
                        if ($record->relationLoaded('payment') && $record->payment) {
                            return $record->payment->razorpay_payment_id;
                        }
                        return null;
                    })
                    ->sortable(),
                TextColumn::make('id')
                    ->label('Appointment ID')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('payment_status')
                    ->label('Payment Status')
                    ->badge()
                    ->getStateUsing(function ($record) {
                        if (!empty($record->status) && in_array($record->status, ['paid', 'failed', 'pending', 'refunded'])) {
                            return ucfirst((string) $record->status);
                        }
                        if ($record->relationLoaded('payment') && $record->payment) {
                            $paymentStatus = $record->payment->status ?? 'N/A';
                            if ($paymentStatus instanceof \BackedEnum) {
                                return ucfirst((string) $paymentStatus->value);
                            }
                            return ucfirst((string) $paymentStatus);
                        }
                        return 'N/A';
                    })
                    ->color(function ($state) {
                        return [
                            'Paid' => 'success',
                            'Failed' => 'danger',
                            'Pending' => 'warning',
                            'Refunded' => 'info',
                        ][$state] ?? 'gray';
                    }),
                TextColumn::make('appointment_time')
                    ->label('Slot')
                    ->time('h:i A'),
                TextColumn::make('patient.user.name')
                    ->label('Patient')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('doctor.user.name')
                    ->label('Doctor')
                    ->sortable(),
                TextColumn::make('consultation_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn($state) => match ($state instanceof BackedEnum ? $state->value : (string) $state) {
                        'video' => 'info',
                        'in_person', 'in-person' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn($state) => match ($state instanceof BackedEnum ? $state->value : (string) $state) {
                        'completed' => 'success',
                        'scheduled' => 'info',
                        'cancelled' => 'danger',
                        'no_show', 'no-show' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('fee_amount')
                    ->label('Fee')
                    ->money('INR')
                    ->sortable()
                    ->alignment('right'),
            ])
            ->filters([
                // Filter by Doctor
                \Filament\Tables\Filters\SelectFilter::make('doctor_id')
                    ->label('Doctor')
                    ->options(
                        Doctor::query()
                            ->orderBy('first_name')
                            ->get()
                            ->mapWithKeys(fn($d) => [$d->id => "{$d->first_name} {$d->last_name}"])
                            ->toArray()
                    )
                    ->searchable()
                    ->query(function (Builder $query, $value) {
                        // Only apply the filter if value is not empty or null
                        if (!is_null($value) && $value !== '' && $value !== 'all') {
                            $query->where('doctor_id', $value);
                        }
                        return $query;
                    }),
                // Filter by Payment Status - must show correct rows for filter selection
                \Filament\Tables\Filters\SelectFilter::make('payment_status')
                    ->label('Payment Status')
                    ->options([
                        'paid' => 'Paid',
                        'failed' => 'Failed',
                        'pending' => 'Pending',
                        'refunded' => 'Refunded',
                    ])
                    ->query(function (Builder $query, $value) {
                        if (!is_null($value) && $value !== '') {
                            // Only check Appointment status if the value is one of the valid AppointmentStatus enum values
                            // AppointmentStatus has: pending, failed (among others like confirmed, completed, etc.)
                            // It DOES NOT have: paid, refunded
                            $isAppointmentStatus = in_array($value, ['pending', 'failed']);

                            $query->where(function ($q) use ($value, $isAppointmentStatus) {
                                if ($isAppointmentStatus) {
                                    $q->where('status', $value)
                                        ->orWhereHas('payment', function ($p) use ($value) {
                                            $p->where('status', $value);
                                        });
                                } else {
                                    // For 'paid', 'refunded', strictly check the payment relationship
                                    $q->whereHas('payment', function ($p) use ($value) {
                                        $p->where('status', $value);
                                    });
                                }
                            });
                        }
                        return $query;
                    }),
                // Filter by Appointment Type (supporting both snake_case and kebab-case)
                \Filament\Tables\Filters\SelectFilter::make('consultation_type')
                    ->label('Appointment Type')
                    ->options([
                        'in_person' => 'In-Person',
                        'video' => 'Video',
                    ])
                    ->query(function (Builder $query, $value) {
                        if (!is_null($value) && $value !== '') {
                            $query->where(function ($q) use ($value) {
                                // Include both snake_case and kebab-case for in-person values
                                if ($value === 'in_person') {
                                    $q->where('consultation_type', 'in_person')
                                        ->orWhere('consultation_type', 'in-person');
                                } else {
                                    $q->where('consultation_type', $value);
                                }
                            });
                        }
                        return $query;
                    }),
            ])
            ->striped()
            ->recordUrl(fn(Appointment $record) => AppointmentResource::getUrl('view', ['record' => $record]))
            ->headerActions([
                ExportAction::make('export_audit_excel')
                    ->exporter(\App\Filament\Exports\AppointmentExporter::class)
                    ->label('Export Audit Excel')
                    ->color('white')
                    ->icon('heroicon-o-document-arrow-down')
                    ->extraAttributes(['class' => 'hidden']),
            ])
            ->groups([
                Group::make('doctor.user.name')
                    ->label('Doctor')
                    ->collapsible(),
            ]);
    }
}
