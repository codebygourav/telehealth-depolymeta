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
        font-weight: 800;
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
        font-weight: 900;
        border: 1px solid rgba(255, 255, 255, 0.24);
    }

    .clock {
        text-align: right;
        font-weight: 800;
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
        font-weight: 900;
        cursor: pointer;
    }

    .control-button.is-paused {
        background: rgba(255, 255, 255, 0.22);
        border-color: rgba(255, 255, 255, 0.42);
    }

    .icon-control {
        width: 46px;
        padding: 0;
    }

    .icon-control svg {
        width: 20px;
        height: 20px;
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
        font-weight: 900;
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
        padding: 20px;
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
        padding: 16px 28px;
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
        font-weight: 900;
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
        padding: 24px;
    }

    .auth-card {
        width: min(640px, 100%);
        border-radius: 24px;
        background: rgba(255, 255, 255, 0.96);
        border: 1px solid rgba(220, 227, 236, 0.95);
        box-shadow: 0 20px 60px rgba(17, 24, 39, 0.14);
        padding: 28px;
    }

    .auth-title {
        margin: 0;
        font-size: 30px;
        line-height: 1.1;
        letter-spacing: -0.03em;
    }

    .auth-copy {
        margin: 12px 0 0;
        color: #5a667a;
        font-size: 15px;
        line-height: 1.6;
    }

    .auth-row {
        display: grid;
        gap: 10px;
        margin-top: 22px;
    }

    .auth-input {
        width: 100%;
        border: 1px solid #d8e0ee;
        border-radius: 16px;
        padding: 16px 18px;
        font-size: 16px;
        outline: none;
        background: #fff;
    }

    .auth-input:focus {
        border-color: var(--display-blue);
        box-shadow: 0 0 0 4px rgba(5, 91, 217, 0.12);
    }

    .auth-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 50px;
        padding: 0 20px;
        border-radius: 16px;
        border: none;
        background: #0b74ff;
        color: #fff;
        font-weight: 900;
        font-size: 15px;
        box-shadow: 0 12px 28px rgba(5, 91, 217, 0.22);
    }

    .screen-note {
        padding: 0 18px 18px;
        color: #596273;
        font-size: 13px;
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
        background: linear-gradient(180deg, #ffffff, #f8fbff);
    }

    .doctor-card-main {
        display: flex;
        gap: clamp(14px, 1vw, 22px);
        align-items: flex-start;
        min-height: 0;
        padding: clamp(16px, 1.1vw, 24px);
        border:1px solid #eee;
        border-radius:8px;
    }

    .doctor-card-avatar,
    .doctor-avatar,
    .doctor-avatar-fallback {
        width: 140px;
        height: 140px;
        border-radius: clamp(10px, 0.8vw, 16px);
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
        font-weight: 900;
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
        font-weight: 800;
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
        font-weight: 900;
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

    .qual-item {
        font-size: 14px;
        color: #516074;
        line-height: 1.4;
        font-weight: 600;
    }

    .doctor-break-note {
        margin-top: 12px;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 12px;
        border-radius: 999px;
        background: #fff1f2;
        border: 1px solid #fecdd3;
        color: #be123c;
        font-size: 12px;
        font-weight: 900;
    }

    .doctor-break-note::before {
        content: '';
        width: 8px;
        height: 8px;
        border-radius: 999px;
        background: #ef4444;
    }

    .doctor-card-bg-icon {
        position: absolute;
        right: 10px;
        top: 10px;
        width: 160px;
        height: 160px;
        color: var(--display-blue);
        opacity: 0.04;
        pointer-events: none;
        z-index: 1;
    }

    .queue-list-container {
        display: flex;
        flex-direction: column;
        gap: 12px;
        overflow-y: auto;
        flex: 1;
        min-height: 0;
        scrollbar-width: none;
        -ms-overflow-style: none;
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
        font-weight: 800;
        color: #0f172a;
    }

    .queue-section-header .all-patients-link {
        font-size: clamp(14px, 0.95vw, 18px);
        font-weight: 800;
        color: #0f172a;
        text-decoration: underline;
    }

    .queue-table-header {
        display: grid;
        grid-template-columns: minmax(88px, 128px) minmax(0, 1fr) minmax(116px, 170px) minmax(92px, 128px);
        gap: clamp(10px, 0.8vw, 18px);
        align-items: center;
        padding: 14px clamp(12px, 0.9vw, 20px);
        background: #f1f5f9;
        border-radius: 8px;
        font-weight: 800;
        font-size: clamp(13px, 0.95vw, 17px);
        color: #475569;
        margin-bottom:10px;
    }

    .queue-table-header .header-col.turn-col {
        text-align: right;
    }



    .queue-row {
        display: grid;
        grid-template-columns: minmax(88px, 128px) minmax(0, 1fr) minmax(116px, 170px) minmax(92px, 128px);
        gap: clamp(10px, 0.8vw, 18px);
        align-items: center;
        min-height:fit-content;
        padding: 10px;
        border: 1px solid #e5ebf3;
        border-radius: 8px;
        background: #fff;
        box-shadow: 0 12px 26px rgba(15,23,42,.04);
        transition: all 0.3s ease;
    }

    .queue-row.active {
        background: #075bd8;
        border-color: transparent;
        color: #fff;
        box-shadow: 0px 12px 80px 0px #00000052;
    }

    .queue-row.next {
        background: #fef2f2;
        border: 2px solid #ef4444;
        box-shadow: 0 10px 25px rgba(239, 68, 68, 0.08);
    }
    .queue-row.next .token {
        color: #991b1b;
        font-weight: 800;
    }
    .queue-row.next .patient {
        color: #7f1d1d;
    }
    .queue-row.next .phone {
        color: #b91c1c;
    }
    .queue-row.next .turn {
        color: #dc2626;
        font-weight: 900;
        animation: pulseNext 2s infinite ease-in-out;
    }
    .queue-row.next .status-pill.next-pill {
        background: #ef4444;
        color: #ffffff;
        border-color: transparent;
    }

    @keyframes pulseNext {
        0%, 100% { opacity: 1; transform: translateX(0); }
        50% { opacity: 0.8; transform: translateX(5px); }
    }

    .queue-row .token {
        font-size: 18px;
        font-weight: 700;
        color: #1e293b;
    }

    .queue-row.active .token {
        color: #ffffff;
    }

    .queue-row .patient {
        font-size: 18px;
        font-weight: 700;
        line-height: 1.1;
        color: #0f172a;
    }

    .queue-row.active .patient {
        color: #ffffff;
    }

    .queue-row .phone {
        font-size: clamp(11px, 0.85vw, 14px);
        margin-top: 4px;
        font-weight: 600;
        color: #64748b;
    }

    .queue-row.active .phone {
        color: rgba(255, 255, 255, 0.8);
    }

    .queue-row .time-slot {
        font-size: clamp(11px, 0.85vw, 14px);
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
        font-weight: 800;
        border-radius: 8px;
        padding: 5px 10px;
        text-align: center;
        border: 1px solid transparent;
        width: max-content;
        justify-self: center;
    }

    .status-pill.active-pill {
        background: #fff;
        color: var(--display-blue);
        border-color: transparent;
    }

    .status-pill.waiting,
    .status-pill.next-pill {
        background: #fffbeb;
        color: #d97706;
        border: 1px solid #fde68a;
    }

    .queue-row .turn {
        font-size: clamp(14px, 1vw, 18px);
        text-align: right;
        font-weight: 800;
        color: #334155;
    }

    .queue-row.active .turn {
        color: #ffffff;
    }

    .doctor-card-footer,
    .queue-display-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        margin-top: auto;
        padding: 14px clamp(16px, 1.1vw, 28px);
        border-top: 1px solid #eef2f7;
        background: #f9fbff;
        min-height: 72px;
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
        height: 24px;
        width: auto;
        object-fit: contain;
    }

    .footer-alert-pill {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 10px 18px;
        border-radius: 999px;
        background: #eef5fb;
        border: 1px solid #bfdbfe;
        color: var(--display-blue);
        font-size: 14px;
        font-weight: 700;
        min-width: 0;
    }

    .footer-alert-pill svg {
        width: 18px;
        height: 18px;
        color: var(--display-blue);
        flex: none;
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
        font-weight: 800;
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
        border-radius: 8px;
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
        border-radius: 34px;
        padding: 42px;
        text-align: center;
        border: 8px solid #dbeafe;
        box-shadow: 0 35px 90px rgba(15, 23, 42, 0.35);
    }

    .patient-popup-label {
        font-size: 24px;
        font-weight: 900;
        color: var(--display-blue);
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .patient-popup-token {
        margin: 12px 0;
        font-size: 88px;
        font-weight: 900;
        color: var(--display-blue);
        line-height: 1;
    }

    .patient-popup-card h2 {
        margin: 0;
        font-size: 40px;
        font-weight: 900;
        color: #0f172a;
    }

    .patient-popup-card p {
        margin: 12px 0 0;
        font-size: 22px;
        color: #64748b;
        font-weight: 800;
    }

    .queue-spotlight-modal {
        position: fixed;
        inset: 0;
        background: rgba(7, 15, 30, 0.58);
        display: grid;
        place-items: center;
        z-index: 60;
        padding: 18px;
    }

    .queue-spotlight-card {
        width: min(1180px, calc(100vw - 36px));
        border-radius: 8px;
        overflow: hidden;
        background: #fff;
        border: 1px solid #d9e8ff;
        box-shadow: 0 28px 72px rgba(5, 91, 217, 0.34);
    }

    .queue-spotlight-head {
        padding: 16px 20px;
        background: var(--display-blue);
        color: #fff;
        font-size: 14px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 0.06em;
    }

    .queue-spotlight-wrap {
        position: relative;
        display: grid;
        grid-template-columns: auto minmax(0, 1fr) auto;
        gap: 14px;
        align-items: center;
        padding: 18px;
    }

    .queue-spotlight-arrow {
        width: 54px;
        height: 54px;
        border-radius: 999px;
        border: 1px solid rgba(255, 255, 255, 0.24);
        background: rgba(255, 255, 255, 0.12);
        color: #fff;
        display: grid;
        place-items: center;
        cursor: pointer;
        flex: none;
    }

    .queue-spotlight-arrow svg {
        width: 20px;
        height: 20px;
    }

    .queue-spotlight-main {
        overflow: hidden;
        background: #fff;
        border-radius: 8px;
    }

    .queue-spotlight-media {
        position: relative;
        height: clamp(320px, 34vw, 430px);
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
        font-weight: 800;
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
        padding: 20px 22px 0;
        display: grid;
        gap: 12px;
    }

    .queue-spotlight-kicker {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }

    .popup-meta-chip {
        display: inline-flex;
        align-items: center;
        padding: 8px 14px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.12);
        border: 1px solid rgba(255, 255, 255, 0.18);
        font-size: clamp(12px, 0.9vw, 18px);
        font-weight: 800;
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
        display: flex;
        justify-content: space-between;
        gap: 12px;
        padding: 14px 18px 18px;
        font-size: 14px;
        font-weight: 800;
        color: #10213a;
    }

    .queue-spotlight-pagination {
        margin-top: 12px;
        text-align: center;
        font-size: 14px;
        font-weight: 800;
        color: #334155;
    }

    .queue-spotlight-dots {
        display: flex;
        gap: 8px;
        align-items: center;
        justify-content: center;
        margin-top: 14px;
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
        width: min(1180px, calc(100vw - 36px));
        border-radius: 8px;
        overflow: hidden;
        background: #fff;
        border: 1px solid #d9e8ff;
        box-shadow: 0 28px 72px rgba(5, 91, 217, 0.34);
    }

    .spotlight-wrap {
        position: relative;
        display: grid;
        grid-template-columns: auto minmax(0, 1fr) auto;
        gap: 14px;
        align-items: center;
    }

    .spotlight-arrow {
        width: 54px;
        height: 54px;
        border-radius: 999px;
        border: 1px solid rgba(255, 255, 255, 0.24);
        background: rgba(255, 255, 255, 0.12);
        color: #fff;
        display: grid;
        place-items: center;
        cursor: pointer;
        flex: none;
    }

    .spotlight-arrow svg {
        width: 20px;
        height: 20px;
    }

    .spotlight-main-card {
        overflow: hidden;
        background: #fff;
        border-radius: 18px;
    }

    .spotlight-media {
        position: relative;
        height: clamp(320px, 34vw, 430px);
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
        font-weight: 800;
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
        padding: 20px 22px 0;
        display: grid;
        gap: 12px;
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
        margin-top: 12px;
        text-align: center;
        font-size: 14px;
        font-weight: 800;
        color: #334155;
    }

    .popup-head {
        padding: 16px 20px;
        background: var(--display-blue);
        color: #fff;
        font-size: 14px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 0.06em;
    }

    .spotlight-footer {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        padding: 14px 18px 18px;
        font-size: 14px;
        font-weight: 800;
        color: #10213a;
    }

    .popup-next-card {
        width: min(650px, 92vw);
        padding: 42px;
        text-align: center;
        background: #fff;
        border-radius: 8px;
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
    }
</style>
