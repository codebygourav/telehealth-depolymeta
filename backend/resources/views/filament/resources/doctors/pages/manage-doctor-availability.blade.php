<x-filament-panels::page>
    @php
        $summary = $this->summary;
        $isGlobalManager = $this->isGlobalManager();
        $showDoctorContext = $this->isAllDoctorsManager();
        $dayLabels = \App\Enums\DayOfWeek::labels();
        $todayDayKey = $this->defaultDayKey();
        $todayDate = now()->toDateString();
        $filteringByDay = $this->isDayFilterApplied();
        $allDaysSelected = $this->allDaysSelected;
        $groupedRows = $this->rows->groupBy('date');
        $activeFilterCount = $this->activeFilterCount;
        $slotGridColumns = $showDoctorContext
            ? '32px minmax(70px, 1fr) minmax(60px, 1.1fr) minmax(66px, .5fr) minmax(90px, 0.5fr) minmax(100px, 0.6fr) minmax(70px, .75fr) minmax(150px, 0.9fr) minmax(210px, 1fr)'
            : '32px minmax(70px, 1fr) minmax(60px, 1.1fr) minmax(66px, .5fr) minmax(90px, 0.5fr) minmax(100px, 0.6fr) minmax(70px, .75fr) minmax(150px, 0.9fr) minmax(210px, 1fr)';
        $slotTableMinWidth = $showDoctorContext ? '1260px' : '1080px';
        $openDate = $groupedRows->has($todayDate) ? $todayDate : $groupedRows->keys()->first() ?? null;
    @endphp

    <style>
        .availability-shell {
            --av-primary: var(--app-primary-hex);
            --av-primary-hover: #052a1f;
            --av-border: #e2e8f0;
            --av-border-strong: #cbd5e1;
            --av-surface: #ffffff;
            --av-surface-muted: #f8fafc;
            --av-surface-subtle: #f1f5f9;
            --av-muted: #64748b;
            --av-text: #0f172a;
            --av-text-secondary: #475569;
            display: grid;
            gap: 16px;
        }

        .availability-kpis {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
        }

        .availability-kpi {
            background: var(--av-surface);
            border: 1px solid var(--av-border);
            border-radius: 12px;
            padding: 16px 18px;
            box-shadow: 0 1px 2px rgb(15 23 42 / 4%);
        }

        .availability-kpi__top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
        }

        .availability-kpi__dot {
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: var(--kpi-dot, var(--app-primary-hex));
            flex-shrink: 0;
        }

        .availability-kpi__label {
            color: var(--av-muted);
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.02em;
        }

        .availability-kpi__value {
            margin-top: 10px;
            color: var(--kpi-value-color, var(--av-text));
            font-size: 28px;
            line-height: 1;
            font-weight: 700;
            letter-spacing: -0.02em;
        }

        .availability-panel {
            background: var(--av-surface);
            border: 1px solid var(--av-border);
            border-radius: 12px;
            padding: 18px;
            box-shadow: 0 1px 2px rgb(15 23 42 / 4%);
        }

        .availability-toolbar {
            flex-wrap: wrap !important;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            border: 1px solid var(--av-border);
            border-radius: 12px;
            background: var(--av-surface);
            padding: 12px 14px;
            flex-direction: column;
        }

        .availability-controls {
            display: grid;
            gap: 14px;
            border-bottom: 1px solid var(--av-border);
            padding: 0;
            margin-bottom: 12px;
            padding-bottom: 12px;
        }

        .availability-controls__head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 14px;
        }

        .availability-controls__title {
            color: var(--av-text);
            font-size: 15px;
            font-weight: 760;
        }

        .availability-controls__hint {
            margin-top: 2px;
            color: var(--av-muted);
            font-size: 12px;
        }

        .availability-toolbar__meta {
            color: var(--av-muted);
            font-size: 12px;
            font-weight: 600;
        }

        .availability-tabs {
            display: flex;
            gap: 2px;
            border: 1px solid var(--av-border);
            border-radius: 10px;
            background: var(--av-surface-muted);
            padding: 3px;
            justify-content: center;
        }

        .availability-tab {
            border: 0;
            border-radius: 8px;
            background: transparent;
            color: var(--av-text-secondary);
            font-size: 13px;
            font-weight: 600;
            min-height: 36px;
            padding: 8px 16px;
            cursor: pointer;
            transition: color 0.15s ease, background 0.15s ease;
        }

        .availability-tab:hover:not(.is-active) {
            color: var(--av-text);
            background: rgb(255 255 255 / 70%);
        }

        .availability-tab.is-active {
            background: var(--app-primary-hex);
            color: #fff;
            box-shadow: 0 1px 3px rgb(7 56 39 / 25%);
        }

        .availability-toolbar-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            justify-content: end;
            padding-bottom: 16px;
        }

        .availability-filter-toggle {
            border: 1px solid var(--av-border-strong);
            border-radius: 10px;
            background: var(--av-surface);
            color: var(--av-text-secondary);
            font-size: 13px;
            font-weight: 600;
            min-height: 38px;
            padding: 8px 14px;
            cursor: pointer;
            transition: border-color 0.15s ease, color 0.15s ease;
        }

        .availability-filter-toggle.is-active,
        .availability-filter-toggle:hover {
            border-color: var(--app-primary-hex);
            color: var(--app-primary-hex);
        }

        .availability-panel__header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 16px;
        }

        .availability-panel__title {
            color: var(--av-text);
            font-size: 16px;
            font-weight: 730;
        }

        .availability-panel__hint {
            margin-top: 3px;
            color: var(--av-muted);
            font-size: 13px;
        }

        .availability-panel__actions {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        .availability-clear {
            border: 1px solid rgb(203 213 225);
            border-radius: 8px;
            background: #fff;
            color: rgb(51 65 85);
            font-size: 13px;
            font-weight: 720;
            min-height: 38px;
            padding: 8px 13px;
        }

        .availability-clear:hover {
            border-color: var(--app-primary-hex);
            color: var(--app-primary-hex);
            background: var(--av-primary-soft);
        }

        .availability-filters {
            display: grid;
            grid-template-columns: minmax(220px, 1.4fr) minmax(280px, 2fr) repeat(4, minmax(150px, 1fr));
            gap: 12px;
            align-items: end;
        }

        .availability-field {
            display: grid;
            gap: 6px;
        }

        .availability-field label,
        .availability-field__label {
            color: rgb(51 65 85);
            font-size: 12px;
            font-weight: 720;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .availability-input {
            width: 100%;
            min-height: 42px;
            border: 1px solid rgb(203 213 225);
            border-radius: 8px;
            background: #fff;
            color: var(--av-text);
            font-size: 14px;
            padding: 8px 11px;
            outline: none;
        }

        .availability-input:focus {
            border-color: var(--app-primary-hex);
            box-shadow: 0 0 0 3px var(--av-primary-ring);
        }

        .availability-days {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 14px;
        }

        .availability-day {
            border: 1px solid var(--av-border-strong);
            border-radius: 999px;
            background: var(--av-surface);
            color: var(--av-text-secondary);
            font-size: 13px;
            font-weight: 600;
            padding: 8px 14px;
            min-height: 36px;
            cursor: pointer;
            transition: border-color 0.15s ease, color 0.15s ease, background 0.15s ease;
        }

        .availability-day:hover:not(.is-active) {
            border-color: var(--av-muted);
            color: var(--av-text);
        }

        .availability-day.is-active {
            border-color: var(--app-primary-hex);
            background: var(--app-primary-hex);
            color: #fff;
        }

        .availability-day.is-today:not(.is-active) {
            border-color: var(--app-primary-hex);
            background: var(--av-surface);
            color: var(--app-primary-hex);
            box-shadow: inset 0 0 0 1px rgb(7 56 39 / 12%);
        }

        .availability-day-strip {
            background: var(--av-surface);
            border: 1px solid var(--av-border);
            border-radius: 12px;
            padding: 16px 18px;
        }

        .availability-settings-bar {
            display: flex;
            align-items: end;
            justify-content: space-between;
            gap: 14px;
            background: var(--av-surface);
            border: 1px solid var(--av-border);
            border-radius: 12px;
            padding: 14px 18px;
            box-shadow: 0 1px 2px rgb(15 23 42 / 4%);
        }

        .availability-settings-bar__copy {
            min-width: 220px;
        }

        .availability-settings-form {
            display: flex;
            align-items: end;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .availability-settings-form .availability-field {
            min-width: 160px;
        }

        .availability-save {
            border: 1px solid var(--app-primary-hex);
            border-radius: 8px;
            background: var(--app-primary-hex);
            color: #fff;
            font-size: 13px;
            font-weight: 720;
            min-height: 42px;
            padding: 8px 14px;
            cursor: pointer;
        }

        .availability-save:hover {
            background: var(--av-primary-hover);
        }

        .availability-day-controls {
            border-top: 1px solid var(--av-border);
            margin-top: 14px;
            padding-top: 14px;
        }

        .availability-day-strip__hint {
            margin-top: 10px;
            color: var(--av-muted);
            font-size: 12px;
            line-height: 1.5;
        }

        .fi-modal .fi-fo-toggle[aria-checked="true"] .fi-toggle,
        .fi-modal .fi-fo-toggle[aria-checked="true"] {
            background-color: var(--app-primary-hex) !important;
            border-color: var(--app-primary-hex) !important;
        }

        .fi-modal .fi-fo-toggle[aria-checked="false"] .fi-toggle,
        .fi-modal .fi-fo-toggle:not([aria-checked="true"]) {
            background-color: rgb(226 232 240) !important;
            border-color: rgb(203 213 225) !important;
        }

        .fi-modal .fi-section-header-heading {
            color: var(--av-text);
            font-weight: 700;
        }

        .fi-modal .fi-section:not(.fi-section-not-contained) {
            border: 1px solid var(--av-border);
            border-radius: 10px;
            background: rgb(248 250 252);
            padding: 4px;
        }

        .fi-modal-window.availability-slot-modal-window {
            max-height: min(94vh, 830px);
            display: flex;
            flex-direction: column;
            height:min(94vh, 830px);
        }

        .fi-modal-window.availability-slot-modal-window .fi-modal-header,
        .fi-modal-window.availability-slot-modal-window .fi-modal-footer {
            flex-shrink: 0;
        }

        .fi-modal-window.availability-slot-modal-window .fi-modal-content {
            overflow-y: auto;
            max-height: calc(min(82vh, 760px) - 0px);
            padding-right: 14px;
        }

        .fi-modal-window.availability-slot-modal-window .fi-modal-content::-webkit-scrollbar {
            width: 8px;
        }

        .fi-modal-window.availability-slot-modal-window .fi-modal-content::-webkit-scrollbar-thumb {
            background: rgb(203 213 225);
            border-radius: 999px;
        }

        .availability-table-wrap {
            overflow: hidden;
            background: var(--av-surface);
            border: 1px solid var(--av-border);
            border-radius: 12px;
            box-shadow: 0 1px 3px rgb(15 23 42 / 5%);
        }

        .availability-table-head {
            display: grid;
            gap: 12px;
            align-items: center;
            padding: 12px 20px;
            background: var(--av-surface-subtle);
            border-bottom: 1px solid var(--av-border);
            color: var(--av-muted);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            position: sticky;
            top: 0;
            z-index: 15;
            box-shadow: 0 1px 0 var(--av-border);
            backdrop-filter: blur(8px);
        }

        .availability-table-scroll {
            width: 100%;
            max-height: 72vh;
            overflow: auto;
            will-change: scroll-position;
            -webkit-overflow-scrolling: touch;
        }

        .availability-date-card {
            border-bottom: 1px solid var(--av-border);
            contain: content;
            will-change: auto;
        }

        .availability-date-card:last-child {
            border-bottom: 0;
        }

        .availability-date-card summary {
            list-style: none;
            cursor: pointer;
            display: block;
            will-change: background-color, border-color;
        }

        .availability-date-summary-wrapper {
            list-style: none;
            cursor: pointer;
            display: block;
            will-change: none;
        }

        .availability-date-card summary::-webkit-details-marker {
            display: none;
        }

        .availability-date-summary-wrapper::-webkit-details-marker {
            display: none;
        }

        .availability-date-summary {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 14px 20px;
            background: var(--av-surface);
            border-left: 3px solid transparent;
            transition: background 0.12s ease, border-color 0.12s ease;
            user-select: none;
        }

        .availability-date-card[open] .availability-date-summary {
            background: var(--av-surface-muted);
            border-left-color: var(--app-primary-hex);
        }

        .availability-date-card.is-today:not([open]) .availability-date-summary {
            border-left-color: rgb(7 56 39 / 35%);
        }

        .availability-date-left {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .availability-expand-btn {
            display: grid;
            place-items: center;
            width: 32px;
            height: 32px;
            border-radius: 10px;
            flex-shrink: 0;
            transition: background 0.12s ease, border-color 0.12s ease, color 0.12s ease;
            will-change: transform;
        }

        .availability-expand-btn {
            border: 1px solid var(--av-border-strong);
            background: var(--av-surface);
            color: var(--av-text-secondary);
        }

        .availability-date-card[open] .availability-expand-btn {
            border-color: var(--app-primary-hex);
            background: var(--app-primary-hex);
            color: #fff;
        }

        .availability-expand-btn svg {
            width: 16px;
            height: 16px;
            transition: transform 0.15s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .availability-date-card[open] .availability-expand-btn svg {
            transform: rotate(90deg);
        }

        .availability-date-group {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
        }

        .availability-date-title {
            display: flex;
            align-items: baseline;
            gap: 10px;
            color: var(--av-text);
            font-size: 15px;
            font-weight: 760;
        }

        .availability-date-count {
            color: var(--av-text-secondary);
            background: var(--av-surface-subtle);
            border: 1px solid var(--av-border);
            border-radius: 8px;
            padding: 5px 10px;
            font-size: 11px;
            font-weight: 600;
        }

        .availability-date-card.is-today .availability-date-count {
            color: var(--app-primary-hex);
            background: rgb(7 56 39 / 6%);
            border-color: rgb(7 56 39 / 15%);
        }

        .availability-slot-row {
            display: grid;
            gap: 12px;
            align-items: center;
            padding: 14px 20px;
            border-top: 1px solid var(--av-border);
            background: var(--av-surface);
            will-change: auto;
            contain: layout style paint;
        }

        .availability-slot-row:nth-child(even) {
            background: rgb(248 250 252 / 45%);
        }

        .availability-slot-row:hover {
            background: var(--av-surface-muted);
            transition: background 0.1s ease;
        }

        .availability-main {
            color: var(--av-text);
            font-size: 13px;
            font-weight: 720;
        }

        .availability-sub {
            color: var(--av-muted);
            font-size: 11px;
            margin-top: 2px;
        }

        .availability-badge {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 720;
            line-height: 1;
            padding: 6px 9px;
            white-space: nowrap;
        }

        .availability-badge--source {
            background: var(--av-surface-subtle);
            color: var(--av-text-secondary);
            border: 1px solid var(--av-border);
        }

        .availability-badge--override {
            background: #eff6ff;
            color: #1d4ed8;
            border: 1px solid #bfdbfe;
        }

        .availability-badge--active {
            background: rgb(7 56 39 / 8%);
            color: var(--app-primary-hex);
            border: 1px solid rgb(7 56 39 / 18%);
        }

        .availability-badge--blocked {
            background: rgb(254 243 199);
            color: rgb(180 83 9);
        }

        .availability-note {
            display: inline-flex;
            align-items: center;
            margin-left: 8px;
            margin-top: 4px;
            padding: 3px 7px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 700;
            line-height: 1;
            color: rgb(120 53 15);
            background: rgb(254 243 199);
            border: 1px solid rgb(251 191 36 / 0.4);
        }

        .availability-badge--deleted {
            background: rgb(254 226 226);
            color: rgb(185 28 28);
        }

        .availability-actions {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            flex-wrap: wrap;
            gap: 6px;
        }

        .availability-bulkbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 14px 20px;
            border-bottom: 1px solid var(--av-border);
            background: var(--av-surface-muted);
        }

        .availability-bulkbar__meta {
            color: rgb(51 65 85);
            font-size: 13px;
            font-weight: 720;
        }

        .availability-bulkbar__actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .availability-bulk-menu {
            position: relative;
        }

        .availability-bulk-menu summary {
            list-style: none;
        }

        .availability-bulk-menu summary::-webkit-details-marker {
            display: none;
        }

        .availability-bulk-trigger {
            border: 1px solid var(--av-border-strong);
            border-radius: 10px;
            background: var(--av-surface);
            color: var(--av-text);
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            min-height: 38px;
            padding: 8px 14px;
            transition: border-color 0.15s ease, background 0.15s ease;
        }

        .availability-bulk-trigger:hover {
            border-color: var(--app-primary-hex);
            color: var(--app-primary-hex);
        }

        .availability-filter-chip {
            color: var(--av-text-secondary);
            background: var(--av-surface-subtle);
            border: 1px solid var(--av-border);
            border-radius: 8px;
            padding: 5px 10px;
            font-size: 12px;
            font-weight: 600;
        }

        .availability-bulk-dropdown {
            position: absolute;
            right: 0;
            z-index: 20;
            display: grid;
            gap: 4px;
            width: 190px;
            margin-top: 8px;
            padding: 8px;
            border: 1px solid var(--av-border);
            border-radius: 8px;
            background: #fff;
            box-shadow: 0 18px 40px rgb(15 23 42 / 14%);
        }

        .availability-bulk-dropdown .availability-action {
            width: 100%;
            text-align: left;
        }

        .availability-check {
            width: 16px;
            height: 16px;
            border-radius: 4px;
            accent-color: var(--app-primary-hex);
        }

        .availability-slot-row.is-blocked {
            background: rgb(255 251 235 / 55%);
        }

        .availability-slot-row.is-blocked:hover {
            background: rgb(255 251 235 / 90%);
        }

        .availability-slot-row.is-deleted {
            background: rgb(254 242 242 / 70%);
        }

        .availability-slot-row.is-deleted:hover {
            background: rgb(254 242 242);
        }

        .availability-slot-row.is-deleted .availability-main,
        .availability-slot-row.is-deleted .availability-sub {
            opacity: .72;
        }

        .availability-action {
            border: 1px solid rgb(203 213 225);
            border-radius: 7px;
            background: #fff;
            color: rgb(51 65 85);
            font-size: 12px;
            font-weight: 720;
            padding: 5px 8px;
        }

        .availability-action:hover {
            border-color: var(--app-primary-hex);
            background: var(--av-primary-soft);
            color: var(--app-primary-hex);
        }

        .availability-action:disabled {
            cursor: not-allowed;
            opacity: .45;
        }

        .availability-action--warning {
            border-color: rgb(252 211 77);
            color: rgb(180 83 9);
        }

        .availability-action--danger {
            border-color: rgb(253 164 175);
            color: rgb(225 29 72);
        }

        .availability-action--success {
            border-color:rgba(5, 90, 217, 0.28);
            background: rgb(7 56 39 / 6%);
            color: var(--app-primary-hex);
        }

        .availability-action--success:hover {
            border-color: var(--app-primary-hex);
            background: var(--app-primary-hex);
            color: #fff;
        }

        .availability-action--primary:hover {
            border-color: var(--app-primary-hex);
            color: var(--app-primary-hex);
        }

        .availability-empty {
            padding: 46px 18px;
            text-align: center;
            color: var(--av-muted);
        }

        @media (max-width: 1100px) {
            .availability-kpis {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .availability-filters {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 700px) {

            .availability-kpis,
            .availability-filters {
                grid-template-columns: 1fr;
            }

            .availability-panel__header {
                display: grid;
            }

            .availability-bulkbar {
                align-items: flex-start;
                flex-direction: column;
            }

            .availability-settings-bar {
                align-items: stretch;
                flex-direction: column;
            }

            .availability-settings-form {
                justify-content: stretch;
            }

            .availability-save {
                width: 100%;
            }

            .availability-toolbar {
                align-items: stretch;
                flex-direction: column;
            }
        }
    </style>

    <div class="availability-shell">
        <div class="availability-kpis">
            <div class="availability-kpi" style="--kpi-dot: var(--app-primary-hex)">
                <div class="availability-kpi__top">
                    <span class="availability-kpi__label">Total slots</span>
                    <span class="availability-kpi__dot"></span>
                </div>
                <div class="availability-kpi__value">{{ $summary['total'] }}</div>
            </div>
            <div class="availability-kpi" style="--kpi-dot: var(--app-primary-hex); --kpi-value-color: var(--app-primary-hex)">
                <div class="availability-kpi__top">
                    <span class="availability-kpi__label">Available</span>
                    <span class="availability-kpi__dot"></span>
                </div>
                <div class="availability-kpi__value">{{ $summary['available'] }}</div>
            </div>
            <div class="availability-kpi" style="--kpi-dot: #d97706; --kpi-value-color: #b45309">
                <div class="availability-kpi__top">
                    <span class="availability-kpi__label">Blocked</span>
                    <span class="availability-kpi__dot"></span>
                </div>
                <div class="availability-kpi__value">{{ $summary['blocked'] + ($summary['deleted'] ?? 0) }}</div>
            </div>
            <div class="availability-kpi" style="--kpi-dot: #2563eb; --kpi-value-color: #1d4ed8">
                <div class="availability-kpi__top">
                    <span class="availability-kpi__label">Modified</span>
                    <span class="availability-kpi__dot"></span>
                </div>
                <div class="availability-kpi__value">{{ $summary['modified'] }}</div>
            </div>
        </div>

        <div class="availability-toolbar">
            <div class="availability-tabs">
                <button type="button" wire:click="setSlotView('upcoming')"
                    class="availability-tab {{ $this->slotView === 'upcoming' ? 'is-active' : '' }}">
                    Upcoming
                </button>
                <button type="button" wire:click="setSlotView('passed')"
                    class="availability-tab {{ $this->slotView === 'passed' ? 'is-active' : '' }}">
                    Passed
                </button>
            </div>

            <div class="availability-toolbar__meta">
                {{ \Carbon\Carbon::parse($this->dateFrom)->format('d M Y') }} -
                {{ \Carbon\Carbon::parse($this->dateTo)->format('d M Y') }}
            </div>
        </div>


        <div class="availability-settings-bar">
            <div class="availability-settings-bar__copy">
                <div class="availability-controls__title">Child OPD Settings</div>
                <div class="availability-controls__hint">
                    One age limit applies to every slot where Child only is enabled.
                </div>
            </div>
            <form wire:submit.prevent="saveGlobalChildAge" class="availability-settings-form">
                <div class="availability-field">
                    <label for="availability-global-child-age">Child age</label>
                    <input id="availability-global-child-age" type="number" min="1" max="18"
                        wire:model="childAge" class="availability-input" />
                </div>
                <button type="submit" class="availability-save">Save Age</button>
            </form>
        </div>


        <div class="availability-day-strip">
            <div class="availability-controls">
                <div class="availability-toolbar-actions">
                    @if ($activeFilterCount > 0)
                        <span class="availability-filter-chip">{{ $activeFilterCount }}
                            filter{{ $activeFilterCount === 1 ? '' : 's' }}</span>
                    @endif
                    @if (!$isGlobalManager)
                        <button type="button" wire:click="toggleFilters"
                            class="availability-filter-toggle {{ $showFilters ? 'is-active' : '' }}">
                            Filters
                        </button>
                    @endif
                    @if ($activeFilterCount > 0)
                        <button type="button" wire:click="clearFilters" class="availability-clear">Clear</button>
                    @endif
                </div>
                @if ($isGlobalManager || $showFilters)
                    <div class="availability-controls__head">
                        <div>
                            <div class="availability-controls__title">Schedule Filters</div>
                            <div class="availability-controls__hint">
                                Filter by doctor, slot, date range, status, and schedule type.
                            </div>
                        </div>
                        <div class="availability-panel__actions">
                            <button type="button" wire:click="clearFilters" class="availability-clear">Clear
                                Filters</button>
                        </div>
                    </div>

                    <div class="availability-filters">
                        @if ($showDoctorContext)
                            <div class="availability-field">
                                <label for="availability-doctor-filter">Doctor</label>
                                <select id="availability-doctor-filter" wire:model.live="doctorFilter"
                                    class="availability-input">
                                    <option value="">All doctors</option>
                                    @foreach ($this->doctorOptions as $id => $label)
                                        <option value="{{ $id }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <div class="availability-field">
                            <label for="availability-slot-filter">Slot</label>
                            <select id="availability-slot-filter" wire:model.live="availabilityFilter"
                                class="availability-input">
                                <option value="">All slots</option>
                                @foreach ($this->availabilityOptions as $id => $label)
                                    <option value="{{ $id }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="availability-field">
                            <label for="availability-from-filter">From</label>
                            <input id="availability-from-filter" type="date" wire:model.live="dateFrom"
                                class="availability-input" />
                        </div>

                        <div class="availability-field">
                            <label for="availability-to-filter">To</label>
                            <input id="availability-to-filter" type="date" wire:model.live="dateTo"
                                class="availability-input" />
                        </div>

                        <div class="availability-field">
                            <label for="availability-status-filter">Status</label>
                            <select id="availability-status-filter" wire:model.live="statusFilter"
                                class="availability-input">
                                <option value="all">All statuses</option>
                                <option value="available">Available</option>
                                <option value="blocked">Blocked</option>
                                <option value="deleted">Deleted</option>
                                <option value="modified">Modified</option>
                            </select>
                        </div>

                        <div class="availability-field">
                            <label for="availability-schedule-type-filter">Schedule Type</label>
                            <select id="availability-schedule-type-filter" wire:model.live="scheduleTypeFilter"
                                class="availability-input">
                                <option value="all">All types</option>
                                <option value="recurring">Recurring only</option>
                                <option value="one-time">One-time only</option>
                            </select>
                        </div>
                    </div>

                @endif
            </div>
            <div class="availability-day-controls">
                <div class="availability-controls__head">
                    <div>
                        <div class="availability-controls__title">Day Filter</div>
                        <div class="availability-controls__hint">Choose a weekday or show all days in the selected date range.</div>
                    </div>
                </div>
                <div class="availability-days" style="margin-top: 8px">
                    <button type="button" wire:click="filterByDay()"
                        class="availability-day {{ $allDaysSelected ? 'is-active' : '' }}">
                        All days
                    </button>
                    @foreach ($dayLabels as $value => $label)
                        @php
                            $isActive = $filteringByDay
                                ? $this->dayFilter === $value
                                : !$allDaysSelected && $todayDayKey === $value;
                            $isToday = $todayDayKey === $value;
                        @endphp
                        <button type="button" wire:click="filterByDay('{{ $value }}')"
                            @class([
                                'availability-day',
                                'is-active' => $isActive,
                                'is-today' => $isToday && !$isActive,
                            ])>
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
                <div class="availability-day-strip__hint">
                    @if ($filteringByDay)
                        Showing {{ $dayLabels[$this->dayFilter] ?? ucfirst($this->dayFilter) }} slots only.
                    @elseif ($allDaysSelected)
                        Showing all days in the selected date range.
                    @else
                        Showing the selected date range.
                        <strong>{{ $dayLabels[$todayDayKey] ?? ucfirst($todayDayKey) }}</strong> is highlighted as today.
                    @endif
                </div>
            </div>
        </div>

        <div class="availability-table-wrap">
            <div class="availability-bulkbar">
                <div class="availability-bulkbar__meta">
                    {{ count($selectedRows) }} selected
                </div>
                <div class="availability-bulkbar__actions" style="display: flex; gap: 8px;">
                    <button type="button" wire:click="toggleAllDates" class="availability-bulk-trigger" title="{{ $this->allDatesExpanded ? 'Collapse all date groups' : 'Expand all date groups' }}">
                        @if ($this->allDatesExpanded)
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width: 16px; height: 16px; display: inline; margin-right: 4px;">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 12H6" />
                            </svg>
                            Collapse All
                        @else
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width: 16px; height: 16px; display: inline; margin-right: 4px;">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v12m6-6H6" />
                            </svg>
                            Expand All
                        @endif
                    </button>
                    <button type="button" wire:click="mountAction('editParentSeries')" class="availability-bulk-trigger">
                        Edit Series
                    </button>
                    <details class="availability-bulk-menu">
                        <summary class="availability-bulk-trigger">Bulk Actions</summary>
                        <div class="availability-bulk-dropdown">
                            <button type="button" wire:click="selectVisibleRows" class="availability-action">Select
                                Visible</button>
                            <button type="button" wire:click="clearSelection" class="availability-action">Clear
                                Selection</button>
                            <button type="button" wire:click="mountAction('bulkEditSelected')"
                                class="availability-action" @disabled(count($selectedRows) === 0)>Edit Selected</button>
                            <button type="button" wire:click="bulkBlockSelected"
                                wire:confirm="Block all selected dates?"
                                class="availability-action availability-action--warning"
                                @disabled(count($selectedRows) === 0)>Block Selected</button>
                            <button type="button" wire:click="bulkDeleteSelected"
                                wire:confirm="Delete all selected dates? Recurring slots will only remove the selected dates."
                                class="availability-action availability-action--danger"
                                @disabled(count($selectedRows) === 0)>Delete Selected</button>
                        </div>
                    </details>
                </div>
            </div>
            <div class="availability-table-scroll">
                <div class="availability-table-head"
                    style="grid-template-columns: {{ $slotGridColumns }}; min-width: {{ $slotTableMinWidth }};">
                    <div></div>
                    @if ($showDoctorContext)
                        <div>Doctor</div>
                    @endif
                    <div>Time</div>
                    <div>Capacity</div>
                    <div>Booked</div>
                    <div>Type</div>
                    <div>Close Time</div>
                    <div>Status</div>
                    <div style="text-align: right">Actions</div>
                </div>

                @forelse ($groupedRows as $date => $dateRows)
                    @php
                        $dateTotalCapacity = $dateRows->sum('capacity');
                        $dateTotalInternal = $dateRows->sum('internal_booked');
                        $dateTotalExternal = $dateRows->sum('external_booked');
                        $dateTotalBooked = $dateRows->sum('total_booked');
                    @endphp
                    <details class="availability-date-card {{ $date === $todayDate ? 'is-today' : '' }}"
                        wire:key="availability-date-{{ $date }}"
                        data-date="{{ $date }}"
                        style="min-width: {{ $slotTableMinWidth }};"
                        @if ($this->allDatesExpanded || in_array($date, $this->openDates) || (empty($this->openDates) && $date === $openDate)) open @endif>
                        <summary class="availability-date-summary-wrapper">
                            <div class="availability-date-summary">
                                <div class="availability-date-left">
                                    <span class="availability-expand-btn" aria-hidden="true">
                                        <svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M7.5 5l5 5-5 5" stroke="currentColor" stroke-width="1.75"
                                                stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </span>
                                    <input type="checkbox" class="availability-check" style="margin-right: 8px;"
                                        wire:click="toggleSelectDay('{{ $date }}')"
                                        @php
                                            $daysRowKeys = $dateRows->pluck('row_key')->toArray();
                                            $daysSelectedCount = count(array_intersect($this->selectedRows, $daysRowKeys));
                                            $allDaySelected = $daysSelectedCount === count($daysRowKeys) && count($daysRowKeys) > 0;
                                        @endphp
                                        @if($allDaySelected) checked @endif
                                        title="Select all slots for this day" />
                                    <div class="availability-date-title"
                                        style="display: flex; align-items: center; gap: 10px;">
                                        <span>{{ \Carbon\Carbon::parse($date)->format('d M Y') }}</span>
                                        <span class="availability-sub 11"
                                            style="font-size: 13px; margin-top: 0;">{{ \Carbon\Carbon::parse($date)->format('l') }}</span>

                                        <div style="display: flex; align-items: center; gap: 8px; margin-left: 16px;">
                                            <span
                                                style="font-size: 11px; font-weight: 600; padding: 3px 8px; background: var(--av-surface-muted); border: 1px solid var(--av-border-strong); border-radius: 6px; color: var(--av-text-secondary);">
                                                Capacity: <strong>{{ $dateTotalCapacity }}</strong>
                                            </span>
                                            <span
                                                style="font-size: 11px; font-weight: 600; padding: 3px 8px; background: {{ $dateTotalBooked > 0 ? '#eff6ff' : 'var(--av-surface-muted)' }}; border: 1px solid {{ $dateTotalBooked > 0 ? '#bfdbfe' : 'var(--av-border)' }}; border-radius: 6px; color: {{ $dateTotalBooked > 0 ? '#1e40af' : 'var(--av-muted)' }};">
                                                Booked: <strong>{{ $dateTotalBooked }}</strong>
                                                @if ($dateTotalBooked > 0)
                                                    <span style="font-weight: normal; font-size: 10px; opacity: 0.85;">
                                                        ({{ $dateTotalInternal }} Online / {{ $dateTotalExternal }}
                                                        external)
                                                    </span>
                                                @endif
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <button type="button"
                                        wire:click.stop="mountAction('createAvailability', { date: '{{ $date }}' })"
                                        class="availability-action availability-action--success iconbtn"
                                        data-tooltip="Add Slot" aria-label="Add Slot"
                                        style="padding: 4px 8px; min-height: unset; border-radius: 8px; display: inline-flex; align-items: center;">
                                        <x-heroicon-o-plus class="w-5 h-5" />
                                    </button>
                                    <div class="availability-date-count">{{ $dateRows->count() }}
                                        slot{{ $dateRows->count() === 1 ? '' : 's' }}</div>
                                </div>
                            </div>
                        </summary>

                        @foreach ($dateRows as $row)
                            <div style="grid-template-columns: {{ $slotGridColumns }}; min-width: {{ $slotTableMinWidth }};"
                                wire:key="availability-row-{{ $row['row_key'] }}-{{ $this->availabilityRefreshVersion }}"
                                @class([
                                    'availability-slot-row',
                                    'is-deleted' => $row['status'] === 'cancelled',
                                    'is-blocked' => $row['status'] === 'blocked',
                                ])>
                                <div>
                                    <input type="checkbox" wire:model.live="selectedRows"
                                        value="{{ $row['row_key'] }}" class="availability-check" />
                                </div>
                                @if ($showDoctorContext)
                                    <div>
                                        <div class="availability-main">{{ $row['doctor_name'] }}</div>
                                        @if ($row['doctor_profile_url'])
                                            <a href="{{ $row['doctor_profile_url'] }}" class="availability-sub 12"
                                                style="color: var(--app-primary-hex); font-weight: 700; text-decoration: none;">Profile</a>
                                        @endif
                                    </div>

                                @endif
                                <div>
                                    <div class="availability-main">
                                        {{ \Carbon\Carbon::parse($row['start_time'])->format('h:i A') }} -
                                        {{ \Carbon\Carbon::parse($row['end_time'])->format('h:i A') }}
                                    </div>

                                    <div class="availability-sub 13">
                                        {{ $row['is_recurring'] ? 'Recurring' : 'One-time' }} | {{ $row['label'] }}
                                        @if ($row['is_ending_soon'])
                                            <span class="availability-badge availability-badge--deleted" style="margin-left: 8px; font-size: 10px; padding: 3px 6px;">
                                                Ends soon: {{ $row['ending_soon_date'] }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                <div>
                                    <div class="availability-main">{{ $row['capacity'] }}</div>
                                    <div class="availability-sub">Base {{ $row['base_capacity'] }}</div>
                                </div>
                                <div>
                                    <div class="availability-main" style="font-weight: 600;">
                                        {{ $row['total_booked'] }}
                                    </div>
                                    <div class="availability-sub" style="font-size: 11px;">
                                        {{ $row['total_booked'] > 1 ? 'booked' : ($row['total_booked'] === 1 ? 'booked' : '0 booked') }}
                                    </div>
                                </div>
                                <div>
                                    @php
                                        $consultationType = trim(strtolower($row['consultation_type'] ?? 'in-person'));
                                        $consultationType = str_contains($consultationType, 'video') ? 'video' : 'in-person';
                                        $opdType = trim((string) ($row['opd_type'] ?? ''));
                                        $opdType = $opdType !== '' ? ucfirst($opdType) : 'General';
                                        if ($consultationType === 'video') {
                                            $typeLabel = 'Video';
                                        } else {
                                            $typeLabel = 'In person' . ($opdType !== 'General' ? ' / ' . $opdType : '');
                                        }
                                    @endphp
                                    <div class="availability-main">
                                        <span>{{ $typeLabel }}</span>
                                    </div>
                                    <div class="availability-sub 14">
                                        {{ $row['room'] ?: ($row['opd_type'] ?: 'No room') }}
                                        @if ($row['is_child_only'])
                                            | Child only{{ $row['child_age'] ? ' up to ' . $row['child_age'] . ' years' : '' }}
                                        @endif
                                    </div>
                                </div>
                                <div>
                                    <span
                                        class="availability-badge {{ $row['source'] === 'override' ? 'availability-badge--override' : 'availability-badge--source' }}">
                                        {{ ucfirst($row['source']) }}
                                    </span>
                                    <div class="availability-sub 15">{{ $row['booking_cutoff_label'] }}</div>
                                </div>
                                <div>
                                    @if ($row['status'] === 'active')
                                        <span class="availability-badge availability-badge--active">Available</span>
                                    @elseif ($row['status'] === 'cancelled')
                                        <span class="availability-badge availability-badge--deleted">Deleted</span>
                                    @else
                                        <span class="availability-badge availability-badge--blocked">Blocked</span>
                                    @endif

                                    @if (! empty($row['blocked_parent']))
                                        <span class="availability-note">Parent blocked. Use Edit Series.</span>
                                    @endif
                                </div>
                                <div>
                                    <div class="availability-actions">
                                        @if ($row['status'] === 'cancelled')
                                            <button type="button"
                                                wire:click="mountAction('restoreOccurrence', { availability: '{{ $row['availability_id'] }}', date: '{{ $row['date'] }}' })"
                                                class="availability-action availability-action--success iconbtn"
                                                data-tooltip="Want to Restore?" aria-label="Restore">
                                                <x-heroicon-o-arrow-uturn-left class="w-5 h-5" />
                                            </button>
                                            <button type="button"
                                                wire:click="mountAction('editOccurrence', { availability: '{{ $row['availability_id'] }}', date: '{{ $row['date'] }}' })"
                                                class="availability-action availability-action--primary iconbtn"
                                                data-tooltip="Edit" aria-label="Edit">
                                                <x-heroicon-o-pencil-square class="w-5 h-5" />
                                            </button>
                                        @else
                                            <button type="button"
                                                wire:click="mountAction('editOccurrence', { availability: '{{ $row['availability_id'] }}', date: '{{ $row['date'] }}' })"
                                                class="availability-action availability-action--primary iconbtn"
                                                data-tooltip="Edit" aria-label="Edit">
                                                <x-heroicon-o-pencil-square class="w-5 h-5" />
                                            </button>
                                            @if ($row['is_recurring'])
                                                <button type="button"
                                                    wire:click="mountAction('editParentAvailability', { availability: '{{ $row['availability_id'] }}', date: '{{ $row['date'] }}' })"
                                                    class="availability-action availability-action--success iconbtn"
                                                    data-tooltip="Edit Weekly Series" aria-label="Edit Weekly Series">
                                                    <x-heroicon-o-cog-6-tooth class="w-5 h-5" />
                                                </button>
                                            @endif
                                            @if ($row['is_ending_soon'])
                                                <button type="button"
                                                    wire:click="mountAction('extendSeries', { availability: '{{ $row['availability_id'] }}' })"
                                                    class="availability-action availability-action--primary iconbtn"
                                                    data-tooltip="Extend Recurrence" aria-label="Extend Recurrence"
                                                    style="border-color: #2563eb; color: #2563eb;">
                                                    <x-heroicon-o-arrow-path class="w-5 h-5" />
                                                </button>
                                            @endif
                                            @if ($row['status'] === 'active')
                                                <button type="button"
                                                    wire:click="mountAction('blockOccurrence', { availability: '{{ $row['availability_id'] }}', date: '{{ $row['date'] }}' })"
                                                    class="availability-action availability-action--warning iconbtn"
                                                    data-tooltip="Want to Block?" aria-label="Block">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5"
                                                        viewBox="0 0 20 20" fill="none" stroke="currentColor">
                                                        <circle cx="10" cy="10" r="7"
                                                            stroke="currentColor" stroke-width="2" />
                                                        <line x1="7" y1="7" x2="13"
                                                            y2="13" stroke="currentColor" stroke-width="2"
                                                            stroke-linecap="round" />
                                                    </svg>
                                                </button>
                                            @elseif ($row['status'] === 'blocked')
                                                <button type="button"
                                                    wire:click="mountAction('unblockOccurrence', { availability: '{{ $row['availability_id'] }}', date: '{{ $row['date'] }}' })"
                                                    class="availability-action availability-action--success iconbtn"
                                                    data-tooltip="Want to Unblock?" aria-label="Unblock">
                                                    <x-heroicon-o-check-circle class="w-5 h-5" />
                                                </button>
                                            @endif
                                            @if ($row['source'] === 'override')
                                                <button type="button"
                                                    wire:click="mountAction('resetOccurrence', { availability: '{{ $row['availability_id'] }}', date: '{{ $row['date'] }}' })"
                                                    class="availability-action iconbtn" data-tooltip="Reset"
                                                    aria-label="Want to Reset?">
                                                    <x-heroicon-o-arrow-path class="w-5 h-5" />
                                                </button>
                                            @endif
                                            <button type="button"
                                                wire:click="mountAction('deleteOccurrence', { availability: '{{ $row['availability_id'] }}', date: '{{ $row['date'] }}' })"
                                                class="availability-action availability-action--danger iconbtn"
                                                data-tooltip="Delete" aria-label="Delete">
                                                <x-heroicon-o-trash class="w-5 h-5" />
                                            </button>
                                        @endif
                                    </div>
                                    <style>
                                        .iconbtn {
                                            position: relative;
                                        }

                                        .iconbtn .w-5,
                                        .iconbtn .h-5,
                                        .iconbtn svg {
                                            width: 16px;
                                            height: 16px;
                                            vertical-align: middle;
                                            display: inline-block;
                                        }

                                        .iconbtn[data-tooltip]:hover:after,
                                        .iconbtn[data-tooltip]:focus:after {
                                            content: attr(data-tooltip);
                                            position: absolute;
                                            top: 110%;
                                            left: 50%;
                                            transform: translateX(-50%);
                                            background: #222f3e;
                                            color: #fff;
                                            padding: 7px 14px;
                                            font-size: 10px;
                                            border-radius: 7px;
                                            box-shadow: 0 2px 8px rgba(31, 38, 135, 0.08);
                                            white-space: nowrap;
                                            z-index: 500;
                                            font-weight: 700;
                                            opacity: 1;
                                            z-index: 999999;
                                            pointer-events: none;
                                            transition: opacity 0.08s;
                                        }

                                        .iconbtn[data-tooltip]:after {
                                            content: '';
                                            opacity: 0;
                                        }
                                    </style>

                                </div>
                            </div>
                        @endforeach
                    </details>
                @empty
                    <div class="availability-empty">No availability dates found for the selected filters.</div>
                @endforelse
            </div>
        </div>
    </div>

</x-filament-panels::page>

