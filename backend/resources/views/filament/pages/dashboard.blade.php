<x-filament-panels::page>
    @php
        $user = auth()->user();
        $hasDashboardPermission = $this->hasDashboardPermission();
        $hasAnyPermissions = $this->hasAnyPermissions();
        $userRole = $this->getUserRole();
        $analytics = $this->getDashboardAnalytics();
        $summary = $this->getDashboardSummary();
    @endphp

    @if (!$hasAnyPermissions)
        {{-- User has no permissions - show simple message --}}
        <div class="p-8 bg-white rounded-lg border border-gray-200 shadow-sm">
            <div class="text-center">
                <div class="mb-4">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-gray-900 mb-2">Welcome, {{ $user->name ?? 'User' }}!</h2>
                <p class="text-gray-600 mb-4">You don't have any permissions assigned yet.</p>
                <p class="text-sm text-gray-500">Please contact your administrator to assign permissions to your role
                    ({{ $userRole }}).</p>
            </div>
        </div>
    @elseif(!$hasDashboardPermission)
        {{-- User has permissions but not dashboard permission - show welcome message --}}
        <div class="p-8 bg-white rounded-lg border border-gray-200 shadow-sm">
            <div class="text-center">
                <h2 class="text-2xl font-bold text-gray-900 mb-2">Welcome, {{ $user->name ?? 'User' }}!</h2>
                <p class="text-gray-600 mb-4">You are logged in as <strong>{{ $userRole }}</strong>.</p>
                <p class="text-sm text-gray-500">Use the navigation menu to access the features you have permission to
                    use.</p>
            </div>
        </div>
    @else
        {{-- User has dashboard permission - show stats cards using native Filament --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-0">
            {{-- Total Patients Card --}}
            <div
                class="rounded-2xl bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 shadow-sm px-5 py-4 flex flex-col gap-3">
                <div class="flex items-center justify-between gap-3">
                    <div
                        class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300">
                        <x-heroicon-o-users class="w-5 h-5" />
                    </div>
                    <span
                        class="inline-flex items-center gap-1.5 rounded-full bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300 px-2 py-0.5 text-[11px] font-medium">
                        <span class="w-1.5 h-1.5 rounded-full bg-primary-1000 animate-pulse"></span>
                        Patient base
                    </span>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Total
                        Patients</p>
                    <p class="mt-1 text-3xl font-semibold text-gray-900 dark:text-white">
                        {{ number_format($summary['total_patients'] ?? 0) }}
                    </p>
                </div>
            </div>

            {{-- Appointments Today Card --}}
            <div
                class="rounded-2xl bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 shadow-sm px-5 py-4 flex flex-col gap-3">
                <div class="flex items-center justify-between gap-3">
                    <div
                        class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300">
                        <x-heroicon-o-calendar-days class="w-5 h-5" />
                    </div>
                    <span
                        class="inline-flex items-center gap-1.5 rounded-full bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300 px-2 py-0.5 text-[11px] font-medium">
                        <span
                            class="w-1.5 h-1.5 rounded-full bg-primary-1000 {{ ($summary['appointments_today'] ?? 0) > 0 ? 'animate-pulse' : '' }}"></span>
                        {{ ($summary['appointments_today'] ?? 0) > 0 ? 'In progress' : 'No slots today' }}
                    </span>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                        Appointments Today</p>
                    <p class="mt-1 text-3xl font-semibold text-gray-900 dark:text-white">
                        {{ number_format($summary['appointments_today'] ?? 0) }}
                    </p>
                </div>
            </div>

            {{-- Doctors Available Card --}}
            <div
                class="rounded-2xl bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 shadow-sm px-5 py-4 flex flex-col gap-3">
                <div class="flex items-center justify-between gap-3">
                    <div
                        class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300">
                        <x-heroicon-o-user-group class="w-5 h-5" />
                    </div>
                    <span
                        class="inline-flex items-center gap-1.5 rounded-full bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300 px-2 py-0.5 text-[11px] font-medium">
                        <span class="w-1.5 h-1.5 rounded-full bg-primary-1000"></span>
                        Active doctors
                    </span>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                        Doctors Available</p>
                    <p class="mt-1 text-3xl font-semibold text-gray-900 dark:text-white">
                        {{ number_format($summary['active_doctors'] ?? 0) }}
                    </p>
                </div>
            </div>

            {{-- Pending Reviews Card --}}
            <div
                class="rounded-2xl bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 shadow-sm px-5 py-4 flex flex-col gap-3">
                <div class="flex items-center justify-between gap-3">
                    <div
                        class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300">
                        <x-heroicon-o-star class="w-5 h-5" />
                    </div>
                    <span
                        class="inline-flex items-center gap-1.5 rounded-full bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300 px-2 py-0.5 text-[11px] font-medium">
                        <span class="w-1.5 h-1.5 rounded-full bg-primary-1000"></span>
                        Awaiting review
                    </span>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                        Pending Reviews</p>
                    <p class="mt-1 text-3xl font-semibold text-gray-900 dark:text-white">
                        {{ number_format($summary['pending_reviews'] ?? 0) }}
                    </p>
                </div>
            </div>
        </div>

        {{-- Appointments & Payments Analytics --}}
        <div x-data="appointmentPaymentChart(@js($analytics))" class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-2">
            {{-- Appointments Chart with Video / General tabs --}}
            <div
                class="rounded-2xl bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 shadow-sm p-6 flex flex-col gap-4">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <x-heroicon-o-chart-bar class="w-5 h-5 text-primary" />
                            Appointments Overview
                        </h2>
                        <div class="mt-1 flex items-center gap-3 text-[11px] text-gray-500">
                            <div
                                class="inline-flex items-center gap-1 rounded-full bg-gray-100 dark:bg-gray-800 px-1 py-0.5">
                                <button type="button" class="px-2 py-0.5 rounded-full"
                                    :class="period === 'week' ?
                                        'bg-white dark:bg-gray-900 text-gray-900 dark:text-white shadow-sm' :
                                        'text-gray-500 dark:text-gray-400'"
                                    @click="setPeriod('week')">
                                    Week
                                </button>
                                <button type="button" class="px-2 py-0.5 rounded-full"
                                    :class="period === 'month' ?
                                        'bg-white dark:bg-gray-900 text-gray-900 dark:text-white shadow-sm' :
                                        'text-gray-500 dark:text-gray-400'"
                                    @click="setPeriod('month')">
                                    Month
                                </button>
                            </div>
                            <span>
                                <span x-show="period === 'week'">Last 7 days · <span class="font-medium"
                                        x-text="currentMonthLabel"></span></span>
                                <span x-show="period === 'month'">Last 12 months overview</span>
                            </span>
                        </div>
                    </div>

                    <div
                        class="inline-flex items-center gap-1 rounded-full bg-gray-100 dark:bg-gray-800 p-1 text-xs font-medium">
                        <button type="button" class="px-3 py-1 rounded-full transition"
                            :class="activeAppointmentTab === 'all'
                                ?
                                'bg-white dark:bg-gray-900 text-gray-900 dark:text-white shadow-sm' :
                                'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white'"
                            @click="setAppointmentTab('all')">
                            All
                        </button>
                        <button type="button" class="px-3 py-1 rounded-full transition"
                            :class="activeAppointmentTab === 'video'
                                ?
                                'bg-white dark:bg-gray-900 text-primary-700 dark:text-primary-300 shadow-sm' :
                                'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white'"
                            @click="setAppointmentTab('video')">
                            Video
                        </button>
                        <button type="button" class="px-3 py-1 rounded-full transition"
                            :class="activeAppointmentTab === 'general'
                                ?
                                'bg-white dark:bg-gray-900 text-primary-700 dark:text-primary-300 shadow-sm' :
                                'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white'"
                            @click="setAppointmentTab('general')">
                            General
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between text-xs text-gray-500 mt-1">
                    <div class="flex items-center gap-3">
                        <div class="flex items-center gap-1.5">
                            <span class="inline-block w-2 h-2 rounded-full bg-primary-1000"></span>
                            <span>Appointments</span>
                        </div>
                        <span class="hidden sm:inline text-[11px] text-gray-400">
                            Hover bars to compare days.
                        </span>
                    </div>
                    <span
                        class="text-[11px] px-2 py-0.5 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300">
                        <span x-show="period === 'week'">7 day window</span>
                        <span x-show="period === 'month'">12 month window</span>
                    </span>
                </div>

                <div class="mt-3">
                    <div
                        class="relative h-52 rounded-2xl border border-dashed border-gray-200 dark:border-gray-800 bg-gradient-to-b from-slate-50 to-white dark:from-slate-900/60 dark:to-slate-950 overflow-hidden px-4 py-4">
                        {{-- Soft grid lines --}}
                        <div class="absolute inset-x-4 inset-y-4 pointer-events-none">
                            <div class="h-full flex flex-col justify-between opacity-60">
                                <div class="border-t border-gray-100 dark:border-gray-800"></div>
                                <div class="border-t border-gray-100 dark:border-gray-800"></div>
                                <div class="border-t border-gray-100 dark:border-gray-800"></div>
                            </div>
                        </div>

                        <div class="relative h-full flex flex-col justify-between">
                            <svg viewBox="0 0 100 40" preserveAspectRatio="none"
                                class="w-full h-32 overflow-visible">
                                <defs>
                                    <linearGradient id="appointmentsArea" x1="0" y1="0"
                                        x2="0" y2="1">
                                        <stop offset="0%" stop-color="#075bd9" stop-opacity="0.25" />
                                        <stop offset="100%" stop-color="#075bd9" stop-opacity="0" />
                                    </linearGradient>
                                </defs>

                                <path fill="url(#appointmentsArea)" stroke="none" :d="appointmentAreaPath"></path>

                                <polyline fill="none" stroke="#075bd9" stroke-width="0.8"
                                    stroke-linecap="round" stroke-linejoin="round" :points="appointmentLinePoints">
                                </polyline>

                                <template x-for="(value, index) in currentAppointments" :key="index">
                                    <circle r="2" fill="white" stroke="#075bd9" stroke-width="1"
                                        :cx="pointForIndex(index, currentAppointments.length).x"
                                        :cy="pointForIndex(index, currentAppointments.length).yFor(value,
                                            maxAppointmentValue)"
                                        style="pointer-events: auto; cursor: pointer;"
                                        @mouseenter="showTooltip('appointments', index)" @mouseleave="hideTooltip">
                                    </circle>
                                </template>
                            </svg>

                            {{-- Appointments tooltip --}}
                            <div x-show="tooltip.visible && tooltip.kind === 'appointments'"
                                class="pointer-events-none absolute z-10 rounded-lg bg-gray-900 text-white text-[11px] px-3 py-1.5 shadow-lg"
                                :style="`left: calc(${tooltip.x}%); top: 0.5rem; transform: translateX(-50%);`">
                                <div class="flex items-center gap-2">
                                    <span class="font-medium" x-text="tooltip.label"></span>
                                    <span class="inline-flex items-center gap-1 text-primary-300">
                                        <span class="inline-block w-1.5 h-1.5 rounded-full bg-primary-400"></span>
                                        <span x-text="tooltip.value + ' appointments'"></span>
                                    </span>
                                </div>
                            </div>

                            <div class="mt-2 flex items-end justify-between gap-2">
                                <template x-for="(value, index) in currentAppointments" :key="`label-${index}`">
                                    <div class="flex flex-col items-center gap-0.5 min-w-[1.75rem]">
                                        <span class="text-[10px] font-medium text-gray-500 dark:text-gray-400"
                                            x-text="labels[index]"></span>
                                        <span class="text-[11px] font-semibold text-gray-900 dark:text-gray-100"
                                            x-text="value"></span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Payments Chart --}}
            <div
                class="rounded-2xl bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 shadow-sm p-6 flex flex-col gap-4">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <x-heroicon-o-banknotes class="w-5 h-5 text-primary" />
                            Payments Trend
                        </h2>
                        <p class="text-xs text-gray-500 mt-1">
                            Captured payments for the last 7 days.
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="text-[11px] uppercase tracking-wide text-gray-400">Total Paid</p>
                        <p class="text-base font-semibold text-primary-600 dark:text-primary-400"
                            x-text="formattedTotalPayments"></p>
                    </div>
                </div>

                <div class="flex items-center justify-between text-xs text-gray-500">
                    <div class="flex items-center gap-1.5">
                        <span class="inline-block w-2 h-2 rounded-full bg-primary-1000"></span>
                        <span>Payments (₹)</span>
                    </div>
                    <span
                        class="text-[11px] px-2 py-0.5 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300">
                        <span x-show="period === 'week'">Daily aggregates</span>
                        <span x-show="period === 'month'">Monthly aggregates</span>
                    </span>
                </div>

                <div class="mt-3">
                    <div
                        class="relative h-52 rounded-2xl border border-dashed border-gray-200 dark:border-gray-800 bg-gradient-to-b from-primary-50 to-white dark:from-primary-900/40 dark:to-slate-950 overflow-hidden px-4 py-4">
                        {{-- Soft grid lines --}}
                        <div class="absolute inset-x-4 inset-y-4 pointer-events-none">
                            <div class="h-full flex flex-col justify-between opacity-60">
                                <div class="border-t border-primary-100 dark:border-primary-900/60"></div>
                                <div class="border-t border-primary-100 dark:border-primary-900/60"></div>
                                <div class="border-t border-primary-100 dark:border-primary-900/60"></div>
                            </div>
                        </div>

                        <div class="relative h-full flex flex-col justify-between">
                            <svg viewBox="0 0 100 40" preserveAspectRatio="none"
                                class="w-full h-32 overflow-visible">
                                <defs>
                                    <linearGradient id="paymentsArea" x1="0" y1="0" x2="0"
                                        y2="1">
                                        <stop offset="0%" stop-color="#075bd9" stop-opacity="0.25" />
                                        <stop offset="100%" stop-color="#075bd9" stop-opacity="0" />
                                    </linearGradient>
                                </defs>

                                <path fill="url(#paymentsArea)" stroke="none" :d="paymentAreaPath"></path>

                                <polyline fill="none" stroke="#075bd9" stroke-width="0.8"
                                    stroke-linecap="round" stroke-linejoin="round" :points="paymentLinePoints">
                                </polyline>

                                <template x-for="(amount, index) in paymentsTotal" :key="index">
                                    <circle r="2" fill="white" stroke="#075bd9" stroke-width="1"
                                        :cx="pointForIndex(index, paymentsTotal.length).x"
                                        :cy="pointForIndex(index, paymentsTotal.length).yFor(amount, maxPaymentValue)"
                                        style="pointer-events: auto; cursor: pointer;"
                                        @mouseenter="showTooltip('payments', index)" @mouseleave="hideTooltip">
                                    </circle>
                                </template>
                            </svg>

                            {{-- Payments tooltip --}}
                            <div x-show="tooltip.visible && tooltip.kind === 'payments'"
                                class="pointer-events-none absolute z-10 rounded-lg bg-gray-900 text-white text-[11px] px-3 py-1.5 shadow-lg"
                                :style="`left: calc(${tooltip.x}%); top: 0.5rem; transform: translateX(-50%);`">
                                <div class="flex items-center gap-2">
                                    <span class="font-medium" x-text="tooltip.label"></span>
                                    <span class="inline-flex items-center gap-1 text-primary-300">
                                        <span class="inline-block w-1.5 h-1.5 rounded-full bg-primary-400"></span>
                                        <span x-text="formatAmountShort(tooltip.value)"></span>
                                    </span>
                                </div>
                            </div>

                            <div class="mt-2 flex items-end justify-between gap-2">
                                <template x-for="(amount, index) in paymentsTotal" :key="`pay-label-${index}`">
                                    <div class="flex flex-col items-center gap-0.5 min-w-[1.75rem]">
                                        <span class="text-[10px] font-medium text-gray-500 dark:text-gray-400"
                                            x-text="labels[index]"></span>
                                        <span class="text-[11px] font-semibold text-gray-900 dark:text-gray-100"
                                            x-text="formatAmountShort(amount)"></span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Operational Snapshot & Quick Actions --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6  mt-2">
            {{-- Snapshot --}}
            <div
                class="lg:col-span-2 rounded-2xl bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <x-heroicon-o-clipboard-document-list class="w-5 h-5 text-primary" />
                        Operational Snapshot
                    </h2>
                    <span class="text-[11px] text-gray-500">High-level audit view</span>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="space-y-1.5">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Load today</p>
                        <p class="text-base font-semibold text-gray-900 dark:text-white">
                            {{ number_format($summary['appointments_today'] ?? 0) }} appointments
                        </p>
                        <p class="text-xs text-gray-500">
                            Based on all consultation types scheduled for {{ now()->format('d M Y') }}.
                        </p>
                    </div>
                    <div class="space-y-1.5">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Network size</p>
                        <p class="text-base font-semibold text-gray-900 dark:text-white">
                            {{ number_format($summary['total_patients'] ?? 0) }} patients ·
                            {{ number_format($summary['active_doctors'] ?? 0) }} doctors
                        </p>
                        <p class="text-xs text-gray-500">
                            Patient base connected to currently active doctors.
                        </p>
                    </div>
                    <div class="space-y-1.5">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Quality queue</p>
                        <p class="text-base font-semibold text-gray-900 dark:text-white">
                            {{ number_format($summary['pending_reviews'] ?? 0) }} reviews pending
                        </p>
                        <p class="text-xs text-gray-500">
                            Use this as a quality and feedback backlog for audits.
                        </p>
                    </div>
                </div>
            </div>

            {{-- Quick Actions --}}
            <div
                class="rounded-2xl bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 shadow-sm p-6 flex flex-col gap-4">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    <x-heroicon-o-bolt class="w-5 h-5 text-primary" />
                    Quick Actions
                </h2>
                <div class="space-y-2 text-sm">
                    <x-filament::button tag="a"
                        href="{{ route('filament.admin.resources.appointments.index') ?? '#' }}" color="success"
                        class="w-full justify-between">
                        <span>View appointments table</span>
                        <x-heroicon-o-arrow-right class="w-4 h-4" />
                    </x-filament::button>
                    <x-filament::button tag="a"
                        href="{{ route('filament.admin.resources.patients.index') ?? '#' }}" color="gray"
                        class="w-full justify-between">
                        <span>Manage patients</span>
                        <x-heroicon-o-arrow-right class="w-4 h-4" />
                    </x-filament::button>
                    <x-filament::button tag="a"
                        href="{{ route('filament.admin.resources.doctors.index') ?? '#' }}" color="gray"
                        class="w-full justify-between">
                        <span>Manage doctors</span>
                        <x-heroicon-o-arrow-right class="w-4 h-4" />
                    </x-filament::button>
                </div>
                <p class="text-[11px] text-gray-500 mt-2">
                    These shortcuts are meant for daily admin workflows and audit reviews.
                </p>
            </div>
        </div>

        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('appointmentPaymentChart', (analytics) => ({
                    analytics,
                    period: 'week',
                    labels: [],
                    appointments: {
                        all: [],
                        video: [],
                        general: [],
                    },
                    payments: {
                        total: [],
                    },

                    activeAppointmentTab: 'all',
                    currentAppointments: [],
                    paymentsTotal: [],
                    maxAppointmentValue: 0,
                    maxPaymentValue: 0,
                    appointmentLinePoints: '',
                    appointmentAreaPath: '',
                    paymentLinePoints: '',
                    paymentAreaPath: '',
                    currentMonthLabel: analytics.meta?.current_month ?? '',
                    tooltip: {
                        visible: false,
                        kind: null,
                        label: '',
                        value: 0,
                        x: 0,
                    },

                    init() {
                        this.applyPeriod();
                    },

                    setAppointmentTab(tab) {
                        this.activeAppointmentTab = tab;
                        this.currentAppointments = this.appointments[tab] ?? [];
                        this.recalculateMaxValues();
                    },

                    setPeriod(period) {
                        this.period = period;
                        this.applyPeriod();
                    },

                    recalculateMaxValues() {
                        this.maxAppointmentValue = Math.max(...(this.currentAppointments.length ? this
                            .currentAppointments : [0]));
                        this.maxPaymentValue = Math.max(...(this.paymentsTotal.length ? this.paymentsTotal :
                            [0]));

                        this.appointmentLinePoints = this.buildLinePoints(this.currentAppointments, this
                            .maxAppointmentValue);
                        this.appointmentAreaPath = this.buildAreaPath(this.currentAppointments, this
                            .maxAppointmentValue);
                        this.paymentLinePoints = this.buildLinePoints(this.paymentsTotal, this
                            .maxPaymentValue);
                        this.paymentAreaPath = this.buildAreaPath(this.paymentsTotal, this.maxPaymentValue);
                    },

                    formatAmountShort(amount) {
                        if (!amount || amount === 0) {
                            return '₹0';
                        }

                        if (amount >= 10000000) {
                            return '₹' + (amount / 10000000).toFixed(1) + 'Cr';
                        }

                        if (amount >= 100000) {
                            return '₹' + (amount / 100000).toFixed(1) + 'L';
                        }

                        if (amount >= 1000) {
                            return '₹' + (amount / 1000).toFixed(1) + 'K';
                        }

                        return '₹' + amount.toFixed(0);
                    },

                    get formattedTotalPayments() {
                        const total = this.paymentsTotal.reduce((sum, value) => sum + value, 0);

                        if (!total) {
                            return '₹0';
                        }

                        return '₹' + total.toLocaleString();
                    },

                    showTooltip(kind, index) {
                        this.tooltip.kind = kind;
                        this.tooltip.label = this.labels[index] ?? '';
                        if (kind === 'appointments') {
                            this.tooltip.value = this.currentAppointments[index] ?? 0;
                            const len = Math.max(1, this.currentAppointments.length - 1);
                            this.tooltip.x = this.currentAppointments.length <= 1 ? 50 : (index / len) *
                                100;
                        } else {
                            this.tooltip.value = this.paymentsTotal[index] ?? 0;
                            const len = Math.max(1, this.paymentsTotal.length - 1);
                            this.tooltip.x = this.paymentsTotal.length <= 1 ? 50 : (index / len) * 100;
                        }
                        this.tooltip.visible = true;
                    },

                    hideTooltip() {
                        this.tooltip.visible = false;
                    },

                    applyPeriod() {
                        const data = this.analytics[this.period] ?? {
                            labels: [],
                            appointments: {
                                all: [],
                                video: [],
                                general: []
                            },
                            payments: {
                                total: []
                            },
                        };

                        this.labels = data.labels ?? [];
                        this.appointments = data.appointments ?? {
                            all: [],
                            video: [],
                            general: []
                        };
                        this.payments = data.payments ?? {
                            total: []
                        };

                        this.currentAppointments = this.appointments[this.activeAppointmentTab] ?? [];
                        this.paymentsTotal = this.payments.total ?? [];

                        this.recalculateMaxValues();
                    },

                    buildLinePoints(series, maxValue) {
                        if (!series || !series.length || !maxValue) {
                            return '';
                        }

                        const lastIndex = series.length - 1;

                        return series
                            .map((value, index) => {
                                const x = series.length === 1 ? 0 : (index / lastIndex) * 100;
                                const ratio = value / maxValue;
                                const y = 40 - ratio * 36;

                                return `${x.toFixed(2)},${y.toFixed(2)}`;
                            })
                            .join(' ');
                    },

                    buildAreaPath(series, maxValue) {
                        if (!series || !series.length || !maxValue) {
                            return '';
                        }

                        const lastIndex = series.length - 1;

                        const points = series.map((value, index) => {
                            const x = series.length === 1 ? 0 : (index / lastIndex) * 100;
                            const ratio = value / maxValue;
                            const y = 40 - ratio * 36;

                            return {
                                x,
                                y
                            };
                        });

                        const topPath = points
                            .map((p, i) => `${i === 0 ? 'M' : 'L'} ${p.x.toFixed(2)} ${p.y.toFixed(2)}`)
                            .join(' ');

                        const last = points[points.length - 1];
                        const first = points[0];

                        return `${topPath} L ${last.x.toFixed(2)} 40 L ${first.x.toFixed(2)} 40 Z`;
                    },

                    pointForIndex(index, length) {
                        const lastIndex = Math.max(1, length - 1);
                        const x = length <= 1 ? 0 : (index / lastIndex) * 100;

                        return {
                            x: x.toFixed(2),
                            yFor(value, maxValue) {
                                if (!maxValue) {
                                    return 40;
                                }

                                const ratio = value / maxValue;
                                const y = 40 - ratio * 36;

                                return y.toFixed(2);
                            },
                        };
                    },
                }));
            });
        </script>
    @endif
</x-filament-panels::page>
