<x-filament-panels::page>
    <style>


        .queue-sticky-legend-inner {
            min-height: 3rem;
        }
        .queue-sticky-head{
            position: sticky;
            top:0px;
            background: #fff !important;
        }
        .started_queue_app{
            background: #055bd92b;
        }



    </style>
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
                this.confirmAction(this.$wire);
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
                            class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 text-gray-900 dark:text-white"
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
                                <div class="grid grid-cols-3 gap-2 py-3 bg-white dark:bg-gray-800 rounded-md border border-gray-100 dark:border-gray-700 text-center">
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
                                        x-on:click.prevent="triggerConfirm('Doctor Check-In Status', 'Are you sure you want to {{ $doctor['is_checked_in'] ? 'check out' : 'check in' }} this doctor?', $wire => { $wire.toggleDoctorCheckIn('{{ $doctor['id'] }}') })"
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

        @else
            <!-- SCREEN 2: Selected Doctor Queue Management -->
            @php
                $doctor = $this->getSelectedDoctor();
                $stats = $this->getDoctorStats();
                $appointments = $this->getAppointments();
                $allAppointments = $this->getAllAppointmentsForQueue();
                $queueStatusStyles = [
                    'scheduled' => [
                        'label' => 'Scheduled',
                        'badge' => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
                        'legend_text' => 'Booked for today',
                        'legend_card' => 'bg-white dark:bg-gray-850 border border-gray-100 dark:border-gray-800',
                        'row' => 'hover:bg-gray-50/50 dark:hover:bg-gray-800/30',
                    ],
                    'no_show' => [
                        'label' => 'No Show',
                        'badge' => 'bg-red-50 text-red-700 dark:bg-red-950/30 dark:text-red-400',
                        'legend_text' => 'Booked, not arrived',
                        'legend_card' => 'bg-white dark:bg-gray-850 border border-gray-100 dark:border-gray-800',
                        'row' => 'hover:bg-gray-50/50 dark:hover:bg-gray-800/30',
                    ],
                    'checkin' => [
                        'label' => 'Checked-in',
                        'badge' => 'bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-400',
                        'legend_text' => 'Arrived & waiting',
                        'legend_card' => 'bg-white dark:bg-gray-850 border border-gray-100 dark:border-gray-800',
                        'row' => 'hover:bg-gray-50/50 dark:hover:bg-gray-800/30',
                    ],
                    'started' => [
                        'label' => 'Started',
                        'badge' => 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/30 dark:text-indigo-400 ring-1 ring-indigo-300',
                        'legend_text' => 'Current with Doctor (Active)',
                        'legend_card' => 'bg-white dark:bg-gray-850 border border-gray-100 dark:border-gray-800 ring-1 ring-indigo-400/40',
                        'row' => 'hover:bg-indigo-50/60 started_queue_app dark:hover:bg-indigo-800/20 ring-1 ring-indigo-500/20 dark:ring-indigo-500/30 shadow-xs border-l-4 border-l-indigo-600',
                   
                   
                    ],
                    'completed' => [
                        'label' => 'Completed',
                        'badge' => 'bg-green-50 text-green-700 dark:bg-green-950/30 dark:text-green-400',
                        'legend_text' => 'Consultation finished',
                        'legend_card' => 'bg-white dark:bg-gray-850 border border-gray-100 dark:border-gray-800',
                        'row' => 'hover:bg-gray-50/50 dark:hover:bg-gray-800/30',
                    ],
                    'skipped' => [
                        'label' => 'Skipped',
                        'badge' => 'bg-gray-150 text-gray-700 dark:bg-gray-800 dark:text-gray-400',
                        'legend_text' => 'Missed turn / Put on hold',
                        'legend_card' => 'bg-white dark:bg-gray-850 border border-gray-100 dark:border-gray-800',
                        'row' => 'hover:bg-gray-50/50 dark:hover:bg-gray-800/30',
                    ],
                ];
                $actionButtonBaseClass = 'inline-flex items-center justify-center min-h-[30px] px-4 py-2 text-[10px] font-semibold tracking-[0.01em] border rounded-full transition-all duration-200 shadow-sm hover:shadow-md hover:-translate-y-[1px] focus:outline-none focus:ring-2 focus:ring-offset-1';
                $actionButtonStyles = [
                    'mark_checkin' => 'bg-emerald-50/95 text-emerald-700 hover:bg-emerald-100 border-emerald-200/90 focus:ring-emerald-200 dark:bg-emerald-950/30 dark:text-emerald-400 dark:border-emerald-900/80',
                    'no_show' => 'bg-red-50/95 text-red-700 hover:bg-red-100 border-red-200/90 focus:ring-red-200 dark:bg-red-950/30 dark:text-red-400 dark:border-red-900/80',
                    'start' => 'bg-amber-50/95 text-amber-700 hover:bg-amber-100 border-amber-200/90 focus:ring-amber-200 dark:bg-amber-950/30 dark:text-amber-400 dark:border-amber-900/80',
                    'skip' => 'bg-red-100 text-red-700 hover:bg-red-200 border-red-200 focus:ring-red-200 dark:bg-red-800 dark:text-red-300 dark:hover:bg-red-700 dark:border-gray-700',
                    'complete' => 'bg-green-50/95 text-green-700 hover:bg-green-100 border-green-200/90 focus:ring-green-200 dark:bg-green-950/30 dark:text-green-400 dark:border-green-900/80',
                    'recheckin' => 'bg-violet-50/95 text-violet-700 hover:bg-violet-100 border-violet-200/90 focus:ring-violet-200 dark:bg-violet-950/30 dark:text-violet-400 dark:border-violet-900/80',
                    'send_note' => 'bg-red-100 text-red-700 hover:bg-red-200 border-red-200 focus:ring-red-200 dark:bg-red-800 dark:text-red-300 dark:hover:bg-red-700 dark:border-gray-700',
                    'revert' => 'bg-orange-50/95 text-orange-700 hover:bg-orange-100 border-orange-200/90 focus:ring-orange-200 dark:bg-orange-950/30 dark:text-orange-400 dark:border-orange-900/80',
                    'view_details' => 'bg-slate-100 text-slate-700 hover:bg-slate-200 border-slate-200 focus:ring-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:border-slate-700',
                ];
                $queueActionsByStatus = [
                    'scheduled' => [],
                    'no_show' => ['mark_checkin'],
                    'checkin' => ['start', 'skip', 'no_show'],
                    'started' => ['complete', 'skip'],
                    'skipped' => ['recheckin'],
                    'completed' => ['revert'],
                ];
            @endphp

            <div class="space-y-6">
                <!-- Doctor Details & Status Panel -->
                <div class="bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-2xl p-6 shadow-sm flex flex-col xl:flex-row xl:items-center justify-between gap-6">
                    <div class="flex items-center gap-4 min-w-0">
                        <div class="h-14 w-14 rounded-full bg-primary-100 dark:bg-primary-900/40 text-primary-600 dark:text-primary-400 flex items-center justify-center font-bold text-xl shrink-0">
                            {{ strtoupper(substr($doctor->first_name, 0, 1) . substr($doctor->last_name, 0, 1)) }}
                        </div>
                        <div class="min-w-0">
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white truncate">Dr. {{ $doctor->first_name }} {{ $doctor->last_name }} - Queue Management</h2>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 flex flex-wrap items-center gap-x-3 gap-y-1">
                                <span>Room: <strong>{{ $doctor->address_line2 ?: 'Room 204' }}</strong></span>
                                <span class="hidden sm:inline">•</span>
                                <span>Department: <strong>{{ $doctor->departments->first()?->name ?? 'General Practice' }}</strong></span>
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap xl:flex-nowrap items-center gap-3 xl:justify-end border-t xl:border-t-0 pt-4 xl:pt-0 border-gray-100 dark:border-gray-800">
                        @if($showLogs)
                        <button
                            wire:click="toggleLogsView"
                            class="flex items-center justify-center gap-2 px-4 py-2.5 bg-primary hover:bg-primary-500 text-white rounded-md text-xs font-bold transition shrink-0"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                            Back to Queue Board
                        </button>
                        @else
                        <button
                            wire:click="toggleLogsView"
                            class="flex items-center justify-center gap-2 px-4 py-2.5 bg-transparent hover:bg-primary dark:bg-gray-800 dark:hover:bg-gray-700 text-primary dark:text-primary rounded-md text-xs font-bold transition border border-primary shrink-0"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            View Queue Logs / Audit
                        </button>
                        @endif

                        <!-- Doctor Check-in & Break Control Buttons -->
                        <div class="flex flex-wrap xl:flex-nowrap items-center gap-3 xl:border-l xl:border-gray-150 xl:dark:border-gray-800 xl:pl-3" style="border-color: #00000021;">
                            <!-- Check In / Out Buttons -->
                            <div class="flex items-center gap-2">
                                <button
                                    @if($doctor->is_checked_in) disabled @endif
                                    x-on:click.prevent="triggerConfirm('Doctor Check-In', 'Are you sure you want to check in this doctor?', $wire => { $wire.checkInDoctor('{{ $doctor->id }}') })"
                                    class="px-4 py-2.5 text-xs font-bold rounded-md border transition-all duration-200 {{ $doctor->is_checked_in ? 'bg-gray-100 border-gray-200 text-gray-400 dark:bg-gray-800 dark:border-gray-700 cursor-not-allowed' : 'bg-green-50 border-green-200 text-green-700 hover:bg-green-100 dark:bg-green-950/20 dark:border-green-900/60 dark:text-green-400' }}"
                                >
                                    Check-In
                                </button>
                                <button
                                    @if(!$doctor->is_checked_in) disabled @endif
                                    x-on:click.prevent="triggerConfirm('Doctor Check-Out', 'Are you sure you want to check out this doctor?', $wire => { $wire.checkOutDoctor('{{ $doctor->id }}') })"
                                    class="px-4 py-2.5 text-xs font-bold rounded-md border transition-all duration-200 {{ !$doctor->is_checked_in ? 'bg-gray-100 border-gray-200 text-gray-400 dark:bg-gray-800 dark:border-gray-700 cursor-not-allowed' : 'bg-red-50 border-red-200 text-red-700 hover:bg-red-100 dark:bg-red-950/20 dark:border-red-900/60 dark:text-red-400' }}"
                                >
                                    Check-Out
                                </button>
                            </div>

                            <!-- Break On / Off Buttons -->
                            <div class="flex items-center gap-2">
                                <button
                                    @if(!$doctor->is_checked_in || $doctor->is_on_break) disabled @endif
                                    x-on:click.prevent="triggerConfirm('Doctor Break Status', 'Are you sure you want to start break for this doctor?', $wire => { $wire.startDoctorBreak('{{ $doctor->id }}') })"
                                    class="px-4 py-2.5 text-xs font-bold rounded-md border transition-all duration-200 {{ !$doctor->is_checked_in || $doctor->is_on_break ? 'bg-gray-100 border-gray-200 text-gray-400 dark:bg-gray-800 dark:border-gray-700 cursor-not-allowed' : 'bg-amber-50 border-amber-200 text-amber-700 hover:bg-amber-100 dark:bg-amber-950/20 dark:border-amber-900/60 dark:text-amber-400' }}"
                                >
                                    On Break
                                </button>
                                <button
                                    @if(!$doctor->is_checked_in || !$doctor->is_on_break) disabled @endif
                                    x-on:click.prevent="triggerConfirm('Doctor Break Status', 'Are you sure you want to end break for this doctor?', $wire => { $wire.endDoctorBreak('{{ $doctor->id }}') })"
                                    class="px-4 py-2.5 text-xs font-bold rounded-md border transition-all duration-200 {{ !$doctor->is_checked_in || !$doctor->is_on_break ? 'bg-gray-100 border-gray-200 text-gray-400 dark:bg-gray-800 dark:border-gray-700 cursor-not-allowed' : 'bg-green-50 border-green-200 text-green-700 hover:bg-green-100 dark:bg-green-950/20 dark:border-green-900/60 dark:text-green-400' }}"
                                >
                                    Off Break
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                @if($showLogs)
                    @php
                        $logs = $this->getSelectedDoctorQueueLogs();
                    @endphp

                    <div class="bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-2xl p-6 shadow-sm">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">Queue Audit Logs</h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Single doctor audit trail for Dr. {{ $doctor->first_name }} {{ $doctor->last_name }}.</p>
                    </div>

                    <div class="bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-2xl p-6 shadow-sm">
                        <div class="relative pl-11">
                            <div class="absolute left-[15px] top-0 bottom-0 w-px bg-gray-300 dark:bg-gray-700"></div>

                            <div class="space-y-6">
                                @forelse($logs as $log)
                                    @php
                                        $patientName = trim(($log->appointment?->patient?->first_name ?? '') . ' ' . ($log->appointment?->patient?->last_name ?? ''));
                                        $doctorName = trim(($log->doctor?->first_name ?? $doctor->first_name ?? '') . ' ' . ($log->doctor?->last_name ?? $doctor->last_name ?? ''));
                                        $queueNumber = $log->appointment?->queue_number;
                                        $remarks = $log->remarks;

                                        $detailsMap = [
                                            'check_in' => [
                                                'title' => 'Checked In',
                                                'desc' => 'Doctor checked in and is available for queue management.',
                                                'color' => 'green',
                                                'icon' => 'checkin.png',
                                            ],
                                            'check_out' => [
                                                'title' => 'Checked Out',
                                                'desc' => 'Doctor checked out from queue management.',
                                                'color' => 'gray',
                                                'icon' => 'no-show.png',
                                            ],
                                            'break_start' => [
                                                'title' => 'Break Started',
                                                'desc' => 'Doctor started a break and is temporarily unavailable.',
                                                'color' => 'amber',
                                                'icon' => 'skip.png',
                                            ],
                                            'break_end' => [
                                                'title' => 'Break Ended',
                                                'desc' => 'Doctor returned from break and is available again.',
                                                'color' => 'green',
                                                'icon' => 'checkin.png',
                                            ],
                                            'revert' => [
                                                'title' => 'Patient Checked In',
                                                'desc' => ($patientName !== '' ? $patientName : 'Patient') . ($queueNumber ? " ({$queueNumber})" : '') . ' checked in.',
                                                'color' => 'green',
                                                'icon' => 'checkin.png',
                                            ],
                                            'start' => [
                                                'title' => 'Consultation Started',
                                                'desc' => 'Consultation started for ' . ($patientName !== '' ? $patientName : 'patient') . ($queueNumber ? " ({$queueNumber})" : '') . '.',
                                                'color' => 'indigo',
                                                'icon' => 'start.png',
                                            ],
                                            'complete' => [
                                                'title' => 'Consultation Completed',
                                                'desc' => 'Consultation completed for ' . ($patientName !== '' ? $patientName : 'patient') . ($queueNumber ? " ({$queueNumber})" : '') . '.',
                                                'color' => 'green',
                                                'icon' => 'completed.png',
                                            ],
                                            'skip' => [
                                                'title' => 'Patient Skipped',
                                                'desc' => ($patientName !== '' ? $patientName : 'Patient') . ($queueNumber ? " ({$queueNumber})" : '') . ' was skipped in the queue.',
                                                'color' => 'gray',
                                                'icon' => 'skip.png',
                                            ],
                                            'not_complete' => [
                                                'title' => 'Marked No Show',
                                                'desc' => ($patientName !== '' ? $patientName : 'Patient') . ($queueNumber ? " ({$queueNumber})" : '') . ' was marked as no show.',
                                                'color' => 'red',
                                                'icon' => 'no-show.png',
                                            ],
                                        ];

                                        $colorMap = [
                                            'green' => [
                                                'badge' => 'bg-green-50 text-green-700 border-green-200 dark:bg-green-950/30 dark:text-green-400 dark:border-green-900/60',
                                            ],
                                            'amber' => [
                                                'badge' => 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/30 dark:text-amber-400 dark:border-amber-900/60',
                                            ],
                                            'indigo' => [
                                                'badge' => 'bg-indigo-50 text-indigo-700 border-indigo-200 dark:bg-indigo-950/30 dark:text-indigo-400 dark:border-indigo-900/60',
                                            ],
                                            'red' => [
                                                'badge' => 'bg-red-50 text-red-700 border-red-200 dark:bg-red-950/30 dark:text-red-400 dark:border-red-900/60',
                                            ],
                                            'gray' => [
                                                'badge' => 'bg-gray-100 text-gray-700 border-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700',
                                            ],
                                        ];

                                        $details = $detailsMap[$log->action] ?? [
                                            'title' => ucwords(str_replace('_', ' ', $log->action ?? 'Unknown')),
                                            'desc' => 'Queue action recorded.',
                                            'color' => 'gray',
                                            'icon' => 'checkin.png',
                                        ];

                                        $style = $colorMap[$details['color']] ?? $colorMap['gray'];
                                        $timeLabel = $log->created_at?->format('H:i') ?? 'N/A';
                                        $dateLabel = $log->created_at?->format('d/m/Y') ?? 'N/A';
                                    @endphp

                                    <div class="relative">
                                        <span class="absolute -left-[45px] top-1 flex h-8 w-8 items-center justify-center rounded-full bg-white dark:bg-gray-900 border-2 border-gray-200 dark:border-gray-800 z-10">
                                            <img src="/images/queue-images/{{ $details['icon'] }}" class="h-4.5 w-4.5 object-contain" alt="{{ $log->action }}" />
                                        </span>

                                        <div class="bg-white dark:bg-gray-900 border border-gray-150 dark:border-gray-850 rounded-md p-4 flex flex-col md:flex-row md:items-center justify-between gap-4 hover:shadow-md transition-all duration-300">
                                            <div class="space-y-1.5 min-w-0 flex-1">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold border {{ $style['badge'] }}">
                                                        {{ $details['title'] }}
                                                    </span>

                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-primary-50 text-primary dark:bg-primary-950/20 border border-primary-100 dark:border-primary-900/60">
                                                        Dr. {{ $doctorName !== '' ? $doctorName : 'N/A' }}
                                                    </span>

                                                    <span class="text-[11px] font-semibold text-gray-450 dark:text-gray-500">
                                                        {{ $dateLabel }}
                                                    </span>
                                                </div>

                                                <p class="text-sm font-medium text-gray-700 dark:text-gray-350 leading-relaxed">
                                                    {{ $details['desc'] }}
                                                </p>

                                                @if($remarks)
                                                    <div class="mt-2 text-xs bg-gray-50 dark:bg-gray-800 rounded-lg p-2.5 border border-gray-150 dark:border-gray-850 text-gray-600 dark:text-gray-450">
                                                        <span class="font-bold text-gray-500 dark:text-gray-400">Note:</span> {{ $remarks }}
                                                    </div>
                                                @endif
                                            </div>

                                            <div class="flex flex-row md:flex-col items-center md:items-end justify-between md:justify-center gap-1.5 pt-2 md:pt-0 border-t md:border-t-0 border-gray-100 dark:border-gray-800 text-xs shrink-0">
                                                <div class="text-xs font-extrabold text-gray-900 dark:text-white">
                                                    {{ $timeLabel }}
                                                </div>
                                                <div class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">
                                                    By {{ $log->creator?->name ?? 'System' }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="py-12 text-center text-gray-500 bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-2xl">
                                        <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                                        <h3 class="font-semibold text-gray-700 dark:text-gray-300">No events logged yet</h3>
                                        <p class="text-sm text-gray-450 mt-1">Activities will show up here as actions are performed.</p>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                @else
                <!-- Statistics Widgets Grid -->
                <div x-data="{ open: false }" class="mb-4">
                    <button
                        type="button"
                        @click="open = !open"
                        class="flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-900 rounded-md shadow-sm border border-gray-200 dark:border-gray-800 text-xs font-bold transition select-none w-full text-left"
                    >
                        <span>
                            <svg :class="{'rotate-90': open}" class="inline mr-2 w-4 h-4 text-gray-400 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </span>
                        <span class="uppercase tracking-wide">Show Today's OPD Statistics</span>
                        <span x-show="!open" class="ml-auto text-gray-400 text-[11px]">(click to expand)</span>
                        <span x-show="open" class="ml-auto text-gray-400 text-[11px]">(click to collapse)</span>
                    </button>
                    <div x-show="open" x-collapse class="mt-2">
                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                            <div class="bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-md p-4 shadow-sm border-l-4 border-l-gray-400">
                                <div class="text-[15px] uppercase font-bold text-gray-400 dark:text-gray-500">Total Booked</div>
                                <div class="text-2xl font-extrabold text-gray-950 dark:text-white mt-1">{{ $stats['total'] }}</div>
                            </div>
                            <div class="bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-md p-4 shadow-sm border-l-4 border-l-amber-500">
                                <div class="text-[15px] uppercase font-bold text-gray-400 dark:text-gray-500">Checked-In / Waiting</div>
                                <div class="text-2xl font-extrabold text-amber-500 mt-1">{{ $stats['waiting'] }}</div>
                            </div>
                            <!-- <div class="bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-md p-4 shadow-sm border-l-4 border-l-indigo-500">
                                <div class="text-[10px] uppercase font-bold text-gray-400 dark:text-gray-500">Started</div>
                                <div class="text-2xl font-extrabold text-indigo-500 mt-1">{{ $stats['started'] }}</div>
                            </div> -->
                            <div class="bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-md p-4 shadow-sm border-l-4 border-l-green-500">
                                <div class="text-[15px] uppercase font-bold text-gray-400 dark:text-gray-500">Completed</div>
                                <div class="text-2xl font-extrabold text-green-500 mt-1">{{ $stats['completed'] }}</div>
                            </div>
                            <!-- <div class="bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-md p-4 shadow-sm border-l-4 border-l-gray-500">
                                <div class="text-[10px] uppercase font-bold text-gray-400 dark:text-gray-500">Skipped</div>
                                <div class="text-2xl font-extrabold text-gray-500 mt-1">{{ $stats['skipped'] }}</div>
                            </div> -->
                            <div class="bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-md p-4 shadow-sm border-l-4 border-l-red-500">
                                <div class="text-[15px] uppercase font-bold text-gray-400 dark:text-gray-500">No Show</div>
                                <div class="text-2xl font-extrabold text-red-500 mt-1">{{ $stats['no_show'] }}</div>
                            </div>
                        </div>
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
                                class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md px-3 py-2 text-xs focus:outline-none focus:ring-1 focus:ring-primary-500 text-gray-900 dark:text-white"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-1">Queue Status</label>
                            <select
                                wire:model.live="statusFilter"
                                class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md px-3 py-2 text-xs focus:outline-none focus:ring-1 focus:ring-primary-500 text-gray-900 dark:text-white"
                            >
                                <option value="all">All Statuses</option>
                                <option value="scheduled">Scheduled</option>
                                <option value="checkin">Checked-In</option>
                                <option value="started">Started</option>
                                <option value="completed">Completed</option>
                                <option value="passed_completed">Passed / Completed</option>
                                <option value="skipped">Skipped</option>
                                <option value="no_show">No Show</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-1">Visit Type</label>
                            <select
                                wire:model.live="visitTypeFilter"
                                class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md px-3 py-2 text-xs focus:outline-none focus:ring-1 focus:ring-primary-500 text-gray-900 dark:text-white"
                            >
                                <option value="all">All Visit Types</option>
                                <option value="in-person">In-Person</option>
                                <option value="video">Video Consultation</option>
                            </select>
                        </div>
                        <div class="flex items-end gap-2">
                            <button
                                wire:click="resetFilters"
                                class="flex-1 bg-gray-100 hover:bg-gray-200 dark:bg-gray-850 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300 py-2 rounded-md text-xs font-bold transition shadow-sm border border-gray-200/50"
                            >
                                Reset Filters
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Appointments Queue Table -->
                <div class="bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-2xl overflow-hidden shadow-sm">
                    <div class="queue-sticky-legend bg-gray-50/90 dark:bg-gray-800/90 border-b border-gray-100 dark:border-gray-800 px-6 py-4">
                        <div class="queue-sticky-legend-inner flex items-center gap-4">
                            <div class="flex items-center gap-2 shrink-0">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                                <span class="text-xs font-bold text-gray-700 dark:text-white">Status Meanings:</span>
                            </div>
                            <div class="flex items-center gap-3 text-xs text-gray-550 dark:text-gray-400 overflow-x-auto pb-1 min-w-0 flex-1">
                                @foreach(['scheduled', 'no_show', 'checkin', 'started', 'completed', 'skipped'] as $legendStatus)
                                    @php
                                        $legend = $queueStatusStyles[$legendStatus];
                                    @endphp
                                    <div class="flex items-center gap-1.5 px-2 py-1 rounded-xl shadow-xs whitespace-nowrap shrink-0 {{ $legend['legend_card'] }}">
                                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[10px] font-bold {{ $legend['badge'] }}">
                                            @if($legendStatus === 'started')
                                                <div class="relative flex h-1.5 w-1.5 shrink-0">
                                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
                                                    <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-indigo-600"></span>
                                                </div>
                                            @endif
                                            <span>{{ $legend['label'] }}</span>
                                        </span>
                                        <span class="text-[10px] font-semibold {{ $legendStatus === 'started' ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-500 dark:text-gray-400' }} {{ $legendStatus === 'started' ? 'animate-pulse' : '' }}">{{ $legend['legend_text'] }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div
                        class="overflow-x-auto"
                        style="height: auto; min-height: 0; max-height: none;"
                        x-data
                        x-init="
                            $nextTick(() => {
                                let el = $el;
                                const MIN_HEIGHT = 500;
                                function adjustHeight() {
                                    if (el.scrollHeight > MIN_HEIGHT) {
                                        el.style.height = MIN_HEIGHT + 'px';
                                        el.style.overflowY = 'auto';
                                    } else {
                                        el.style.height = 'auto';
                                        el.style.overflowY = 'visible';
                                    }
                                }
                                adjustHeight();
                                const resizeObserver = new ResizeObserver(adjustHeight);
                                resizeObserver.observe(el);
                            });
                        "
                    >

                        <table class="w-full text-left border-collapse">
                            <thead class="queue-sticky-head">
                                <tr class="bg-gray-50/95 dark:bg-gray-800/95 border-b border-gray-100 dark:border-gray-800 text-[11px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">
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
                                        $queueStatus = $this->resolveQueueStatus($app);
                                        $statusMeta = $queueStatusStyles[$queueStatus] ?? [
                                            'label' => ucfirst($queueStatus),
                                            'badge' => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
                                            'row' => 'hover:bg-gray-50/50 dark:hover:bg-gray-800/30',
                                        ];
                                        $rowActions = $queueActionsByStatus[$queueStatus] ?? [];
                                        if (($queueStatus === 'scheduled') && $this->shouldShowScheduledQueueAction($app)) {
                                            $rowActions = ['mark_checkin', 'no_show'];
                                        }
                                    @endphp
                                    <tr class="text-sm transition duration-150 {{ $statusMeta['row'] }}">
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

                                         <!-- Status Badge / Icon -->
                                         <td class="px-6 py-4 whitespace-nowrap">
                                             <div class="flex items-center gap-2">
                                                 <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-xs font-bold {{ $statusMeta['badge'] }}">
                                                     {{ $statusMeta['label'] }}
                                                 </span>
                                             </div>
                                         </td>

                                         <!-- Next in Queue Info -->
                                         <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-500 dark:text-gray-400 font-semibold font-mono">
                                             {{ $this->getNextInQueueText($app, $allAppointments) }}
                                         </td>

                                         <!-- Actions Grid -->
                                         <td class="px-6 py-4 whitespace-nowrap text-center">
                                             <div class="flex items-center justify-center gap-2">
                                                 @foreach($rowActions as $actionKey)
                                                     @if($actionKey === 'mark_checkin')
                                                         <button
                                                             x-on:click.prevent="triggerConfirm('Mark Patient Checked-in', 'Are you sure you want to mark this patient as checked in?', $wire => { $wire.markCheckIn('{{ $app->id }}') })"
                                                             title="Mark Check-in"
                                                             class="{{ $actionButtonBaseClass }} {{ $actionButtonStyles['mark_checkin'] }}"
                                                         >
                                                             Check-in
                                                         </button>
                                                     @elseif($actionKey === 'start')
                                                         <button
                                                             x-on:click.prevent="triggerConfirm('Start Consultation', 'Are you sure you want to start this consultation?', $wire => { $wire.startAppointment('{{ $app->id }}') })"
                                                             title="Start Consultation"
                                                             class="{{ $actionButtonBaseClass }} {{ $actionButtonStyles['start'] }}"
                                                         >
                                                             Start
                                                         </button>
                                                     @elseif($actionKey === 'no_show')
                                                         <button
                                                             wire:click="openNoShowModal('{{ $app->id }}')"
                                                             title="Mark No Show"
                                                             class="{{ $actionButtonBaseClass }} {{ $actionButtonStyles['skip'] }}"
                                                         >
                                                             No Show
                                                         </button>
                                                     @elseif($actionKey === 'skip')
                                                         <button
                                                             wire:click="openSkipModal('{{ $app->id }}')"
                                                             title="Skip / Notify Patient"
                                                             class="{{ $actionButtonBaseClass }} {{ $actionButtonStyles['skip'] }}"
                                                         >
                                                             Skip / Notify
                                                         </button>
                                                     @elseif($actionKey === 'complete')
                                                         <button
                                                             wire:click="openCompleteModal('{{ $app->id }}')"
                                                             title="Complete Consultation"
                                                             class="{{ $actionButtonBaseClass }} {{ $actionButtonStyles['complete'] }}"
                                                         >
                                                            Mark Complete
                                                         </button>
                                                     @elseif($actionKey === 'recheckin')
                                                         <button
                                                             x-on:click.prevent="triggerConfirm('Re-check-in Patient', 'Are you sure you want to recheck-in this patient?', $wire => { $wire.markCheckIn('{{ $app->id }}') })"
                                                             title="Re-checkin"
                                                             class="{{ $actionButtonBaseClass }} {{ $actionButtonStyles['recheckin'] }}"
                                                         >
                                                             Re-check-in
                                                         </button>
                                                     @elseif($actionKey === 'send_note')
                                                         <button
                                                             wire:click="openSkipModal('{{ $app->id }}')"
                                                             title="Skip / Notify Patient"
                                                             class="{{ $actionButtonBaseClass }} {{ $actionButtonStyles['send_note'] }}"
                                                         >
                                                             Skip / Notify
                                                         </button>
                                                     @elseif($actionKey === 'revert')
                                                         <button
                                                             x-on:click.prevent="triggerConfirm('Re-check-in Patient', 'Are you sure you want to check-in this patient again?', $wire => { $wire.revertAppointment('{{ $app->id }}') })"
                                                             title="Re-checkin"
                                                             class="{{ $actionButtonBaseClass }} {{ $actionButtonStyles['revert'] }}"
                                                         >
                                                             Re-check-in
                                                         </button>
                                                     @endif
                                                 @endforeach

                                                 <!-- View Patient Details Eye Action -->
                                                 <a
                                                     href="/admin/appointments/{{ $app->slug }}"
                                                     target="_blank"
                                                     title="View Details"
                                                     class="{{ $actionButtonBaseClass }} {{ $actionButtonStyles['view_details'] }}"
                                                 >
                                                     View Details
                                                 </a>
                                             </div>
                                             @if($queueStatus === 'scheduled' && empty($rowActions))
                                                 <div class="mt-2 text-[10px] font-medium text-gray-500 dark:text-gray-400">
                                                     {{ $this->getScheduledActionHint($app) }}
                                                 </div>
                                             @endif
                                         </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="px-6 py-12 text-center text-gray-550">
                                            No patients found matching the criteria.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($appointments->hasPages())
                        <div class="border-t border-gray-100 dark:border-gray-800 px-6 py-4">
                            {{ $appointments->links('livewire::tailwind') }}
                        </div>
                    @endif
                </div>
                @endif
        @endif

        <!-- Remarks Input Modal Overlay -->
        @if($activeModal)
        <div
            class="fixed inset-0 z-[1000] flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm"
        >
            <!-- Modal Box -->
            <div
                class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-150 dark:border-gray-800 shadow-2xl max-w-md w-full overflow-hidden p-6 space-y-4 transform transition-all duration-300"
            >
                <div class="flex items-start gap-4">
                    <div class="h-10 w-10 rounded-full {{ $activeModal === 'complete' ? 'bg-green-50 dark:bg-green-950/30 text-green-600 dark:text-green-400' : 'bg-amber-50 dark:bg-amber-950/30 text-amber-600 dark:text-amber-400' }} flex items-center justify-center shrink-0">
                        @if($activeModal === 'complete')
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        @else
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        @endif
                    </div>
                    <div class="space-y-1.5 flex-1 min-w-0">
                        <h3 class="text-lg font-bold text-gray-950 dark:text-white">
                            {{ match($activeModal) {
                                'complete' => 'Complete Consultation',
                                'no_show' => 'Mark No Show',
                                default => 'Skip Patient',
                            } }}
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-405 font-medium leading-relaxed">
                            {{ match($activeModal) {
                                'complete' => 'Please add any remarks or notes for this completed consultation.',
                                'no_show' => 'Please add a note for why this patient was marked as no show.',
                                default => 'Please add a reason or remark for skipping this patient. This note will be recorded in the audit trail and sent to the patient.',
                            } }}
                        </p>
                    </div>
                </div>

                <div class="space-y-2">
                    <label for="modalRemarks" class="block text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Remarks / Notes</label>
                    <textarea
                        id="modalRemarks"
                        wire:model="modalRemarks"
                        rows="3"
                        placeholder="Type remarks here..."
                        class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500"
                    ></textarea>
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <button
                        wire:click="closeModal"
                        class="px-4 py-2 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-md text-xs font-bold transition duration-200"
                    >
                        Cancel
                    </button>
                    <button
                        wire:click="{{ $activeModal === 'complete' ? 'submitComplete' : ($activeModal === 'no_show' ? 'submitNoShow' : 'submitSkip') }}"
                        class="px-4 py-2 bg-primary hover:bg-primary-500 text-white rounded-md text-xs font-bold transition duration-200 shadow-sm"
                    >
                        Confirm
                    </button>
                </div>
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
                        class="px-4 py-2 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-md text-xs font-bold transition duration-200"
                    >
                        Cancel
                    </button>
                    <button
                        x-on:click="executeConfirm()"
                        class="px-4 py-2 bg-primary hover:bg-primary-500 text-white rounded-md text-xs font-bold transition duration-200 shadow-sm"
                    >
                        Confirm
                    </button>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
