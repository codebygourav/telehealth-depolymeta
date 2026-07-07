@php
    use App\Enums\AppointmentStatus;
    use App\Enums\AuthStatus;
    use App\Models\Patient;
    use Illuminate\Support\Arr;
    use Illuminate\Support\Str;

    /** @var \Filament\Infolists\Components\ViewEntry $entry */
    $patient = $entry->getRecord();
    $patientModel = new Patient();
    $patientFillableKeys = $patientModel->getFillable();
    // Ensure commonly used relationships are available for the template.
    $patient->loadMissing(['user', 'appointments', 'appointments.doctor.user']);

    $fullName = trim(($patient->first_name ?? '') . ' ' . ($patient->last_name ?? '')) ?: 'Patient';
    $phone = $patient->user?->phone ?? ($patient->mobile_no ?? null);
    $email = $patient->user?->email ?? ($patient->email ?? null);
    $userStatus = $patient->user?->status;

    $todayDate = now()->toDateString();

    // Selected week (Mon-Sun) - supports navigation via query param
    $weekStartInput = request()->query('week_start');
    $weekStart = filled($weekStartInput)
        ? \Carbon\Carbon::parse($weekStartInput)->startOfWeek(\Carbon\Carbon::MONDAY)->startOfDay()
        : now()->startOfWeek(\Carbon\Carbon::MONDAY)->startOfDay();
    $weekEnd = $weekStart->copy()->addDays(6)->endOfDay();
    $weekDays = collect(range(0, 6))->map(fn($i) => $weekStart->copy()->addDays($i)->toDateString())->values();

    $selectedWeekIncludesToday = $todayDate >= $weekStart->toDateString() && $todayDate <= $weekEnd->toDateString();
    $currentDayInWeek = $selectedWeekIncludesToday ? $todayDate : $weekStart->toDateString();

    $weekAppointments = $patient
        ->appointments()
        ->whereIn('status', [
            AppointmentStatus::PENDING->value,
            AppointmentStatus::CONFIRMED->value,
            AppointmentStatus::COMPLETED->value,
            AppointmentStatus::RESCHEDULED->value,
        ])
        ->whereBetween('appointment_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
        ->orderBy('appointment_date')
        ->orderBy('appointment_time')
        ->with(['doctor.user', 'availability', 'payment'])
        ->get();

    $appointmentsByDay = $weekAppointments->groupBy(fn($a) => $a->appointment_date?->toDateString() ?? '');

    // Used when switching "Upcoming" tab - picks the first upcoming day (within selected week)
    $firstUpcomingDay = $weekDays->first(
        fn($dayDate) => $dayDate > $todayDate && ($appointmentsByDay->get($dayDate)?->isNotEmpty() ?? false),
    );
    $firstUpcomingDay =
        $firstUpcomingDay ?? ($weekDays->first(fn($dayDate) => $dayDate > $todayDate) ?? '__no_upcoming__');

    // Tabs: Current = Today, Upcoming = after Today (within selected week)
    $currentAppointments = $weekAppointments
        ->filter(fn($a) => $a->appointment_date?->toDateString() === $todayDate)
        ->values();

    $upcomingAppointments = $weekAppointments
        ->filter(fn($a) => $a->appointment_date && $a->appointment_date->toDateString() > $todayDate)
        ->values();

    $appointmentUrl = function ($appointment): string {
        return \App\Filament\Resources\Appointments\AppointmentResource::getUrl('view', [
            'record' => $appointment->slug ?? $appointment->id,
        ]);
    };

    // Quick previous/next appointment links (relative to today inside this selected week)
    $previousAppointment = $weekAppointments->last(
        fn($a) => $a->appointment_date && $a->appointment_date->toDateString() <= $todayDate,
    );
    $nextAppointment = $weekAppointments->first(
        fn($a) => $a->appointment_date && $a->appointment_date->toDateString() >= $todayDate,
    );

    $previousAppointmentUrl = $previousAppointment ? $appointmentUrl($previousAppointment) : null;
    $nextAppointmentUrl = $nextAppointment ? $appointmentUrl($nextAppointment) : null;

    $notifyPreviousAppointment = $patient
        ->appointments()
        ->whereIn('status', [
            AppointmentStatus::CONFIRMED->value,
            AppointmentStatus::COMPLETED->value,
            AppointmentStatus::RESCHEDULED->value,
        ])
        ->orderByDesc('appointment_date')
        ->orderByDesc('appointment_time')
        ->with(['doctor.user'])
        ->first();

    $notifyNextAppointment = $patient
        ->appointments()
        ->whereIn('status', [
            AppointmentStatus::PENDING->value,
            AppointmentStatus::CONFIRMED->value,
            AppointmentStatus::RESCHEDULED->value,
        ])
        ->whereDate('appointment_date', '>=', now()->toDateString())
        ->orderBy('appointment_date')
        ->orderBy('appointment_time')
        ->with(['doctor.user'])
        ->first();

    $notifyReferenceAppointment = $notifyPreviousAppointment ?? $patient
        ->appointments()
        ->orderByDesc('appointment_date')
        ->orderByDesc('appointment_time')
        ->with(['doctor.user'])
        ->first();

    $notifyDefaultDoctorId = old('doctor_id', $notifyReferenceAppointment?->doctor_id);
    $notifySlotWindowEnd = now()->copy()->addMonths(2);
    $notifyAvailableSlots = \App\Models\DoctorAvailability::query()
        ->with('doctor.user')
        ->where('is_available', true)
        ->where(function ($query) use ($notifySlotWindowEnd) {
            $query
                ->where(function ($query) {
                    $query->where('is_recurring', false)->whereDate('date', '>=', now()->toDateString());
                })
                ->orWhere(function ($query) use ($notifySlotWindowEnd) {
                    $query
                        ->where('is_recurring', true)
                        ->where(function ($query) use ($notifySlotWindowEnd) {
                            $query
                                ->whereNull('recurring_start_date')
                                ->orWhereDate('recurring_start_date', '<=', $notifySlotWindowEnd->toDateString());
                        })
                        ->where(function ($query) {
                            $query
                                ->whereNull('recurring_end_date')
                                ->orWhereDate('recurring_end_date', '>=', now()->toDateString());
                        });
                });
        })
        ->orderBy('doctor_id')
        ->orderBy('date')
        ->orderBy('start_time')
        ->get();

    $notifySlotOptions = collect();
    foreach ($notifyAvailableSlots as $availability) {
        $doctorName = $availability->doctor
            ? trim(($availability->doctor->first_name ?? '') . ' ' . ($availability->doctor->last_name ?? ''))
            : 'Doctor';

        $dates = [];
        if ($availability->is_recurring) {
            $cursor = now()->copy()->startOfDay();
            $recurringStart = $availability->recurring_start_date
                ? \Carbon\Carbon::parse($availability->recurring_start_date)
                : $cursor->copy();
            $recurringEnd = $availability->recurring_end_date
                ? \Carbon\Carbon::parse($availability->recurring_end_date)
                : $notifySlotWindowEnd->copy();
            $blockedDates = collect($availability->blocked_dates ?? [])
                ->map(fn($date) => \Carbon\Carbon::parse($date)->toDateString())
                ->all();

            while ($cursor->lte($notifySlotWindowEnd)) {
                if (
                    $cursor->gte($recurringStart) &&
                    $cursor->lte($recurringEnd) &&
                    strtolower($cursor->format('l')) === strtolower((string) $availability->day_of_week) &&
                    !in_array($cursor->toDateString(), $blockedDates, true)
                ) {
                    $dates[] = $cursor->copy();
                }

                $cursor->addDay();
            }
        } elseif ($availability->date && \Carbon\Carbon::parse($availability->date)->gte(now()->startOfDay())) {
            $dates[] = \Carbon\Carbon::parse($availability->date);
        }

        foreach ($dates as $date) {
            $startTime = \Carbon\Carbon::parse($availability->start_time)->format('H:i:s');
            $endTime = \Carbon\Carbon::parse($availability->end_time)->format('H:i:s');
            $notifySlotOptions->push([
                'key' => implode('|', [$availability->id, $date->toDateString(), $startTime, $endTime]),
                'doctor_id' => $availability->doctor_id,
                'doctor_name' => $doctorName,
                'label' => trim(
                    implode(
                        ' | ',
                        array_filter([
                            $date->format('d M Y'),
                            \Carbon\Carbon::parse($startTime)->format('h:i A') . ' - ' . \Carbon\Carbon::parse($endTime)->format('h:i A'),
                            $doctorName,
                        ]),
                    ),
                ),
                'sort' => $date->toDateString() . ' ' . $startTime,
            ]);
        }
    }
    $notifySlotOptions = $notifySlotOptions->sortBy(['doctor_name', 'sort'])->values();
    $notifyDoctorOptions = $notifySlotOptions->unique('doctor_id')->values();

    $statusColors = [
        AppointmentStatus::PENDING->value => 'bg-yellow-100 text-yellow-800 border-yellow-200',
        AppointmentStatus::CONFIRMED->value => 'bg-blue-100 text-blue-800 border-blue-200',
        AppointmentStatus::COMPLETED->value => 'bg-green-100 text-green-800 border-green-200',
        AppointmentStatus::CANCELLED->value => 'bg-red-100 text-red-800 border-red-200',
        AppointmentStatus::FAILED->value => 'bg-red-100 text-red-800 border-red-200',
    ];

    // Account / login helpers (matches API login restrictions)
    $user = $patient->user;

    $userStatusValue = $userStatus;
    $emailVerifiedAt = $user?->email_verified_at;
    $isEmailVerified = filled($emailVerifiedAt);

    $registrationStatusLabel = match ($userStatusValue) {
        AuthStatus::new_register->value => 'New (OTP not verified)',
        AuthStatus::verified->value => 'Email verified (profile pending)',
        AuthStatus::registered->value => 'Registered (login allowed)',
        default => 'Unknown',
    };

    $canLogin = (bool) $user && $userStatus === AuthStatus::registered->value && $isEmailVerified;

    $loginRestrictionReason = null;
    if (!$user) {
        $loginRestrictionReason = 'User account is not created for this patient.';
    } elseif ($userStatus !== AuthStatus::registered->value) {
        $loginRestrictionReason = 'Patient is not fully registered yet.';
    } elseif (!$isEmailVerified) {
        $loginRestrictionReason = 'Email is not verified yet.';
    } else {
        $loginRestrictionReason = 'User can login successfully.';
    }

    $isActive = $canLogin;
    $isRegistered = $userStatusValue === AuthStatus::registered->value;

    $formatFieldValue = function ($value): string {
        if (is_null($value) || $value === '') {
            return '—';
        }
        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->format('d M, Y');
        }
        if ($value instanceof \BackedEnum) {
            return (string) $value->value;
        }
        if (is_array($value)) {
            // For multi-value fields stored as arrays/JSON
            return implode(', ', array_filter(array_map(fn($v) => (string) $v, $value)));
        }
        if (is_object($value)) {
            // Many model attributes may be enums coming through as objects
            if (property_exists($value, 'value')) {
                return (string) $value->value;
            }
            if (property_exists($value, 'name')) {
                return (string) $value->name;
            }
        }
        return (string) $value;
    };

    $formatOptionLabel = function ($value, array $labels) use ($formatFieldValue): string {
        if (is_object($value) && property_exists($value, 'value')) {
            $value = $value->value;
        }

        if (is_null($value) || $value === '') {
            return '—';
        }

        return $labels[(string) $value] ?? Str::of((string) $value)->replace('_', ' ')->title()->toString();
    };

    $patientRows = [];
    $patientFieldValuesByKey = [];
    foreach ($patientFillableKeys as $key) {
        $label = Str::of($key)->replace('_', ' ')->title()->__toString();
        $patientRows[$label] = $patient->{$key} ?? null;
        $patientFieldValuesByKey[$key] = $patient->{$key} ?? null;
    }

    // Group patient fields into professional cards.
    $personalPatientKeys = array_values(
        array_unique([
            'first_name',
            'last_name',
            'age',
            'email',
            'mobile_no',
            'alternate_no',
            'gender',
            'date_of_birth',
            'blood_group',
            'marital_status',
            'partner_relation_type',
            'spouse_name',
            'father_name',
            'wife_name',
            'husband_name',
            'guardian_name',
            'nationality',
            'bio',
        ]),
    );

    $locationPatientKeys = array_values(
        array_unique(['address', 'pincode', 'area', 'city', 'landmark', 'state', 'nationality']),
    );

    $medicalPatientKeys = array_values(
        array_unique(['allergies', 'existing_conditions', 'current_medications', 'past_medical_history']),
    );

    $emergencyInsurancePatientKeys = array_values(
        array_unique([
            'emergency_contact_name',
            'emergency_contact_relationship',
            'emergency_contact_phone',
            'insurance_provider',
            'insurance_policy_number',
            'insurance_policy_expiry',
            'insurance_tpa_details',
            'treatment_consent_accepted',
            'is_existing_patient',
            'existing_patient_id',
            'source',
        ]),
    );

    $usedPatientKeys = array_values(
        array_unique(
            array_merge(
                $personalPatientKeys,
                $locationPatientKeys,
                $medicalPatientKeys,
                $emergencyInsurancePatientKeys,
            ),
        ),
    );

    $otherPatientKeys = array_values(array_diff($patientFillableKeys, $usedPatientKeys));

    $labelForKey = function (string $key): string {
        return Str::of($key)->replace('_', ' ')->title()->__toString();
    };

    $genderValue =
        is_object($patient->gender ?? null) && property_exists($patient->gender, 'value')
            ? $patient->gender->value
            : $patient->gender ?? null;
    $maritalStatusValue =
        is_object($patient->marital_status ?? null) && property_exists($patient->marital_status, 'value')
            ? $patient->marital_status->value
            : $patient->marital_status ?? null;
    $familyRows =
        $maritalStatusValue === \App\Enums\MaritalStatus::MARRIED->value
            ? ['Husband Name' => $patient->husband_name ?? null]
            : ['Father Name' => $patient->father_name ?? null];

    $identityRows = [
        'Gender' => $formatOptionLabel($patient->gender, \App\Enums\GenderOption::labels()),
        'Marital Status' => $formatOptionLabel($patient->marital_status, \App\Enums\MaritalStatus::labels()),
        'Date of Birth' => $patient->date_of_birth ?? null,
        'Age' => $patient->age ?? null,
        // Fix: merge the $familyRows key-value into the main array, not as an array element itself
        // This handles only one key in $familyRows, so array_merge is used safely
        ...$familyRows,
        'Blood Group' => $patient->blood_group ?? null,
    ];

    $familyRows = array_filter($familyRows, fn($value) => filled($value));

    $personalContactRows = [
        'Email' => $email,
        'Mobile No.' => $patient->mobile_no ?? $phone,
        'Alternate No.' => $patient->alternate_no ?? null,
        'Nationality' => $patient->nationality ?? null,
    ];

    $notifyEmailDefault = old('email', $email);
    $notifySlotDefault = old(
        'slot_key',
        $notifySlotOptions->firstWhere('doctor_id', $notifyDefaultDoctorId)['key'] ?? $notifySlotOptions->first()['key'] ?? null,
    );

    $accountRows = [
        'User Created' => (bool) $user,
        'Registration Status' => $registrationStatusLabel,
        'User Status Value' => $userStatus,
        'Email Verified' => $isEmailVerified ? $emailVerifiedAt : null,
        'Can Login' => $canLogin,
        'Login Restriction Reason' => $loginRestrictionReason,
        'User Email' => $email,
        'User Phone' => $phone,
    ];

    $consultationTypeLabel = function ($type) {
        return match ((string) $type) {
            'video' => 'Online (video)',
            'in-person', 'clinic' => 'In-person',
            default => Str::title(str_replace('-', ' ', (string) $type)),
        };
    };

    $normalizeVisitReason = function ($reason): array|string|null {
        if (is_null($reason) || $reason === '') {
            return null;
        }

        if (is_array($reason)) {
            return $reason;
        }

        if (is_string($reason)) {
            $decoded = json_decode($reason, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }

            return $reason;
        }

        return (string) $reason;
    };

    $appointmentPaymentId = function ($appointment): array {
        // Returns: [label, idOrDash, method]
        $payment = $appointment->payment;
        if (!$payment) {
            return ['Payment ID', '—', null];
        }

        $method = (string) ($payment->payment_method ?? '');
        $isCash = strtolower($method) === 'cash';

        $id = null;
        $label = $isCash ? 'Cash Transaction ID' : 'Payment ID';
        if ($isCash) {
            $id =
                $payment->transaction_id ??
                (is_array($payment->notes) ? $payment->notes['cash_transaction_id'] ?? null : null);
        } else {
            $id = $payment->razorpay_payment_id ?? ($payment->transaction_id ?? ($payment->razorpay_order_id ?? null));
        }

        return [$label, filled($id) ? (string) $id : '—', $method ?: null];
    };
