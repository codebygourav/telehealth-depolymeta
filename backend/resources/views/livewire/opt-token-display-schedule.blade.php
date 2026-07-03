<div
    id="opt-token-board-root"
    data-opt-token-board-root
    x-data="optTokenBoard()"
    x-init="init()"
    :class="{ 'board-refreshing': isRefreshing }"
    class="h-screen max-h-screen flex flex-col overflow-hidden"
>
    <div class="board-refresh-badge" x-show="isRefreshing" x-cloak>
        <span class="pulse"></span>
        Syncing queue
    </div>

    @php
        $scheduleDays = $board['schedule_days'] ?? [];
        $slotColumns = max(2, min(3, (int) ($board['same_time_card_columns'] ?? 2)));
    @endphp

    <div class="layout" style="grid-template-columns: minmax(0, 50%) minmax(0, 50%); gap: 0px; padding: 0px; overflow: hidden;">
        <aside class="panel doctor-panel" style="border-radius:0px; overflow: hidden;">
            <x-display.queue-display.doctor-card
                class="fade-enter"
                :alpine="true"
                variant="split"
                :show-topbar="true"
                :show-queue="true"
                :show-footer="true"
            />
        </aside>

        <section class="panel schedule-panel no-footer" style="overflow: hidden; display: grid; grid-template-rows: auto minmax(0, 1fr); min-height: 0; padding: 0; border-radius: 0px;border:none;">
            <div class="panel-head">
                <div class="panel-title">{{ $board['schedule_title'] ?? 'Full OPD Weekly Schedule' }}</div>
                <div class="badge">Auto Sliding</div>
            </div>

            <div class="slider">
                <div class="track">
                    @forelse($scheduleDays as $day)
                        <div class="day">
                            <div class="day-top">
                                <div>
                                    <div class="day-title">{{ $day['label'] ?? 'OPD Slots' }}</div>
                                    <div style="color: var(--muted); font-weight: 700; margin-top: 2px; font-size: 13px;">{{ $day['date_label'] ?? '' }}</div>
                                </div>
                            </div>

                            @if(!empty($day['items']))
                                <div class="slot-grid" style="grid-template-columns: repeat({{ $slotColumns }}, minmax(0, 1fr));">
                                    @foreach($day['items'] as $item)
                                        <div class="slot-card {{ !empty($item['is_active']) ? 'active' : '' }}">
                                            <div class="slot-card-main">
                                                <div class="slot-card-top">
                                                    <div>
                                                        <div class="slot-doctor">{{ $item['doctor'] ?? '' }}</div>
                                                        <div class="slot-meta">{{ $item['department'] ?? '' }}</div>
                                                    </div>
                                                    <span class="slot-availability-pill {{ $item['availability_class'] ?? $item['tag_class'] ?? 'available' }}">
                                                        {{ $item['availability_label'] ?? $item['tag_label'] ?? 'Available' }}
                                                    </span>
                                                </div>

                                                <div class="slot-time">{{ $item['time_slot'] ?? 'Time not set' }}</div>

                                                <div class="slot-stats">
                                                    <span>{{ $item['booking_summary'] ?? '0/0 booked' }}</span>
                                                    <span>{{ $item['remaining_slots'] ?? 0 }} left</span>
                                                </div>
                                            </div>
                                            <div class="room-pill">
                                                Room<br>{{ $item['room'] ?? '—' }}
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div style="height:70%;display:grid;place-items:center;text-align:center;">
                                    <div>
                                        <div style="font-size:54px;">🏥</div>
                                        <h2 style="font-size:28px;margin:10px 0 6px;">No Regular OPD Scheduled</h2>
                                        <p style="font-size:16px;color:#64748b;font-weight:700;">Availability slots are not configured for this day.</p>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="day">
                            <div style="height:100%;display:grid;place-items:center;text-align:center;">
                                <div>
                                    <div style="font-size:54px;">🏥</div>
                                    <h2 style="font-size:28px;margin:10px 0 6px;">No Regular OPD Scheduled</h2>
                                    <p style="font-size:16px;color:#64748b;font-weight:700;">Availability slots are not configured for this week.</p>
                                </div>
                            </div>
                        </div>
                    @endforelse
                </div>
            </div>

        </section>
    </div>

    <div class="patient-popup-backdrop"
         x-show="activePopup && activePopup.kind === 'patient'"
         x-transition.opacity.duration.400ms
         x-cloak
    >
        <div class="patient-popup-card"
             x-show="activePopup && activePopup.kind === 'patient'"
             x-transition.scale.90.duration.400ms
        >
            <div class="patient-popup-label" x-text="displayCopy.next_patient_label || 'NEXT PATIENT TURN'"></div>
            <div class="patient-popup-token" x-text="activePopup?.current_token || 'Pending'"></div>
            <h2 x-text="activePopup?.current_patient || 'No next patient'"></h2>
            <p x-text="activePopup?.room_number ? ('Please proceed to Room ' + activePopup.room_number) : 'Please proceed when called'"></p>
        </div>
    </div>
</div>
