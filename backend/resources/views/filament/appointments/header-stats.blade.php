@php
    $activeTab = $this->activeTab ?? 'today_booked';
@endphp

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6 p-6 bg-white dark:bg-gray-900 border-b border-gray-100 dark:border-gray-800">
    <!-- Booked Today -->
    <button 
        wire:click="$set('activeTab', 'today_booked')"
        type="button"
        class="group flex items-center justify-between p-5 rounded-2xl border transition-all duration-300 text-left hover:scale-[1.02] active:scale-[0.98] cursor-pointer header-stat-card {{ $activeTab === 'today_booked' ? 'is-active' : 'is-inactive' }}"
    >
        <div class="space-y-1">
            <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Booked Today</span>
            <div class="text-3xl font-bold text-gray-900 dark:text-white flex items-baseline gap-2">
                <span>{{ \App\Filament\Resources\Appointments\AppointmentResource::getEloquentQuery()->whereDate('created_at', today())->whereHas('payment', fn($q) => $q->where('status', \App\Enums\PaymentStatus::PAID->value))->count() }}</span>
                @if($activeTab === 'today_booked')
                    <span class="inline-flex items-center rounded-md px-1.5 py-0.5 text-xs font-medium header-stat-badge is-active">Active</span>
                @else
                    <span class="text-[10px] font-medium text-gray-400 opacity-0 group-hover:opacity-100 transition-opacity duration-200">Click to filter</span>
                @endif
            </div>
        </div>
        <div class="header-stat-icon {{ $activeTab === 'today_booked' ? 'is-active' : 'is-inactive' }}">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-5.625 3h18.75a3 3 0 0 0 3-3V7.5a3 3 0 0 0-3-3H3.75a3 3 0 0 0-3 3v10.5a3 3 0 0 0 3 3Z" />
            </svg>
        </div>
    </button>

    <!-- Today's Visits -->
    <button 
        wire:click="$set('activeTab', 'today_opd')"
        type="button"
        class="group flex items-center justify-between p-5 rounded-2xl border transition-all duration-300 text-left hover:scale-[1.02] active:scale-[0.98] cursor-pointer header-stat-card {{ $activeTab === 'today_opd' ? 'is-active' : 'is-inactive' }}"
    >
        <div class="space-y-1">
            <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Today's Visits</span>
            <div class="text-3xl font-bold text-gray-900 dark:text-white flex items-baseline gap-2">
                <span>{{ \App\Filament\Resources\Appointments\AppointmentResource::getEloquentQuery()->whereDate('appointment_date', today())->whereHas('payment', fn($q) => $q->where('status', \App\Enums\PaymentStatus::PAID->value))->count() }}</span>
                @if($activeTab === 'today_opd')
                    <span class="inline-flex items-center rounded-md px-1.5 py-0.5 text-xs font-medium header-stat-badge is-active">Active</span>
                @else
                    <span class="text-[10px] font-medium text-gray-400 opacity-0 group-hover:opacity-100 transition-opacity duration-200">Click to filter</span>
                @endif
            </div>
        </div>
        <div class="header-stat-icon {{ $activeTab === 'today_opd' ? 'is-active' : 'is-inactive' }}">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
            </svg>
        </div>
    </button>

    <!-- Tomorrow's Visits -->
    <button 
        wire:click="$set('activeTab', 'tomorrow_opd')"
        type="button"
        class="group flex items-center justify-between p-5 rounded-2xl border transition-all duration-300 text-left hover:scale-[1.02] active:scale-[0.98] cursor-pointer header-stat-card {{ $activeTab === 'tomorrow_opd' ? 'is-active' : 'is-inactive' }}"
    >
        <div class="space-y-1">
            <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tomorrow's Visits</span>
            <div class="text-3xl font-bold text-gray-900 dark:text-white flex items-baseline gap-2">
                <span>{{ \App\Filament\Resources\Appointments\AppointmentResource::getEloquentQuery()->whereDate('appointment_date', \Carbon\Carbon::tomorrow())->whereHas('payment', fn($q) => $q->where('status', \App\Enums\PaymentStatus::PAID->value))->count() }}</span>
                @if($activeTab === 'tomorrow_opd')
                    <span class="inline-flex items-center rounded-md px-1.5 py-0.5 text-xs font-medium header-stat-badge is-active">Active</span>
                @else
                    <span class="text-[10px] font-medium text-gray-400 opacity-0 group-hover:opacity-100 transition-opacity duration-200">Click to filter</span>
                @endif
            </div>
        </div>
        <div class="header-stat-icon {{ $activeTab === 'tomorrow_opd' ? 'is-active' : 'is-inactive' }}">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
            </svg>
        </div>
    </button>

    <!-- Upcoming Visits -->
    <button 
        wire:click="$set('activeTab', 'upcoming_opd')"
        type="button"
        class="group flex items-center justify-between p-5 rounded-2xl border transition-all duration-300 text-left hover:scale-[1.02] active:scale-[0.98] cursor-pointer header-stat-card {{ $activeTab === 'upcoming_opd' ? 'is-active' : 'is-inactive' }}"
    >
        <div class="space-y-1">
            <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Upcoming Visits</span>
            <div class="text-3xl font-bold text-gray-900 dark:text-white flex items-baseline gap-2">
                <span>{{ \App\Filament\Resources\Appointments\AppointmentResource::getEloquentQuery()->whereDate('appointment_date', '>', \Carbon\Carbon::tomorrow())->whereHas('payment', fn($q) => $q->where('status', \App\Enums\PaymentStatus::PAID->value))->count() }}</span>
                @if($activeTab === 'upcoming_opd')
                    <span class="inline-flex items-center rounded-md px-1.5 py-0.5 text-xs font-medium header-stat-badge is-active">Active</span>
                @else
                    <span class="text-[10px] font-medium text-gray-400 opacity-0 group-hover:opacity-100 transition-opacity duration-200">Click to filter</span>
                @endif
            </div>
        </div>
        <div class="header-stat-icon {{ $activeTab === 'upcoming_opd' ? 'is-active' : 'is-inactive' }}">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
        </div>
    </button>

    <!-- All Appointments -->
    <button 
        wire:click="$set('activeTab', 'all')"
        type="button"
        class="group flex items-center justify-between p-5 rounded-2xl border transition-all duration-300 text-left hover:scale-[1.02] active:scale-[0.98] cursor-pointer header-stat-card {{ $activeTab === 'all' ? 'is-active' : 'is-inactive' }}"
    >
        <div class="space-y-1">
            <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">All Appointments</span>
            <div class="text-3xl font-bold text-gray-900 dark:text-white flex items-baseline gap-2">
                <span>{{ \App\Filament\Resources\Appointments\AppointmentResource::getEloquentQuery()->count() }}</span>
                @if($activeTab === 'all')
                    <span class="inline-flex items-center rounded-md px-1.5 py-0.5 text-xs font-medium header-stat-badge is-active">Active</span>
                @else
                    <span class="text-[10px] font-medium text-gray-400 opacity-0 group-hover:opacity-100 transition-opacity duration-200">Click to filter</span>
                @endif
            </div>
        </div>
        <div class="header-stat-icon {{ $activeTab === 'all' ? 'is-active' : 'is-inactive' }}">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 12h.007v.008H3.75V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-3.75 5.25h.007v.008H3.75v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
            </svg>
        </div>
    </button>
</div>
