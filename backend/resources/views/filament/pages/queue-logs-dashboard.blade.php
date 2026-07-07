<x-filament-panels::page>
    @php
        $logs = $this->getQueueLogs();
        $summaryMetrics = $this->getQueueSummaryMetrics();
        $patientConsultations = $this->getPatientConsultations();
        $doctorSummaries = $this->getDoctorAuditSummaries();
        $isSingleDay = $logFromDate === $logToDate;
        $selectedDoctor = $logDoctorId ? \App\Models\Doctor::with('departments')->find($logDoctorId) : null;
        $selectedDoctorStats = $selectedDoctor && $isSingleDay ? $this->getTimingStatsForDate($selectedDoctor->id, $logFromDate) : null;
    @endphp

    <div class="space-y-4">
        <section class="rounded-[10px] border border-[#dce3ec] bg-white px-5 py-4 shadow-[0_1px_3px_rgba(0,0,0,0.06)]">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                <div>
                    <div class="mb-2 text-[13px] font-medium text-gray-500">Appointments &amp; Finance / Queue Logs &amp; Audit</div>
                    <h1 class="text-[22px] font-extrabold tracking-[-0.02em] text-gray-950">Doctor Queue Logs &amp; Audit</h1>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <button
                        type="button"
                        onclick="history.back()"
                        class="inline-flex items-center gap-2 rounded-[8px] border border-[#bdd3f6] bg-white px-4 py-2.5 text-[13px] font-extrabold text-primary transition hover:bg-[#eef5ff]"
                    >
                        <x-heroicon-o-arrow-left class="h-4 w-4" />
                        Back
                    </button>

                    <button
                        type="button"
                        wire:click="downloadLogs"
                        class="inline-flex items-center gap-2 rounded-[8px] bg-primary px-4 py-2.5 text-[13px] font-extrabold text-white transition hover:bg-primary-600"
                    >
                        <x-heroicon-o-arrow-down-tray class="h-4 w-4" />
                        Export CSV
                    </button>

                    <button
                        type="button"
                        onclick="window.print()"
                        class="inline-flex items-center gap-2 rounded-[8px] bg-[#eef2f7] px-4 py-2.5 text-[13px] font-extrabold text-slate-700 transition hover:bg-[#e2e8f0]"
                    >
                        <x-heroicon-o-printer class="h-4 w-4" />
                        Print Report
                    </button>
                </div>
            </div>
        </section>

        <div class="grid grid-cols-1 gap-4 xl:grid-cols-[280px_minmax(0,1fr)]">
            <aside class="rounded-[12px] border border-[#dce3ec] bg-white p-4 shadow-[0_1px_3px_rgba(0,0,0,0.06)] xl:sticky xl:top-24 xl:h-[calc(100vh-11rem)] xl:overflow-y-auto">
                <div class="text-[12px] font-black uppercase tracking-[0.12em] text-slate-400">Doctors Directory</div>

                <div class="mt-3">
                    <input
                        wire:model.live="doctorSearchQuery"
                        type="text"
                        placeholder="Search doctor..."
                        class="w-full rounded-[8px] border border-[#d8dee8] bg-white px-3 py-2.5 text-[13px] text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-primary focus:ring-2 focus:ring-primary/10"
                    />
                </div>

                <div class="mt-4 space-y-2">
                    <button
                        wire:click="selectDoctor(null)"
                        class="flex w-full items-start gap-3 rounded-[10px] border px-3 py-3 text-left transition {{ is_null($logDoctorId) ? 'border-[#cfe1ff] bg-[#eef5ff]' : 'border-transparent hover:border-[#cfe1ff] hover:bg-[#f8fbff]' }}"
                    >
                        <div class="grid h-9 w-9 shrink-0 place-items-center rounded-full {{ is_null($logDoctorId) ? 'bg-primary text-white' : 'bg-[#dbeafe] text-primary' }} text-[11px] font-black">AD</div>
                        <div class="min-w-0 flex-1">
                            <div class="truncate text-[14px] font-extrabold text-slate-900">All Doctors</div>
                            <div class="mt-0.5 truncate text-[12px] text-slate-500">Combined queue report</div>
                            <span class="mt-2 inline-flex rounded-full bg-[#eaf2ff] px-2 py-0.5 text-[11px] font-black text-primary">Live</span>
                        </div>
                    </button>

                    @foreach ($this->getDoctors() as $doctor)
                        @php
                            $isActive = $logDoctorId === $doctor['id'];
                            $statusClasses = match ($doctor['status']) {
                                'On Break' => 'bg-[#fff3df] text-[#c26300]',
                                'Checked Out' => 'bg-[#ffecec] text-[#e11d48]',
                                default => 'bg-[#e7f8ee] text-[#0c8a43]',
                            };
                        @endphp

                        <button
                            wire:click="selectDoctor('{{ $doctor['id'] }}')"
                            class="flex w-full items-start gap-3 rounded-[10px] border px-3 py-3 text-left transition {{ $isActive ? 'border-[#cfe1ff] bg-[#eef5ff]' : 'border-transparent hover:border-[#cfe1ff] hover:bg-[#f8fbff]' }}"
                        >
                            <div class="grid h-9 w-9 shrink-0 place-items-center rounded-full {{ $isActive ? 'bg-primary text-white' : 'bg-[#dbeafe] text-primary' }} text-[11px] font-black">
                                {{ $doctor['initials'] }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="truncate text-[14px] font-extrabold text-slate-900">{{ $doctor['name'] }}</div>
                                <div class="mt-0.5 truncate text-[12px] text-slate-500">{{ $doctor['department'] }} • {{ $doctor['log_count_today'] }} logs today</div>
                                <span class="mt-2 inline-flex rounded-full px-2 py-0.5 text-[11px] font-black {{ $statusClasses }}">{{ $doctor['status'] }}</span>
                            </div>
                        </button>
                    @endforeach
                </div>
            </aside>

            <section class="min-w-0 rounded-[12px] border border-[#dce3ec] bg-white p-4 shadow-[0_1px_3px_rgba(0,0,0,0.06)] md:p-5">
                <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <h2 class="text-[22px] font-extrabold tracking-[-0.02em] text-slate-950">{{ $this->getCurrentAuditHeading() }}</h2>
                        <p class="mt-1 max-w-4xl text-[13px] leading-6 text-slate-500">{{ $this->getCurrentDoctorDescription() }}</p>
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="inline-flex rounded-full bg-[#eaf2ff] px-3 py-1 text-[11px] font-black text-primary">Live audit view</span>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-3 lg:grid-cols-[1fr_1fr_1fr_180px]">
                    <div>
                        <label class="mb-1.5 block text-[12px] font-extrabold text-slate-500">From Date</label>
                        <input
                            wire:model.live="logFromDate"
                            type="date"
                            class="h-[38px] w-full rounded-[8px] border border-[#d8dee8] bg-white px-3 text-[13px] text-slate-700 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/10"
                        />
                    </div>

                    <div>
                        <label class="mb-1.5 block text-[12px] font-extrabold text-slate-500">To Date</label>
                        <input
                            wire:model.live="logToDate"
                            type="date"
                            class="h-[38px] w-full rounded-[8px] border border-[#d8dee8] bg-white px-3 text-[13px] text-slate-700 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/10"
                        />
                    </div>

                    <div>
                        <label class="mb-1.5 block text-[12px] font-extrabold text-slate-500">Log Type</label>
                        <select
                            wire:model.live="logTypeFilter"
                            class="h-[38px] w-full rounded-[8px] border border-[#d8dee8] bg-white px-3 text-[13px] text-slate-700 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/10"
                        >
                            <option value="all">All logs</option>
                            <option value="patient">Patient logs</option>
                            <option value="break">Break logs</option>
                            <option value="queue">Queue changes</option>
                            <option value="system">System/admin actions</option>
                        </select>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-[12px] font-extrabold text-transparent">Apply</label>
                        <button
                            type="button"
                            wire:click="applyFilters"
                            class="h-[38px] w-full rounded-[8px] bg-primary px-3 text-[13px] font-extrabold text-white transition hover:bg-primary-600"
                        >
                            Apply
                        </button>
                    </div>
                </div>

                @if ($selectedDoctor && $isSingleDay && $selectedDoctorStats && !$selectedDoctorStats['is_future'] && count($selectedDoctorStats['slots']) > 0)
                    <div class="mt-4 rounded-[10px] border border-[#dce3ec] bg-[#f8fbff] p-4">
                        <div class="mb-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div class="text-[12px] font-black uppercase tracking-[0.12em] text-slate-400">Doctor Shift Windows</div>
                            @if (!is_null($selectedSlotIndex))
                                <button wire:click="$set('selectedSlotIndex', null)" class="text-[12px] font-extrabold text-primary transition hover:underline">Show full day</button>
                            @endif
                        </div>

                        <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
                            <button
                                wire:click="$set('selectedSlotIndex', null)"
                                class="rounded-[10px] border p-3 text-left transition {{ is_null($selectedSlotIndex) ? 'border-primary bg-white ring-2 ring-primary/10' : 'border-[#dce3ec] bg-white hover:border-[#bdd3f6]' }}"
                            >
                                <div class="text-[11px] font-black uppercase tracking-[0.08em] text-slate-400">Full Day Summary</div>
                                <div class="mt-1 text-[14px] font-extrabold text-slate-900">All shifts combined</div>
                                <div class="mt-1 text-[11px] font-semibold text-slate-500">{{ count($selectedDoctorStats['slots']) }} shift(s) scheduled</div>
                            </button>

                            @foreach ($selectedDoctorStats['slots'] as $idx => $slot)
                                <button
                                    wire:click="$set('selectedSlotIndex', {{ $idx }})"
                                    class="rounded-[10px] border p-3 text-left transition {{ $selectedSlotIndex === $idx ? 'border-primary bg-white ring-2 ring-primary/10' : 'border-[#dce3ec] bg-white hover:border-[#bdd3f6]' }}"
                                >
                                    <div class="text-[11px] font-black uppercase tracking-[0.08em] text-slate-400">Shift {{ $idx + 1 }}</div>
                                    <div class="mt-1 text-[14px] font-extrabold text-slate-900">{{ $slot['label'] }}</div>
                                    <div class="mt-1 text-[11px] font-semibold text-slate-500">
                                        @if ($slot['check_in'])
                                            Attended: {{ $slot['check_in']->format('H:i') }} - {{ $slot['last_app_end'] ? $slot['last_app_end']->format('H:i') : '—' }}
                                        @else
                                            No attendance logged
                                        @endif
                                    </div>
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="mt-4 grid grid-cols-2 gap-3 xl:grid-cols-5">
                    <div class="rounded-[12px] border border-[#e1e7ef] bg-gradient-to-b from-white to-[#f9fbff] p-4">
                        <div class="text-[28px] font-extrabold leading-none text-slate-950">{{ $summaryMetrics['total'] }}</div>
                        <div class="mt-2 text-[12px] font-bold text-slate-500">Total Logs</div>
                    </div>
                    <div class="rounded-[12px] border border-[#e1e7ef] bg-gradient-to-b from-white to-[#f9fbff] p-4">
                        <div class="text-[28px] font-extrabold leading-none text-slate-950">{{ $summaryMetrics['patient'] }}</div>
                        <div class="mt-2 text-[12px] font-bold text-slate-500">Patient Actions</div>
                    </div>
                    <div class="rounded-[12px] border border-[#e1e7ef] bg-gradient-to-b from-white to-[#f9fbff] p-4">
                        <div class="text-[28px] font-extrabold leading-none text-slate-950">{{ $summaryMetrics['break'] }}</div>
                        <div class="mt-2 text-[12px] font-bold text-slate-500">Break Actions</div>
                    </div>
                    <div class="rounded-[12px] border border-[#e1e7ef] bg-gradient-to-b from-white to-[#f9fbff] p-4">
                        <div class="text-[28px] font-extrabold leading-none text-slate-950">{{ $summaryMetrics['queue'] }}</div>
                        <div class="mt-2 text-[12px] font-bold text-slate-500">Queue Changes</div>
                    </div>
                    <div class="col-span-2 rounded-[12px] border border-[#e1e7ef] bg-gradient-to-b from-white to-[#f9fbff] p-4 xl:col-span-1">
                        <div class="text-[28px] font-extrabold leading-none text-slate-950">{{ $summaryMetrics['system'] }}</div>
                        <div class="mt-2 text-[12px] font-bold text-slate-500">Admin / System</div>
                    </div>
                </div>

                <div class="mt-5 border-b border-[#e5e7eb]">
                    <div class="-mb-px flex flex-wrap gap-2">
                        @foreach ([
                            'timeline' => 'Timeline',
                            'consultations' => 'Patient-wise',
                            'doctor' => 'Doctor Summary',
                            'audit' => 'Audit Details',
                        ] as $tabKey => $tabLabel)
                            <button
                                wire:click="selectTab('{{ $tabKey }}')"
                                class="border-b-[3px] px-3 py-3 text-[13px] font-black transition {{ $logTab === $tabKey ? 'border-primary text-primary' : 'border-transparent text-slate-500 hover:text-slate-700' }}"
                            >
                                {{ $tabLabel }}
                            </button>
                        @endforeach
                    </div>
                </div>

                @if ($logTab === 'timeline')
                    <div class="mt-4 rounded-[10px] border border-[#ffe0a3] bg-[#fff8e9] px-4 py-3 text-[13px] leading-6 text-[#8a4b00]">
                        This timeline shows every important queue action in order: doctor availability, breaks, patient attended, skipped or re-queued, completed consultation, remarks and who performed the action.
                    </div>

                    <div class="mt-4">
                        @if ($logs->hasPages())
                            <div class="mb-4">{{ $logs->links('livewire::tailwind') }}</div>
                        @endif

                        <div class="relative pl-7 before:absolute before:left-[10px] before:top-0 before:h-full before:w-[2px] before:bg-[#dce8fb] before:content-['']">
                            @forelse ($logs as $log)
                                @php
                                    $details = $this->getLogDetails($log);
                                    $tagClasses = match ($details['color']) {
                                        'green' => 'bg-[#e7f8ee] text-[#0c8a43]',
                                        'amber' => 'bg-[#fff3df] text-[#c26300]',
                                        'indigo' => 'bg-[#eaf2ff] text-primary',
                                        'danger' => 'bg-[#ffecec] text-[#e11d48]',
                                        default => 'bg-[#eef2f7] text-slate-700',
                                    };
                                @endphp

                                <div class="relative mb-3 grid gap-4 rounded-[12px] border border-[#e1e7ef] bg-white p-4 shadow-[0_1px_2px_rgba(0,0,0,0.04)] lg:grid-cols-[92px_minmax(0,1fr)_160px]">
                                    <span class="absolute -left-[23px] top-[18px] h-3 w-3 rounded-full border-[3px] border-white bg-primary shadow-[0_0_0_2px_#bdd7ff]"></span>

                                    <div class="text-[15px] font-black text-primary">{{ $log->created_at->format('h:i A') }}</div>

                                    <div class="min-w-0">
                                        <h4 class="text-[15px] font-extrabold text-slate-950">{{ $details['title'] }}</h4>
                                        <p class="mt-1 text-[13px] leading-6 text-slate-500">{{ $details['desc'] }}</p>
                                        <p class="mt-2 text-[12px] text-slate-400">
                                            Performed by:
                                            <span class="font-bold text-slate-600">{{ $log->creator?->name ?? 'System' }}</span>
                                            @if ($details['remarks'])
                                                | Remarks: {{ $details['remarks'] }}
                                            @endif
                                        </p>
                                    </div>

                                    <div class="flex items-start justify-start lg:justify-end">
                                        <span class="inline-flex rounded-full px-3 py-1 text-[12px] font-black {{ $tagClasses }}">{{ $details['title'] }}</span>
                                    </div>
                                </div>
                            @empty
                                <div class="rounded-[12px] border border-[#e1e7ef] bg-white px-4 py-10 text-center text-[14px] text-slate-500">No logs found for the selected filters.</div>
                            @endforelse
                        </div>

                        @if ($logs->hasPages())
                            <div class="mt-4">{{ $logs->links('livewire::tailwind') }}</div>
                        @endif
                    </div>
                @elseif ($logTab === 'consultations')
                    <div class="mt-4 overflow-hidden rounded-[12px] border border-[#dce3ec]">
                        <div class="border-b border-[#dce3ec] bg-white px-4 py-3">
                            <h3 class="text-[15px] font-extrabold text-slate-950">Patient-wise Consultation Summary</h3>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full border-collapse text-left text-[13px]">
                                <thead>
                                    <tr class="bg-[#f8fafc] text-[12px] font-black uppercase tracking-[0.06em] text-slate-600">
                                        <th class="px-4 py-3">Token</th>
                                        <th class="px-4 py-3">Patient</th>
                                        <th class="px-4 py-3">Doctor</th>
                                        <th class="px-4 py-3">Booked</th>
                                        <th class="px-4 py-3">Check-In</th>
                                        <th class="px-4 py-3">Started</th>
                                        <th class="px-4 py-3">Ended</th>
                                        <th class="px-4 py-3">Waiting</th>
                                        <th class="px-4 py-3">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[#edf0f5] bg-white">
                                    @forelse ($patientConsultations as $consultation)
                                        @php
                                            $statusClasses = match ($consultation['status']) {
                                                'completed' => 'bg-[#e7f8ee] text-[#0c8a43]',
                                                'started' => 'bg-[#eaf2ff] text-primary',
                                                'checkin' => 'bg-[#fff3df] text-[#c26300]',
                                                'skipped' => 'bg-[#eef2f7] text-slate-700',
                                                default => 'bg-[#ffecec] text-[#e11d48]',
                                            };
                                        @endphp
                                        <tr class="align-top text-slate-700">
                                            <td class="px-4 py-3 font-black text-primary">{{ $consultation['token'] }}</td>
                                            <td class="px-4 py-3">
                                                <div class="font-extrabold text-slate-900">{{ $consultation['patient_name'] }}</div>
                                                <div class="mt-1 text-[11px] text-slate-400">{{ $consultation['phone'] }}</div>
                                            </td>
                                            <td class="px-4 py-3">{{ $consultation['doctor_name'] }}</td>
                                            <td class="px-4 py-3">{{ $consultation['booked_time'] }}</td>
                                            <td class="px-4 py-3">{{ $consultation['check_in'] ? $consultation['check_in']->format('H:i') : '—' }}</td>
                                            <td class="px-4 py-3">{{ $consultation['started'] ? $consultation['started']->format('H:i') : '—' }}</td>
                                            <td class="px-4 py-3">{{ $consultation['completed'] ? $consultation['completed']->format('H:i') : '—' }}</td>
                                            <td class="px-4 py-3">{{ $consultation['waiting_seconds'] !== null ? $this->formatDurationMinutes($consultation['waiting_seconds']) : '—' }}</td>
                                            <td class="px-4 py-3">
                                                <span class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-black {{ $statusClasses }}">
                                                    {{ $consultation['status'] === 'checkin' ? 'Checked-in' : ($consultation['status'] === 'no_show' ? 'No Show' : ucfirst($consultation['status'])) }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="px-4 py-10 text-center text-slate-500">No patient consultation records found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                @elseif ($logTab === 'doctor')
                    <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                        @foreach ($doctorSummaries as $summary)
                            <div class="rounded-[10px] border border-[#e1e7ef] bg-white p-4">
                                <div class="text-[15px] font-extrabold text-slate-950">{{ $summary['name'] }}</div>
                                <div class="mt-1 text-[12px] text-slate-500">{{ $summary['department'] }}</div>
                                <div class="mt-3 space-y-1.5 text-[13px] leading-6 text-slate-600">
                                    <div>Check-in: <span class="font-bold text-slate-900">{{ $summary['check_in'] }}</span></div>
                                    <div>Last End: <span class="font-bold text-slate-900">{{ $summary['last_end'] }}</span></div>
                                    <div>Patients attended: <span class="font-bold text-slate-900">{{ $summary['patients_attended'] }}</span></div>
                                    <div>Total break: <span class="font-bold text-slate-900">{{ $summary['break_time'] }}</span></div>
                                    <div>Active time: <span class="font-bold text-slate-900">{{ $summary['active_time'] }}</span></div>
                                    <div>Extra time: <span class="font-bold text-slate-900">{{ $summary['extra_time'] }}</span></div>
                                    <div>Skipped/Requeued: <span class="font-bold text-slate-900">{{ $summary['skipped_count'] }}</span></div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if ($selectedDoctor && $selectedDoctorStats && !$selectedDoctorStats['is_future'])
                        <div class="mt-4 overflow-hidden rounded-[12px] border border-[#dce3ec]">
                            <div class="border-b border-[#dce3ec] bg-white px-4 py-3">
                                <h3 class="text-[15px] font-extrabold text-slate-950">Daily Timing Calculation</h3>
                            </div>

                            <div class="grid gap-4 bg-white p-4 md:grid-cols-2">
                                <div class="rounded-[10px] border border-[#e1e7ef] bg-[#f9fbff] p-4">
                                    <div class="text-[11px] font-black uppercase tracking-[0.08em] text-slate-400">Scheduled Shift</div>
                                    <div class="mt-2 text-[17px] font-extrabold text-slate-950">
                                        {{ count($selectedDoctorStats['shift_intervals']) ? implode(' / ', $selectedDoctorStats['shift_intervals']) : 'No shift scheduled' }}
                                    </div>
                                </div>
                                <div class="rounded-[10px] border border-[#e1e7ef] bg-[#f9fbff] p-4">
                                    <div class="text-[11px] font-black uppercase tracking-[0.08em] text-slate-400">Actual Active Time</div>
                                    <div class="mt-2 text-[17px] font-extrabold text-primary">
                                        {{ $this->formatDurationMinutes((int) ($selectedDoctorStats['overall']['active_seconds'] ?? 0)) }}
                                    </div>
                                </div>
                                <div class="rounded-[10px] border border-[#e1e7ef] bg-[#f9fbff] p-4">
                                    <div class="text-[11px] font-black uppercase tracking-[0.08em] text-slate-400">Total Break</div>
                                    <div class="mt-2 text-[17px] font-extrabold text-[#c26300]">
                                        {{ $this->formatDurationMinutes((int) ($selectedDoctorStats['overall']['total_break_seconds'] ?? 0)) }}
                                    </div>
                                </div>
                                <div class="rounded-[10px] border border-[#e1e7ef] bg-[#f9fbff] p-4">
                                    <div class="text-[11px] font-black uppercase tracking-[0.08em] text-slate-400">Extra Time</div>
                                    <div class="mt-2 text-[17px] font-extrabold text-[#e11d48]">
                                        {{ $this->formatDurationMinutes((int) ($selectedDoctorStats['overall']['extra_seconds'] ?? 0)) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                @elseif ($logTab === 'audit')
                    <div class="mt-4 overflow-hidden rounded-[12px] border border-[#dce3ec]">
                        <div class="overflow-x-auto">
                            <table class="min-w-full border-collapse text-left text-[13px]">
                                <thead>
                                    <tr class="bg-[#f8fafc] text-[12px] font-black uppercase tracking-[0.06em] text-slate-600">
                                        <th class="px-4 py-3">Time</th>
                                        <th class="px-4 py-3">Action</th>
                                        <th class="px-4 py-3">Old Status</th>
                                        <th class="px-4 py-3">New Status</th>
                                        <th class="px-4 py-3">Performed By</th>
                                        <th class="px-4 py-3">Remarks</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[#edf0f5] bg-white">
                                    @forelse ($logs as $log)
                                        @php
                                            $details = $this->getLogDetails($log);
                                            $transition = $this->getStatusTransition($log);
                                        @endphp
                                        <tr class="align-top text-slate-700">
                                            <td class="px-4 py-3 font-semibold text-primary">{{ $log->created_at->format('h:i A') }}</td>
                                            <td class="px-4 py-3 font-extrabold text-slate-900">{{ $details['title'] }}</td>
                                            <td class="px-4 py-3">{{ $transition['old'] }}</td>
                                            <td class="px-4 py-3">{{ $transition['new'] }}</td>
                                            <td class="px-4 py-3">{{ $log->creator?->name ?? 'System' }}</td>
                                            <td class="px-4 py-3 text-slate-500">{{ $details['remarks'] ?: '—' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="px-4 py-10 text-center text-slate-500">No audit rows found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </section>
        </div>

        <div class="border-t border-[#dbe1e9] pt-4 text-center text-[13px] text-slate-500">
            © {{ now()->format('Y') }} Telehealth Deploymeta. All rights reserved.<br>
            info@cmctelehealth.com
        </div>
    </div>
</x-filament-panels::page>
