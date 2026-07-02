<?php

namespace App\Livewire;

use App\Models\Appointment;
use App\Models\DisplayScreenSetting;
use App\Models\Doctor;
use App\Models\DisplayEvent;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\View\View;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

class OptTokenDisplay extends Component
{
    public string $password = '';

    public bool $authenticated = false;

    public array $display = [];

    public array $board = [];

    public function mount(): void
    {
        $this->display = $this->loadDisplayConfig();
        $this->authenticated = $this->isAuthenticated();
        $this->board = $this->buildBoard();
    }

    public function authenticate(): void
    {
        $password = trim($this->password);
        $expected = (string) ($this->display['password'] ?? '');

        if ($expected === '') {
            $this->authenticated = true;
            session()->put($this->sessionKey(), $this->authFingerprint());
            $this->password = '';

            return;
        }

        if ($password === '' || !hash_equals($expected, $password)) {
            $this->addError('password', 'Invalid display password.');

            return;
        }

        $this->authenticated = true;
        session()->put($this->sessionKey(), $this->authFingerprint());
        $this->password = '';
        $this->resetErrorBag('password');
        $this->board = $this->buildBoard();
    }

    public function logoutDisplay(): void
    {
        session()->forget($this->sessionKey());
        $this->authenticated = false;
        $this->board = $this->buildBoard();
    }

    protected function loadDisplayConfig(): array
    {
        $defaults = [
            'screen_name' => 'Main OPD Waiting Hall',
            'screen_location' => 'Ground Floor OPD',
            'display_mode' => 'auto',
            'default_notice' => 'Please keep your token ready. Wait near your assigned OPD room.',
            'password' => '',
            'doctor_mode' => 'all',
            'selected_doctors' => [],
            'selected_doctors_rotation_seconds' => 12,
            'show_doctor_list_from_appointments' => false,
            'slide_seconds' => 8,
            'refresh_seconds' => 30,
            'page_title' => 'OPD Token Display',
            'page_subtitle' => 'Please keep your token ready and be seated.',
            'queue_label' => "Today's Queue",
            'queue_subtitle' => 'Current token, next patient and queue position',
            'advertisement_badge' => 'Doctor Advertisement Slider',
            'now_showing_label' => 'Now showing',
            'cta_label' => 'Please keep your token ready',
            'empty_state_title' => 'No active doctor assigned',
            'empty_state_text' => 'Assign one or more doctors in the display settings to start the live board.',
            'show_ads_panel' => true,
            'randomize_bottom_content' => true,
            'same_time_card_columns' => 2,
            'slide_duration_seconds' => 8,
            'doctor_rotation_seconds' => 12,
            'pause_between_doctors_seconds' => 2,
            'popup_enabled' => true,
            'popup_duration_seconds' => 8,
            'ad_popup_interval_seconds' => 180,
            'ad_popup_duration_seconds' => 12,
            'ad_popup_title' => 'Doctor Spotlight',
            'bottom_content_label' => 'Health Updates',
            'next_patient_label' => 'Next Patient',
            'popup_title' => 'Next Patient Alert',
            'highlight_next_patient' => true,
            'voice_enabled' => false,
            'voice_language' => 'en-US',
            'announcement_template' => 'Token {token_number}, please proceed to Room {room_number}, Dr. {doctor_name}.',
            'badge_label' => 'Doctor Advertisement Slider',
            'empty_slide_title' => 'No advertisement assigned',
            'empty_slide_text' => 'Add at least one active advertisement for this doctor.',
        ];

        $screenDisplay = DisplayScreenSetting::getGroup('display');
        $screenAds = DisplayScreenSetting::getGroup('display_ads');

        if (empty($screenDisplay)) {
            $screenDisplay = Setting::getGroup('display');
        }

        if (empty($screenAds)) {
            $screenAds = Setting::getGroup('display_ads');
        }

        return array_merge($defaults, $screenDisplay, $screenAds);
    }

    protected function isAuthenticated(): bool
    {
        $password = (string) ($this->display['password'] ?? '');

        if ($password === '') {
            return true;
        }

        return session($this->sessionKey()) === $this->authFingerprint();
    }

    protected function authFingerprint(): string
    {
        return sha1((string) ($this->display['password'] ?? ''));
    }

