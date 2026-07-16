@php
$primaryColor = primary_color();
$secondaryColor = secondary_color();
// Create a lighter version (12% opacity) for bg-primary-100
$primaryColor12 = $primaryColor . '1f'; // 1f is ~12% in hex
@endphp
<style>
      :root {
        --app-primary-hex: {{ $primaryColor }};
        --app-secondary-hex: {{ $secondaryColor }};
        --app-primary-hex-12: {{ $primaryColor }}1f;
    }
    /* Force sidebar transition with smoother easing */
    .fi-sidebar {
        transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        overflow-x: hidden;
        /* Prevent horizontal scroll/pop-out during transition */
        will-change: width;
    }

    /* Prevent text wrap during transition */
    .fi-sidebar-item-label,
    .fi-sidebar-group-label {
        white-space: nowrap;
    }

    @media (min-width: 1024px) {

        /* Default State (Collapsed) */
        .fi-body-has-sidebar-collapsible-on-desktop .fi-sidebar {
            width: 4rem !important;
            padding-inline: 0 !important;
        }

        /* Open State (Expanded) */
        .fi-body-has-sidebar-collapsible-on-desktop .fi-sidebar.fi-sidebar-open {
            width: 17rem !important;
        }

        /* Hide Text when Collapsed - Immediate hide to prevent layout thrashing */
        .fi-body-has-sidebar-collapsible-on-desktop .fi-sidebar:not(.fi-sidebar-open) .fi-sidebar-item-label,
        .fi-body-has-sidebar-collapsible-on-desktop .fi-sidebar:not(.fi-sidebar-open) .fi-sidebar-group-label,
        .fi-body-has-sidebar-collapsible-on-desktop .fi-sidebar:not(.fi-sidebar-open) .fi-sidebar-group-collapse-button {
            display: none !important;
        }

        /* Hide Group Headers (Label + Arrow) completely when collapsed */
        .fi-body-has-sidebar-collapsible-on-desktop .fi-sidebar:not(.fi-sidebar-open) .fi-sidebar-group-btn {
            display: none !important;
        }

        /* Remove padding/margin from group lists to flatten visuals */
        .fi-body-has-sidebar-collapsible-on-desktop .fi-sidebar:not(.fi-sidebar-open) .fi-sidebar-group-items {
            padding-inline: 0 !important;
            margin-block: 0 !important;
        }

        /* Hide Sub-Navigation items when collapsed */
        .fi-body-has-sidebar-collapsible-on-desktop .fi-sidebar:not(.fi-sidebar-open) .fi-sidebar-sub-group-items,
        .fi-body-has-sidebar-collapsible-on-desktop .fi-sidebar:not(.fi-sidebar-open) .fi-sidebar-group-items .fi-sidebar-group-items {
            display: none !important;
        }

        /* Ensure item content (icon+text wrapper) is flexible and centered */
        .fi-body-has-sidebar-collapsible-on-desktop .fi-sidebar:not(.fi-sidebar-open) .fi-sidebar-item-button {
            justify-content: center;
            padding-inline: 0.5rem;
        }

        /* Center Icons when Collapsed */
        .fi-body-has-sidebar-collapsible-on-desktop .fi-sidebar:not(.fi-sidebar-open) .fi-sidebar-item-btn {
            justify-content: center;
            padding-inline: 0;
        }
    }

    /* Dynamic CSS color overrides for Diet Template Builder and other sections */
    .diet-template-chart>.fi-section-header {
        background: linear-gradient(90deg, #eef7f3dc 0%, var(--app-primary-hex) 72%) !important;
    }
    .diet-chart-helper {
        box-shadow: 0 4px 12px color-mix(in srgb, var(--app-primary-hex) 4%, transparent) !important;
    }
    .diet-chart-helper__step {
        background: color-mix(in srgb, var(--app-primary-hex) 8%, transparent) !important;
        color: var(--app-primary-hex) !important;
    }
    .diet-scenario-tab {
        border-color: color-mix(in srgb, var(--app-primary-hex) 30%, transparent) !important;
        color: var(--app-primary-hex) !important;
    }
    .diet-scenario-card {
        border-color: color-mix(in srgb, var(--app-primary-hex) 15%, transparent) !important;
    }
    .diet-meal-repeater {
        border-top-color: var(--app-primary-hex) !important;
    }
    .diet-schedule-card.is-clickable:hover {
        border-color: color-mix(in srgb, var(--app-primary-hex) 40%, transparent) !important;
        box-shadow: 0 4px 12px color-mix(in srgb, var(--app-primary-hex) 10%, transparent) !important;
    }
    .diet-schedule-card.is-clickable:focus-visible {
        outline-color: var(--app-primary-hex) !important;
    }
    .diet-schedule-card.is-active {
        border-color: color-mix(in srgb, var(--app-primary-hex) 50%, transparent) !important;
        background: color-mix(in srgb, var(--app-primary-hex) 8%, transparent) !important;
    }
    .diet-schedule-note {
        border-color: color-mix(in srgb, var(--app-primary-hex) 25%, transparent) !important;
        background: color-mix(in srgb, var(--app-primary-hex) 4%, transparent) !important;
    }
    .diet-week-tabs {
        background: color-mix(in srgb, var(--app-primary-hex) 4%, transparent) !important;
    }
    .diet-week-tab {
        border-color: color-mix(in srgb, var(--app-primary-hex) 25%, transparent) !important;
        color: var(--app-primary-hex) !important;
    }
    .diet-week-tab:hover {
        border-color: color-mix(in srgb, var(--app-primary-hex) 40%, transparent) !important;
        background: color-mix(in srgb, var(--app-primary-hex) 8%, transparent) !important;
    }
    .diet-week-tab.is-active {
        background: var(--app-primary-hex) !important;
        border-color: var(--app-primary-hex) !important;
    }
    .diet-form-day-tab.is-active {
        border-color: var(--app-primary-hex) !important;
        background: color-mix(in srgb, var(--app-primary-hex) 8%, transparent) !important;
        color: var(--app-primary-hex) !important;
    }
    .diet-form-day-panel {
        border-color: color-mix(in srgb, var(--app-primary-hex) 15%, transparent) !important;
    }
    .diet-form-day-panel-title {
        border-bottom-color: color-mix(in srgb, var(--app-primary-hex) 15%, transparent) !important;
        background: color-mix(in srgb, var(--app-primary-hex) 8%, transparent) !important;
    }
    .diet-form-day-panel-title span:last-child {
        border-color: color-mix(in srgb, var(--app-primary-hex) 25%, transparent) !important;
        color: var(--app-primary-hex) !important;
    }
    .diet-primary-btn {
        border-color: var(--app-primary-hex) !important;
        background: var(--app-primary-hex) !important;
    }
    .diet-primary-btn:hover {
        background: color-mix(in srgb, var(--app-primary-hex) 85%, black) !important;
        border-color: color-mix(in srgb, var(--app-primary-hex) 85%, black) !important;
    }
    .diet-secondary-btn:hover {
        border-color: color-mix(in srgb, var(--app-primary-hex) 40%, transparent) !important;
        background: color-mix(in srgb, var(--app-primary-hex) 8%, transparent) !important;
    }
    .diet-form-add-day-btn {
        border-color: color-mix(in srgb, var(--app-primary-hex) 30%, transparent) !important;
        color: var(--app-primary-hex) !important;
    }
    .diet-form-add-day-btn:hover {
        background: color-mix(in srgb, var(--app-primary-hex) 8%, transparent) !important;
        border-color: var(--app-primary-hex) !important;
    }
    .diet-week-day-item.is-active {
        border-color: var(--app-primary-hex) !important;
        background: color-mix(in srgb, var(--app-primary-hex) 8%, transparent) !important;
        color: var(--app-primary-hex) !important;
    }
    .diet-week-day-meals {
        border-color: color-mix(in srgb, var(--app-primary-hex) 15%, transparent) !important;
    }
    .diet-week-day-title-row {
        border-bottom-color: color-mix(in srgb, var(--app-primary-hex) 15%, transparent) !important;
        background: color-mix(in srgb, var(--app-primary-hex) 8%, transparent) !important;
    }
    .diet-week-meals-count {
        border-color: color-mix(in srgb, var(--app-primary-hex) 25%, transparent) !important;
        color: var(--app-primary-hex) !important;
    }
    .diet-week-meal-summary strong {
        color: var(--app-primary-hex) !important;
    }
</style>