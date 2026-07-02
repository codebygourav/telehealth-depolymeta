<div
    x-data="optTokenBoard()"
    x-init="initStatic()"
    class="h-screen max-h-screen flex flex-col overflow-hidden"
>
    <div class="topbar">
        <div class="brand">
           <img src="{{ asset('images/white-logo.png') }}" alt="logo" class="brand-logo">
        </div>
        <div class="clock">
            <div x-text="timeText"></div>
            <div x-text="dateText"></div>
        </div>
    </div>

    <div class="layout no-ads-layout">
        <aside class="panel doctor-panel">
            <x-display.queue-display.doctor-card
                class="fade-enter"
                :alpine="true"
                variant="split"
                :show-queue="true"
                :show-footer="true"
            />

            <div class="no-doctor-state fade-enter" x-show="!currentDoctor()" x-cloak>
                <div class="pill accent" style="width:max-content;" x-text="displayCopy.empty_state_title || 'No active doctor assigned'"></div>
                <h2 x-text="displayCopy.empty_state_title || 'No active doctor assigned'"></h2>
                <p x-text="displayCopy.empty_state_text || 'Please wait while we show the next available content.'"></p>
            </div>
        </aside>

        <section class="panel" style="padding: 18px; display: grid; gap: 14px; align-content: start;">
            <div class="mini-card" style="padding:18px;" x-show="currentDoctor()?.queue_summary?.next_token || currentDoctor()?.queue_summary?.next_patient">
                <div class="next-callout" style="margin:0;">
                    <div class="next-callout-label" x-text="displayCopy.next_patient_label || 'Next Patient'"></div>
                    <div class="next-callout-value">
                        <template x-if="currentDoctor()?.queue_summary?.next_token">
                            <span class="next-callout-token" x-text="currentDoctor()?.queue_summary?.next_token"></span>
                        </template>
                        <span x-text="currentDoctor()?.queue_summary?.next_patient || ''"></span>
                    </div>
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
