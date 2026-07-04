<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $board['display']['page_title'] ?? 'Opt Token Display' }}</title>
    @php
        $favicon = \App\Services\SettingService::getFavicon() ?: asset('images/fav-icon.png');
    @endphp
    <link rel="icon" type="image/png" href="{{ $favicon }}">
    <link rel="apple-touch-icon" href="{{ $favicon }}">
    @vite(['resources/css/app.css'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    @include('components.display.queue-display.styles')
    </head>
    <body>
    @php
        $board = $board ?? [];
        $refreshSeconds = max(10, (int) ($board['refresh_seconds'] ?? 30));
        $displayMode = (string) ($board['display_mode'] ?? 'events_only');
        $boardJson = json_encode(
            $board,
            JSON_THROW_ON_ERROR
            | JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
            | JSON_HEX_TAG
            | JSON_HEX_AMP
            | JSON_HEX_APOS
            | JSON_HEX_QUOT
        );
    @endphp
    <div class="display-shell">
        <x-display.queue-display.voice-announcer
            :enabled="(bool) ($board['voice_enabled'] ?? false)"
            :language="$board['voice_language'] ?? 'en-US'"
            :voice-name="$board['voice_name'] ?? ''"
            :template="$board['announcement_template'] ?? 'Token {token_number}, please proceed to Room {room_number}, Dr. {doctor_name}.'"
        />
        @if (! $authenticated)
            <div class="auth-shell">
                <div class="auth-card">
                    <h1 class="auth-title">{{ $board['display']['page_title'] ?? 'OPD Token Display' }}</h1>
                    <p class="auth-copy">{{ $board['display']['page_subtitle'] ?? 'Please keep your token ready and be seated.' }}</p>

                    <form class="auth-row" method="POST" action="{{ $authenticateAction ?? route('opt-token.authenticate') }}">
                        @csrf
                        <div>
                            <input
                                type="password"
                                name="password"
                                class="auth-input"
                                placeholder="Enter display password"
                                autocomplete="current-password"
                            >
                            @if (!empty($passwordError))
                                <div class="error-text mt-2">{{ $passwordError }}</div>
                            @endif
                        </div>

                        <button type="submit" class="auth-button">
                            Unlock Display
                        </button>
                    </form>

                    <div class="screen-note">
                        This screen is password protected and can be configured from the admin settings.
                    </div>

                    <div class="auth-brand">
                        <div class="footer-brand">
                            <img src="{{ asset('images/queue-images/powered_by_logo.jpg') }}" alt="Deploy Meta Logo">
                        </div>
                    </div>
                </div>
            </div>
        @else
            <script type="application/json" id="opt-token-board-data">{!! $boardJson !!}</script>
            @if($displayMode === 'split_ads')
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

                <div class="layout single-ads-layout">
                    <aside class="left-column">
                        <x-display.queue-display.topbar />
                        <div class="left_column_body">
                            <!-- Redesigned Standalone Doctor Card -->
                            <x-display.queue-display.doctor-card
                                class="fade-enter"
                                :alpine="true"
                                variant="split"
                                :show-queue="true"
                                :show-footer="true"
                            />

                            <div class="no-doctor-state fade-enter" x-show="!currentDoctor()" x-cloak>
                                <div class="pill accent" style="width:max-content;" x-text="displayCopy.bottom_content_label || 'Hospital Updates'"></div>
                                <h2 x-text="displayCopy.empty_state_title || 'No active doctor assigned'"></h2>
                                <p x-text="displayCopy.empty_state_text || 'Please wait while we show hospital updates and the next available content.'"></p>
                            </div>
                        </div>
                    </aside>
                    <main class="panel media-panel" style="position: relative; background: #000; overflow: hidden; border-radius: 0; box-shadow: none; border: none;">
                        <!-- Full-bleed Slide Container -->
                        <div style="width: 100%; height: 100%; position: relative;">
                            <template x-if="currentSlide()">
                                <div style="width: 100%; height: 100%;">
                                    <!-- Image Slide -->
                                    <template x-if="currentSlide().image && !currentSlide().video_url && !isIframeSlide(currentSlide())">
                                        <img :src="currentSlide().image" style="width: 100%; height: 100%; object-fit: cover; display: block;" :alt="currentSlide().title">
                                    </template>

                                    <!-- Video Slide -->
                                    <template x-if="currentSlide().video_url && !isIframeSlide(currentSlide())">
                                        <video
                                            :src="currentSlide().video_url"
                                            :autoplay="currentSlide()?.autoplay ?? true"
                                            :muted="currentSlide()?.muted ?? true"
                                            :loop="false"
                                            playsinline
                                            style="width: 100%; height: 100%; object-fit: cover; display: block;"
                                            @ended="advanceSlide()"
                                        ></video>
                                    </template>

                                    <!-- Iframe/YouTube Slide -->
                                    <template x-if="isIframeSlide(currentSlide())">
                                        <iframe
                                            :src="currentSlide().embed_url"
                                            style="width: 100%; height: 100%; border: none; display: block;"
                                            allow="autoplay; encrypted-media; picture-in-picture; fullscreen"
                                            @load="bindMediaFrame($el, currentSlide())"
                                        ></iframe>
                                    </template>

                                    <!-- Fallback slide (Notice/Text Only) -->
                                    <template x-if="!currentSlide().image && !currentSlide().video_url && !isIframeSlide(currentSlide())">
                                        <div style="display:grid;place-items:center;width:100%;height:100%;padding:40px;text-align:center;background:linear-gradient(135deg,#fff4ec,#fde7d8);color:#7d4754;font-weight:800;">
                                            <div>
                                                <div style="font-size: clamp(30px, 2.7vw, 58px); line-height: 1.04; color:#9d3749; font-family: Georgia, serif;" x-text="spotlightText(currentSlide()?.title || 'Doctor Spotlight')"></div>
                                                <div style="margin-top: 14px; font-size: clamp(16px, 1.15vw, 26px); line-height:1.55;" x-text="spotlightText(currentSlide()?.description || displayCopy.empty_slide_text)"></div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </template>

                            <template x-if="!currentSlide()">
                                <div style="display:grid;place-items:center;width:100%;height:100%;padding:40px;text-align:center;background:linear-gradient(135deg,#fff4ec,#fde7d8);color:#7d4754;font-weight:800;">
                                    <div>
                                        <h3 style="font-size: 24px; font-weight: 700; margin-bottom: 8px;">No Ads Available</h3>
                                        <p>Active advertisements for this doctor will be shown here.</p>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <!-- Premium Glassmorphic Slide Dots Indicators Overlay -->
                        <div class="slide-dots-overlay" x-show="slidesForCurrentDoctor().length > 1">
                            <template x-for="(slide, idx) in slidesForCurrentDoctor()" :key="slide.id + '-stage-' + idx">
                                <span class="dot" :class="{ 'active': idx === slideIndex }" style="width: 8px; height: 8px; border-radius: 50%; display: inline-block; transition: all 0.3s;" :style="idx === slideIndex ? 'background: #ffffff; width: 24px; border-radius: 4px;' : 'background: rgba(255,255,255,0.5);'"></span>
                            </template>
                        </div>
                    </main>
                </div>

                <!-- Next Patient Popup -->
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
            @elseif($displayMode === 'grid_modal_ads')
            <div id="opt-token-board-root" data-opt-token-board-root x-data="optTokenBoard()" x-init="initStatic()" :class="{ 'board-refreshing': isRefreshing }" class="h-screen max-h-screen flex flex-col overflow-hidden">
                <div class="board-refresh-badge" x-show="isRefreshing" x-cloak>
                    <span class="pulse"></span>
                    Syncing queue
                </div>

                <x-display.queue-display.topbar />

                <div class="layout" style="grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0px; padding: 0px; overflow: hidden;">
                    @for($panelIndex = 0; $panelIndex < 2; $panelIndex++)
                        <template x-for="panelRenderKey in [gridPanelRenderKey({{ $panelIndex }})]" :key="'grid-panel-' + {{ $panelIndex }} + '-' + panelRenderKey">
                            <aside class="panel doctor-panel" data-grid-panel="{{ $panelIndex }}" style="border-radius:0px; overflow: hidden;">
                                <x-display.queue-display.doctor-card
                                    class="fade-enter"
                                    :alpine="true"
                                    variant="split"
                                    :show-topbar="false"
                                    :show-queue="true"
                                    :show-footer="false"
                                    :doctor-accessor="'gridPanelDoctor(' . $panelIndex . ')'"
                                    focused-expression="isGridPanelFocusedQueueItem({{ $panelIndex }}, item)"
                                    sync-queue-expression="syncGridPanelQueueFocus({{ $panelIndex }}, true)"
                                />
                            </aside>
                        </template>
                    @endfor
                </div>

                <x-display.queue-display.doctor-card-footer
                    :alpine="true"
                />
            </div>
            @elseif($displayMode === 'doctor_schedule_sidebar')
                @include('livewire.opt-token-display-schedule')
            @elseif($displayMode === 'events_only')
            <div id="opt-token-board-root" data-opt-token-board-root x-data="optTokenBoard()" x-init="initStatic()" :class="{ 'board-refreshing': isRefreshing }" class="h-screen max-h-screen flex flex-col overflow-hidden">
                <div class="board-refresh-badge" x-show="isRefreshing" x-cloak>
                    <span class="pulse"></span>
                    Syncing queue
                </div>
                <div class="topbar">
                    <div class="brand">
                       <img src="{{ asset('images/white-logo.png') }}" alt="logo" class="brand-logo">
                    </div>
                    <div class="topbar-actions">
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
                        <button
                            type="button"
                            class="control-button icon-control"
                            @click="prevGlobalSlide()"
                            aria-label="Previous event"
                        >
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                        <button
                            type="button"
                            class="control-button icon-control"
                            @click="nextGlobalSlide()"
                            aria-label="Next event"
                        >
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M9 6l6 6-6 6" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                        <button
                            type="button"
                            class="control-button"
                            :class="{ 'is-paused': adsPaused }"
                            @click="toggleAds()"
                            x-text="adsPaused ? 'Restart' : 'Pause'"
                        ></button>
                        <div class="clock">
                            <div x-text="timeText"></div>
                            <div x-text="dateText"></div>
                        </div>
                    </div>
                </div>

                <div class="layout events-layout">
                    <main class="panel" style="padding:16px;">
                        <div class="section-head">
                            <div>
                                <div>{{ $board['screen_name'] ?? 'Main OPD Waiting Hall' }}</div>
                                <small>{{ $board['display']['bottom_content_label'] ?? 'Health Updates' }}</small>
                            </div>
                            <div class="text-right">
                                <div>{{ count($board['global_slides'] ?? []) }} Live Events</div>
                                <small>{{ $board['default_notice'] ?? 'Announcements, ads, and video content play here.' }}</small>
                            </div>
                        </div>

                        <div class="events-stage">
                            <div class="events-stage-media">
                                <template x-if="currentGlobalSlide() && currentGlobalSlide().embed_url && currentGlobalSlide().embed_type === 'iframe'">
                                    <iframe
                                        :src="currentGlobalSlide().embed_url"
                                        title="Event content"
                                        allow="autoplay; encrypted-media; picture-in-picture; fullscreen"
                                        referrerpolicy="strict-origin-when-cross-origin"
                                        @load="bindGlobalMediaFrame($el, currentGlobalSlide())"
                                    ></iframe>
                                </template>
                                <template x-if="currentGlobalSlide() && currentGlobalSlide().video_url && !(currentGlobalSlide().embed_url && currentGlobalSlide().embed_type === 'iframe')">
                                    <video
                                        :src="currentGlobalSlide().video_url"
                                        :autoplay="currentGlobalSlide()?.autoplay ?? true"
                                        :muted="currentGlobalSlide()?.muted ?? true"
                                        :loop="false"
                                        playsinline
                                        controls
                                        @ended="advanceGlobalSlide()"
                                    ></video>
                                </template>
                                <template x-if="currentGlobalSlide() && currentGlobalSlide().image && !(currentGlobalSlide().embed_url && currentGlobalSlide().embed_type === 'iframe') && !currentGlobalSlide().video_url">
                                    <img :src="currentGlobalSlide().image" :alt="currentGlobalSlide()?.title || 'Event content'">
                                </template>
                                <template x-if="currentGlobalSlide() && !currentGlobalSlide().image && !currentGlobalSlide().video_url && !(currentGlobalSlide().embed_url && currentGlobalSlide().embed_type === 'iframe')">
                                    <div style="display:grid;place-items:center;height:100%;padding:24px;text-align:left;background:linear-gradient(135deg,#07245a,#0b74ff);color:#fff;font-weight:800;">
                                        <div style="max-width:min(900px,86%);">
                                            <div class="pill accent" style="width:max-content;background:rgba(255,255,255,0.14);color:#fff;border-color:rgba(255,255,255,0.18);" x-text="currentGlobalSlide()?.category_label || 'Content'"></div>
                                            <div class="events-stage-title" x-text="spotlightText(currentGlobalSlide()?.title || 'Live Announcements')"></div>
                                            <div class="events-stage-desc" x-text="spotlightText(currentGlobalSlide()?.description || displayCopy.default_notice || 'Announcements, ads, and events are displayed here.')"></div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                            <div class="events-stage-overlay"></div>
                            <div class="events-stage-copy">
                                <div class="pill accent" style="width:max-content;background:rgba(255,255,255,0.14);color:#fff;border-color:rgba(255,255,255,0.18);" x-text="currentGlobalSlide()?.category_label || 'Content'"></div>
                                <div class="events-stage-title" x-text="spotlightText(currentGlobalSlide()?.title || 'Live Announcements')"></div>
                                <div class="events-stage-desc" x-text="spotlightText(currentGlobalSlide()?.description || displayCopy.default_notice || 'Announcements, ads, and events are displayed here.')"></div>
                            </div>
                        </div>

                        <div class="events-rail">
                            <div class="bottom-grid" style="padding:0; grid-template-columns: repeat(3, minmax(0, 1fr));">
                                <template x-for="slide in previewGlobalSlides(3)" :key="slide.id">
                                    <div class="mini-card" style="padding:0; overflow:hidden; min-height:240px; background:#fff;">
                                        <template x-if="slide.image">
                                            <img :src="slide.image" :alt="slide.title || 'Display content'" style="width:100%;height:140px;object-fit:cover;display:block;">
                                        </template>
                                        <template x-if="!slide.image">
                                            <div style="width:100%;height:140px;display:grid;place-items:center;background:#eef5ff;color:var(--display-blue);font-weight:1000;">
                                                <span x-text="slide.category_label || displayCopy.bottom_content_label"></span>
                                            </div>
                                        </template>
                                        <div style="padding:16px 18px;">
                                            <div class="pill accent" style="margin-bottom:10px;" x-text="slide.category_label || 'Content'"></div>
                                            <div class="bottom-slider-title" style="font-size:clamp(18px,1.35vw,28px);" x-text="spotlightText(slide.title || 'Display content')"></div>
                                            <div class="bottom-slider-text" style="font-size:clamp(13px,0.95vw,18px);" x-text="spotlightText(slide.description || '')"></div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </main>
                </div>
            </div>
            @elseif($displayMode === 'emergency')
            <div id="opt-token-board-root" data-opt-token-board-root x-data="optTokenBoard()" x-init="initStatic()" :class="{ 'board-refreshing': isRefreshing }" class="h-screen max-h-screen flex flex-col overflow-hidden">
                <div class="board-refresh-badge" x-show="isRefreshing" x-cloak>
                    <span class="pulse"></span>
                    Syncing queue
                </div>
                <div class="topbar">
                    <div class="brand">
                       <img src="{{ asset('images/white-logo.png') }}" alt="logo" class="w-100 h-10  inline-block">
                    </div>
                    <div class="clock">
                        <div x-text="timeText"></div>
                        <div x-text="dateText"></div>
                    </div>
                </div>
                <div style="flex:1;display:grid;place-items:center;padding:24px;">
                    <div style="width:100%;min-height:70vh;border-radius:24px;background:#b91c1c;color:#fff;display:grid;place-items:center;text-align:center;padding:40px;">
                        <div>
                            <div style="font-size:68px;font-weight:900;line-height:1.05;">Emergency Notice</div>
                            <p style="margin:18px auto 0;max-width:900px;font-size:28px;font-weight:800;line-height:1.35;">
                                {{ $board['default_notice'] ?: 'Please wait for staff instructions. This screen is temporarily showing an urgent message.' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            @else
                @include('livewire.opt-token-display-split-ads', ['board' => $board, 'boardJson' => $boardJson, 'refreshSeconds' => $refreshSeconds])
            @endif
        @endif
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('optTokenBoard', () => ({
                doctors: [],
                displayCopy: {},
                doctorRotationSeconds: 12,
                slideSeconds: 8,
                adPopupIntervalSeconds: 180,
                adPopupDurationSeconds: 12,
                adsPaused: false,
                doctorIndex: 0,
                slideIndex: 0,
                spotlightIndex: 0,
                globalSlideIndex: 0,
                timeText: '',
                dateText: '',
                clockTimer: null,
                doctorTimer: null,
                globalSlideTimer: null,
                slideAdvanceTimer: null,
                popupSyncTimer: null,
                refreshTimer: null,
                refreshInFlight: false,
                isRefreshing: false,
                popupTimer: null,
                mediaFrameHandler: null,
                activeFrameSlideId: null,
                activeFrameScope: 'doctor',
                activeAdPopup: null,
                focusedQueueToken: null,
                queueCycleIndex: -1,
                queueScrollTimer: null,
                queuePauseUntil: 0,
                youtubePlayers: {},
                youtubeApiPromise: null,
                payload: null,
                activePopup: null,
                initialized: false,
                displayMode: '',
                gridPanels: [],
                gridPanelTimers: [],
                gridPanelQueueTimers: [],
                init() {
                    this.initialize(true);
                },
                initStatic() {
                    this.initialize(false);
                },
                initialize(rotateSlides) {
                    if (this.initialized) {
                        return;
                    }

                    this.initialized = true;
                    this.payload = this.readPayload();
                    this.applyPayload(this.payload);

                    const seed = this.payload.now ? new Date(this.payload.now) : new Date();
                    this.setClock(seed);
                    this.clockTimer = setInterval(() => {
                        this.setClock(new Date());
                    }, 1000);

                    this.startRefreshTimer();

                    this.mediaFrameHandler = (event) => {
                        const data = event?.data;
                        if (!data) {
                            return;
                        }

                        let payload = data;
                        if (typeof data === 'string') {
                            try {
                                payload = JSON.parse(data);
                            } catch (error) {
                                return;
                            }
                        }

                        if (payload?.event === 'onStateChange' && Number(payload?.info) === 0) {
                            if (this.activeAdPopup?.kind === 'ad') {
                                this.spotlightNextSlide();
                                return;
                            }

                            this.advanceSlide(this.activeFrameScope || 'doctor');
                        }
                    };

                    window.addEventListener('message', this.mediaFrameHandler);

                    this.popupSyncTimer = setInterval(() => {
                        this.syncPopupState();
                    }, 3000);

                    this.syncPopupState(true);
                    this.scheduleSlideAdvance();
                    this.syncRotationTimers();
                    if (!this.usesIndependentDoctorGrid()) {
                        this.syncQueueFocus(true);
                        this.startQueueScrollTimer();
                    }
                },
                applyPayload(payload) {
                    const nextPayload = payload || {};
                    const previousDoctorId = this.currentDoctor()?.id || null;
                    const previousSlideId = this.currentSlide()?.id || this.activeAdPopup?.slide_id || null;

                    this.payload = nextPayload;
                    this.doctors = nextPayload.doctors || [];
                    this.displayCopy = nextPayload.display || {};
                    this.displayMode = String(nextPayload.display_mode || '');
                    this.doctorRotationSeconds = Number(nextPayload.doctor_rotation_seconds || 12);
                    this.slideSeconds = Number(nextPayload.slide_seconds || 8);
                    this.adPopupIntervalSeconds = Math.max(30, Number(nextPayload.ad_popup_interval_seconds || 180));
                    this.adPopupDurationSeconds = Math.max(5, Number(nextPayload.ad_popup_duration_seconds || 12));

                    if (!this.adsEnabled()) {
                        this.activeAdPopup = null;
                    }

                    if (!this.patientPopupEnabled()) {
                        this.activePopup = null;
                    }

                    if (this.doctors.length === 0) {
                        this.doctorIndex = 0;
                        this.slideIndex = 0;
                    } else if (previousDoctorId) {
                        const matchingDoctorIndex = this.doctors.findIndex((doctor) => String(doctor.id) === String(previousDoctorId));
                        this.doctorIndex = matchingDoctorIndex >= 0 ? matchingDoctorIndex : Math.min(this.doctorIndex, this.doctors.length - 1);
                    } else {
                        this.doctorIndex = Math.min(this.doctorIndex, this.doctors.length - 1);
                    }

                    if (this.doctors.length && this.currentDoctor()?.slides?.length) {
                        this.slideIndex = Math.min(this.slideIndex, this.currentDoctor().slides.length - 1);
                    } else {
                        this.slideIndex = 0;
                    }

                    if (previousSlideId) {
                        const currentSlides = this.currentDoctor()?.slides || this.payload?.fallback_slides || [];
                        const matchingSlideIndex = currentSlides.findIndex((slide) => String(slide.id) === String(previousSlideId));
                        if (matchingSlideIndex >= 0) {
                            this.slideIndex = matchingSlideIndex;
                        }
                    }

                    this.syncRotationTimers();
                    this.scheduleSlideAdvance();
                    if (!this.usesIndependentDoctorGrid()) {
                        this.syncQueueFocus(true);
                        this.startQueueScrollTimer();
                    }
                },
                syncRotationTimers() {
                    this.clearGridPanelTimers();

                    if (this.doctorTimer) {
                        clearInterval(this.doctorTimer);
                        this.doctorTimer = null;
                    }

                    if (this.globalSlideTimer) {
                        clearInterval(this.globalSlideTimer);
                        this.globalSlideTimer = null;
                    }

                    if (this.usesIndependentDoctorGrid()) {
                        this.syncGridPanels();
                    } else if (this.doctors.length) {
                        this.doctorTimer = setInterval(() => {
                            this.doctorIndex = (this.doctorIndex + 1) % this.doctors.length;
                            this.slideIndex = 0;
                            this.scheduleSlideAdvance();
                            this.focusedQueueToken = null;
                            this.queueCycleIndex = -1;
                            this.queuePauseUntil = Date.now() + 1200;
                            if (this.patientPopupEnabled()) {
                                this.showPopup(this.buildPatientPopup(this.currentDoctor()));
                            }
                            this.startQueueScrollTimer();
                        }, Math.max(5, this.doctorRotationSeconds) * 1000);
                    }

                    this.globalSlideTimer = setInterval(() => {
                        if (this.adsPaused) {
                            return;
                        }

                        const slide = this.currentGlobalSlide();
                        const globalSlides = this.globalSlides();
                        if (globalSlides.length && !this.isVideoSlide(slide)) {
                            this.globalSlideIndex = (this.globalSlideIndex + 1) % globalSlides.length;
                        }
                    }, Math.max(4, this.slideSeconds) * 1000);
                },
                readPayload() {
                    const raw = document.getElementById('opt-token-board-data')?.textContent || '{}';

                    try {
                        return JSON.parse(raw);
                    } catch (error) {
                        return {};
                    }
                },
                startRefreshTimer() {
                    const refreshSeconds = Math.max(10, Number(this.payload?.refresh_seconds || 30));
                    this.clearRefreshTimer();
                    this.refreshTimer = setInterval(() => {
                        this.refreshBoard();
                    }, refreshSeconds * 1000);
                },
                clearRefreshTimer() {
                    if (this.refreshTimer) {
                        clearInterval(this.refreshTimer);
                        this.refreshTimer = null;
                    }
                },
                clearQueueScrollTimer() {
                    if (this.queueScrollTimer) {
                        clearInterval(this.queueScrollTimer);
                        this.queueScrollTimer = null;
                    }
                },
                startQueueScrollTimer() {
                    this.clearQueueScrollTimer();

                    const items = this.currentQueueItems();
                    if (items.length <= 1) {
                        this.focusedQueueToken = items[0]?.token || this.focusedQueueToken || null;
                        this.scrollQueueTokenIntoView(this.focusedQueueToken);
                        return;
                    }

                    const currentToken = this.focusedQueueToken || this.queuePopupToken() || items[0]?.token || null;
                    const currentIndex = items.findIndex((item) => String(item.token || '') === String(currentToken || ''));
                    this.queueCycleIndex = currentIndex >= 0 ? currentIndex : 0;
                    this.focusedQueueToken = items[this.queueCycleIndex]?.token || null;
                    this.scrollQueueTokenIntoView(this.focusedQueueToken);

                    this.queueScrollTimer = setInterval(() => {
                        const queueItems = this.currentQueueItems();
                        if (!queueItems.length) {
                            this.clearQueueScrollTimer();
                            return;
                        }

                        if (Date.now() < this.queuePauseUntil) {
                            return;
                        }

                        const popupToken = this.queuePopupToken();
                        const currentItemIndex = queueItems.findIndex((item) => String(item.token || '') === String(this.focusedQueueToken || ''));
                        const nextIndex = currentItemIndex >= 0
                            ? (currentItemIndex + 1) % queueItems.length
                            : 0;
                        const nextItem = queueItems[nextIndex];

                        this.queueCycleIndex = nextIndex;
                        this.focusedQueueToken = nextItem?.token || null;
                        this.scrollQueueTokenIntoView(this.focusedQueueToken);

                        if (popupToken && String(nextItem?.token || '') === String(popupToken)) {
                            this.queuePauseUntil = Date.now() + 1800;
                            if (this.activePopup?.kind !== 'patient' || String(this.activePopup?.current_token || '') !== String(popupToken)) {
                                this.showPopup(this.buildPatientPopup(this.currentDoctor()));
                            }
                        }
                    }, 1100);
                },
                async refreshBoard() {
                    if (this.refreshInFlight) {
                        return;
                    }

                    this.refreshInFlight = true;
                    this.isRefreshing = true;

                    try {
                        const response = await fetch(window.location.href, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Cache-Control': 'no-cache',
                            },
                            credentials: 'same-origin',
                        });

                        if (!response.ok) {
                            return;
                        }

                        const html = await response.text();
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');
                        const nextRoot = doc.querySelector('[data-opt-token-board-root]');
                        const currentRoot = document.querySelector('[data-opt-token-board-root]');
                        const nextPayloadRaw = doc.getElementById('opt-token-board-data')?.textContent || '{}';
                        let nextPayload = {};

                        try {
                            nextPayload = JSON.parse(nextPayloadRaw);
                        } catch (error) {
                            nextPayload = {};
                        }

                        if (!nextRoot || !currentRoot) {
                            return;
                        }

                        currentRoot.innerHTML = nextRoot.innerHTML;

                        if (window.Alpine?.initTree) {
                            window.Alpine.initTree(currentRoot);
                        }

                        this.applyPayload(nextPayload);
                    } catch (error) {
                        // Keep the current board on transient refresh errors.
                    } finally {
                        window.setTimeout(() => {
                            this.isRefreshing = false;
                        }, 260);
                        this.refreshInFlight = false;
                    }
                },
                setClock(date) {
                    this.timeText = date.toLocaleTimeString('en-US', {
                        hour: 'numeric',
                        minute: '2-digit',
                        second: '2-digit',
                    });

                    this.dateText = date.toLocaleDateString('en-US', {
                        weekday: 'long',
                        day: '2-digit',
                        month: 'long',
                        year: 'numeric',
                    });
                },
                currentDoctor() {
                    return this.doctors[this.doctorIndex] || null;
                },
                usesIndependentDoctorGrid() {
                    return this.displayMode === 'grid_modal_ads';
                },
                adsEnabled() {
                    return !['grid_modal_ads', 'doctor_schedule_sidebar'].includes(this.displayMode);
                },
                patientPopupEnabled() {
                    return this.displayMode !== 'grid_modal_ads' && Boolean(this.payload?.popup_enabled);
                },
                spotlightText(value) {
                    return String(value ?? '')
                        .replace(/<[^>]*>/g, ' ')
                        .replace(/&nbsp;/gi, ' ')
                        .replace(/\s+/g, ' ')
                        .trim();
                },
                formatQual(qual) {
                    if (!qual) return '';
                    const parts = qual.split(' - ');
                    if (parts.length > 1) {
                        return `<strong>${parts[0]}</strong> - ${parts.slice(1).join(' - ')}`;
                    }
                    return qual;
                },
                syncGridPanels(force = false) {
                    if (!this.usesIndependentDoctorGrid()) {
                        return;
                    }

                    const previousPanels = Array.isArray(this.gridPanels) ? this.gridPanels : [];
                    const panelCount = 2;
                    const doctorCount = this.doctors.length;
                    const nextPanels = [];

                    for (let panelIndex = 0; panelIndex < panelCount; panelIndex += 1) {
                        const previousPanel = previousPanels[panelIndex] || {};
                        const fallbackDoctorIndex = doctorCount
                            ? (panelIndex % doctorCount)
                            : 0;
                        let doctorIndex = doctorCount
                            ? Math.min(Number(previousPanel.doctorIndex ?? fallbackDoctorIndex), doctorCount - 1)
                            : 0;

                        if (doctorCount > 1) {
                            const usedDoctorIndices = nextPanels.map((panel) => panel.doctorIndex);
                            let attempts = 0;

                            while (usedDoctorIndices.includes(doctorIndex) && attempts < doctorCount) {
                                doctorIndex = (doctorIndex + 1) % doctorCount;
                                attempts += 1;
                            }
                        }

                        nextPanels.push({
                            doctorIndex,
                            renderKey: Number(previousPanel.renderKey || 0),
                            focusedQueueToken: force ? null : (previousPanel.focusedQueueToken || null),
                            queueCycleIndex: force ? -1 : Number(previousPanel.queueCycleIndex ?? -1),
                            queuePauseUntil: force ? 0 : Number(previousPanel.queuePauseUntil || 0),
                        });
                    }

                    this.gridPanels = nextPanels;
                    this.startGridPanelTimers();

                    this.$nextTick(() => {
                        this.gridPanels.forEach((panel, panelIndex) => {
                            this.syncGridPanelQueueFocus(panelIndex, true);
                            this.startGridPanelQueueScroll(panelIndex);
                        });
                    });
                },
                clearGridPanelTimers() {
                    this.gridPanelTimers.forEach((timerId) => clearTimeout(timerId));
                    this.gridPanelTimers = [];

                    this.gridPanelQueueTimers.forEach((timerId) => clearInterval(timerId));
                    this.gridPanelQueueTimers = [];
                },
                startGridPanelTimers() {
                    this.clearGridPanelTimers();

                    if (!this.usesIndependentDoctorGrid() || this.doctors.length <= 1) {
                        return;
                    }

                    const rotationMs = Math.max(5, this.doctorRotationSeconds) * 1000;
                    const staggerMs = Math.max(1500, Math.floor(rotationMs / Math.max(2, this.gridPanels.length)));

                    this.gridPanels.forEach((panel, panelIndex) => {
                        const loop = () => {
                            this.advanceGridPanelDoctor(panelIndex);
                            this.gridPanelTimers[panelIndex] = setTimeout(loop, rotationMs);
                        };

                        const initialDelay = panelIndex === 0 ? rotationMs : (rotationMs + (staggerMs * panelIndex));
                        this.gridPanelTimers[panelIndex] = setTimeout(loop, initialDelay);
                    });
                },
                gridPanelDoctor(panelIndex) {
                    const panel = this.gridPanels[panelIndex];
                    if (!panel || !this.doctors.length) {
                        return null;
                    }

                    return this.doctors[panel.doctorIndex] || this.doctors[0] || null;
                },
                gridPanelRenderKey(panelIndex) {
                    return this.gridPanels[panelIndex]?.renderKey || 0;
                },
                resolveNextGridPanelDoctorIndex(panelIndex, delta = 1) {
                    const panel = this.gridPanels[panelIndex];
                    const doctorCount = this.doctors.length;

                    if (!panel || !doctorCount) {
                        return 0;
                    }

                    let candidate = (panel.doctorIndex + delta + doctorCount) % doctorCount;

                    if (doctorCount <= 1) {
                        return candidate;
                    }

                    const activeDoctorIndices = this.gridPanels
                        .filter((_, index) => index !== panelIndex)
                        .map((item) => item.doctorIndex);

                    let attempts = 0;
                    while (activeDoctorIndices.includes(candidate) && attempts < doctorCount) {
                        candidate = (candidate + delta + doctorCount) % doctorCount;
                        attempts += 1;
                    }

                    return candidate;
                },
                advanceGridPanelDoctor(panelIndex, delta = 1) {
                    const panel = this.gridPanels[panelIndex];
                    if (!panel || !this.doctors.length) {
                        return;
                    }

                    panel.doctorIndex = this.resolveNextGridPanelDoctorIndex(panelIndex, delta);
                    panel.renderKey += 1;
                    panel.focusedQueueToken = null;
                    panel.queueCycleIndex = -1;
                    panel.queuePauseUntil = Date.now() + 900;

                    this.$nextTick(() => {
                        this.syncGridPanelQueueFocus(panelIndex, true);
                        this.startGridPanelQueueScroll(panelIndex);
                    });
                },
                gridPanelQueueItems(panelIndex) {
                    return this.gridPanelDoctor(panelIndex)?.queue_items || [];
                },
                gridPanelQueuePopupToken(panelIndex) {
                    const doctor = this.gridPanelDoctor(panelIndex);
                    const summary = doctor?.queue_summary || {};

                    return summary.popup_token
                        || summary.current_token
                        || summary.next_token
                        || null;
                },
                isGridPanelFocusedQueueItem(panelIndex, item) {
                    const panel = this.gridPanels[panelIndex];
                    const token = String(item?.token || '');
                    const focusedToken = String(panel?.focusedQueueToken || this.gridPanelQueuePopupToken(panelIndex) || '');

                    return token !== '' && focusedToken !== '' && token === focusedToken;
                },
                syncGridPanelQueueFocus(panelIndex, force = false, token = null) {
                    const panel = this.gridPanels[panelIndex];
                    if (!panel) {
                        return;
                    }

                    const nextToken = token || this.gridPanelQueuePopupToken(panelIndex) || this.gridPanelQueueItems(panelIndex)[0]?.token || null;

                    if (!nextToken) {
                        if (force || !panel.focusedQueueToken) {
                            panel.focusedQueueToken = null;
                        }
                        return;
                    }

                    if (!force && panel.focusedQueueToken === nextToken) {
                        return;
                    }

                    panel.focusedQueueToken = nextToken;

                    this.$nextTick(() => {
                        this.scrollGridPanelQueueTokenIntoView(panelIndex, nextToken);
                    });
                },
                scrollGridPanelQueueTokenIntoView(panelIndex, token) {
                    if (!token) {
                        return;
                    }

                    const root = document.querySelector(`[data-grid-panel="${panelIndex}"]`);
                    const container = root?.querySelector('.queue-list-container');

                    if (!container) {
                        return;
                    }

                    const rows = Array.from(container.querySelectorAll('[data-queue-token]'));
                    const row = rows.find((item) => String(item.dataset.queueToken || '') === String(token));

                    if (!row) {
                        return;
                    }

                    row.classList.remove('queue-row-focus-flash');
                    void row.offsetWidth;
                    row.classList.add('queue-row-focus-flash');

                    row.scrollIntoView({
                        behavior: this.isRefreshing ? 'auto' : 'smooth',
                        block: 'center',
                        inline: 'nearest',
                    });
                },
                startGridPanelQueueScroll(panelIndex) {
                    if (this.gridPanelQueueTimers[panelIndex]) {
                        clearInterval(this.gridPanelQueueTimers[panelIndex]);
                        this.gridPanelQueueTimers[panelIndex] = null;
                    }

                    const panel = this.gridPanels[panelIndex];
                    const items = this.gridPanelQueueItems(panelIndex);
                    if (!panel) {
                        return;
                    }

                    if (items.length <= 1) {
                        panel.focusedQueueToken = items[0]?.token || panel.focusedQueueToken || null;
                        this.scrollGridPanelQueueTokenIntoView(panelIndex, panel.focusedQueueToken);
                        return;
                    }

                    const currentToken = panel.focusedQueueToken || this.gridPanelQueuePopupToken(panelIndex) || items[0]?.token || null;
                    const currentIndex = items.findIndex((item) => String(item.token || '') === String(currentToken || ''));
                    panel.queueCycleIndex = currentIndex >= 0 ? currentIndex : 0;
                    panel.focusedQueueToken = items[panel.queueCycleIndex]?.token || null;
                    this.scrollGridPanelQueueTokenIntoView(panelIndex, panel.focusedQueueToken);

                    this.gridPanelQueueTimers[panelIndex] = setInterval(() => {
                        const queueItems = this.gridPanelQueueItems(panelIndex);
                        const state = this.gridPanels[panelIndex];

                        if (!state || !queueItems.length) {
                            clearInterval(this.gridPanelQueueTimers[panelIndex]);
                            this.gridPanelQueueTimers[panelIndex] = null;
                            return;
                        }

                        if (Date.now() < state.queuePauseUntil) {
                            return;
                        }

                        const currentItemIndex = queueItems.findIndex((item) => String(item.token || '') === String(state.focusedQueueToken || ''));
                        const nextIndex = currentItemIndex >= 0
                            ? (currentItemIndex + 1) % queueItems.length
                            : 0;
                        const nextItem = queueItems[nextIndex];

                        state.queueCycleIndex = nextIndex;
                        state.focusedQueueToken = nextItem?.token || null;
                        this.scrollGridPanelQueueTokenIntoView(panelIndex, state.focusedQueueToken);
                    }, 1100);
                },
                doctorById(doctorId) {
                    if (doctorId === null || doctorId === undefined) {
                        return null;
                    }

                    return this.doctors.find((doctor) => String(doctor.id) === String(doctorId)) || null;
                },
                spotlightDoctor() {
                    return this.doctorById(this.activePopup?.doctor_id) || this.currentDoctor();
                },
                spotlightSlides() {
                    const doctor = this.spotlightDoctor();

                    if (doctor?.slides?.length) {
                        return doctor.slides;
                    }

                    return this.payload?.fallback_slides || [];
                },
                spotlightSlideIndex() {
                    const slides = this.spotlightSlides();

                    if (!slides.length) {
                        return 0;
                    }

                    return this.spotlightIndex % slides.length;
                },
                spotlightCurrentSlide() {
                    const slides = this.spotlightSlides();

                    if (!slides.length) {
                        return null;
                    }

                    return slides[this.spotlightSlideIndex()] || null;
                },
                spotlightSlideAt(offset = 0) {
                    const slides = this.spotlightSlides();

                    if (!slides.length) {
                        return null;
                    }

                    const index = (this.spotlightSlideIndex() + offset + slides.length) % slides.length;

                    return slides[index] || null;
                },
                spotlightPrevSlide() {
                    const slides = this.spotlightSlides();

                    if (!slides.length) {
                        return;
                    }

                    this.spotlightIndex = (this.spotlightIndex - 1 + slides.length) % slides.length;
                    this.syncSpotlightPopup();
                },
                spotlightNextSlide() {
                    const slides = this.spotlightSlides();

                    if (!slides.length) {
                        return;
                    }

                    this.spotlightIndex = (this.spotlightIndex + 1) % slides.length;
                    this.syncSpotlightPopup();
                },
                announceCurrentPatient() {
                    if (!this.patientPopupEnabled() || !this.payload?.voice_enabled || !('speechSynthesis' in window)) {
                        return;
                    }

                    const popup = this.activePopup?.kind === 'patient'
                        ? this.activePopup
                        : this.buildPatientPopup(this.currentDoctor());

                    if (!popup) {
                        return;
                    }

                    if (this.activePopup?.kind !== 'patient') {
                        this.showPopup(popup);
                        return;
                    }

                    window.dispatchEvent(new CustomEvent('display-voice-announce', { detail: popup }));
                },
                prevDoctor() {
                    if (!this.doctors.length) {
                        return;
                    }

                    this.doctorIndex = (this.doctorIndex - 1 + this.doctors.length) % this.doctors.length;
                    this.slideIndex = 0;
                    this.scheduleSlideAdvance();
                    this.syncPopupState(true);
                },
                nextDoctor() {
                    if (!this.doctors.length) {
                        return;
                    }

                    this.doctorIndex = (this.doctorIndex + 1) % this.doctors.length;
                    this.slideIndex = 0;
                    this.scheduleSlideAdvance();
                    this.syncPopupState(true);
                },
                prevSlide() {
                    this.advanceSlide('doctor', -1);
                },
                nextSlide() {
                    this.advanceSlide('doctor', 1);
                },
                prevGlobalSlide() {
                    this.advanceSlide('global', -1);
                },
                nextGlobalSlide() {
                    this.advanceSlide('global', 1);
                },
                isIframeSlide(slide) {
                    return Boolean(slide?.embed_url && slide?.embed_type === 'iframe');
                },
                isYoutubeSlide(slide) {
                    const embedUrl = String(slide?.embed_url || '').toLowerCase();
                    return this.isIframeSlide(slide) && embedUrl.includes('youtube.com/embed');
                },
                isVideoSlide(slide) {
                    return Boolean(slide?.video_url) || this.isYoutubeSlide(slide);
                },
                loadYoutubeApi() {
                    if (window.YT && window.YT.Player) {
                        return Promise.resolve();
                    }

                    if (this.youtubeApiPromise) {
                        return this.youtubeApiPromise;
                    }

                    this.youtubeApiPromise = new Promise((resolve) => {
                        const previousReady = window.onYouTubeIframeAPIReady;

                        window.onYouTubeIframeAPIReady = () => {
                            if (typeof previousReady === 'function') {
                                previousReady();
                            }

                            resolve();
                        };

                        if (!document.querySelector('script[data-opt-token-youtube-api]')) {
                            const script = document.createElement('script');
                            script.src = 'https://www.youtube.com/iframe_api';
                            script.async = true;
                            script.defer = true;
                            script.dataset.optTokenYoutubeApi = 'true';
                            document.head.appendChild(script);
                        }
                    });

                    return this.youtubeApiPromise;
                },
                clearSlideAdvanceTimer() {
                    if (this.slideAdvanceTimer) {
                        clearTimeout(this.slideAdvanceTimer);
                        this.slideAdvanceTimer = null;
                    }
                },
                destroyYoutubePlayer(slideId) {
                    if (!slideId || !this.youtubePlayers[slideId]) {
                        return;
                    }

                    try {
                        this.youtubePlayers[slideId].destroy();
                    } catch (error) {
                        // Ignore player teardown errors during rerender.
                    }

                    delete this.youtubePlayers[slideId];
                },
                scheduleSlideAdvance() {
                    this.clearSlideAdvanceTimer();

                    if (!this.adsEnabled()) {
                        this.activeAdPopup = null;
                        return;
                    }

                    if (this.adsPaused) {
                        return;
                    }

                    if (this.activeAdPopup?.kind === 'ad') {
                        const slide = this.spotlightCurrentSlide();

                        if (!slide) {
                            return;
                        }

                        if (this.isVideoSlide(slide)) {
                            // Safety 30-second timeout for video slides
                            this.slideAdvanceTimer = setTimeout(() => {
                                this.spotlightNextSlide();
                            }, 30000);
                            return;
                        }

                        this.slideAdvanceTimer = setTimeout(() => {
                            this.spotlightNextSlide();
                        }, Math.max(4, this.slideSeconds) * 1000);

                        return;
                    }

                    const slide = this.currentSlide();
                    if (!slide) {
                        return;
                    }

                    if (this.isVideoSlide(slide)) {
                        // Safety 30-second timeout for video slides
                        this.slideAdvanceTimer = setTimeout(() => {
                            this.advanceSlide();
                        }, 30000);
                        return;
                    }

                    this.slideAdvanceTimer = setTimeout(() => {
                        this.advanceSlide();
                    }, Math.max(4, this.slideSeconds) * 1000);
                },
                bindMediaFrame(el, slide, scope = 'doctor') {
                    const slideId = slide?.id || slide?.slide_id || null;
                    this.activeFrameSlideId = slideId;
                    this.activeFrameScope = scope;

                    if (!el || !this.isIframeSlide(slide)) {
                        return;
                    }

                    if (!this.isYoutubeSlide(slide) || !slideId) {
                        return;
                    }

                    if (this.youtubePlayers[slideId]) {
                        return;
                    }

                    this.loadYoutubeApi().then(() => {
                        if (!window.YT?.Player || this.youtubePlayers[slideId]) {
                            return;
                        }

                        if (this.currentSlide()?.id !== slideId && this.activeAdPopup?.slide_id !== slideId) {
                            return;
                        }

                        try {
                            this.youtubePlayers[slideId] = new window.YT.Player(el, {
                                events: {
                                    onStateChange: (event) => {
                                        if (event?.data === window.YT.PlayerState.ENDED) {
                                            this.destroyYoutubePlayer(slideId);
                                            this.advanceSlide(scope);
                                        }
                                    },
                                },
                            });
                        } catch (error) {
                            this.destroyYoutubePlayer(slideId);
                        }
                    });
                },
                bindGlobalMediaFrame(el, slide) {
                    this.bindMediaFrame(el, slide, 'global');
                },
                syncPopupState(force = false) {
                    if (!this.doctors.length) {
                        return;
                    }

                    if (!this.patientPopupEnabled()) {
                        this.activePopup = null;
                    }

                    const storageKey = 'opt-token-popup-state';
                    let previous = {};
                    try {
                        previous = JSON.parse(window.localStorage.getItem(storageKey) || '{}');
                    } catch (error) {
                        previous = {};
                    }
                    const nextState = {};
                    let changedDoctor = null;

                    this.doctors.forEach((doctor) => {
                        const summary = doctor.queue_summary || {};
                        const popupToken = summary.popup_token ?? summary.current_token ?? summary.next_token ?? null;
                        nextState[doctor.id] = popupToken;

                        if (!changedDoctor && popupToken && previous[doctor.id] && previous[doctor.id] !== popupToken) {
                            changedDoctor = doctor;
                        }

                        if (!changedDoctor && force && popupToken && !previous[doctor.id]) {
                            changedDoctor = doctor;
                        }
                    });

                    window.localStorage.setItem(storageKey, JSON.stringify(nextState));

                    if (changedDoctor && this.patientPopupEnabled()) {
                        this.showPopup(this.buildPatientPopup(changedDoctor));
                    }

                    if (!this.adsEnabled()) {
                        this.activeAdPopup = null;
                        return;
                    }

                    const doctor = this.currentDoctor();
                    if (!this.payload?.ad_popup_enabled || this.adsPaused || !this.payload?.popup_enabled) {
                        return;
                    }

                    const adPopup = this.buildAdPopup(doctor);
                    if (!adPopup) {
                        return;
                    }

                    const adStateKey = 'opt-token-ad-popup-state';
                    let adState = {};
                    try {
                        adState = JSON.parse(window.localStorage.getItem(adStateKey) || '{}');
                    } catch (error) {
                        adState = {};
                    }
                    const lastShownAt = Number(adState[doctor.id] || 0);
                    const now = Date.now();

                    if (!lastShownAt) {
                        adState[doctor.id] = now;
                        window.localStorage.setItem(adStateKey, JSON.stringify(adState));
                        return;
                    }

                    if (now - lastShownAt >= this.adPopupIntervalSeconds * 1000) {
                        this.showPopup(adPopup);
                        adState[doctor.id] = now;
                        window.localStorage.setItem(adStateKey, JSON.stringify(adState));
                    }
                },
                buildPatientPopup(doctor) {
                    if (!doctor) {
                        return null;
                    }

                    const summary = doctor.queue_summary || {};
                    const popupToken = summary.popup_token ?? summary.current_token ?? summary.next_token ?? 'TOK-000';
                    const popupPatient = summary.popup_patient ?? summary.current_patient ?? summary.next_patient ?? 'Waiting for next patient';
                    const popupTimeSlot = summary.popup_time_slot ?? summary.current_time_slot ?? summary.next_time_slot ?? null;
                    const popupRoom = summary.popup_room ?? summary.room_number ?? doctor.room ?? null;

                    return {
                        kind: 'patient',
                        doctor_id: doctor.id,
                        doctor_name: doctor.name || 'Doctor',
                        current_token: popupToken,
                        current_patient: popupPatient,
                        current_time_slot: popupTimeSlot,
                        next_token: summary.next_token || popupToken,
                        next_patient: summary.next_patient || popupPatient,
                        next_time_slot: summary.next_time_slot || popupTimeSlot,
                        room_number: popupRoom,
                    };
                },
                buildAdPopup(doctor, slide = null) {
                    if (!this.adsEnabled() || !doctor) {
                        return null;
                    }

                    const selectedSlide = slide || this.currentDoctorSlide(doctor);

                    if (!selectedSlide) {
                        return null;
                    }

                    return {
                        kind: 'ad',
                        doctor_id: doctor.id,
                        doctor_name: doctor.name || 'Doctor',
                        slide_id: selectedSlide.id || null,
                        title: selectedSlide.title || this.displayCopy.ad_popup_title || 'Doctor Spotlight',
                        description: selectedSlide.description || this.displayCopy.empty_slide_text || '',
                        image: selectedSlide.image || null,
                        video_url: selectedSlide.video_url || null,
                        embed_url: selectedSlide.embed_url || null,
                        embed_type: selectedSlide.embed_type || null,
                        category_label: selectedSlide.category_label || selectedSlide.type_label || 'Advertisement',
                        type_label: selectedSlide.type_label || 'Advertisement',
                        room_number: doctor.room || null,
                    };
                },
                showPopup(popup) {
                    if (!popup) {
                        return;
                    }

                    if (popup.kind === 'ad' && !this.adsEnabled()) {
                        return;
                    }

                    if (popup.kind === 'patient' && !this.patientPopupEnabled()) {
                        return;
                    }

                    if (this.popupTimer) {
                        clearTimeout(this.popupTimer);
                        this.popupTimer = null;
                    }

                    if (popup.kind === 'patient') {
                        this.activePopup = popup;
                        const popupPauseSeconds = Math.max(3, Number(this.payload?.popup_duration_seconds || 8));
                        this.queuePauseUntil = Date.now() + (popupPauseSeconds * 1000);
                        this.syncQueueFocus(true, popup.current_token || null);
                    } else if (popup.kind === 'ad') {
                        this.activeAdPopup = popup;
                    }

                    if (popup.kind === 'ad') {
                        const slides = this.spotlightSlides();
                        const matchedIndex = popup.slide_id
                            ? slides.findIndex((slide) => String(slide.id) === String(popup.slide_id))
                            : 0;
                        this.spotlightIndex = matchedIndex >= 0 ? matchedIndex : 0;
                    }

                    if (popup.kind === 'patient' && this.payload?.voice_enabled) {
                        window.dispatchEvent(new CustomEvent('display-voice-announce', { detail: popup }));
                    }

                    if (popup.kind === 'ad') {
                        this.scheduleSlideAdvance();
                        return;
                    }

                    const durationSeconds = Number(this.payload?.popup_duration_seconds || 8);

                    if (durationSeconds) {
                        this.popupTimer = setTimeout(() => {
                            this.activePopup = null;
                        }, Math.max(3, durationSeconds) * 1000);
                    }
                },
                currentQueueItems() {
                    return this.currentDoctor()?.queue_items || [];
                },
                queuePopupToken() {
                    const doctor = this.currentDoctor();
                    const summary = doctor?.queue_summary || {};

                    if (this.activePopup?.kind === 'patient' && this.activePopup?.current_token) {
                        return this.activePopup.current_token;
                    }

                    return summary.popup_token
                        || summary.current_token
                        || summary.next_token
                        || null;
                },
                isFocusedQueueItem(item) {
                    const token = String(item?.token || '');
                    const focusedToken = String(this.focusedQueueToken || this.queuePopupToken() || '');

                    return token !== '' && focusedToken !== '' && token === focusedToken;
                },
                syncQueueFocus(force = false, token = null) {
                    const nextToken = token || this.queuePopupToken() || null;

                    if (!nextToken) {
                        if (force || !this.focusedQueueToken) {
                            this.focusedQueueToken = null;
                        }
                        return;
                    }

                    if (!force && this.focusedQueueToken === nextToken) {
                        return;
                    }

                    this.focusedQueueToken = nextToken;

                    this.$nextTick(() => {
                        this.scrollQueueTokenIntoView(nextToken);
                    });
                },
                scrollQueueTokenIntoView(token) {
                    if (!token) {
                        return;
                    }

                    const root = document.querySelector('[data-opt-token-board-root]');
                    const container = root?.querySelector('.queue-list-container');

                    if (!container) {
                        return;
                    }

                    const rows = Array.from(container.querySelectorAll('[data-queue-token]'));
                    const row = rows.find((item) => String(item.dataset.queueToken || '') === String(token));

                    if (!row) {
                        return;
                    }

                    row.classList.remove('queue-row-focus-flash');
                    void row.offsetWidth;
                    row.classList.add('queue-row-focus-flash');

                    row.scrollIntoView({
                        behavior: this.isRefreshing ? 'auto' : 'smooth',
                        block: 'center',
                        inline: 'nearest',
                    });
                },
                currentDoctorSlide(doctor = null) {
                    const slides = doctor?.slides || this.slidesForCurrentDoctor();

                    if (!slides.length) {
                        return null;
                    }

                    const index = doctor && doctor.id === this.currentDoctor()?.id
                        ? this.slideIndex % slides.length
                        : 0;

                    return slides[index] || null;
                },
                syncSpotlightPopup() {
                    const doctor = this.spotlightDoctor();

                    if (!doctor) {
                        return;
                    }

                    const slide = this.spotlightCurrentSlide();

                    if (!slide) {
                        return;
                    }

                    this.showPopup(this.buildAdPopup(doctor, slide));
                },
                slidesForCurrentDoctor() {
                    const doctor = this.currentDoctor();

                    if (doctor?.slides?.length) {
                        return doctor.slides;
                    }

                    return this.payload?.fallback_slides || [];
                },
                currentSlide() {
                    const slides = this.slidesForCurrentDoctor();

                    if (!slides.length) {
                        return null;
                    }

                    return slides[this.slideIndex % slides.length] || null;
                },
                globalSlides() {
                    return this.payload?.global_slides || this.payload?.fallback_slides || [];
                },
                currentGlobalSlide() {
                    const slides = this.globalSlides();

                    if (!slides.length) {
                        return null;
                    }

                    return slides[this.globalSlideIndex % slides.length] || null;
                },
                previewGlobalSlides(limit = 3) {
                    const slides = this.globalSlides();

                    if (!slides.length) {
                        return [];
                    }

                    const count = Math.min(limit, slides.length);

                    return Array.from({ length: count }, (_, offset) => {
                        return slides[(this.globalSlideIndex + 1 + offset) % slides.length];
                    });
                },
                doctorCounterLabel() {
                    if (!this.doctors.length) {
                        return '0 / 0';
                    }

                    return `${this.doctorIndex + 1} / ${this.doctors.length}`;
                },
                toggleAds() {
                    this.adsPaused = !this.adsPaused;
                    if (this.adsPaused) {
                        this.clearSlideAdvanceTimer();
                    } else {
                        this.scheduleSlideAdvance();
                    }
                },
                advanceSlide(scope = 'doctor', delta = 1) {
                    const slides = scope === 'global'
                        ? this.globalSlides()
                        : this.slidesForCurrentDoctor();

                    if (!slides.length) {
                        return;
                    }

                    const currentSlide = scope === 'global' ? this.currentGlobalSlide() : this.currentSlide();

                    this.destroyYoutubePlayer(currentSlide?.id || this.activeAdPopup?.slide_id || null);

                    if (scope === 'global') {
                        this.globalSlideIndex = (this.globalSlideIndex + delta + slides.length) % slides.length;
                    } else {
                        this.slideIndex = (this.slideIndex + delta + slides.length) % slides.length;
                        if (this.activeAdPopup?.kind === 'ad') {
                            this.destroyYoutubePlayer(this.activeAdPopup?.slide_id || null);
                            this.activeAdPopup = null;
                        }
                    }

                    this.scheduleSlideAdvance();
                },
                advanceGlobalSlide(delta = 1) {
                    this.advanceSlide('global', delta);
                },
                formattedQuote(title) {
                    if (!title) {
                        return 'Doctor advertisement';
                    }

                    return `"${title}"`;
                },
                destroy() {
                    if (this.clockTimer) clearInterval(this.clockTimer);
                    if (this.doctorTimer) clearInterval(this.doctorTimer);
                    if (this.globalSlideTimer) clearInterval(this.globalSlideTimer);
                    this.clearGridPanelTimers();
                    this.clearQueueScrollTimer();
                    this.clearSlideAdvanceTimer();
                    if (this.popupSyncTimer) clearInterval(this.popupSyncTimer);
                    this.clearRefreshTimer();
                    if (this.popupTimer) clearTimeout(this.popupTimer);
                    if (this.mediaFrameHandler) {
                        window.removeEventListener('message', this.mediaFrameHandler);
                    }
                    Object.keys(this.youtubePlayers).forEach((slideId) => this.destroyYoutubePlayer(slideId));
                },
            }));
        });
    </script>
</body>
</html>