@endphp

<x-dynamic-component :component="$getEntryWrapperView()" :entry="$entry">
    <div class="flex flex-col gap-6 pb-8">
        {{-- 1. HERO PROFILE --}}
        <div
            class="relative bg-white dark:bg-gray-900 rounded-2xl overflow-hidden border border-gray-200 dark:border-gray-800">
            <div class="absolute inset-x-0 top-0 h-24 bg-gradient-to-r from-primary/80 to-primary/40"></div>

            <div class="relative pt-12 px-6 sm:px-8 pb-8 flex flex-col md:flex-row gap-6 items-start md:items-center">
                <div class="shrink-0 relative">
                    <div
                        class="w-28 h-28 rounded-full border-4 border-white dark:border-gray-900 overflow-hidden bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
                        @if (!empty($patient->avatar))
                            <img src="{{ storage_url($patient->avatar) }}" alt="{{ $fullName }}"
                                class="w-full h-full object-cover" />
                        @else
                            <x-heroicon-o-user class="w-16 h-16 text-gray-400" />
                        @endif
                    </div>
                    @if ($canLogin)
                        <span
                            class="absolute bottom-2 right-2 w-4 h-4 bg-green-500 border-2 border-white dark:border-gray-900 rounded-full flex items-center justify-center"
                            title="Login Active">
                            <span
                                class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                        </span>
                    @elseif ($user)
                        <span
                            class="absolute bottom-2 right-2 w-4 h-4 bg-yellow-500 border-2 border-white dark:border-gray-900 rounded-full flex items-center justify-center"
                            title="Login Restricted">
                            <span
                                class="animate-ping absolute inline-flex h-full w-full rounded-full bg-yellow-300 opacity-75"></span>
                        </span>
                    @else
                        <span
                            class="absolute bottom-2 right-2 w-4 h-4 bg-red-500 border-2 border-white dark:border-gray-900 rounded-full flex items-center justify-center"
                            title="No User Account">
                            <span
                                class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-300 opacity-75"></span>
                        </span>
                    @endif
                </div>

                <div class="flex-1 space-y-3">
                    <div>
                        <h1 class="text-2xl font-extrabold text-gray-900 dark:text-white tracking-tight">
                            {{ $fullName }}
                        </h1>

                        <div class="flex flex-wrap items-center gap-2 mt-2">
                            <span
                                class="inline-flex items-center gap-1 text-xs font-bold px-3 py-1 rounded-md border
                                {{ $canLogin ? 'bg-green-100 text-green-800 border-green-200' : 'bg-red-100 text-red-800 border-red-200' }}">
                                <x-dynamic-component :component="$canLogin ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle'" class="w-4 h-4" />
                                {{ $canLogin ? 'Login Active' : 'Login Restricted' }}
                            </span>

                            <span class="text-xs font-bold px-3 py-1 bg-primary/10 rounded-md border border-primary/20">
                                {{ $registrationStatusLabel }}
                            </span>

                            <span
                                class="text-xs font-bold px-3 py-1 bg-gray-50 dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-md">
                                Email: {{ $isEmailVerified ? 'Verified' : 'Unverified' }}
                            </span>
                        </div>

                        <div class="flex flex-wrap items-center gap-2 mt-2">
                            @if (!empty($patient->gender))
                                <span class="text-xs font-bold text-primary px-3 py-1 bg-primary/10 rounded-md">
                                    {{ is_object($patient->gender) ? $patient->gender->value : $patient->gender }}
                                </span>
                            @endif
                            @if (!empty($patient->blood_group))
                                <span class="text-xs font-bold text-primary px-3 py-1 bg-primary/10 rounded-md">
                                    Blood Group: {{ $patient->blood_group }}
                                </span>
                            @endif
                        </div>

                        <div class="flex flex-wrap items-center gap-3 mt-3 text-sm text-gray-600 dark:text-gray-300">
                            @if (!empty($patient->date_of_birth))
                                <span
                                    class="flex items-center gap-1.5 px-3 py-1 bg-gray-50 dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-full font-medium">
                                    <x-heroicon-o-cake class="w-4 h-4 text-primary" />
                                    {{ \Carbon\Carbon::parse($patient->date_of_birth)->format('d M, Y') }}
                                </span>
                            @endif
                            @if (!empty($patient->marital_status))
                                <span
                                    class="flex items-center gap-1.5 px-3 py-1 bg-gray-50 dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-full font-medium">
                                    <x-heroicon-o-users class="w-4 h-4 text-primary" />
                                    {{ is_object($patient->marital_status) ? $patient->marital_status->value : $patient->marital_status }}
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="flex flex-col md:flex-row md:items-center gap-2 w-full md:w-auto">
                        @if ($phone)
                            <a href="tel:{{ $phone }}"
                                class="flex items-center gap-2 px-4 py-2 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                                <x-heroicon-o-phone class="w-4 h-4 text-primary" />
                                <span
                                    class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ $phone }}</span>
                            </a>
                        @endif
                        @if ($email)
                            <a href="mailto:{{ $email }}"
                                class="flex items-center gap-2 px-4 py-2 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition text-wrap break-all">
                                <x-heroicon-o-envelope class="w-4 h-4 text-primary" />
                                <span
                                    class="text-sm font-medium text-gray-700 dark:text-gray-200 truncate max-w-[200px]">{{ $email }}</span>
                            </a>
                        @endif
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex flex-col gap-2 w-full md:w-auto" x-data="{
                    notifyOpen: {{ $errors->any() ? 'true' : 'false' }},
                    notifyDoctor: '{{ $notifyDefaultDoctorId }}',
                    selectedKey: '{{ $notifySlotDefault }}',
                    selectedLabel: '',
                    search: '',
                    openSlotDropdown: false,
                    slots: @js($notifySlotOptions),
                    init() {
                        let initial = this.slots.find(s => s.key === this.selectedKey);
                        if (initial) {
                            this.selectedLabel = initial.label;
                        }
                    },
                    selectSlot(slot) {
                        this.selectedKey = slot.key;
                        this.selectedLabel = slot.label;
                        this.openSlotDropdown = false;
                    },
                    updateDefaultSlotForDoctor(docId) {
                        if (!docId) {
                            this.selectedKey = '';
                            this.selectedLabel = '';
                            return;
                        }
                        let defaultSlot = this.slots.find(s => String(s.doctor_id) === String(docId));
                        if (defaultSlot) {
                            this.selectedKey = defaultSlot.key;
                            this.selectedLabel = defaultSlot.label;
                        } else {
                            this.selectedKey = '';
                            this.selectedLabel = '';
                        }
                    },
                    get filteredSlots() {
                        let docId = this.notifyDoctor;
                        return this.slots.filter(s => {
                            let matchDoc = !docId || String(s.doctor_id) === String(docId);
                            let matchSearch = !this.search || s.label.toLowerCase().includes(this.search.toLowerCase());
                            return matchDoc && matchSearch;
                        });
                    }
                }">
                    @if ($phone)
                        <a href="tel:{{ $phone }}"
                            class="px-4 py-2 rounded-lg bg-primary text-white text-sm font-semibold hover:bg-primary/90 transition text-center">
                            Call Patient
                        </a>
                    @endif

                    <button type="button" @click="notifyOpen = true"
                        class="px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-600/90 transition text-center">
                        Notify Patient (next appt)
                    </button>

                    @if (session('patient_notification_sent'))
                        <div
                            class="text-xs font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-lg px-3 py-2">
                            Email sent to {{ session('patient_notification_sent') }}.
                        </div>
                    @endif

                    @if (session('patient_notification_error'))
                        <div
                            class="text-xs font-semibold text-red-700 bg-red-50 border border-red-200 rounded-lg px-3 py-2">
                            {{ session('patient_notification_error') }}
                        </div>
                    @endif

                    <div x-show="notifyOpen" x-cloak
                        class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4"
                        @keydown.escape.window="notifyOpen = false">
                        <div class="absolute inset-0" @click="notifyOpen = false"></div>

                        <form method="POST" action="{{ route('admin.patients.notify-next', $patient) }}"
                            class="relative w-full max-w-xl rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-xl">
                            @csrf

                            <div
                                class="flex items-start justify-between gap-4 px-5 py-4 border-b border-gray-100 dark:border-gray-800">
                                <div>
                                    <h3 class="text-base font-bold text-gray-900 dark:text-white">Send Appointment Slot
                                        Email</h3>
                                    <p class="text-sm text-gray-500 mt-1">Send previous and next appointment details to
                                        a selected email.</p>
                                </div>
                                <button type="button" @click="notifyOpen = false"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50"
                                    title="Close">
                                    <x-heroicon-o-x-mark class="w-4 h-4" />
                                </button>
                            </div>

                            <div class="p-5 space-y-4">
                                <label class="block">
                                    <span class="text-xs font-semibold text-gray-600">Recipient Email</span>
                                    <input type="email" name="email" value="{{ $notifyEmailDefault }}" required
                                        class="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary focus:ring-primary dark:bg-gray-950 dark:border-gray-700" />
                                    @error('email')
                                        <span
                                            class="text-xs font-semibold text-red-600 mt-1 block">{{ $message }}</span>
                                    @enderror
                                </label>

                                <label class="block">
                                    <span class="text-xs font-semibold text-gray-600">Admin Note</span>
                                    <textarea name="message" rows="3"
                                        class="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary focus:ring-primary dark:bg-gray-950 dark:border-gray-700"
                                        placeholder="Optional note for the patient">{{ old('message') }}</textarea>
                                    @error('message')
                                        <span
                                            class="text-xs font-semibold text-red-600 mt-1 block">{{ $message }}</span>
                                    @enderror
                                </label>

                                <label class="block">
                                    <span class="text-xs font-semibold text-gray-600">Filter Doctor</span>
                                    <select name="doctor_id" x-model="notifyDoctor" @change="updateDefaultSlotForDoctor(notifyDoctor)" required
                                        class="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary focus:ring-primary dark:bg-gray-950 dark:border-gray-700">
                                        <option value="">Choose doctor</option>
                                        @foreach ($notifyDoctorOptions as $doctorOption)
                                            <option value="{{ $doctorOption['doctor_id'] }}" @selected((string) $notifyDefaultDoctorId === (string) $doctorOption['doctor_id'])>
                                                {{ $doctorOption['doctor_name'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('doctor_id')
                                        <span
                                            class="text-xs font-semibold text-red-600 mt-1 block">{{ $message }}</span>
                                    @enderror
                                </label>

                                <label class="block relative">
                                    <span class="text-xs font-semibold text-gray-600">Select Available Slot</span>
                                    
                                    <!-- Hidden input to submit slot_key -->
                                    <input type="hidden" name="slot_key" x-model="selectedKey" required>

                                    <!-- Custom Select Trigger Button -->
                                    <div class="relative mt-1">
                                        <button type="button" @click="openSlotDropdown = !openSlotDropdown"
                                            class="w-full bg-white dark:bg-gray-950 border border-gray-300 dark:border-gray-700 rounded-lg py-2 px-3 text-left text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary flex items-center justify-between text-gray-800 dark:text-gray-200">
                                            <span x-text="selectedLabel || 'Choose appointment slot'" class="truncate"></span>
                                            <svg class="w-4 h-4 text-gray-400 shrink-0 ml-2 transition-transform duration-200" x-bind:class="openSlotDropdown ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                            </svg>
                                        </button>

                                        <!-- Dropdown Menu -->
                                        <div x-show="openSlotDropdown" @click.away="openSlotDropdown = false" x-cloak
                                            class="absolute left-0 right-0 z-50 mt-1 w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg shadow-lg py-1"
                                            style="box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05); border: 1px solid #e5e7eb;">
                                            
                                            <!-- Search Bar -->
                                            <div class="p-2 border-b border-gray-100 dark:border-gray-800 flex items-center gap-2"
                                                style="border-bottom: 1px solid #f3f4f6; display: flex; align-items: center; gap: 8px; padding: 8px;">
                                                <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                                </svg>
                                                <input type="text" x-model="search" placeholder="Search slots..."
                                                    class="w-full border-0 p-0 text-xs focus:ring-0 focus:outline-none dark:bg-transparent dark:text-white placeholder-gray-400"
                                                    style="width: 100%; border: none; padding: 4px 8px; font-size: 12px; outline: none; background: transparent; box-shadow: none;"
                                                    @keydown.escape.stop="openSlotDropdown = false">
                                            </div>

                                            <!-- Scrollable list of slots -->
                                            <div class="divide-y divide-gray-50 dark:divide-gray-800/50"
                                                style="max-height: 200px; overflow-y: auto;">
                                                <template x-for="slot in filteredSlots" :key="slot.key">
                                                    <button type="button" @click="selectSlot(slot)"
                                                        class="w-full text-left px-3 py-2 text-xs hover:bg-gray-50 dark:hover:bg-gray-800/50 flex flex-col gap-0.5 transition"
                                                        style="width: 100%; text-align: left; padding: 8px 12px; border: none; background: transparent; cursor: pointer; display: block; margin: 0; outline: none; box-shadow: none;"
                                                        x-bind:class="selectedKey === slot.key ? 'bg-primary/10 text-primary font-bold' : 'text-gray-700 dark:text-gray-300'">
                                                        <span x-text="slot.label"></span>
                                                    </button>
                                                </template>
                                                <div x-show="filteredSlots.length === 0" class="px-3 py-4 text-xs text-gray-500 text-center"
                                                    style="padding: 16px; text-align: center; color: #9ca3af; font-size: 12px;">
                                                    No matching slots found.
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    @error('slot_key')
                                        <span class="text-xs font-semibold text-red-600 mt-1 block">{{ $message }}</span>
                                    @enderror
                                    @if ($notifySlotOptions->isEmpty())
                                        <span class="text-xs font-semibold text-red-600 mt-1 block">No future availability slots found.</span>
                                    @endif
                                </label>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div class="rounded-lg border border-gray-200 dark:border-gray-800 p-3">
                                        <div class="text-xs font-semibold text-gray-500">Previous Appointment Reference</div>
                                        <div class="mt-1 text-sm font-bold text-gray-900 dark:text-white">
                                            {{ $notifyPreviousAppointment ? $notifyPreviousAppointment->appointment_date?->format('d M Y') . ' ' . \Carbon\Carbon::parse($notifyPreviousAppointment->appointment_time)->format('h:i A') : 'No previous slot' }}
                                        </div>
                                    </div>
                                    <div class="rounded-lg border border-gray-200 dark:border-gray-800 p-3">
                                        <div class="text-xs font-semibold text-gray-500">Default Doctor</div>
                                        <div class="mt-1 text-sm font-bold text-gray-900 dark:text-white">
                                            {{ $notifyReferenceAppointment?->doctor ? trim(($notifyReferenceAppointment->doctor->first_name ?? '') . ' ' . ($notifyReferenceAppointment->doctor->last_name ?? '')) : 'No doctor reference' }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-end gap-2 px-5 py-4 border-t border-gray-100 dark:border-gray-800">
                                <button type="button" @click="notifyOpen = false"
                                    class="px-4 py-2 rounded-lg border border-gray-200 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                                    Cancel
                                </button>
                                <button type="submit"
                                    class="px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-600/90">
                                    Send Email
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- 2. PATIENT DETAILS --}}
        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-6">
            <div class="flex items-center justify-between gap-4 flex-wrap">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <x-heroicon-o-document-text class="w-5 h-5 text-primary" />
                    Patient Details
                </h2>

                <div class="flex items-center gap-2">
                    <span
                        class="text-xs font-semibold px-3 py-1 rounded-full border
                        {{ $canLogin ? 'bg-green-100 text-green-800 border-green-200' : 'bg-red-100 text-red-800 border-red-200' }}">
                        {{ $canLogin ? 'Login Active' : 'Login Restricted' }}
                    </span>
                    <span
                        class="text-xs font-semibold px-3 py-1 rounded-full border
                        {{ $isRegistered ? 'bg-primary/10 text-primary border-primary/20' : 'bg-gray-100 text-gray-700 border-gray-200' }}">
                        {{ $isRegistered ? 'Fully Registered' : 'Not Fully Registered' }}
                    </span>
                </div>
            </div>

            <div class="mt-5 flex flex-col lg:flex-row gap-6 items-start">
                <div class="w-full lg:w-1/3 flex flex-col gap-4">
                    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-5">
                        <h3 class="text-base font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                            <x-heroicon-o-user-circle class="w-5 h-5 text-primary" />
                            Personal Details
                        </h3>

                        <table class="w-full text-sm">
                            <tbody>
                                @foreach ($identityRows as $label => $value)
                                    <tr class="border-b border-gray-100 dark:border-gray-800">
                                        <td class="py-2 pr-3 text-xs font-semibold text-gray-500 w-[42%] align-top">
                                            {{ $label }}
                                        </td>
                                        <td
                                            class="py-2 font-bold text-gray-900 dark:text-white text-right break-words w-[58%] align-top">
                                            {{ $formatFieldValue($value) }}
                                        </td>
                                    </tr>
                                @endforeach

                                @foreach ($personalContactRows as $label => $value)
                                    <tr class="border-b border-gray-100 dark:border-gray-800">
                                        <td class="py-2 pr-3 text-xs font-semibold text-gray-500 w-[42%] align-top">
                                            {{ $label }}
                                        </td>
                                        <td
                                            class="py-2 font-bold text-gray-900 dark:text-white text-right break-words w-[58%] align-top">
                                            {{ $formatFieldValue($value) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                    </div>

                    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-5">
                        <h3 class="text-base font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                            <x-heroicon-o-map-pin class="w-5 h-5 text-primary" />
                            Location
                        </h3>

                        <div class="space-y-3">
                            @foreach ($locationPatientKeys as $key)
                                @php
                                    $value = $patientFieldValuesByKey[$key] ?? null;
                                    $label = $labelForKey($key);
                                @endphp
                                <div class="flex items-start justify-between gap-4">
                                    <span
                                        class="text-xs font-medium text-gray-500 whitespace-nowrap pr-2">{{ $label }}</span>
                                    <span class="text-sm font-semibold text-gray-900 text-right break-words">
                                        {{ $formatFieldValue($value) }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-5">
                        <h3 class="text-base font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                            <x-heroicon-o-shield-check class="w-5 h-5 text-primary" />
                            Login & Verification
                        </h3>

                        <div class="space-y-3">
                            <div class="flex items-start justify-between gap-4">
                                <span class="text-xs font-medium text-gray-500 whitespace-nowrap pr-2">Can Login</span>
                                <span class="text-sm font-semibold text-gray-900 text-right break-words">
                                    {{ $canLogin ? 'Yes' : 'No' }}
                                </span>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <span class="text-xs font-medium text-gray-500 whitespace-nowrap pr-2">Registration
                                    Status</span>
                                <span class="text-sm font-semibold text-gray-900 text-right break-words">
                                    {{ $registrationStatusLabel }}
                                </span>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <span class="text-xs font-medium text-gray-500 whitespace-nowrap pr-2">Email
                                    Verified</span>
                                <span class="text-sm font-semibold text-gray-900 text-right break-words">
                                    {{ $isEmailVerified ? 'Yes' : 'No' }}
                                </span>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <span class="text-xs font-medium text-gray-500 whitespace-nowrap pr-2">Reason</span>
                                <span class="text-sm font-semibold text-gray-900 text-right break-words">
                                    {{ $canLogin ? '—' : $loginRestrictionReason ?? '—' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="w-full lg:w-2/3 flex flex-col gap-4">
                    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-5">
                        <h3 class="text-base font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                            <x-heroicon-o-heart class="w-5 h-5 text-primary" />
                            Medical Profile
                        </h3>

                        <div class="space-y-3">
                            @foreach ($medicalPatientKeys as $key)
                                @php
                                    $value = $patientFieldValuesByKey[$key] ?? null;
                                    $label = $labelForKey($key);
                                @endphp
                                <div class="flex items-start justify-between gap-4">
                                    <span
                                        class="text-xs font-medium text-gray-500 whitespace-nowrap pr-2">{{ $label }}</span>
                                    <span class="text-sm font-semibold text-gray-900 text-right break-words">
                                        {{ $formatFieldValue($value) }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-5">
                        <h3 class="text-base font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                            <x-heroicon-o-phone-x-mark class="w-5 h-5 text-primary" />
                            Emergency & Insurance
                        </h3>

                        <div class="space-y-3">
                            @foreach ($emergencyInsurancePatientKeys as $key)
                                @php
                                    $value = $patientFieldValuesByKey[$key] ?? null;
                                    $label = $labelForKey($key);
                                @endphp
                                <div class="flex items-start justify-between gap-4">
                                    <span
                                        class="text-xs font-medium text-gray-500 whitespace-nowrap pr-2">{{ $label }}</span>
                                    <span class="text-sm font-semibold text-gray-900 text-right break-words">
                                        {{ $formatFieldValue($value) }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    @if (!empty($otherPatientKeys))
                        <div
                            class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-5">
                            <h3 class="text-base font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                                <x-heroicon-o-squares-2x2 class="w-5 h-5 text-primary" />
                                Other Fields
                            </h3>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                @foreach ($otherPatientKeys as $key)
                                    @php
                                        $value = $patientFieldValuesByKey[$key] ?? null;
                                    @endphp
                                    <div class="flex items-start justify-between gap-4">
                                        <span
                                            class="text-xs font-medium text-gray-500 whitespace-nowrap pr-2">{{ $labelForKey($key) }}</span>
                                        <span class="text-sm font-semibold text-gray-900 text-right break-words">
                                            {{ $formatFieldValue($value) }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- 2. APPOINTMENTS --}}
        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-6">
            <div class="flex items-center justify-between gap-4 flex-wrap">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <x-heroicon-o-calendar-days class="w-5 h-5 text-primary" />
                    Appointments
                    ({{ $selectedWeekIncludesToday ? 'Current Week' : 'Selected Week (' . $weekStart->format('d M') . ' - ' . $weekEnd->format('d M') . ')' }})
                </h2>
                <div class="flex items-center gap-2">
                    @php
                        $prevWeekStart = $weekStart->copy()->subWeek()->toDateString();
                        $nextWeekStart = $weekStart->copy()->addWeek()->toDateString();
                        $prevWeekUrl = request()->fullUrlWithQuery(
                            array_merge(request()->query(), ['week_start' => $prevWeekStart]),
                        );
                        $nextWeekUrl = request()->fullUrlWithQuery(
                            array_merge(request()->query(), ['week_start' => $nextWeekStart]),
                        );
                    @endphp

                    <a href="{{ $prevWeekUrl }}"
                        class="inline-flex items-center justify-center w-9 h-9 rounded-xl border border-gray-200 hover:border-primary/40 hover:bg-primary/5 transition"
                        title="Previous week">
                        <x-heroicon-o-chevron-left class="w-4 h-4 text-gray-500" />
                    </a>

                    <span
                        class="text-xs font-semibold text-primary bg-primary/10 px-3 py-1 rounded-full border border-primary/20">
                        {{ $weekStart->format('d M') }} - {{ $weekEnd->format('d M') }}
                    </span>

                    <a href="{{ $nextWeekUrl }}"
                        class="inline-flex items-center justify-center w-9 h-9 rounded-xl border border-gray-200 hover:border-primary/40 hover:bg-primary/5 transition"
                        title="Next week">
                        <x-heroicon-o-chevron-right class="w-4 h-4 text-gray-500" />
                    </a>
                </div>
            </div>

            <div class="mt-2 flex items-center justify-end gap-2">
                @if ($previousAppointmentUrl)
                    <a href="{{ $previousAppointmentUrl }}"
                        class="inline-flex items-center justify-center w-9 h-9 rounded-xl border border-gray-200 hover:border-primary/40 hover:bg-primary/5 transition"
                        title="Previous appointment">
                        <x-heroicon-o-arrow-left class="w-4 h-4 text-gray-500" />
                    </a>
                @else
                    <span
                        class="inline-flex items-center justify-center w-9 h-9 rounded-xl border border-gray-200 bg-gray-50"
                        title="No previous appointment">
                        <x-heroicon-o-arrow-left class="w-4 h-4 text-gray-300" />
                    </span>
                @endif

                @if ($nextAppointmentUrl)
                    <a href="{{ $nextAppointmentUrl }}"
                        class="inline-flex items-center justify-center w-9 h-9 rounded-xl border border-gray-200 hover:border-primary/40 hover:bg-primary/5 transition"
                        title="Next appointment">
                        <x-heroicon-o-arrow-right class="w-4 h-4 text-gray-500" />
                    </a>
                @else
                    <span
                        class="inline-flex items-center justify-center w-9 h-9 rounded-xl border border-gray-200 bg-gray-50"
                        title="No next appointment">
                        <x-heroicon-o-arrow-right class="w-4 h-4 text-gray-300" />
                    </span>
                @endif
            </div>

            <div class="mt-5" x-data="{ tab: 'current', activeDay: '{{ $currentDayInWeek }}' }">
                {{-- Tabs: Current (today) and Upcoming (after today) --}}
                <div class="flex items-center gap-2 flex-wrap mb-4">
                    <button type="button" @click="tab = 'current'; activeDay = '{{ $currentDayInWeek }}'"
                        x-bind:class="tab === 'current'
                            ?
                            'bg-primary text-white border-primary' :
                            'bg-white text-gray-700 border-gray-200 hover:bg-primary/5 hover:border-primary/40'"
                        class="px-4 py-2 border rounded-xl text-sm font-semibold transition">
                        Current
                    </button>
                    <button type="button" @click="tab = 'upcoming'; activeDay = '{{ $firstUpcomingDay }}'"
                        x-bind:class="tab === 'upcoming'
                            ?
                            'bg-primary text-white border-primary' :
                            'bg-white text-gray-700 border-gray-200 hover:bg-primary/5 hover:border-primary/40'"
                        class="px-4 py-2 border rounded-xl text-sm font-semibold transition">
                        Upcoming
                    </button>
                </div>

                <div class="flex items-center gap-2 flex-wrap">
                    @foreach ($weekDays as $dayDate)
                        @php
                            $dayObj = \Carbon\Carbon::parse($dayDate);
                            $label = $dayObj->format('D');
                            $isCurrentDay = $dayObj->toDateString() === $currentDayInWeek;
                            $isToday = $dayObj->toDateString() === $todayDate;
                        @endphp

                        @if ($isCurrentDay)
                            <button type="button" x-show="tab === 'current'"
                                @click="activeDay = '{{ $dayDate }}'"
                                x-bind:class="activeDay === '{{ $dayDate }}'
                                    ?
                                    'bg-primary text-white border-primary' :
                                    'bg-white text-gray-700 border-gray-200 hover:bg-primary/5 hover:border-primary/40'"
                                class="px-3 py-2 border rounded-xl text-sm font-semibold transition">
                                <span class="mr-1">{{ $label }}</span>
                                <span class="text-xs font-bold opacity-90">{{ $dayObj->format('d') }}</span>
                                @if ($isToday)
                                    <span class="ml-2 text-[10px] font-black uppercase">Today</span>
                                @else
                                    <span class="ml-2 text-[10px] font-black uppercase">Current</span>
                                @endif
                            </button>
                        @elseif ($dayDate > $todayDate)
                            <button type="button" x-show="tab === 'upcoming'"
                                @click="activeDay = '{{ $dayDate }}'"
                                x-bind:class="activeDay === '{{ $dayDate }}'
                                    ?
                                    'bg-primary text-white border-primary' :
                                    'bg-white text-gray-700 border-gray-200 hover:bg-primary/5 hover:border-primary/40'"
                                class="px-3 py-2 border rounded-xl text-sm font-semibold transition">
                                <span class="mr-1">{{ $label }}</span>
                                <span class="text-xs font-bold opacity-90">{{ $dayObj->format('d') }}</span>
                            </button>
                        @endif
                    @endforeach
                </div>

                {{-- Upcoming empty state --}}
                <div x-show="tab === 'upcoming' && activeDay === '__no_upcoming__'"
                    class="text-sm text-gray-500 bg-gray-50/50 border border-gray-100 rounded-xl p-4 text-center"
                    x-cloak>
                    No upcoming appointments in this selected week.
                </div>

                <div class="mt-4 space-y-4">
                    @foreach ($weekDays as $dayDate)
                        @php
                            $dayAppointments = $appointmentsByDay->get($dayDate, collect());
                        @endphp
                        <div x-show="activeDay === '{{ $dayDate }}'" x-cloak>
                            @if ($dayAppointments->isEmpty())
                                <div
                                    class="text-sm text-gray-500 bg-gray-50/50 border border-gray-100 rounded-xl p-4 text-center">
                                    No appointments on {{ \Carbon\Carbon::parse($dayDate)->format('l, d M Y') }}.
                                </div>
                            @else
                                <div class="grid grid-cols-1 gap-3">
                                    @foreach ($dayAppointments as $appointment)
                                        @php
                                            $statusValue =
                                                $appointment->status instanceof AppointmentStatus
                                                    ? $appointment->status->value
                                                    : (string) $appointment->status;

                                            $doctorName = $appointment->doctor
                                                ? trim(
                                                    ($appointment->doctor->first_name ?? '') .
                                                        ' ' .
                                                        ($appointment->doctor->last_name ?? ''),
                                                )
                                                : '—';

                                            $reason = $normalizeVisitReason(
                                                $appointment->visit_reason ?? ($appointment->notes ?? null),
                                            );
                                            $reasonText = is_array($reason)
                                                ? implode(', ', array_filter($reason))
                                                : $reason;

                                            [$paymentLabel, $paymentId, $paymentMethod] = $appointmentPaymentId(
                                                $appointment,
                                            );

                                            $appointmentUrl = \App\Filament\Resources\Appointments\AppointmentResource::getUrl(
                                                'view',
                                                ['record' => $appointment->slug ?? $appointment->id],
                                            );
                                        @endphp

                                        <div
                                            class="p-4 rounded-xl border border-gray-100 hover:border-primary/30 hover:bg-primary-50/30 transition">
                                            <div class="flex items-start justify-between gap-3">
                                                <div class="flex items-start gap-3">
                                                    <span
                                                        class="inline-flex items-center px-3 py-1 rounded-xl text-xs font-semibold border {{ $statusColors[$statusValue] ?? 'bg-gray-100 text-gray-800 border-gray-200' }}">
                                                        <x-dynamic-component :component="match ($statusValue) {
                                                            AppointmentStatus::PENDING->value => 'heroicon-o-clock',
                                                            AppointmentStatus::CONFIRMED->value
                                                                => 'heroicon-o-check-circle',
                                                            AppointmentStatus::COMPLETED->value
                                                                => 'heroicon-o-check-badge',
                                                            AppointmentStatus::RESCHEDULED->value
                                                                => 'heroicon-o-arrow-path',
                                                            AppointmentStatus::CANCELLED->value,
                                                            AppointmentStatus::FAILED->value,
                                                            AppointmentStatus::NO_SHOW->value
                                                                => 'heroicon-o-x-circle',
                                                            default => 'heroicon-o-information-circle',
                                                        }" class="w-4 h-4 mr-2" />
                                                        {{ ucfirst($appointment->status instanceof AppointmentStatus ? $appointment->status->name : $statusValue) }}
                                                    </span>

                                                    <div>
                                                        <div class="text-sm font-bold text-gray-900">
                                                            {{ \Carbon\Carbon::parse($appointment->appointment_date)->format('l, d M Y') }}
                                                        </div>
                                                        <div class="text-sm text-gray-600">
                                                            {{ \Carbon\Carbon::parse($appointment->appointment_time)->format('g:i A') }}
                                                            -
                                                            {{ \Carbon\Carbon::parse($appointment->appointment_end_time)->format('g:i A') }}
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="flex items-center gap-2">
                                                    <div class="text-right">
                                                        <p
                                                            class="text-[11px] font-bold uppercase tracking-wider text-gray-500">
                                                            {{ $paymentLabel }}
                                                        </p>
                                                        <p class="text-sm font-semibold text-gray-900 break-all">
                                                            {{ $paymentId }}
                                                        </p>
                                                    </div>

                                                    <a href="{{ $appointmentUrl }}"
                                                        class="inline-flex items-center justify-center w-9 h-9 rounded-xl border border-primary/20 text-primary hover:bg-primary/10 transition"
                                                        title="Open appointment profile">
                                                        <x-heroicon-o-arrow-top-right-on-square class="w-4 h-4" />
                                                    </a>
                                                </div>
                                            </div>

                                            <div
                                                class="mt-3 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                                                <div class="text-sm text-gray-700 dark:text-gray-200">
                                                    <div class="font-semibold">{{ $doctorName }}</div>
                                                    <div class="text-gray-600">
                                                        {{ $consultationTypeLabel($appointment->consultation_type) }}
                                                    </div>
                                                </div>

                                                @if (!empty($reasonText))
                                                    <div class="text-sm text-gray-700 dark:text-gray-200">
                                                        <span class="font-semibold text-gray-600">Visit Reason:</span>
                                                        <span>{{ $reasonText }}</span>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-dynamic-component>
