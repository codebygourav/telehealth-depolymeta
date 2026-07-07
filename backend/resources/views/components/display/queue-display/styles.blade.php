<style>
    :root {
        --display-blue: {{ $board['primary_color'] ?? '#055bd9' }};
        --display-green: {{ $board['secondary_color'] ?? '#22c55e' }};
        --display-ink: #152033;
        --display-line: #dce3ec;
        --display-surface: #ffffff;
        --display-bg: #edf2f7;
        --display-scale: 1;
        --display-gap: clamp(14px, 1.05vw, 28px);
        --display-panel-radius: clamp(18px, 1.2vw, 30px);
        --display-avatar: clamp(96px, 7vw, 180px);
        --display-topbar-height: clamp(64px, 4.8vw, 110px);
    }

    * { box-sizing: border-box; }
    html, body {
        margin: 0;
        height: 100vh;
        max-height: 100vh;
        overflow: hidden;
        background: var(--display-bg);
        color: var(--display-ink);
    }
    body {
        font-family: Inter, Arial, sans-serif;
        font-size: clamp(18px, calc(1rem * var(--display-scale)), 30px);
        height: 100%;
        display: flex;
        flex-direction: column;
    }
    [x-cloak] { display: none !important; }

    .display-shell {
        height: 100vh;
        max-height: 100vh;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        background:
            radial-gradient(circle at top left, rgba(5, 91, 217, 0.08), transparent 32%),
            radial-gradient(circle at bottom right, rgba(34, 197, 94, 0.08), transparent 25%),
            linear-gradient(180deg, #f7fbff 0%, #edf2f7 100%);
    }

    [data-opt-token-board-root] {
        position: relative;
        transition: opacity 180ms ease, filter 180ms ease;
        will-change: opacity, transform;
    }

    [data-opt-token-board-root].board-refreshing {
        opacity: 0.92;
        filter: saturate(0.99);
    }

    .board-refresh-badge {
        position: absolute;
        top: 14px;
        right: 16px;
        z-index: 30;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 12px;
        border-radius: 999px;
        background: rgba(9, 35, 84, 0.86);
        color: #fff;
        font-size: 12px;
        font-weight: 600;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        box-shadow: 0 12px 24px rgba(9, 35, 84, 0.2);
        backdrop-filter: blur(10px);
    }

    .board-refresh-badge .pulse {
        width: 8px;
        height: 8px;
        border-radius: 999px;
        background: #60a5fa;
        box-shadow: 0 0 0 0 rgba(96, 165, 250, 0.65);
        animation: refresh-pulse 1.35s infinite;
    }

    @keyframes refresh-pulse {
        0% {
            box-shadow: 0 0 0 0 rgba(96, 165, 250, 0.55);
        }

        70% {
            box-shadow: 0 0 0 10px rgba(96, 165, 250, 0);
        }

        100% {
            box-shadow: 0 0 0 0 rgba(96, 165, 250, 0);
        }
    }

    .topbar {
        min-height:50px;
        background: var(--display-blue);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 24px;
        box-shadow: 0 6px 24px rgba(5, 91, 217, 0.18);
    }

    .brand {
        display: flex;
        align-items: center;
        gap: clamp(12px, 0.9vw, 20px);
        font-size: clamp(24px, 1.7vw, 40px);
        font-weight: 600;
        letter-spacing: -0.02em;
    }

    .brand-logo {
        display: block;
        width: auto;
        height: clamp(38px, 2.8vw, 78px);
        object-fit: contain;
    }

    .brand-badge {
        width: clamp(36px, 2.6vw, 58px);
        height: clamp(36px, 2.6vw, 58px);
        border-radius: clamp(11px, 0.8vw, 18px);
        background: rgba(255, 255, 255, 0.16);
        display: grid;
        place-items: center;
        font-size: clamp(18px, 1.1vw, 28px);
        font-weight: 600;
        border: 1px solid rgba(255, 255, 255, 0.24);
    }

    .clock {
        text-align: right;
        font-weight: 600;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        font-size: clamp(13px, 0.95vw, 22px);
        line-height: 1.2;
    }

    .topbar-actions {
        display: flex;
        align-items: center;
        gap: clamp(10px, 0.8vw, 18px);
    }

    .control-button {
        border: 1px solid rgba(255, 255, 255, 0.35);
        background: rgba(255, 255, 255, 0.16);
        color: #fff;
        min-height: clamp(38px, 2.8vw, 54px);
        padding: 0 clamp(14px, 1vw, 18px);
        border-radius: 999px;
        font-size: clamp(12px, 0.9vw, 18px);
        font-weight: 600;
        cursor: pointer;
    }

    .control-button.is-paused {
        background: rgba(255, 255, 255, 0.22);
        border-color: rgba(255, 255, 255, 0.42);
    }

    .icon-control {
        width: 30px;
        height: 30px;
        min-height: 30px;
        padding: 0;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .icon-control svg {
        width: 17px;
        height: 17px;
    }

    .layout {
        flex: 1;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(min(100%, 540px), 1fr));
        padding: 0;
        align-items: stretch;
        min-height: 0;
        height: 100%;
        overflow: hidden;
    }

    .single-ads-layout {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .panel,
    .mini-card {
        background: rgba(255, 255, 255, 0.96);
        border: 1px solid rgba(220, 227, 236, 0.95);
        box-shadow: 0 10px 30px rgba(18, 34, 55, 0.07);
        overflow: hidden;
    }

    .doctor-panel {
        display: flex;
        flex-direction: column;
        min-height: 0;
        height: 100%;
    }

    .section-head {
        min-height: clamp(56px, 4vw, 96px);
        padding: 0 clamp(16px, 1.2vw, 28px);
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: clamp(14px, 1vw, 22px);
        font-weight: 600;
        color: #1e2a3a;
    }

    .section-head small {
        display: block;
        margin-top: 4px;
        font-size: clamp(11px, 0.8vw, 17px);
        font-weight: 600;
        color: #64748b;
    }

    .no-doctor-state {
        display: grid;
        gap: 12px;
        padding: 22px;
        border-radius: 20px;
        background: linear-gradient(135deg, rgba(5, 91, 217, 0.08), rgba(34, 197, 94, 0.08));
        border: 1px solid rgba(5, 91, 217, 0.12);
    }

    .no-doctor-state h2 {
        margin: 0;
        font-size: clamp(24px, 2.5vw, 44px);
        line-height: 1.05;
    }

    .no-doctor-state p {
        margin: 0;
        font-size: clamp(16px, 1.2vw, 22px);
        line-height: 1.5;
        color: #475569;
    }

    .multi-board {
        display: grid;
        grid-template-columns: 1fr;
        gap: var(--display-gap);
        min-height: 0;
        height: 100%;
    }

    .multi-board.with-ads {
        grid-template-columns: minmax(0, 1.45fr) minmax(520px, 0.95fr);
    }

    .multi-main-panel {
        padding: 0;
        display: flex;
        flex-direction: column;
        min-height: 0;
        height: 100%;
        overflow: hidden;
    }

    .doctor-grid {
        display: grid;
        gap: var(--display-gap);
        padding: 0 clamp(10px, 0.8vw, 18px) clamp(10px, 0.8vw, 18px);
        align-items: stretch;
        grid-auto-rows: minmax(460px, 1fr);
        flex: 1;
        min-height: 0;
        height: 100%;
        overflow-y: auto;
        grid-template-columns: 1fr;
        scrollbar-width: none;
        -ms-overflow-style: none;
    }
    .doctor-grid::-webkit-scrollbar {
        display: none;
    }

    .doctor-grid.cols-2,
    .doctor-grid.cols-3 {
        grid-template-columns: 1fr;
    }

    @media (min-width: 1100px) {
        .doctor-grid.cols-2,
        .doctor-grid.cols-3 {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (min-width: 1800px) {
        .doctor-grid.cols-3 {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }

    .multi-media-panel {
        min-height: clamp(760px, 76vh, 1180px);
    }

    .no-ads-layout {
        grid-template-columns: minmax(0, 1.35fr) minmax(420px, 0.8fr);
    }

    .left-column {
        display: flex;
        flex-direction: column;
        gap: 0;
        height: 100%;
        overflow: hidden;
    }

    .left_column_body {
        padding:0px;
        background: #fff;
        display: flex;
        flex-direction: column;
        gap: 16px;
        flex: 1;
        min-height: 0;
        overflow: hidden;
    }

    .left-column-topbar {
        background: var(--display-blue);
        color: #fff;
        padding: 0px 20px;
        border-radius: 0;
        display: flex;
        align-items: center;
        justify-content: space-between;
        min-height: 55px;
        box-shadow: 0 10px 25px rgba(5, 91, 217, 0.15);
        flex-shrink: 0;
    }

    .events-layout {
        grid-template-columns: 1fr;
    }

    .events-stage {
        position: relative;
        min-height: min(72vh, 1080px);
        border-radius: 28px;
        overflow: hidden;
        background: #0b4fcf;
        box-shadow: 0 30px 70px rgba(6, 35, 90, 0.24);
    }

    .events-stage-media {
        position: absolute;
        inset: 0;
    }

    .events-stage-media img,
    .events-stage-media video,
    .events-stage-media iframe {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border: 0;
    }

    .events-stage-overlay {
        position: absolute;
        inset: 0;
        background: linear-gradient(90deg, rgba(7, 36, 90, 0.86) 0%, rgba(7, 36, 90, 0.52) 38%, rgba(7, 36, 90, 0.12) 72%, rgba(7, 36, 90, 0.02) 100%);
    }

    .events-stage-copy {
        position: absolute;
        inset: auto clamp(18px, 1.4vw, 32px) clamp(18px, 1.4vw, 32px) clamp(18px, 1.4vw, 32px);
        z-index: 2;
        display: grid;
        gap: 14px;
        max-width: min(920px, 92%);
        color: #fff;
    }

    .events-stage-title {
        font-size: clamp(40px, 4vw, 88px);
        line-height: 0.98;
        font-weight: 1000;
        letter-spacing: -0.04em;
    }

    .events-stage-desc {
        font-size: clamp(18px, 1.35vw, 32px);
        line-height: 1.45;
        font-weight: 700;
        max-width: 56rem;
        color: rgba(255, 255, 255, 0.92);
    }

    .events-rail {
        margin-top: clamp(14px, 1vw, 22px);
    }

    .bottom-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 14px;
        padding: 0 14px 14px;
    }

    .bottom-slider-card {
        display: grid;
        grid-template-columns: minmax(280px, 0.95fr) minmax(0, 1fr);
        gap: clamp(18px, 1.2vw, 28px);
        align-items: center;
        padding: clamp(18px, 1.2vw, 28px);
        background: linear-gradient(135deg, #ffffff 0%, #f7fbff 100%);
        border: 1px solid #dbe6f2;
    }

    .bottom-slider-image {
        width: 100%;
        height: clamp(180px, 15vw, 280px);
        object-fit: cover;
        border-radius: clamp(14px, 1vw, 22px);
        background: linear-gradient(135deg, #dbeafe, #eff6ff);
        border: 1px solid #dbe6f2;
    }

    .bottom-slider-copy {
        min-width: 0;
    }

    .bottom-slider-title {
        font-size: clamp(24px, 1.6vw, 40px);
        font-weight: 600;
        line-height: 1.1;
        color: #10213a;
    }

    .bottom-slider-text {
        margin-top: 10px;
        font-size: clamp(15px, 1vw, 24px);
        line-height: 1.55;
        color: #475569;
    }

    .auth-shell {
        min-height: 100vh;
        display: grid;
        place-items: center;
        padding: 32px;
        background: #f3f7fd;
    }

    .auth-card {
        width: min(560px, 100%);
        border-radius: 28px;
        background: #ffffff;
        border: 1px solid #d7e1ef;
        box-shadow: 0 16px 48px rgba(15, 23, 42, 0.08);
        padding: 30px 32px 26px;
        position: relative;
        overflow: hidden;
    }


    .auth-title {
        margin: 0;
        font-size: clamp(28px, 2.2vw, 36px);
        line-height: 1.08;
        letter-spacing: -0.03em;
        font-weight: 600;
        color: #17233a;
    }

    .auth-copy {
        margin: 12px 0 0;
        color: #5a667a;
        font-size: 15px;
        line-height: 1.6;
    }

    .auth-row {
        display: grid;
        gap: 12px;
        margin-top: 24px;
    }

    .auth-input {
        width: 100%;
        border: 1px solid #cfd9ea;
        border-radius: 16px;
        padding: 16px 18px;
        font-size: 16px;
        outline: none;
        background: #fff;
        transition: border-color 160ms ease, box-shadow 160ms ease, transform 160ms ease;
    }

    .auth-input:focus {
        border-color: var(--display-blue);
        box-shadow: 0 0 0 5px rgba(5, 91, 217, 0.12);
        transform: translateY(-1px);
    }

    .auth-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 54px;
        padding: 0 20px;
        border-radius: 16px;
        border: none;
        background: var(--display-blue);
        color: #fff;
        font-weight: 600;
        font-size: 15px;
        box-shadow: 0 12px 28px rgba(5, 91, 217, 0.18);
        cursor: pointer;
        transition: transform 160ms ease, box-shadow 160ms ease, filter 160ms ease;
    }

    .auth-button:hover {
        transform: translateY(-1px);
        box-shadow: 0 18px 36px rgba(5, 91, 217, 0.28);
        filter: brightness(1.02);
    }

    .screen-note {
        margin-top: 18px;
        padding: 14px 16px;
        color: #51607a;
        font-size: 13px;
        line-height: 1.5;
        border-radius: 14px;
        background: #f7faff;
        border: 1px solid #dbe5f3;
    }

    .auth-brand {
        display: flex;
        justify-content: center;
        align-items: center;
        margin-top: 18px;
        padding-top: 4px;
    }

    .error-text {
        color: #dc2626;
        font-size: 13px;
        font-weight: 700;
    }

    .fade-enter {
        animation: fadeEnter 0.35s ease-out both;
    }

    @keyframes fadeEnter {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @media (max-width: 1100px) {
        .layout { grid-template-columns: 1fr; }
        .bottom-grid { grid-template-columns: 1fr; }
        .queue-row { grid-template-columns: 1fr; }
        .multi-board.with-ads { grid-template-columns: 1fr; }
        .no-ads-layout { grid-template-columns: 1fr; }
        .single-ads-layout { grid-template-columns: 1fr; }
        .topbar-actions { width: 100%; justify-content: space-between; }
    }

    @media (min-width: 1025px) and (max-width: 1440px) {
        .multi-board.with-ads { grid-template-columns: 1fr; }
        .popup-split { grid-template-columns: 1fr; }
        .popup-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .popup-slider-preview { grid-template-columns: 1fr; }
    }

    @media (max-width: 700px) {
        .topbar { height: auto; padding: 16px 18px; gap: 10px; flex-direction: column; align-items: flex-start; }
        .clock { text-align: left; }
        .layout { padding: 10px; gap: 10px; }
        .popup-split { grid-template-columns: 1fr; }
        .popup-media { min-height: 220px; }
        .queue-row { grid-template-columns: 1fr; gap: 6px; }
        .status, .turn { justify-self: start; text-align: left; }
        .popup-grid { grid-template-columns: 1fr; }
        .bottom-slider-card { grid-template-columns: 1fr; }
        .popup-slider-preview { grid-template-columns: 1fr; }
    }

    .doctor-card-shell {
        position: relative;
        display: flex;
        flex-direction: column;
        width: 100%;
        flex: 1;
        min-height: 0;
        height: 100%;
    }

    .doctor-card-inner {
        position: relative;
        z-index: 2;
        width: 100%;
        height: 100%;
        display: flex;
        flex-direction: column;
        gap: 0;
        min-height: 0;
        overflow: hidden;
        padding:20px;
        background: rgba(255, 255, 255, 1);
    }

    .doctor-card-main {
        display: flex;
        gap: clamp(14px, 1vw, 22px);
        align-items: flex-start;
        min-height: 0;
        padding: 20px;
        border:1px solid #eee;
        border-radius:8px;
        background-image: url('{{ asset('images/queue-images/doctor-card-bg.png') }}');
        background-repeat: no-repeat;
        background-position: right 18px center;
        background-size: clamp(110px, 11vw, 170px);
        /* padding-right: clamp(110px, 12vw, 170px); */
    }

    .doctor-card-avatar,
    .doctor-avatar,
    .doctor-avatar-fallback {
        width: 140px;
        height: 140px;
        border-radius: 6px;
        flex: none;
    }

    .doctor-avatar,
    .doctor-avatar-fallback {
        object-fit: cover;
        border: 1px solid #d8e0ee;
    }

    .doctor-avatar-fallback {
        background: #0b74ff;
        color: #fff;
        display: grid;
        place-items: center;
        font-size: 48px;
        font-weight: 600;
        border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .doctor-info {
        flex: 1;
        min-width: 0;
        position: relative;
    }

    .doctor-info h2 {
        margin: 0;
        font-size: 32px;
        font-weight: 600;
        color: #0f172a;
        letter-spacing: -0.02em;
        line-height: 1.08;
    }

    .speciality-row {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-top: 8px;
        flex-wrap: wrap;
    }

    .speciality {
        color: var(--display-blue);
        font-weight: 600;
        font-size: 18px;
    }

    .exp-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-weight: 700;
        font-size: 15px;
        color: #475569;
    }

    .star-icon {
        color: #eab308;
        font-size: 17px;
    }

    .qualifications-list {
        margin-top: 12px;
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .doctor-bio {
        margin-top: 10px;
        font-size: 14px;
        line-height: 1.45;
        color: #475569;
        font-weight: 600;
        max-width: 72ch;
    }

    .doctor-slot-summary {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 12px;
    }

    .slot-chip {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        min-height: 36px;
        padding: 8px 12px;
        border-radius: 999px;
        font-size: 13px;
        font-weight: 700;
        border: 1px solid #d9e4f5;
        background: #f8fbff;
        color: #1e3a5f;
    }

    .slot-chip.current {
        border-color: rgba(5, 91, 217, 0.18);
        background: rgba(5, 91, 217, 0.08);
        color: #0b4fcf;
    }

    .slot-chip.next {
        border-color: rgba(34, 197, 94, 0.18);
        background: rgba(34, 197, 94, 0.09);
        color: #166534;
    }

    .slot-chip-label {
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: inherit;
        opacity: 0.78;
    }

    .qual-item {
        font-size: 14px;
        color: #516074;
        line-height: 1.4;
        font-weight: 600;
    }

    .doctor-break-note {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 5px;
        border-radius: 6px;
        background: #fff1f2;
        border: 1px solid #fecdd3;
        color: #be123c;
        font-size: 12px;
        font-weight: 600;
    }

    .doctor-break-note::before {
        content: '';
        width: 8px;
        height: 8px;
        border-radius: 999px;
        background: #ef4444;
    }

    .doctor-card-bg-icon {
        display: none;
    }

    .queue-list-container {
        display: flex;
        flex-direction: column;
        gap: 12px;
        overflow-y: auto;
        flex: 1;
        min-height: 0;
        scroll-behavior: smooth;
        scroll-snap-type: y proximity;
        scroll-padding-block: 14px;
        scrollbar-width: none;
        -ms-overflow-style: none;
        background-color: #fff;
        padding:0px;
    }

    .queue-list-container::-webkit-scrollbar {
        display: none;
    }



    .slide-dots-overlay {
        position: absolute;
        bottom: 20px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 10;
        background: rgba(0, 0, 0, 0.35);
        backdrop-filter: blur(8px);
        padding: 8px 16px;
        border-radius: 999px;
        display: flex;
        gap: 6px;
        align-items: center;
    }

    .queue-section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 0;
    }

    .queue-section-header h2 {
        margin: 0;
        font-size: 18px;
        font-weight: 600;
        color: #0f172a;
    }

    .queue-section-header .all-patients-link {
        font-size: clamp(14px, 0.95vw, 18px);
        font-weight: 600;
        color: #0f172a;
        text-decoration: underline;
    }

    .queue-table-header {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: clamp(10px, 0.8vw, 18px);
        align-items: center;
        padding: 10px;
        border-radius: 6px;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
        border: 1px solid rgba(30, 30, 30, 0.15);
        font-weight: 600;
        font-size: clamp(13px, 0.95vw, 17px);
        color: #000;
        margin-bottom:10px;
        transition: box-shadow 0.22s ease, transform 0.22s ease;
    }

    .queue-table-header .header-col {
        min-width: 0;
    }

    .queue-table-header .header-col.turn-col {
        text-align: right;
        white-space: nowrap;
    }



    .queue-row {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: clamp(10px, 0.8vw, 18px);
        align-items: center;
        min-height:fit-content;
        padding: 10px;
        border: 1px solid #e5ebf3;
        border-radius: 6px;
        background: #fff;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
        transition: transform 0.22s ease, box-shadow 0.22s ease, background 0.22s ease, border-color 0.22s ease, color 0.22s ease;
        scroll-snap-align: center;
        transform-origin: center;
    }

    .queue-row.focused {
        border-color: rgba(5, 91, 217, 0.35);
        z-index: 2;
        animation: queueRowFocusIn 0.38s ease both;
    }

    .queue-row.queue-row-focus-flash {
        animation: queueRowFlash 0.38s ease both;
    }

    .queue-row.focused.next {
        border-color: #ef4444;
        box-shadow: 0 16px 34px rgba(239, 68, 68, 0.16);
    }

    .queue-row.focused.active {
        border-color: rgba(5, 91, 217, 0.55);
        border: 1px solid rgba(5, 91, 217, 1);
    }

    .queue-row.active {
        background: #075bd8;
        border-color: transparent;
        color: #fff;
        border: 1px solid rgba(5, 91, 217, 1);
    }

    .queue-row.next {
        background: #fff1f2;
        border: 2px solid #ef4444;
    }

    .queue-row.next .token {
        color: #991b1b;
        font-weight: 600;
    }

    .queue-row.next .patient {
        color: #7f1d1d;
    }

    .queue-row.next .phone {
        color: #b91c1c;
    }

    .queue-row.next .turn {
        color: #dc2626;
        font-weight: 600;
    }

    .queue-row.next .status-pill {
        background: #ef4444;
        color: #ffffff;
        border-color: transparent;
    }

    .queue-row .token {
        font-size: 16px;
        font-weight: 500;
        color: #1e293b;
    }

    .queue-row.active .token {
        color: #ffffff;
    }

    .queue-row .patient {
        font-size: 16px;
        font-weight: 500;
        line-height: 1.1;
        color: #0f172a;
    }

    .queue-row.active .patient {
        color: #ffffff;
    }

    .queue-row .phone {
        font-size: 12px;
        font-weight: 600;
        color: #64748b;
    }

    .queue-row.active .phone {
        color: rgba(255, 255, 255, 0.8);
    }

    .queue-row .time-slot {
        font-size: 14px;
        margin-top: 4px;
        font-weight: 700;
        color: #64748b;
    }

    .queue-row.active .time-slot {
        color: rgba(255, 255, 255, 0.8);
    }

    .status-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        font-weight: 600;
        border-radius: 6px;
        min-height: 34px;
        width: 112px;
        padding: 5px 12px;
        text-align: center;
        border: 1px solid transparent;
        justify-self: center;
        box-sizing: border-box;
        white-space: nowrap;
        box-shadow: 0 8px 18px rgba(15, 23, 42, 0.08);
        transition: box-shadow 0.22s ease, transform 0.22s ease, background 0.22s ease, color 0.22s ease, border-color 0.22s ease;
    }

    .status-pill.active-pill {
        background: #fff;
        color: var(--display-blue);
        border-color: transparent;
    }

    .status-pill.waiting {
        background: #fffbeb;
        color: #d97706;
        border: 1px solid #fde68a;
    }

    .status-pill.status-checkin {
        background: #fffbeb;
        color: #d97706;
        border: 1px solid #fde68a;
    }

    .status-pill.status-started {
        background: #eef2ff;
        color: #4338ca;
        border: 1px solid #c7d2fe;
    }

    .status-pill.status-completed {
        background: #ecfdf5;
        color: #047857;
        border: 1px solid #a7f3d0;
    }

    .status-pill.status-scheduled {
        background: #f8fafc;
        color: #475569;
        border: 1px solid #cbd5e1;
    }

    .queue-row .turn {
        font-size: 14px;
        text-align: right;
        font-weight: 600;
        color: #334155;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .queue-row.active .turn {
        color: #ffffff;
    }

    @keyframes queueRowFocusIn {
        from {
            opacity: 0.65;
            transform: scale(0.985);
        }

        to {
            opacity: 1;
            transform: scale(1.018);
        }
    }

    @keyframes queueRowFlash {
        0% {
            filter: saturate(0.95);
        }

        100% {
            filter: saturate(1);
        }
    }

    .doctor-card-footer,
    .queue-display-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        margin-top: auto;
        padding: 0 20px;
    }

    .doctor-card-footer {
        flex-shrink: 0;
    }

    .footer-brand {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        font-weight: 700;
        color: #475569;
    }

    .footer-brand img {
        height: 58px;
        width: auto;
        object-fit: contain;
    }

    .footer-alert-pill {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px;
        border-radius: 10px;
        background: #F6F9FF;
        border: 1px solid rgba(237, 237, 237, 1);
        color: var(--display-blue);
        font-size: 14px;
        font-weight: 700;
        min-width: 0;
    }


    .schedule-panel {
        position: relative;
        overflow: hidden;
        border-radius: 6px;
        background: #fff;
    }

    .slider {
        height: calc(100% - 52px);
        overflow: hidden;
    }

    .schedule-panel.no-footer .slider {
        height: 100%;
    }

    .track {
        display: flex;
        height: 100%;
        animation: weekSlide 28s infinite ease-in-out;
    }

    .day {
        min-width: 100%;
        padding: 10px 12px 12px;
        display: grid;
        grid-template-rows: auto 1fr;
        min-height: 0;
    }

    .day-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
        gap: 10px;
    }

    .day-title {
        font-size: 20px;
        font-weight: 600;
        line-height: 1.1;
    }

    .date-pill {
        background: var(--display-blue);
        color: white;
        padding: 8px 12px;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 600;
        white-space: nowrap;
    }

    .slot-grid {
        display: grid;
        gap: 8px;
        align-content: start;
        min-height: 0;
    }

    .slot-card {
        border: 1px solid var(--display-line);
        border-radius: 6px;
        padding: 10px;
        background: #fff;
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 10px;
        align-items: stretch;
        box-shadow: 0 6px 14px rgba(15, 23, 42, 0.03);
    }

    .slot-card.active {
        background: #eef6ff;
        border-color: #9cc6ff;
    }

    .slot-doctor {
        font-size: 17px;
        font-weight: 600;
        line-height: 1.1;
    }

    .slot-meta {
        color: #64748b;
        font-weight: 700;
        margin-top: 3px;
        font-size: 12px;
    }

    .slot-time {
        margin-top: 6px;
        font-size: 14px;
        color: var(--display-blue);
        font-weight: 600;
    }

    .room-pill {
        background: #f8fafc;
        border: 1px solid var(--display-line);
        padding: 8px 10px;
        border-radius: 6px;
        font-weight: 600;
        text-align: center;
        line-height: 1.15;
        min-width: 68px;
        font-size: 13px;
        align-self: center;
    }

    .tag {
        margin-top: 6px;
        display: inline-block;
        font-size: 13px;
        padding: 6px 10px;
        border-radius: 6px;
        font-weight: 600;
    }

    .schedule-panel .panel-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 10px 12px 8px;
    }

    .schedule-panel .panel-title {
        font-size: 20px;
        font-weight: 600;
        color: #0f172a;
    }

    .schedule-panel .badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 6px;
        padding: 6px 10px;
        background: #eff6ff;
        color: var(--display-blue);
        font-size: 12px;
        font-weight: 600;
    }

    .slot-card-main {
        display: flex;
        flex-direction: column;
        gap: 8px;
        min-width: 0;
    }

    .slot-card-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 10px;
    }

    .slot-availability-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 6px;
        padding: 6px 10px;
        font-size: 12px;
        font-weight: 600;
        white-space: nowrap;
        flex: none;
    }

    .slot-stats {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 10px;
        font-size: 12px;
        font-weight: 600;
        color: #64748b;
    }

    .slot-stats span:last-child {
        color: #0f172a;
    }

    .available,
    .slot-availability-pill.available {
        background: #ecfdf5;
        color: var(--display-green);
    }

    .limited,
    .slot-availability-pill.limited {
        background: #fff7ed;
        color: #c2410c;
    }

    .closed,
    .slot-availability-pill.closed {
        background: #f1f5f9;
        color: #475569;
    }

    @keyframes weekSlide {
        0%, 12% { transform: translateX(0); }
        14%, 26% { transform: translateX(-100%); }
        28%, 40% { transform: translateX(-200%); }
        42%, 54% { transform: translateX(-300%); }
        56%, 68% { transform: translateX(-400%); }
        70%, 82% { transform: translateX(-500%); }
        84%, 96% { transform: translateX(-600%); }
        100% { transform: translateX(0); }
    }

    .queue-display-board-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        padding: 0 8px;
        margin-bottom: 14px;
    }

    .queue-display-board-header h2 {
        margin: 0;
        font-size: 24px;
        font-weight: 600;
        color: #0f172a;
    }

    .queue-display-board-header small {
        display: block;
        color: #64748b;
        font-size: 15px;
        font-weight: 700;
        margin-top: 4px;
    }

    .popup-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(7, 15, 30, 0.58);
        display: grid;
        place-items: center;
        z-index: 60;
        padding: 18px;
    }

    .popup-card {
        width: min(920px, 100%);
        border-radius: 6px;
        overflow: hidden;
        background: #ffffff;
        border: 1px solid rgba(220, 227, 236, 0.95);
        box-shadow: 0 32px 80px rgba(15, 23, 42, 0.32);
    }

    /* Next Turn Popup styles matching preview.html */
    .patient-popup-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.42);
        display: grid;
        place-items: center;
        z-index: 9999;
    }

    .patient-popup-card {
        width: min(650px, 92vw);
        background: #ffffff;
        border-radius: 15px;
        padding: 42px;
        text-align: center;
        border: 8px solid #dbeafe;
        box-shadow: 0 35px 90px rgba(15, 23, 42, 0.35);
    }

    .patient-popup-label {
        font-size: 24px;
        font-weight: 600;
        color: var(--display-blue);
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .patient-popup-token {
        margin: 12px 0;
        font-size: 88px;
        font-weight: 600;
        color: red;
        line-height: 1;
    }

    .patient-popup-card h2 {
        margin: 0;
        font-size: 40px;
        font-weight: 600;
        color: #0f172a;
    }

    .patient-popup-card p {
        margin: 12px 0 0;
        font-size: 22px;
        color: #64748b;
        font-weight: 600;
    }

    .queue-spotlight-modal {
        position: fixed;
        inset: 0;
        background: rgba(7, 15, 30, 0.58);
        display: grid;
        place-items: stretch;
        z-index: 60;
        padding: 0;
    }

    .queue-spotlight-card {
        width: 100vw;
        height: 100vh;
        display: grid;
        grid-template-rows: minmax(0, 1fr);
        border-radius: 0;
        overflow: hidden;
        background: #fff;
        border: 1px solid #d9e8ff;
        box-shadow: 0 28px 72px rgba(5, 91, 217, 0.34);
        position: relative;
    }

    .queue-spotlight-head {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        z-index: 6;
        padding: 10px 14px;
        background: linear-gradient(180deg, rgba(5, 91, 217, 0.96), rgba(5, 91, 217, 0.82));
        color: #fff;
        font-size: 13px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.06em;
    }

    .queue-spotlight-wrap {
        position: relative;
        display: block;
        padding: 0;
        height: 100%;
        min-height: 0;
        width: 100%;
    }

    .queue-spotlight-arrow {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        width: 54px;
        height: 54px;
        border-radius: 6px;
        border: 1px solid rgba(255, 255, 255, 0.34);
        background: rgba(15, 23, 42, 0.72);
        color: #fff;
        display: grid;
        place-items: center;
        cursor: pointer;
        flex: none;
        z-index: 4;
    }

    .queue-spotlight-arrow svg {
        width: 20px;
        height: 20px;
    }

    .queue-spotlight-arrow-left {
        left: 12px;
    }

    .queue-spotlight-arrow-right {
        right: 12px;
    }

    .queue-spotlight-main {
        display: block;
        position: relative;
        width: 100%;
        height: 100%;
        min-height: 0;
        overflow: hidden;
        background: #000;
        border-radius: 0;
    }

    .queue-spotlight-media {
        position: absolute;
        inset: 0;
        background: #000;
        overflow: hidden;
    }

    .queue-spotlight-media img,
    .queue-spotlight-media video,
    .queue-spotlight-media iframe {
        display: block;
        width: 100%;
        height: 100%;
        object-fit: cover;
        border: 0;
        background: #000;
    }

    .queue-spotlight-empty {
        position: absolute;
        inset: 0;
        display: grid;
        place-items: center;
        padding: 28px;
        text-align: center;
        background: #fde7d8;
        color: #7d4754;
        font-weight: 600;
    }

    .queue-spotlight-empty-title {
        font-size: clamp(30px, 2.7vw, 58px);
        line-height: 1.04;
        color: #9d3749;
        font-family: Georgia, serif;
    }

    .queue-spotlight-empty-desc {
        margin-top: 14px;
        font-size: clamp(16px, 1.15vw, 26px);
        line-height: 1.55;
    }

    .queue-spotlight-content {
        position: absolute;
        left: clamp(12px, 1vw, 20px);
        right: clamp(12px, 1vw, 20px);
        bottom: clamp(72px, 6vh, 112px);
        display: grid;
        gap: 8px;
        z-index: 4;
        padding: 12px 14px;
        border-radius: 6px;
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.08), rgba(255, 255, 255, 0.24));
        backdrop-filter: blur(12px);
    }

    .queue-spotlight-kicker {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }

    .popup-meta-chip {
        display: inline-flex;
        align-items: center;
        padding: 7px 12px;
        border-radius: 6px;
        background: rgba(255, 255, 255, 0.12);
        border: 1px solid rgba(255, 255, 255, 0.18);
        font-size: clamp(12px, 0.85vw, 16px);
        font-weight: 600;
        color: #fff;
    }

    .queue-spotlight-title {
        margin: 0;
        color: #ba2b5f;
        font-size: clamp(28px, 2.6vw, 46px);
        line-height: 1.04;
        font-weight: 1000;
        letter-spacing: -0.03em;
    }

    .queue-spotlight-desc {
        margin: 0;
        color: #475569;
        font-size: clamp(15px, 1.1vw, 22px);
        line-height: 1.5;
        font-weight: 700;
    }

    .queue-spotlight-footer {
        position: absolute;
        left: 0;
        right: 0;
        bottom: 0;
        display: flex;
        justify-content: space-between;
        gap: 12px;
        padding: 10px 12px 12px;
        font-size: 13px;
        font-weight: 600;
        color: #10213a;
        background: rgba(255, 255, 255, 0.88);
        backdrop-filter: blur(10px);
        z-index: 5;
    }

    .queue-spotlight-pagination {
        position: absolute;
        left: 50%;
        bottom: clamp(18px, 2.8vh, 30px);
        transform: translateX(-50%);
        margin-top: 0;
        text-align: center;
        font-size: 13px;
        font-weight: 600;
        color: #334155;
        z-index: 6;
        background: rgba(255, 255, 255, 0.82);
        border-radius: 999px;
        padding: 6px 10px;
    }

    .queue-spotlight-dots {
        position: absolute;
        left: 50%;
        bottom: clamp(48px, 4.8vh, 64px);
        transform: translateX(-50%);
        display: flex;
        gap: 8px;
        align-items: center;
        justify-content: center;
        margin: 0;
        z-index: 6;
        padding: 6px 10px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.65);
    }

    .queue-spotlight-dots button {
        width: 10px;
        height: 10px;
        border-radius: 999px;
        border: 0;
        background: #d1dbe8;
    }

    .queue-spotlight-dots button.active {
        width: 30px;
        background: var(--display-blue);
    }

    .spotlight-dots-overlay {
        position: absolute;
        bottom: 20px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 10;
        background: rgba(0, 0, 0, 0.35);
        backdrop-filter: blur(8px);
        padding: 8px 16px;
        border-radius: 999px;
        display: flex;
        gap: 6px;
        align-items: center;
    }

    .spotlight-dots {
        display: flex;
        gap: 8px;
        align-items: center;
        justify-content: center;
        margin-top: 14px;
    }

    .spotlight-dots button {
        width: 10px;
        height: 10px;
        border-radius: 999px;
        border: 0;
        background: #d1dbe8;
    }

    .spotlight-dots button.active {
        width: 30px;
        background: var(--display-blue);
    }

    .doctor-spotlight-slider {
        width: 100vw;
        height: 100vh;
        display: grid;
        grid-template-rows: minmax(0, 1fr);
        border-radius: 0;
        overflow: hidden;
        background: #fff;
        border: 1px solid #d9e8ff;
        box-shadow: 0 28px 72px rgba(5, 91, 217, 0.34);
        position: relative;
    }

    .spotlight-wrap {
        position: relative;
        display: block;
        padding: 0;
        height: 100%;
        min-height: 0;
        width: 100%;
    }

    .spotlight-arrow {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        width: 54px;
        height: 54px;
        border-radius: 6px;
        border: 1px solid rgba(255, 255, 255, 0.34);
        background: rgba(15, 23, 42, 0.72);
        color: #fff;
        display: grid;
        place-items: center;
        cursor: pointer;
        flex: none;
        z-index: 4;
    }

    .spotlight-arrow svg {
        width: 20px;
        height: 20px;
    }

    .spotlight-arrow-left {
        left: 12px;
    }

    .spotlight-arrow-right {
        right: 12px;
    }

    .spotlight-main-card {
        display: block;
        position: relative;
        width: 100%;
        height: 100%;
        min-height: 0;
        overflow: hidden;
        background: #000;
        border-radius: 0;
    }

    .spotlight-media {
        position: absolute;
        inset: 0;
        background: #000;
        overflow: hidden;
    }

    .spotlight-media img,
    .spotlight-media video,
    .spotlight-media iframe {
        display: block;
        width: 100%;
        height: 100%;
        object-fit: cover;
        border: 0;
        background: #000;
    }

    .spotlight-empty {
        position: absolute;
        inset: 0;
        display: grid;
        place-items: center;
        padding: 28px;
        text-align: center;
        background: #fde7d8;
        color: #7d4754;
        font-weight: 600;
    }

    .spotlight-empty-title {
        font-size: clamp(30px, 2.7vw, 58px);
        line-height: 1.04;
        color: #9d3749;
        font-family: Georgia, serif;
    }

    .spotlight-empty-desc {
        margin-top: 14px;
        font-size: clamp(16px, 1.15vw, 26px);
        line-height: 1.55;
    }

    .spotlight-content {
        position: absolute;
        left: clamp(12px, 1vw, 20px);
        right: clamp(12px, 1vw, 20px);
        bottom: clamp(72px, 6vh, 112px);
        display: grid;
        gap: 8px;
        z-index: 4;
        padding: 12px 14px;
        border-radius: 6px;
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.08), rgba(255, 255, 255, 0.24));
        backdrop-filter: blur(12px);
    }

    .spotlight-kicker {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }

    .spotlight-title {
        margin: 0;
        color: #ba2b5f;
        font-size: clamp(28px, 2.6vw, 46px);
        line-height: 1.04;
        font-weight: 1000;
        letter-spacing: -0.03em;
    }

    .spotlight-desc {
        margin: 0;
        color: #475569;
        font-size: clamp(15px, 1.1vw, 22px);
        line-height: 1.5;
        font-weight: 700;
    }

    .spotlight-pagination {
        position: absolute;
        left: 50%;
        bottom: clamp(18px, 2.8vh, 30px);
        transform: translateX(-50%);
        margin-top: 0;
        text-align: center;
        font-size: 13px;
        font-weight: 600;
        color: #334155;
        z-index: 6;
        background: rgba(255, 255, 255, 0.82);
        border-radius: 999px;
        padding: 6px 10px;
    }

    .popup-head {
        padding: 10px 14px;
        background: var(--display-blue);
        color: #fff;
        font-size: 13px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.06em;
    }

    .spotlight-footer {
        position: absolute;
        left: 0;
        right: 0;
        bottom: 0;
        display: flex;
        justify-content: space-between;
        gap: 12px;
        padding: 10px 12px 12px;
        font-size: 13px;
        font-weight: 600;
        color: #10213a;
        background: rgba(255, 255, 255, 0.88);
        backdrop-filter: blur(10px);
        z-index: 5;
    }

    .popup-next-card {
        width: min(650px, 92vw);
        padding: 42px;
        text-align: center;
        background: #fff;
        border-radius: 6px;
        border: 8px solid #dbeafe;
        box-shadow: 0 35px 90px rgba(15, 23, 42, 0.35);
    }
    .doctor-grid-track {
        display: flex !important;
        gap: var(--display-gap) !important;
        width: 100% !important;
        height: 100% !important;
        transition: transform 0.8s cubic-bezier(0.25, 1, 0.5, 1) !important;
    }

    .doctor-card-slide-wrapper {
        flex: 0 0 calc(50% - (var(--display-gap) / 2)) !important;
        width: calc(50% - (var(--display-gap) / 2)) !important;
        height: 100% !important;
        display: flex !important;
        flex-direction: column !important;
        border:1px solid #eee;
    }
</style>
