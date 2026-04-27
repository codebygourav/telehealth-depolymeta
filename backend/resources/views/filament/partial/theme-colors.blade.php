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
</style>