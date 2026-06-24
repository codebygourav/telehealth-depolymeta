<x-filament-panels::page>
    <div class="space-y-6" x-data="{ 
        confirmOpen: false, 
        confirmTitle: 'Confirm Action', 
        confirmMessage: 'Are you sure you want to perform this action?', 
        confirmAction: null,
        triggerConfirm(title, message, callback) {
            this.confirmTitle = title;
            this.confirmMessage = message;
            this.confirmAction = callback;
            this.confirmOpen = true;
        },
        executeConfirm() {
            if (this.confirmAction) {
                this.confirmAction();
            }
            this.confirmOpen = false;
        }
    }">
        @if(!$selectedDoctorId)
            <!-- SCREEN 1: Doctors Queue Dashboard -->
            <div class="bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-2xl p-6 shadow-sm">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Doctors Queue Dashboard</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Select a doctor to manage today's appointment queue.</p>
                    </div>
                    <div class="w-full md:w-80">
                        <input 
                            wire:model.live="doctorSearchQuery" 
                            type="text" 
                            placeholder="Search doctors..." 
                            class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 text-gray-900 dark:text-white"
                        />
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    @forelse($this->getDoctors() as $doctor)
                        <div

                        wire:click="selectDoctor('{{ $doctor['id'] }}')"  
                        class="bg-gray-50 cursor-pointer dark:bg-gray-800/50 border border-gray-150 dark:border-gray-700/60 rounded-2xl p-5 hover:shadow-md transition-all duration-300 flex flex-col justify-between">
                            <div class="space-y-4">
                                <!-- Top Row: Initials & Info -->
                                <div class="flex items-start gap-4">
                                    <div class="h-12 w-12 rounded-full bg-primary-100 dark:bg-primary-900/40 text-primary-600 dark:text-primary-400 flex items-center justify-center font-bold text-lg shrink-0">
                                        {{ $doctor['initials'] }}
                                    </div>
                                    <div class="min-w-0">
                                        <h3 class="font-bold text-gray-900 dark:text-white text-base truncate">{{ $doctor['name'] }}</h3>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate mt-0.5">{{ $doctor['department'] }}</p>
                                        <p class="text-xs font-semibold text-gray-400 dark:text-gray-500 mt-1 flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                                            {{ $doctor['room'] }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Middle Row: Stats Grid -->
                                <div class="grid grid-cols-3 gap-2 py-3 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 text-center">
                                    <div>
                                        <div class="text-lg font-bold text-amber-500">{{ $doctor['waiting'] }}</div>
                                        <div class="text-[10px] uppercase font-semibold text-gray-400 dark:text-gray-500">Waiting</div>
                                    </div>
                                    <div class="border-x border-gray-100 dark:border-gray-700">
                                        <div class="text-lg font-bold text-primary-500">{{ $doctor['running'] }}</div>
                                        <div class="text-[10px] uppercase font-semibold text-gray-400 dark:text-gray-500">Running</div>
                                    </div>
                                    <div>
                                        <div class="text-lg font-bold text-green-500">{{ $doctor['done'] }}</div>
                                        <div class="text-[10px] uppercase font-semibold text-gray-400 dark:text-gray-500">Done</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Bottom Row: Status Badges & Action Buttons -->
                            <div class="mt-5 pt-3 border-t border-gray-100 dark:border-gray-700/60 flex items-center justify-between gap-2">
                                <div class="flex flex-col gap-1">
                                    <!-- Checked-In Status Badge -->
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold {{ $doctor['is_checked_in'] ? 'bg-green-50 text-green-700 dark:bg-green-950/30 dark:text-green-400' : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-400' }}">
                                        <span class="h-1.5 w-1.5 rounded-full {{ $doctor['is_checked_in'] ? 'bg-green-500' : 'bg-gray-400' }}"></span>
                                        {{ $doctor['is_checked_in'] ? 'Checked In' : 'Not Checked In' }}
                                    </span>
                                    <!-- Break Status Badge -->
                                    @if($doctor['is_on_break'])
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-400">
                                            On Break
                                        </span>
                                    @endif
                                </div>

                                <div class="flex gap-1 shrink-0">
                                    <!-- Check-In Action Switcher -->
                                    <button 
                                        x-on:click.prevent="triggerConfirm('Doctor Check-In Status', 'Are you sure you want to {{ $doctor['is_checked_in'] ? 'check out' : 'check in' }} this doctor?', () => { $wire.toggleDoctorCheckIn('{{ $doctor['id'] }}') })"
                                        title="{{ $doctor['is_checked_in'] ? 'Check Out' : 'Check In' }}"
                                        class="p-2 rounded-lg border {{ $doctor['is_checked_in'] ? 'border-red-200 text-red-600 hover:bg-red-50 dark:border-red-900 dark:text-red-400' : 'border-green-200 text-green-600 hover:bg-green-50 dark:border-green-900 dark:text-green-400' }} transition-colors"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 01-3-3h7a3 3 0 013 3v1"></path></svg>
                                    </button>

                                    <!-- Open Queue Button -->
                                    <button 
                                        wire:click="selectDoctor('{{ $doctor['id'] }}')" 
                                        class="px-3 py-1.5 bg-primary hover:bg-primary-500 text-white rounded-lg text-xs font-semibold shadow-sm hover:shadow transition-all"
                                    >
                                        Open Queue
                                    </button>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-full py-12 text-center">
                            <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <h3 class="font-semibold text-gray-700 dark:text-gray-300">No doctors found</h3>
                            <p class="text-sm text-gray-400 mt-1">Try adjusting your search criteria.</p>
                        </div>
                    @endforelse
                </div>
            </div>

        @elseif($selectedDoctorId && !$viewingLogs)
            <!-- SCREEN 2: Selected Doctor Queue Management -->
            @php
                $doctor = $this->getSelectedDoctor();
                $stats = $this->getDoctorStats();
                $appointments = $this->getAppointments();
            @endphp

            <div class="space-y-6">
                <!-- Navigation & Action Headers -->
                <div class="flex items-center justify-between flex-wrap gap-4">
                    <button 
                        wire:click="deselectDoctor" 
                        class="flex items-center gap-2 px-4 py-3 bg-primary hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-white dark:text-gray-300 rounded-md text-xs font-bold transition "
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                        Back to Doctors List
                    </button>

                    <button 
                        wire:click="toggleLogsView" 
                        class="flex items-center gap-2 px-4 py-3 bg-transparent hover:bg-primary dark:bg-gray-800 dark:hover:bg-gray-700 text-primary dark:text-primary rounded-md text-xs font-bold transition border border-primary "
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        View Queue Logs / Audit
                    </button>
                </div>

                <!-- Doctor Details & Status Panel -->
                <div class="bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-2xl p-6 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div class="flex items-center gap-4">
                        <div class="h-14 w-14 rounded-full bg-primary-100 dark:bg-primary-900/40 text-primary-600 dark:text-primary-400 flex items-center justify-center font-bold text-xl">
                            {{ strtoupper(substr($doctor->first_name, 0, 1) . substr($doctor->last_name, 0, 1)) }}
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white">Dr. {{ $doctor->first_name }} {{ $doctor->last_name }} - Queue Management</h2>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 flex items-center gap-3">
                                <span>Room: <strong>{{ $doctor->address_line2 ?: 'Room 204' }}</strong></span>
                                <span>•</span>
                                <span>Department: <strong>{{ $doctor->departments->first()?->name ?? 'General Practice' }}</strong></span>
                            </p>
                        </div>
                    </div>

                    <!-- Toggle Switched / Breaks -->
                    <div class="flex items-center gap-4 border-t md:border-t-0 pt-4 md:pt-0 border-gray-100 dark:border-gray-800">
                        <!-- Break Toggle Button -->
                        <button 
                            x-on:click.prevent="triggerConfirm('Doctor Break Status', 'Are you sure you want to {{ $doctor->is_on_break ? 'end' : 'start' }} break for this doctor?', () => { $wire.toggleDoctorBreak('{{ $doctor->id }}') })"
                            class="px-4 py-2 border rounded-xl text-xs font-semibold transition {{ $doctor->is_on_break ? 'bg-amber-50 border-amber-300 text-amber-700 dark:bg-amber-950/30 dark:text-amber-400' : 'border-gray-200 text-gray-700 dark:border-gray-700 dark:text-gray-300 hover:bg-gray-50' }}"
                        >
                            {{ $doctor->is_on_break ? 'On Break' : 'Take Break' }}
                        </button>

                        <div class="flex items-center gap-3">
                            <div class="text-right">
                                <div class="text-xs font-bold {{ $doctor->is_checked_in ? 'text-green-600 dark:text-green-400' : 'text-gray-500' }}">
                                    {{ $doctor->is_checked_in ? 'Checked In' : 'Checked Out' }}
                                </div>
                                <div class="text-[10px] text-gray-400 dark:text-gray-500">
                                    {{ $doctor->is_checked_in && $doctor->checked_in_at ? 'Since ' . $doctor->checked_in_at->format('h:i A') : 'Doctor Status' }}
                                </div>
                            </div>
                            <!-- Livewire Toggle Switch -->
                            <button 
                                x-on:click.prevent="triggerConfirm('Doctor Check-In Status', 'Are you sure you want to {{ $doctor->is_checked_in ? 'check out' : 'check in' }} this doctor?', () => { $wire.toggleDoctorCheckIn('{{ $doctor->id }}') })"
                                class="w-12 h-6 flex items-center rounded-full p-1 cursor-pointer {{ $doctor->is_checked_in ? 'bg-green-500' : 'bg-gray-300 dark:bg-gray-700' }} transition-colors"
                            >
                                <div class="bg-white w-4 h-4 rounded-full shadow-md transform {{ $doctor->is_checked_in ? 'translate-x-6' : 'translate-x-0' }} transition-transform"></div>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Statistics Widgets Grid -->
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                    <div class="bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-xl p-4 shadow-sm">
                        <div class="text-[10px] uppercase font-bold text-gray-400 dark:text-gray-500">Total Patients</div>
                        <div class="text-2xl font-extrabold text-gray-950 dark:text-white mt-1">{{ $stats['total'] }}</div>
                    </div>
                    <div class="bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-xl p-4 shadow-sm border-l-4 border-l-amber-500">
                        <div class="text-[10px] uppercase font-bold text-gray-400 dark:text-gray-500">Waiting</div>
                        <div class="text-2xl font-extrabold text-amber-500 mt-1">{{ $stats['waiting'] }}</div>
                    </div>
                    <div class="bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-xl p-4 shadow-sm border-l-4 border-l-primary-500">
                        <div class="text-[10px] uppercase font-bold text-gray-400 dark:text-gray-500">Running</div>
                        <div class="text-2xl font-extrabold text-primary-500 mt-1">{{ $stats['running'] }}</div>
                    </div>
                    <div class="bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-xl p-4 shadow-sm border-l-4 border-l-green-500">
                        <div class="text-[10px] uppercase font-bold text-gray-400 dark:text-gray-500">Completed</div>
                        <div class="text-2xl font-extrabold text-green-500 mt-1">{{ $stats['completed'] }}</div>
                    </div>
                    <div class="bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-xl p-4 shadow-sm border-l-4 border-l-red-400 col-span-2 lg:col-span-1">
                        <div class="text-[10px] uppercase font-bold text-gray-400 dark:text-gray-500">Skipped</div>
                        <div class="text-2xl font-extrabold text-red-500 mt-1">{{ $stats['skipped'] }}</div>
                    </div>
                </div>

                <!-- Filters Panel -->
                <div class="bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-2xl p-5 shadow-sm space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-1">Search Patient</label>
                            <input 
                                wire:model.live="searchQuery" 
                                type="text" 
                                placeholder="Search by name or phone..." 
                                class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl px-3 py-2 text-xs focus:outline-none focus:ring-1 focus:ring-primary-500 text-gray-900 dark:text-white"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-1">Queue Status</label>
                            <select 
                                wire:model.live="statusFilter" 
                                class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl px-3 py-2 text-xs focus:outline-none focus:ring-1 focus:ring-primary-500 text-gray-900 dark:text-white"
                            >
                                <option value="all">All Statuses</option>
                                <option value="waiting">Waiting</option>
                                <option value="running">Running</option>
                                <option value="completed">Completed</option>
                                <option value="skipped">Skipped</option>
                                <option value="not_completed">Not Completed</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-1">Visit Type</label>
                            <select 
                                wire:model.live="visitTypeFilter" 
                                class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl px-3 py-2 text-xs focus:outline-none focus:ring-1 focus:ring-primary-500 text-gray-900 dark:text-white"
                            >
                                <option value="all">All Visit Types</option>
                                <option value="in-person">In-Person</option>
                                <option value="video">Video Consultation</option>
                            </select>
                        </div>
                        <div class="flex items-end gap-2">
                            <button 
                                wire:click="resetFilters" 
                                class="flex-1 bg-gray-100 hover:bg-gray-200 dark:bg-gray-850 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300 py-2 rounded-xl text-xs font-bold transition shadow-sm border border-gray-200/50"
                            >
                                Reset Filters
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Appointments Queue Table -->
                <div class="bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-2xl overflow-hidden shadow-sm">
                    <!-- Actions Meaning Bar / Legend -->
                    <div class="bg-gray-50/60 dark:bg-gray-800/40 border-b border-gray-100 dark:border-gray-800 px-6 py-4 flex flex-wrap items-center justify-between gap-4">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <span class="text-xs font-bold text-gray-700 dark:text-white">Actions Legend:</span>
                        </div>
                        <div class="flex flex-wrap items-center gap-6 text-xs text-gray-500 dark:text-gray-400">
                            <div class="flex items-center gap-1.5">
                                <span class="p-1 text-amber-600 bg-amber-50 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-900 rounded-lg">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12.066 11.2a1 1 0 000 1.6l5.334 4A1 1 0 0019 16V8a1 1 0 00-1.6-.8l-5.334 4zM4.066 11.2a1 1 0 000 1.6l5.334 4A1 1 0 0011 16V8a1 1 0 00-1.6-.8l-5.334 4z"></path></svg>
                                </span>
                                <span class="font-semibold text-gray-700 dark:text-gray-300 text-[11px]">Re-Queue / Revert</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span class="p-1 text-indigo-600 bg-indigo-50 dark:bg-indigo-950/20 border border-indigo-200 dark:border-indigo-900 rounded-lg">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                </span>
                                <span class="font-semibold text-gray-700 dark:text-gray-300 text-[11px]">Start Consultation</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span class="p-1 text-green-600 bg-green-50 dark:bg-green-950/20 border border-green-200 dark:border-green-900 rounded-lg">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                </span>
                                <span class="font-semibold text-gray-700 dark:text-gray-300 text-[11px]">Complete Consultation</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span class="p-1 text-red-600 bg-red-50 dark:bg-red-950/20 border border-red-200 dark:border-red-900 rounded-lg">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                </span>
                                <span class="font-semibold text-gray-700 dark:text-gray-300 text-[11px]">No Show / Not Completed</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span class="p-1 text-gray-500 bg-gray-550 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                                </span>
                                <span class="font-semibold text-gray-700 dark:text-gray-300 text-[11px]">Skip Patient</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span class="p-1 text-gray-600 bg-gray-50 dark:bg-gray-850 border border-gray-200 dark:border-gray-700 rounded-lg">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                </span>
                                <span class="font-semibold text-gray-700 dark:text-gray-300 text-[11px]">View Details</span>
                            </div>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 dark:bg-gray-800/70 border-b border-gray-100 dark:border-gray-800 text-[11px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">
                                    <th class="px-6 py-4">Queue No.</th>
                                    <th class="px-6 py-4">Patient</th>
                                    <!-- <th class="px-6 py-4">Phone</th> -->
                                    <th class="px-6 py-4">Visit Type</th>
                                    <th class="px-6 py-4">Time</th>
                                    <th class="px-6 py-4">Status</th>
                                    <th class="px-6 py-4">Next in Queue</th>
                                    <th class="px-6 py-4 text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-850">
                                @forelse($appointments as $app)
                                    @php
                                        $queueStatus = $app->queue_status ?: 'waiting';
                                    @endphp
                                    <tr class="text-sm hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition duration-150">
                                        <!-- Queue No -->
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-3 py-1 bg-primary-50 dark:bg-primary-950/20 text-primary-700 dark:text-primary-400 font-bold rounded-lg text-xs">
                                                {{ $app->queue_number ?: '—' }}
                                            </span>
                                        </td>
                                        
                                        <!-- Patient Info -->
                                        <td class="px-6 py-4">
                                            <div class="font-bold text-gray-900 dark:text-white">
                                                {{ $app->patient ? $app->patient->first_name . ' ' . $app->patient->last_name : 'Faker Patient' }}
                                            </div>
                                            <div class="text-[12px]">{{ $app->patient?->mobile_no ?: '—' }}</div>
                                        </td>

                                    

                                        <!-- Visit Type -->
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if($app->consultation_type === 'video')
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-purple-50 text-purple-700 dark:bg-purple-950/30 dark:text-purple-400">
                                                    Video Consultation
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 dark:bg-blue-950/30 dark:text-blue-400">
                                                    In-Person
                                                </span>
                                            @endif
                                        </td>

                                        <!-- Time -->
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-xs font-semibold text-gray-900 dark:text-white">
                                                {{ $app->appointment_time ? Carbon\Carbon::parse($app->appointment_time)->format('h:i A') : '—' }}
                                            </div>
                                            <div class="text-[10px] text-gray-400 dark:text-gray-500 mt-0.5">
                                                {{ $app->appointment_date ? $app->appointment_date->format('d M Y') : '—' }}
                                            </div>
                                        </td>

                                        <!-- Status Badge -->
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold 
                                                {{ $queueStatus === 'completed' ? 'bg-green-50 text-green-700 dark:bg-green-950/30 dark:text-green-400' : '' }}
                                                {{ $queueStatus === 'running' ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/30 dark:text-indigo-400' : '' }}
                                                {{ $queueStatus === 'waiting' ? 'bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-400 animate-pulse' : '' }}
                                                {{ $queueStatus === 'skipped' ? 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-400' : '' }}
                                                {{ $queueStatus === 'not_completed' ? 'bg-red-50 text-red-700 dark:bg-red-950/30 dark:text-red-400' : '' }}
                                            ">
                                                <span class="h-1.5 w-1.5 rounded-full
                                                    {{ $queueStatus === 'completed' ? 'bg-green-500' : '' }}
                                                    {{ $queueStatus === 'running' ? 'bg-indigo-500' : '' }}
                                                    {{ $queueStatus === 'waiting' ? 'bg-amber-500' : '' }}
                                                    {{ $queueStatus === 'skipped' ? 'bg-gray-500' : '' }}
                                                    {{ $queueStatus === 'not_completed' ? 'bg-red-500' : '' }}
                                                "></span>
                                                {{ ucfirst(str_replace('_', ' ', $queueStatus)) }}
                                            </span>
                                        </td>

                                        <!-- Next in Queue Info -->
                                        <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-500 dark:text-gray-400 font-semibold">
                                            {{ $this->getNextInQueueText($app, $appointments) }}
                                        </td>

                                        <!-- Actions Grid -->
                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                            <div class="flex items-center justify-center gap-1.5">
                                                <!-- REVERT ACTION (Re-Queue) - Show if status is completed, running, skipped, or not completed -->
                                                @if($queueStatus !== 'waiting')
                                                    <button 
                                                        x-on:click.prevent="triggerConfirm('Re-Queue Patient', 'Are you sure you want to revert this patient back to waiting?', () => { $wire.revertAppointment('{{ $app->id }}') })"
                                                        title="Re-Queue / Revert back to Waiting"
                                                        class="p-2 text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-950/30 border border-amber-200 dark:border-amber-900 rounded-lg transition-all"
                                                    >
                                                        <!-- Revert Icon -->
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12.066 11.2a1 1 0 000 1.6l5.334 4A1 1 0 0019 16V8a1 1 0 00-1.6-.8l-5.334 4zM4.066 11.2a1 1 0 000 1.6l5.334 4A1 1 0 0011 16V8a1 1 0 00-1.6-.8l-5.334 4z"></path></svg>
                                                    </button>
                                                @endif
 
                                                <!-- Conditional Buttons based on queue status -->
                                                @if($queueStatus === 'waiting')
                                                    <!-- Start play action -->
                                                    <button 
                                                        x-on:click.prevent="triggerConfirm('Start Consultation', 'Are you sure you want to start this consultation?', () => { $wire.startAppointment('{{ $app->id }}') })"
                                                        title="Start Consultation"
                                                        class="p-2 text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-950/30 border border-indigo-200 dark:border-indigo-900 rounded-lg transition-all"
                                                    >
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                    </button>
 
                                                    <!-- Skip block action -->
                                                    <button 
                                                        x-on:click.prevent="triggerConfirm('Skip Patient', 'Are you sure you want to skip this patient?', () => { $wire.skipAppointment('{{ $app->id }}') })"
                                                        title="Skip Patient"
                                                        class="p-2 text-gray-500 hover:bg-gray-100 border border-gray-200 dark:border-gray-700 rounded-lg transition-all"
                                                    >
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                                                    </button>
                                                @endif
 
                                                @if($queueStatus === 'running')
                                                    <!-- Complete Checkmark Action -->
                                                    <button 
                                                        x-on:click.prevent="triggerConfirm('Complete Consultation', 'Are you sure you want to mark this consultation as completed?', () => { $wire.completeAppointment('{{ $app->id }}') })"
                                                        title="Complete Consultation"
                                                        class="p-2 text-green-600 hover:bg-green-50 dark:hover:bg-green-950/30 border border-green-200 dark:border-green-900 rounded-lg transition-all"
                                                    >
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                    </button>
 
                                                    <!-- Not Complete X Action -->
                                                    <button 
                                                        x-on:click.prevent="triggerConfirm('Mark as No Show', 'Are you sure you want to mark this patient as not completed (No Show)?', () => { $wire.notCompleteAppointment('{{ $app->id }}') })"
                                                        title="Mark as Not Completed"
                                                        class="p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-950/30 border border-red-200 dark:border-red-900 rounded-lg transition-all"
                                                    >
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                    </button>
 
                                                    <!-- Skip action is also supported in running -->
                                                    <button 
                                                        x-on:click.prevent="triggerConfirm('Skip Patient', 'Are you sure you want to skip this patient?', () => { $wire.skipAppointment('{{ $app->id }}') })"
                                                        title="Skip Patient"
                                                        class="p-2 text-gray-500 hover:bg-gray-100 border border-gray-200 dark:border-gray-700 rounded-lg transition-all"
                                                    >
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                                                    </button>
                                                @endif
 
                                                <!-- View Patient Details Eye Action -->
                                                <a 
                                                    href="/admin/appointments/{{ $app->slug }}" 
                                                    target="_blank"
                                                    title="View Appointment Details"
                                                    class="p-2 text-gray-600 hover:bg-gray-50 dark:text-gray-400 dark:hover:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg transition-all"
                                                >
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                            No patients found matching the criteria.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        @elseif($selectedDoctorId && $viewingLogs)
            <!-- SCREEN 3: Past Queue Records Logs / Audit -->
            @php
                $doctor = $this->getSelectedDoctor();
                $logs = $this->getQueueLogs();
            @endphp

            <div class="space-y-6">
                <!-- Navigation Headers -->
                <div class="flex items-center justify-between">
                    <button 
                        wire:click="deselectDoctor" 
                        class="flex items-center gap-2 px-4 py-3 bg-transparent hover:bg-primary/10 dark:bg-gray-800 dark:hover:bg-primary-700 text-primary hover:text-white dark:text-primary-100 rounded-md text-xs font-bold transition border border-primary dark:border-primary-100"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                        Back to Doctors List
                    </button>

                    <button 
                        wire:click="toggleLogsView" 
                        class="flex items-center gap-2 px-4 py-3 bg-primary hover:bg-primary-800 dark:bg-primary-100 dark:hover:bg-white text-white dark:text-gray-950 rounded-md text-xs font-bold transition "
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                        Back to Queue Board
                    </button>
                </div>

                <!-- Doctor Log Description Card -->
                <div class="bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-2xl p-6 shadow-sm">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">Audit Trail - Dr. {{ $doctor->first_name }} {{ $doctor->last_name }}</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Detailed operations logs tracking checked-in times, breaks, and individual patient consultations.</p>
                </div>

                <!-- Timeline Feed Section -->
                <div class="space-y-4">
                    <div class="relative pl-6 border-l-2 border-gray-250 dark:border-gray-800 space-y-6">
                        @forelse($logs as $log)
                            @php
                                $details = $this->getLogDetails($log);
                                $colorMap = [
                                    'green' => [
                                        'bg' => 'bg-green-500',
                                        'bg-light' => 'bg-green-50 dark:bg-green-950/20',
                                        'text' => 'text-green-700 dark:text-green-400',
                                        'border' => 'border-green-200 dark:border-green-900/60',
                                        'icon' => '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>'
                                    ],
                                    'danger' => [
                                        'bg' => 'bg-red-500',
                                        'bg-light' => 'bg-red-50 dark:bg-red-950/20',
                                        'text' => 'text-red-700 dark:text-red-400',
                                        'border' => 'border-red-200 dark:border-red-900/60',
                                        'icon' => '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>'
                                    ],
                                    'amber' => [
                                        'bg' => 'bg-amber-500',
                                        'bg-light' => 'bg-amber-50 dark:bg-amber-950/20',
                                        'text' => 'text-amber-700 dark:text-amber-400',
                                        'border' => 'border-amber-200 dark:border-amber-900/60',
                                        'icon' => '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>'
                                    ],
                                    'indigo' => [
                                        'bg' => 'bg-indigo-500',
                                        'bg-light' => 'bg-indigo-50 dark:bg-indigo-950/20',
                                        'text' => 'text-indigo-700 dark:text-indigo-400',
                                        'border' => 'border-indigo-200 dark:border-indigo-900/60',
                                        'icon' => '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>'
                                    ],
                                    'gray' => [
                                        'bg' => 'bg-gray-500',
                                        'bg-light' => 'bg-gray-100 dark:bg-gray-800',
                                        'text' => 'text-gray-700 dark:text-gray-300',
                                        'border' => 'border-gray-200 dark:border-gray-700',
                                        'icon' => '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 5l7 7-7 7M5 5l7 7-7 7"></path></svg>'
                                    ],
                                ];
                                
                                $style = $colorMap[$details['color']] ?? $colorMap['gray'];
                            @endphp
                            
                            <!-- Timeline Entry Node -->
                            <div class="relative">
                                <!-- Line Marker Dot -->
                                <span class="absolute -left-[31px] top-1.5 flex h-4.5 w-4.5 items-center justify-center rounded-full bg-white dark:bg-gray-900 border-2 {{ $details['color'] === 'green' ? 'border-green-500' : ($details['color'] === 'danger' ? 'border-red-500' : ($details['color'] === 'amber' ? 'border-amber-500' : ($details['color'] === 'indigo' ? 'border-indigo-500' : 'border-gray-400'))) }} shrink-0 z-10">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $style['bg'] }}"></span>
                                </span>
                                
                                <!-- Timeline Card -->
                                <div class="bg-white dark:bg-gray-900 border border-gray-150 dark:border-gray-850 rounded-xl p-4 flex flex-col md:flex-row md:items-center justify-between gap-4 hover:shadow-md transition-all duration-300">
                                    <div class="space-y-1.5 min-w-0 flex-1">
                                        <!-- Header line: Action Badge & Title -->
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold {{ $style['bg-light'] }} {{ $style['text'] }} border {{ $style['border'] }}">
                                                {!! $style['icon'] !!}
                                                {{ $details['title'] }}
                                            </span>
                                            
                                            <!-- Duration Tag -->
                                            @if($details['duration'])
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-gray-50 border border-gray-200 text-gray-600 dark:bg-gray-850 dark:border-gray-800 dark:text-gray-300">
                                                    <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                    Duration: {{ $details['duration'] }}
                                                </span>
                                            @endif
                                            
                                            <!-- Timestamp -->
                                            <span class="text-[11px] font-semibold text-gray-400 dark:text-gray-500">
                                                {{ $log->created_at->diffForHumans() }}
                                            </span>
                                        </div>
                                        
                                        <!-- Description -->
                                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300 leading-relaxed">
                                            {{ $details['desc'] }}
                                        </p>
                                    </div>
                                    
                                    <!-- Log Meta: Time & Logged By -->
                                    <div class="flex flex-row md:flex-col items-center md:items-end justify-between md:justify-center gap-1.5 pt-2 md:pt-0 border-t md:border-t-0 border-gray-100 dark:border-gray-800 text-xs shrink-0">
                                        <div class="text-xs font-extrabold text-gray-900 dark:text-white">
                                            {{ $details['time_range'] }}
                                        </div>
                                        <div class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">
                                            By {{ $log->creator ? $log->creator->name : 'System' }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="py-12 text-center text-gray-500 bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-2xl p-6">
                                <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                                <h3 class="font-semibold text-gray-700 dark:text-gray-300">No events logged yet</h3>
                                <p class="text-sm text-gray-400 mt-1">Activities will show up here as actions are performed.</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                    @if($logs->hasPages())
                        <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-900">
                            {{ $logs->links() }}
                        </div>
                    @endif
                </div>
            </div>
        @endif

        <!-- Custom Confirmation Modal Overlay -->
        <div 
            x-show="confirmOpen" 
            class="fixed inset-0 z-[1000] flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            style="display: none;"
            x-cloak
        >
            <!-- Modal Box -->
            <div 
                x-show="confirmOpen"
                x-on:click.away="confirmOpen = false"
                class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-150 dark:border-gray-800 shadow-2xl max-w-md w-full overflow-hidden p-6 space-y-4 transform transition-all duration-300"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 scale-95"
            >
                <div class="flex items-start gap-4">
                    <div class="h-10 w-10 rounded-full bg-primary-50 dark:bg-primary-950/30 text-primary-650 dark:text-primary-400 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    </div>
                    <div class="space-y-1.5 flex-1 min-w-0">
                        <h3 class="text-lg font-bold text-gray-950 dark:text-white" x-text="confirmTitle">Confirm Action</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 font-medium leading-relaxed" x-text="confirmMessage"></p>
                    </div>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button 
                        x-on:click="confirmOpen = false" 
                        class="px-4 py-2 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-xl text-xs font-bold transition duration-200"
                    >
                        Cancel
                    </button>
                    <button 
                        x-on:click="executeConfirm()" 
                        class="px-4 py-2 bg-primary hover:bg-primary-500 text-white rounded-xl text-xs font-bold transition duration-200 shadow-sm"
                    >
                        Confirm
                    </button>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
