@props([
'doctor' => null,
'alpine' => false,
'variant' => 'grid',
'showQueue' => true,
'showFooter' => true,
'showTopbar' => false,
'doctorAccessor' => 'currentDoctor()',
'displayCopyAccessor' => 'displayCopy',
'focusedExpression' => 'isFocusedQueueItem(item)',
'syncQueueExpression' => 'syncQueueFocus(true)',
])

@php
$doctor = is_array($doctor) ? $doctor : [];

$doctorName = $doctor['name'] ?? 'Dr. Doctor';
$doctorDepartment = $doctor['department'] ?? 'General Practice';
$doctorExperience = $doctor['experience'] ?? '';
$doctorInitials = $doctor['initials'] ?? 'DR';
$doctorAvatar = $doctor['avatar'] ?? '';
$doctorEducation = $doctor['education_list'] ?? [];
$doctorBreakNote = $doctor['is_on_break'] ?? false;
$doctorQueueItems = $doctor['queue_items'] ?? [];
$doctorDefaultNotice = $doctor['default_notice'] ?? null;
@endphp

<div {{ $attributes->class(['doctor-card-shell']) }} @if($alpine) x-show="{{ $doctorAccessor }}" x-cloak @endif>
    @if($showTopbar)
        <x-display.queue-display.topbar />
    @endif
    <div class="doctor-card-inner">
        <x-display.queue-display.doctor-card-header
            :doctor="$doctor"
            :alpine="$alpine"
            :doctor-accessor="$doctorAccessor"
            :display-copy-accessor="$displayCopyAccessor"
        />

        @if($showQueue)
        <div class="queue-section-header">
            <h2>Today's Queue</h2>
        </div>

        <div class="queue-table-header">
            <div class="header-col token-col">Token No.</div>
            <div class="header-col patient-col">Patient Name</div>
            <div class="header-col status-col">Status</div>
            <div class="header-col turn-col">Your Turn</div>
        </div>

        <div class="queue-list-container"
             @if($alpine) x-init="{{ $syncQueueExpression }}" @endif>
            @if($alpine)
            <template x-if="{{ $doctorAccessor }} && {{ $doctorAccessor }}.queue_items && {{ $doctorAccessor }}.queue_items.length">
                <template x-for="item in {{ $doctorAccessor }}.queue_items" :key="item.token + item.patient">
                    <div class="queue-row fade-enter"
                        :data-queue-token="item.token"
                        :class="{ 'active': item.is_active, 'next': item.is_next, 'focused': {{ $focusedExpression }} }">
                        <div class="token" x-text="item.token"></div>
                        <div>
                            <div class="patient" x-text="item.patient"></div>
                            <div class="phone" x-text="item.mobile"></div>
                            <div class="time-slot" x-show="item.time_slot" x-text="item.time_slot"></div>
                        </div>
                        <div>
                            <div class="status-pill"
                                :class="item.is_active ? 'active-pill' : (item.status_pill_class || 'waiting')"
                                x-text="item.status_label || item.status"></div>
                        </div>
                        <div class="turn"
                            x-text="item.next_in_queue_text || item.turn">
                        </div>
                    </div>
                </template>
            </template>

            <template x-if="!{{ $doctorAccessor }} || !{{ $doctorAccessor }}.queue_items || !{{ $doctorAccessor }}.queue_items.length">
                <div class="queue-row" style="grid-template-columns: 1fr;">
                    <div>
                        <div class="patient" x-text="{{ $displayCopyAccessor }}.empty_state_title || 'No active doctor assigned'">
                        </div>
                        <div class="phone"
                            x-text="{{ $displayCopyAccessor }}.empty_state_text || 'Please wait while we load the queue.'"></div>
                    </div>
                </div>
            </template>
            @else
            @forelse($doctorQueueItems as $item)
            <div
                class="queue-row fade-enter {{ !empty($item['is_active']) ? 'active' : '' }} {{ !empty($item['is_next']) ? 'next' : '' }}"
                data-queue-token="{{ $item['token'] ?? '' }}">
                <div class="token">{{ $item['token'] ?? '' }}</div>
                <div>
                    <div class="patient">{{ $item['patient'] ?? '' }}</div>
                    <div class="phone">{{ $item['mobile'] ?? '' }}</div>
                    @if(!empty($item['time_slot']))
                    <div class="time-slot">{{ $item['time_slot'] }}</div>
                    @endif
                </div>
                <div>
                    <div
                        class="status-pill {{ !empty($item['is_active']) ? 'active-pill' : ($item['status_pill_class'] ?? 'waiting') }}">
                        {{ $item['status_label'] ?? ($item['status'] ?? '') }}
                    </div>
                </div>
                <div class="turn">
                    {{ $item['next_in_queue_text'] ?? $item['turn'] ?? '' }}
                </div>
            </div>
            @empty
            <div class="queue-row" style="grid-template-columns: 1fr;">
                <div>
                    <div class="patient">{{ $doctorDefaultNotice ?: 'No active doctor assigned' }}</div>
                    <div class="phone">No queue available right now.</div>
                </div>
            </div>
            @endforelse
            @endif
        </div>
        @endif

    </div>

    <div class="doctor-card-bg-icon" @if($alpine) x-cloak @endif>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"
            stroke-linejoin="round" style="width: 100%; height: 100%;">
            <path
                d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" />
            <path d="M12 5v6M9 8h6" />
        </svg>
    </div>

</div>

@if($showFooter)
<x-display.queue-display.doctor-card-footer
    :alpine="$alpine"
    :notice="$doctorDefaultNotice"
    :display-copy-accessor="$displayCopyAccessor"
/>
@endif
