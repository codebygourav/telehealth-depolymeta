<div class="left-column-topbar">
    <div style="width:100%; display: flex; align-items: center; gap: clamp(8px, 0.8vw, 16px); font-weight: 800; ">
        <button
            type="button"
            class="control-button icon-control"
            @click="announceCurrentPatient()"
            :disabled="!patientPopupEnabled()"
            :style="!patientPopupEnabled() ? 'opacity: 0.4; cursor: not-allowed; pointer-events: none;' : ''"
            aria-label="Announce patient"
            title="Announce patient"
        >
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M11 5L6 9H3v6h3l5 4V5Z" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M16.5 8.5a5 5 0 010 7" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
                <path d="M19 6a9 9 0 010 12" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
            </svg>
        </button>
        <div style="display:flex;align-items:center;gap:12px;justify-content:space-between;width:100%;">
            <div style="display:flex;justify-content:space-between; align-items: center;gap:5px;">
                <svg style="width: 22px; height: 22px; opacity: 0.9;" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span x-text="timeText"></span>
            </div>

            <span style="opacity: 0.4; font-weight: 300;">|</span>
            <span x-text="dateText" style="text-transform: uppercase; opacity: 0.9;"></span>
        </div>
    </div>
</div>