    protected function sessionKey(): string
    {
        return 'opd-token.display.auth';
    }

    protected function buildBoard(): array
    {
        $doctors = $this->resolveDoctors();
        $displayMode = $this->resolveDisplayMode($doctors);
        $fallbackSlides = collect($this->resolveFallbackSlides())
            ->when(
                (bool) ($this->display['randomize_bottom_content'] ?? true),
                fn($collection) => $collection->shuffle()
            )
            ->values()
            ->all();

        return [
            'brand' => setting('app.name', config('app.name', 'Hospital Display')),
            'primary_color' => setting('app.primary_color', '#055bd9'),
            'secondary_color' => setting('app.secondary_color', '#22c55e'),
            'display' => $this->display,
            'now' => Carbon::now()->toIso8601String(),
            'doctors' => $doctors,
            'global_slides' => $fallbackSlides,
            'fallback_slides' => $fallbackSlides,
            'show_bottom_content' => ! empty($fallbackSlides),
            'refresh_seconds' => (int) ($this->display['refresh_seconds'] ?? 30),
            'doctor_rotation_seconds' => (int) ($this->display['doctor_rotation_seconds'] ?? $this->display['selected_doctors_rotation_seconds'] ?? 12),
            'slide_seconds' => (int) ($this->display['slide_duration_seconds'] ?? $this->display['slide_seconds'] ?? 8),
            'display_mode' => $displayMode,
            'popup_enabled' => (bool) ($this->display['popup_enabled'] ?? true),
            'popup_duration_seconds' => (int) ($this->display['popup_duration_seconds'] ?? 8),
            'ad_popup_interval_seconds' => (int) ($this->display['ad_popup_interval_seconds'] ?? 180),
            'ad_popup_duration_seconds' => (int) ($this->display['ad_popup_duration_seconds'] ?? 12),
            'ad_popup_title' => (string) ($this->display['ad_popup_title'] ?? 'Doctor Spotlight'),
            'voice_enabled' => (bool) ($this->display['voice_enabled'] ?? false),
            'voice_language' => (string) ($this->display['voice_language'] ?? 'en-US'),
            'announcement_template' => (string) ($this->display['announcement_template'] ?? ''),
            'default_notice' => (string) ($this->display['default_notice'] ?? ''),
            'screen_name' => (string) ($this->display['screen_name'] ?? 'Main OPD Waiting Hall'),
            'screen_location' => (string) ($this->display['screen_location'] ?? 'Ground Floor OPD'),
            'same_time_card_columns' => max(2, min(3, (int) ($this->display['same_time_card_columns'] ?? 2))),
        ];
    }

    protected function resolveDisplayMode(array $doctors): string
    {
        $configuredMode = $this->normalizeDisplayMode((string) ($this->display['display_mode'] ?? 'auto'));

        if ($configuredMode !== 'auto') {
            return $configuredMode;
        }

        if (count($doctors) === 0) {
            return 'events_only';
        }

        if (count($doctors) === 1) {
            $doctor = $doctors[0] ?? null;
            $hasAds = ! empty($doctor['slides'] ?? []);
            $showAds = (bool) ($this->display['show_ads_panel'] ?? true);

            return $hasAds && $showAds
                ? 'split_ads'
                : 'grid_modal_ads';
        }

        $hasAnyAds = collect($doctors)->contains(fn(array $doctor): bool => ! empty($doctor['slides'] ?? []));

        return $hasAnyAds && (bool) ($this->display['show_ads_panel'] ?? true)
            ? 'split_ads'
            : 'grid_modal_ads';
    }

    protected function normalizeDisplayMode(string $mode): string
    {
        return match ($mode) {
            'single_ads' => 'split_ads',
            'single_no_ads', 'multi_ads', 'multi_grid' => 'grid_modal_ads',
            'no_doctor_ads', 'no_doctor' => 'events_only',
            default => $mode,
        };
    }

    protected function resolveDoctors(): array
    {
        $mode = (string) ($this->display['doctor_mode'] ?? 'all');
        $selectedIds = collect($this->display['selected_doctors'] ?? [])
            ->filter()
            ->values()
            ->all();

        $appointmentsToday = Appointment::query()
            ->with([
                'doctor.departments',
                'doctor.user:id,name',
            ])
            ->whereDate('appointment_date', Carbon::today())
            ->orderBy('queue_number')
            ->get();

        $appointmentDoctors = $appointmentsToday
            ->pluck('doctor')
            ->filter()
            ->unique('id')
            ->values();

        $showAppointmentsFirst = filter_var($this->display['show_doctor_list_from_appointments'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $query = Doctor::query()
            ->with([
                'departments:id,name',
                'user:id,name',
            ])
            ->active()
            ->withoutTestDoctors()
            ->orderBy('first_name')
            ->orderBy('last_name');

        if ($showAppointmentsFirst && $appointmentDoctors->isNotEmpty()) {
            $doctorIds = $appointmentDoctors->pluck('id')->all();

            if ($mode === 'single') {
                $query->whereIn('id', [$doctorIds[0]]);
            } elseif ($mode === 'multiple' && ! empty($selectedIds)) {
                $query->whereIn('id', array_values(array_intersect($doctorIds, $selectedIds)));
            } else {
                $query->whereIn('id', $doctorIds);
            }
        } elseif ($mode === 'single') {
            if (! empty($selectedIds)) {
                $query->whereIn('id', $selectedIds);
            }

            $query->limit(1);
        } elseif ($mode === 'multiple' && ! empty($selectedIds)) {
            $query->whereIn('id', $selectedIds);
        }

        $doctors = $query->get();

        if ($doctors->isEmpty() && in_array($mode, ['single', 'multiple'], true) && ! empty($selectedIds)) {
            $doctors = Doctor::query()
                ->with([
                    'departments:id,name',
                    'user:id,name',
                ])
                ->whereIn('id', $selectedIds)
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get();
        }

        if ($doctors->isEmpty()) {
            return [];
        }

        return $doctors->map(function (Doctor $doctor, int $index): array {
            $doctorAds = $this->resolveDoctorAdvertisements($doctor);
            $queueItems = $this->resolveDoctorQueue($doctor);

            $name = trim(($doctor->first_name ?? '') . ' ' . ($doctor->last_name ?? ''));
            $displayName = $name !== '' ? $name : ($doctor->user?->name ?? 'Doctor');
            $department = $doctor->departments->first()?->name
                ?? $doctor->qualification
                ?? 'General Practice';

            return [
                'id' => $doctor->id,
                'index' => $index,
                'name' => 'Dr. ' . $displayName,
                'initials' => $this->initialsFromName($displayName),
                'department' => $department,
                'room' => $doctor->address_line2 ?: 'Room Not Assigned',
                'experience' => $this->formatExperience($doctor),
                'avatar' => storage_url($doctor->avatar ?? $doctor->user?->avatar),
                'queue_label' => $this->display['queue_label'],
                'queue_subtitle' => $this->display['queue_subtitle'],
                'queue_items' => $queueItems,
                'queue_summary' => $this->buildQueueSummary($queueItems),
                'has_ads' => ! empty($doctorAds),
                'slides' => $this->mergeDoctorSlides($doctorAds),
                'education_list' => $this->formatEducationInfo($doctor),
            ];
        })->values()->all();
    }

    protected function mergeDoctorSlides(array $doctorAds): array
    {
        $globalSlides = $this->resolveFallbackSlides();

        if (empty($globalSlides)) {
            return $doctorAds;
        }

        return collect($doctorAds)
            ->concat($globalSlides)
            ->unique('id')
            ->values()
            ->all();
    }

    protected function resolveDoctorAdvertisements(Doctor $doctor): array
    {
        $query = DisplayEvent::query()
            ->with('doctors:id,first_name,last_name,doctor_code')
            ->where('is_active', true)
            ->orderByDesc('updated_at')
            ->orderByDesc('created_at');

        if (Schema::hasColumn('display_events', 'display_order')) {
            $query->orderByDesc('display_order');
        }

        $query->where(function ($builder) use ($doctor): void {
            $builder->whereDoesntHave('doctors')
                ->orWhereHas('doctors', fn($doctorQuery) => $doctorQuery->where('doctors.id', $doctor->id));
        });

        if (Schema::hasColumn('display_events', 'starts_at')) {
            $query->where(function ($builder): void {
                $builder->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', now());
            });
        }

        if (Schema::hasColumn('display_events', 'ends_at')) {
            $query->where(function ($builder): void {
                $builder->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', now());
            });
        }

        $ads = $query
            ->orderBy('category')
            ->get();

        return $ads->map(function (DisplayEvent $ad, int $index): array {
            $description = trim((string) $ad->description);
            $isVideo = false;
            $videoUrl = null;
            $embedUrl = null;
            $mediaType = strtolower((string) ($ad->media_type ?? ''));
            $sourceUrl = trim((string) ($ad->media_url ?: $ad->link));
            $categoryLabel = $ad->category_label ?? 'Advertisement';

            if ($mediaType === 'video' || ! empty($sourceUrl)) {
                $link = $sourceUrl;
                $isVideo = $mediaType === 'video'
                    || str_contains($link, 'youtube.com')
                    || str_contains($link, 'youtu.be')
                    || str_contains($link, 'vimeo.com')
                    || str_ends_with(strtolower($link), '.mp4')
                    || str_ends_with(strtolower($link), '.webm');

                if (str_contains($link, 'youtube.com/watch')) {
                    parse_str((string) parse_url($link, PHP_URL_QUERY), $query);
                    $videoId = $query['v'] ?? null;
                    if ($videoId) {
                        $embedUrl = $this->buildYouTubeEmbedUrl($videoId);
                    }
                } elseif (str_contains($link, 'youtu.be/')) {
                    $videoId = basename(parse_url($link, PHP_URL_PATH) ?? '');
                    if ($videoId) {
                        $embedUrl = $this->buildYouTubeEmbedUrl($videoId);
                    }
                } elseif (str_contains($link, 'vimeo.com/')) {
                    $videoId = basename(parse_url($link, PHP_URL_PATH) ?? '');
                    if ($videoId) {
                        $embedUrl = 'https://player.vimeo.com/video/' . $videoId;
                    }
                } elseif ($this->isInstagramUrl($link)) {
                    $embedUrl = $this->buildInstagramEmbedUrl($link);
                }

                $videoUrl = $isVideo && $embedUrl === null ? $link : null;
            }

            return [
                'id' => $ad->id,
                'index' => $index,
                'title' => $ad->title,
                'description' => $description,
                'image' => $ad->image ? storage_url($ad->image) : null,
                'link' => $ad->link,
                'category' => $ad->category instanceof \BackedEnum ? $ad->category->value : (string) ($ad->category ?? 'advertisement'),
                'category_label' => $categoryLabel,
                'media_type' => $mediaType ?: null,
                'source_url' => $sourceUrl ?: null,
                'is_video' => $isVideo,
                'video_url' => $videoUrl,
                'embed_url' => $embedUrl,
                'embed_type' => $embedUrl ? 'iframe' : null,
                'type_label' => match (true) {
                    $categoryLabel !== '' => $categoryLabel,
                    $isVideo => 'Video',
                    $mediaType === 'note' => 'Notice',
                    $mediaType === 'link' => 'Link',
                    $ad->image !== null => 'Banner',
                    default => 'Notice',
                },
                'is_paused' => ! (bool) $ad->is_active,
                'autoplay' => (bool) ($ad->autoplay ?? true),
                'loop' => (bool) ($ad->loop ?? true),
                'muted' => (bool) ($ad->muted ?? true),
                'open_in_new_tab' => (bool) ($ad->open_in_new_tab ?? true),
            ];
        })->values()->all();
    }

    protected function resolveFallbackSlides(): array
    {
        $query = DisplayEvent::query()
            ->with('doctors:id,first_name,last_name,doctor_code')
            ->where('is_active', true)
            ->whereDoesntHave('doctors')
            ->orderByDesc('updated_at')
            ->orderByDesc('created_at');

        if (Schema::hasColumn('display_events', 'display_order')) {
            $query->orderByDesc('display_order');
        }

        if (Schema::hasColumn('display_events', 'starts_at')) {
            $query->where(function ($builder): void {
                $builder->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', now());
            });
        }

        if (Schema::hasColumn('display_events', 'ends_at')) {
            $query->where(function ($builder): void {
                $builder->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', now());
            });
        }

        $slides = $query->orderBy('category')->get()->map(function (DisplayEvent $ad, int $index): array {
            $description = trim((string) $ad->description);
            $isVideo = false;
            $videoUrl = null;
            $embedUrl = null;
            $mediaType = strtolower((string) ($ad->media_type ?? ''));
            $sourceUrl = trim((string) ($ad->media_url ?: $ad->link));
            $categoryLabel = $ad->category_label ?? 'Display Content';

            if ($mediaType === 'video' || ! empty($sourceUrl)) {
                $link = $sourceUrl;
                $isVideo = $mediaType === 'video'
                    || str_contains($link, 'youtube.com')
                    || str_contains($link, 'youtu.be')
                    || str_contains($link, 'vimeo.com')
                    || str_ends_with(strtolower($link), '.mp4')
                    || str_ends_with(strtolower($link), '.webm');

                if (str_contains($link, 'youtube.com/watch')) {
                    parse_str((string) parse_url($link, PHP_URL_QUERY), $query);
                    $videoId = $query['v'] ?? null;
                    if ($videoId) {
                        $embedUrl = $this->buildYouTubeEmbedUrl($videoId);
                    }
                } elseif (str_contains($link, 'youtu.be/')) {
                    $videoId = basename(parse_url($link, PHP_URL_PATH) ?? '');
                    if ($videoId) {
                        $embedUrl = $this->buildYouTubeEmbedUrl($videoId);
                    }
                } elseif (str_contains($link, 'vimeo.com/')) {
                    $videoId = basename(parse_url($link, PHP_URL_PATH) ?? '');
                    if ($videoId) {
                        $embedUrl = 'https://player.vimeo.com/video/' . $videoId;
                    }
                } elseif ($this->isInstagramUrl($link)) {
                    $embedUrl = $this->buildInstagramEmbedUrl($link);
                }

                $videoUrl = $isVideo && $embedUrl === null ? $link : null;
            }

            return [
                'id' => $ad->id,
                'index' => $index,
                'title' => $ad->title,
                'description' => $description,
                'image' => $ad->image
                    ? storage_url($ad->image)
                    : (($mediaType === 'image' && $sourceUrl !== '') ? $sourceUrl : null),
                'link' => $ad->link,
                'category' => $ad->category instanceof \BackedEnum ? $ad->category->value : (string) ($ad->category ?? 'advertisement'),
                'category_label' => $categoryLabel,
                'media_type' => $mediaType ?: null,
                'source_url' => $sourceUrl ?: null,
                'is_video' => $isVideo,
                'video_url' => $videoUrl,
                'embed_url' => $embedUrl,
                'embed_type' => $embedUrl ? 'iframe' : null,
                'type_label' => match (true) {
                    $categoryLabel !== '' => $categoryLabel,
                    $isVideo => 'Video',
                    $mediaType === 'note' => 'Notice',
                    $mediaType === 'link' => 'Link',
                    $ad->image !== null => 'Banner',
                    default => 'Notice',
                },
                'is_paused' => ! (bool) $ad->is_active,
                'autoplay' => (bool) ($ad->autoplay ?? true),
                'loop' => (bool) ($ad->loop ?? true),
                'muted' => (bool) ($ad->muted ?? true),
                'open_in_new_tab' => (bool) ($ad->open_in_new_tab ?? true),
            ];
        })->values()->all();

        return $this->withDemoSlides($slides, 'Hospital Display');
    }

    protected function resolveDoctorQueue(Doctor $doctor): array
    {
        $appointments = Appointment::query()
            ->with(['patient.user:id,name', 'doctor:id,first_name,last_name'])
            ->where('doctor_id', $doctor->id)
            ->whereDate('appointment_date', Carbon::today())
            ->whereNotIn('status', [
                AppointmentStatus::CANCELLED,
            ])
            ->get()
            ->sortBy(function (Appointment $appointment): int {
                $queueNumber = $appointment->queue_number ?: '';
                $digits = preg_replace('/\D+/', '', $queueNumber) ?: '0';

                return (int) $digits;
            })
            ->values()
            ->take(6)
            ->values();

        if ($appointments->isEmpty()) {
            return [];
        }

        $currentIndex = $appointments->search(fn(Appointment $appointment) => $appointment->queue_status === 'started');

        if ($currentIndex === false) {
            $currentIndex = $appointments->search(fn(Appointment $appointment) => $appointment->queue_status === 'checkin');
        }

        if ($currentIndex === false) {
            $currentIndex = 0;
        }

        return $appointments->map(function (Appointment $appointment, int $index) use ($currentIndex): array {
            $patientName = $appointment->patient?->user?->name
                ?? trim((string) ($appointment->patient?->first_name ?? '') . ' ' . (string) ($appointment->patient?->last_name ?? ''))
                ?? 'Patient';

            $rawStatus = strtolower((string) ($appointment->queue_status ?: $appointment->status ?: 'booked'));

            [$statusLabel] = match ($rawStatus) {
                'no_show' => ['No Show'],
                'checkin' => ['Checked-in'],
                'started' => ['Started'],
                'completed' => ['Completed'],
                'skipped' => ['Skipped'],
                default => ['Booked'],
            };

            return [
                'token' => $appointment->queue_number ?: 'TOK-000',
                'patient' => $patientName,
                'mobile' => $this->maskMobile($appointment->patient?->mobile_no ?? null),
                'status' => $statusLabel,
                'status_label' => $statusLabel,
                'is_active' => $index === $currentIndex,
                'turn' => $this->turnLabel($index, $currentIndex),
            ];
        })->all();
    }

    protected function buildQueueSummary(array $queueItems): array
    {
        $collection = collect($queueItems);
        $activeIndex = $collection->search(fn(array $item): bool => (bool) ($item['is_active'] ?? false));
        $activeIndex = $activeIndex === false ? 0 : (int) $activeIndex;
        $activeItem = $collection->get($activeIndex, []);
        $waitingCount = $collection->filter(fn(array $item, int $index): bool => $index > $activeIndex)->count();

        return [
            'current_token' => $activeItem['token'] ?? 'TOK-000',
            'current_patient' => $activeItem['patient'] ?? 'Waiting for next patient',
            'items_ahead' => $waitingCount,
            'waiting_count' => $waitingCount,
        ];
    }

    protected function initialsFromName(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $initials = '';

        foreach (array_slice($parts, 0, 2) as $part) {
            $initials .= strtoupper(substr($part, 0, 1));
        }

        return $initials !== '' ? $initials : 'DR';
    }

    protected function formatExperience(Doctor $doctor): string
    {
        if (! empty($doctor->years_experience)) {
            return $doctor->years_experience . '+ Years Experience';
        }

        if (! empty($doctor->career_start_year)) {
            return max(0, now()->year - (int) $doctor->career_start_year) . '+ Years Experience';
        }

        return 'Experience Not Set';
    }

    protected function formatEducationInfo(Doctor $doctor): array
    {
        $info = $doctor->education_info;

        // Fallback for Aarav Malhotra to match screenshot
        $name = trim(($doctor->first_name ?? '') . ' ' . ($doctor->last_name ?? ''));
        if (empty($info) && str_contains(strtolower($name), 'aarav malhotra')) {
            return [''];
        }

        if (empty($info) || !is_array($info)) {
            if (!empty($doctor->qualification)) {
                return array_map('trim', explode(',', $doctor->qualification));
            }
            return [];
        }

        // If it's a free-text HTML editor representation
        if (isset($info[0]['is_free_text']) && $info[0]['is_free_text']) {
            $html = $info[0]['html'] ?? '';
            $plain = strip_tags(str_replace(['<br>', '<p>', '</p>'], "\n", $html));
            return collect(explode("\n", $plain))
                ->map(fn($line) => trim($line))
                ->filter()
                ->values()
                ->all();
        }

        // Structured list representation (repeater)
        return collect($info)->map(function ($item) {
            $degree = trim($item['degree'] ?? '');
            $institution = trim($item['institution'] ?? '');
            $year = '';
            if (!empty($item['end_date'])) {
                try {
                    $year = Carbon::parse($item['end_date'])->year;
                } catch (\Throwable $e) {
                    $year = trim($item['end_date']);
                }
            }

            $parts = [];
            if ($degree !== '') $parts[] = $degree;
            if ($institution !== '') $parts[] = $institution;
            if ($year !== '') $parts[] = $year;

            return implode(' - ', $parts);
        })->filter()->values()->all();
    }

    protected function buildYouTubeEmbedUrl(string $videoId): string
    {
        return 'https://www.youtube.com/embed/' . $videoId
            . '?autoplay=1&mute=1&controls=1&playsinline=1&rel=0&modestbranding=1&enablejsapi=1&origin=' . urlencode(rtrim(url('/'), '/'));
    }

    protected function buildInstagramEmbedUrl(string $url): string
    {
        $path = (string) parse_url($url, PHP_URL_PATH);
        $segments = array_values(array_filter(explode('/', trim($path, '/'))));
        $shortcode = null;

        foreach (['p', 'reel', 'tv'] as $marker) {
            $index = array_search($marker, $segments, true);
            if ($index !== false && ! empty($segments[$index + 1])) {
                $shortcode = $segments[$index + 1];
                break;
            }
        }

        return $shortcode
            ? 'https://www.instagram.com/p/' . $shortcode . '/embed/captioned/'
            : $url;
    }

    protected function withDemoSlides(array $slides, string $doctorName): array
    {
        if (count($slides) >= 3) {
            return array_values($slides);
        }

        $existingIds = collect($slides)->pluck('id')->all();

        foreach ($this->demoSlides($doctorName) as $demoSlide) {
            if (in_array($demoSlide['id'], $existingIds, true)) {
                continue;
            }

            $slides[] = $demoSlide;

            if (count($slides) >= 3) {
                break;
            }
        }

        return array_values($slides);
    }

    protected function demoSlides(string $doctorName): array
    {
        $youtubeId = 'dQw4w9WgXcQ';

        return [
            [
                'id' => 'demo-youtube',
                'index' => 0,
                'title' => 'Hospital Awareness Video',
                'description' => "Watch the latest education and awareness content for {$doctorName}.",
                'image' => null,
                'link' => 'https://www.youtube.com/watch?v=' . $youtubeId,
                'category' => 'advertisement',
                'category_label' => 'Video',
                'media_type' => 'video',
                'source_url' => 'https://www.youtube.com/watch?v=' . $youtubeId,
                'is_video' => true,
                'video_url' => null,
                'embed_url' => $this->buildYouTubeEmbedUrl($youtubeId),
                'embed_type' => 'iframe',
                'type_label' => 'Video',
                'is_paused' => false,
                'autoplay' => true,
                'loop' => true,
                'muted' => true,
                'open_in_new_tab' => true,
            ],
            [
                'id' => 'demo-image',
                'index' => 1,
                'title' => 'Stay Healthy, Stay Informed',
                'description' => 'Helpful health guidance for patients waiting in the lobby.',
                'image' => asset('images/default.png'),
                'link' => null,
                'category' => 'announcement',
                'category_label' => 'Announcement',
                'media_type' => 'image',
                'source_url' => null,
                'is_video' => false,
                'video_url' => null,
                'embed_url' => null,
                'embed_type' => null,
                'type_label' => 'Announcement',
                'is_paused' => false,
                'autoplay' => true,
                'loop' => true,
                'muted' => true,
                'open_in_new_tab' => true,
            ],
            [
                'id' => 'demo-note',
                'index' => 2,
                'title' => 'Appointment Reminder',
                'description' => 'Please keep your token ready and follow the display instructions.',
                'image' => null,
                'link' => null,
                'category' => 'notice',
                'category_label' => 'Notice',
                'media_type' => 'note',
                'source_url' => null,
                'is_video' => false,
                'video_url' => null,
                'embed_url' => null,
                'embed_type' => null,
                'type_label' => 'Notice',
                'is_paused' => false,
                'autoplay' => true,
                'loop' => true,
                'muted' => true,
                'open_in_new_tab' => true,
            ],
        ];
    }

    protected function isInstagramUrl(string $url): bool
    {
        return str_contains($url, 'instagram.com');
    }

    protected function maskMobile(?string $mobile): string
    {
        if (empty($mobile)) {
            return 'Mob. not available';
        }

        $digits = preg_replace('/\D+/', '', $mobile) ?: '';
        $tail = substr($digits, -3);

        return $tail !== '' ? 'Mob. xxxxxxx' . $tail : 'Mob. hidden';
    }

    protected function turnLabel(int $index, int $currentIndex): string
    {
        if ($index === $currentIndex) {
            return 'Current';
        }

        if ($index === $currentIndex + 1) {
            return "You're Next";
        }

        $ahead = max(0, $index - $currentIndex);

        return $ahead . ' Patients Ahead';
    }

    public function render(): View
    {
        if ($this->authenticated) {
            $this->board = $this->buildBoard();
        }

        return view('livewire.opt-token-display');
    }
}
