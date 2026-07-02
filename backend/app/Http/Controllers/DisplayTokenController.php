<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\DisplayScreenSetting;
use App\Models\Doctor;
use App\Models\DisplayEvent;
use App\Models\Setting;
use App\Enums\AppointmentStatus;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class DisplayTokenController extends Controller
{
    public function show(Request $request): View
    {
        $display = $this->loadDisplayConfig();
        $authenticated = $this->isAuthenticated($request, $display);
        $board = $this->buildBoard($display);

        return view('livewire.opt-token-display', [
            'authenticated' => $authenticated,
            'board' => $board,
            'display' => $display,
            'passwordError' => session('display_auth_error'),
        ]);
    }

    public function boardData(Request $request): JsonResponse
    {
        $display = $this->loadDisplayConfig();

        if (! $this->isAuthenticated($request, $display)) {
            return response()->json([
                'message' => 'Unauthenticated display access.',
            ], 403);
        }

        return response()->json($this->buildBoard($display));
    }

    public function authenticate(Request $request): RedirectResponse
    {
        $display = $this->loadDisplayConfig();
        $password = trim((string) $request->input('password'));
        $expected = (string) ($display['password'] ?? '');

        if ($expected === '' || hash_equals($expected, $password)) {
            $request->session()->put($this->sessionKey(), $this->authFingerprint($display));
            Cookie::queue(cookie()->make(
                $this->cookieKey(),
                $this->authFingerprint($display),
                60 * 24 * 7
            ));

            return redirect()->route('opd-token.display');
        }

        return redirect()
            ->route('opd-token.display')
            ->with('display_auth_error', 'Invalid display password.');
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget($this->sessionKey());
        Cookie::queue(Cookie::forget($this->cookieKey()));

        return redirect()->route('opd-token.display');
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
            'show_doctor_list_from_appointments' => false,
            'refresh_seconds' => 30,
            'auto_reload_enabled' => false,
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
            'slide_duration_seconds' => 8,
            'doctor_rotation_seconds' => 12,
            'pause_between_doctors_seconds' => 2,
            'popup_enabled' => true,
            'popup_duration_seconds' => 8,
            'ad_popup_enabled' => true,
            'ad_popup_interval_seconds' => 180,
            'ad_popup_duration_seconds' => 12,
            'ad_popup_title' => 'Doctor Spotlight',
            'voice_enabled' => false,
            'voice_language' => 'en-US',
            'announcement_template' => 'Token {token_number}, please proceed to Room {room_number}, Dr. {doctor_name}.',
            'badge_label' => 'Doctor Advertisement Slider',
            'empty_slide_title' => 'No advertisement assigned',
            'empty_slide_text' => 'Add at least one active advertisement for this doctor.',
            'bottom_content_label' => 'Health Updates',
            'next_patient_label' => 'Next Patient',
            'popup_title' => 'Next Patient Alert',
            'same_time_card_columns' => 2,
            'show_slide_heading' => false,
            'show_slide_description' => false,
            'show_events_title' => false,
            'show_events_description' => false,
            'show_media_images' => true,
            'show_media_videos' => true,
        ];

        $screenDisplay = DisplayScreenSetting::getGroup('display');
        $screenAds = DisplayScreenSetting::getGroup('display_ads');

        if (empty($screenDisplay)) {
            $screenDisplay = Setting::getGroup('display');
        }

        if (empty($screenAds)) {
            $screenAds = Setting::getGroup('display_ads');
        }

        $config = array_merge($defaults, $screenDisplay, $screenAds);

        foreach (
            [
                'show_ads_panel',
                'randomize_bottom_content',
                'popup_enabled',
                'ad_popup_enabled',
                'voice_enabled',
                'show_slide_heading',
                'show_slide_description',
                'show_events_title',
                'show_events_description',
                'show_media_images',
                'show_media_videos',
                'auto_reload_enabled',
            ] as $boolKey
        ) {
            $config[$boolKey] = $this->toBool($config[$boolKey] ?? $defaults[$boolKey] ?? false);
        }

        return $config;
    }

    protected function buildBoard(array $display): array
    {
        $doctors = $this->resolveDoctors($display);
        $displayMode = $this->resolveDisplayMode($display, $doctors);
        $fallbackSlides = collect($this->resolveFallbackSlides($display))
            ->when(
                (bool) ($display['randomize_bottom_content'] ?? true),
                fn($collection) => $collection->shuffle()
            )
            ->values()
            ->all();

        return [
            'brand' => setting('app.name', config('app.name', 'Hospital Display')),
            'primary_color' => setting('app.primary_color', '#055bd9'),
            'secondary_color' => setting('app.secondary_color', '#22c55e'),
            'display' => $display,
            'now' => Carbon::now()->toIso8601String(),
            'doctors' => $doctors,
            'global_slides' => $fallbackSlides,
            'show_bottom_content' => ! empty($fallbackSlides),
            'refresh_seconds' => (int) ($display['refresh_seconds'] ?? 30),
            'doctor_rotation_seconds' => (int) ($display['doctor_rotation_seconds'] ?? 12),
            'slide_seconds' => (int) ($display['slide_duration_seconds'] ?? 8),
            'display_mode' => $displayMode,
            'popup_enabled' => (bool) ($display['popup_enabled'] ?? true),
            'popup_duration_seconds' => (int) ($display['popup_duration_seconds'] ?? 8),
            'ad_popup_enabled' => (bool) ($display['ad_popup_enabled'] ?? true),
            'ad_popup_interval_seconds' => (int) ($display['ad_popup_interval_seconds'] ?? 180),
            'ad_popup_duration_seconds' => (int) ($display['ad_popup_duration_seconds'] ?? 12),
            'ad_popup_title' => (string) ($display['ad_popup_title'] ?? 'Doctor Spotlight'),
            'voice_enabled' => (bool) ($display['voice_enabled'] ?? false),
            'voice_language' => (string) ($display['voice_language'] ?? 'en-US'),
            'announcement_template' => (string) ($display['announcement_template'] ?? ''),
            'default_notice' => (string) ($display['default_notice'] ?? ''),
            'screen_name' => (string) ($display['screen_name'] ?? 'Main OPD Waiting Hall'),
            'screen_location' => (string) ($display['screen_location'] ?? 'Ground Floor OPD'),
            'fallback_slides' => $fallbackSlides,
            'same_time_card_columns' => max(2, min(3, (int) ($display['same_time_card_columns'] ?? 2))),
        ];
    }

    protected function resolveDisplayMode(array $display, array $doctors): string
    {
        $configuredMode = $this->normalizeDisplayMode((string) ($display['display_mode'] ?? 'auto'));
        $showAds = (bool) ($display['show_ads_panel'] ?? true);

        if ($configuredMode !== 'auto') {
            return $configuredMode;
        }

        if (count($doctors) === 0) {
            return 'events_only';
        }

        if (count($doctors) === 1) {
            $doctor = $doctors[0] ?? null;
            $hasAds = ! empty($doctor['slides'] ?? []);

            return $hasAds && $showAds
                ? 'split_ads'
                : 'grid_modal_ads';
        }

        $hasAnyAds = collect($doctors)->contains(fn(array $doctor): bool => ! empty($doctor['slides'] ?? []));

        return $hasAnyAds && $showAds
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

    protected function resolveDoctors(array $display): array
    {
        $mode = (string) ($display['doctor_mode'] ?? 'all');
        $selectedIds = collect($display['selected_doctors'] ?? [])->filter()->values()->all();

        $appointmentsToday = Appointment::query()
            ->with(['doctor.departments', 'doctor.user:id,name', 'availability:id,doctor_room'])
            ->whereDate('appointment_date', Carbon::today())
            ->whereNotIn('status', [
                AppointmentStatus::COMPLETED,
                AppointmentStatus::CANCELLED,
                AppointmentStatus::NO_SHOW,
                AppointmentStatus::FAILED,
            ])
            ->where(function ($query): void {
                $query->whereIn('queue_status', ['started', 'checkin'])
                    ->orWhere(function ($nested): void {
                        $nested->whereNotNull('appointment_end_time')
                            ->where('appointment_end_time', '>=', now()->format('H:i:s'));
                    })
                    ->orWhere(function ($nested): void {
                        $nested->whereNull('appointment_end_time')
                            ->where('appointment_time', '>=', now()->format('H:i:s'));
                    });
            })
            ->orderBy('queue_number')
            ->get();

        $appointmentDoctors = $appointmentsToday->pluck('doctor')->filter()->unique('id')->values();

        $query = Doctor::query()
            ->with(['departments:id,name', 'user:id,name'])
            ->active()
            ->withoutTestDoctors()
            ->orderBy('first_name')
            ->orderBy('last_name');

        $showAppointmentsFirst = filter_var($display['show_doctor_list_from_appointments'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if ($showAppointmentsFirst && $appointmentDoctors->isNotEmpty()) {
            $doctorIds = $appointmentDoctors->pluck('id')->all();

            if ($mode === 'single') {
                $query->whereIn('id', [$doctorIds[0]]);
            } elseif ($mode === 'multiple' && ! empty($selectedIds)) {
                $query->whereIn('id', array_values(array_intersect($doctorIds, $selectedIds)));
            } elseif (! empty($selectedIds)) {
                $intersection = array_values(array_intersect($doctorIds, $selectedIds));
                $query->whereIn('id', ! empty($intersection) ? $intersection : $doctorIds);
            } else {
                $query->whereIn('id', $doctorIds);
            }
        } elseif ($mode === 'single') {
            $query->whereRaw('1 = 0');
        } elseif ($mode === 'multiple' && ! empty($selectedIds)) {
            $query->whereRaw('1 = 0');
        }

        $doctors = $query->get();

        return $doctors->map(function (Doctor $doctor, int $index) use ($display, $appointmentsToday): array {
            $doctorAds = $this->resolveDoctorAdvertisements($doctor, $display);
            $queueItems = $this->resolveDoctorQueue($doctor);
            $queueSummary = $this->buildQueueSummary($queueItems);
            $room = $queueSummary['room_number']
                ?? $appointmentsToday->firstWhere('doctor_id', $doctor->id)?->availability?->doctor_room
                ?? $doctor->address_line2
                ?? null;
            $name = trim(($doctor->first_name ?? '') . ' ' . ($doctor->last_name ?? ''));
            $displayName = $name !== '' ? $name : ($doctor->user?->name ?? 'Doctor');
            $department = $doctor->departments->first()?->name ?? $doctor->qualification ?? 'General Practice';

            return [
                'id' => $doctor->id,
                'index' => $index,
                'name' => 'Dr. ' . $displayName,
                'initials' => $this->initialsFromName($displayName),
                'department' => $department,
                'room' => $room,
                'experience' => $this->formatExperience($doctor),
                'avatar' => storage_url($doctor->avatar ?? $doctor->user?->avatar),
                'queue_label' => $display['queue_label'],
                'queue_subtitle' => $display['queue_subtitle'],
                'ads_enabled' => filter_var($display['show_ads_panel'] ?? true, FILTER_VALIDATE_BOOLEAN),
                'is_checked_in' => (bool) $doctor->is_checked_in,
                'is_on_break' => (bool) $doctor->is_on_break,
                'queue_items' => $queueItems,
                'queue_summary' => $queueSummary,
                'has_ads' => ! empty($doctorAds),
                'slides' => $this->mergeDoctorSlides($doctorAds, $display),
            ];
        })->values()->all();
    }

    protected function mergeDoctorSlides(array $doctorAds, array $display): array
    {
        $globalSlides = $this->resolveFallbackSlides($display);

        if (empty($globalSlides)) {
            return $doctorAds;
        }

        return collect($doctorAds)
            ->concat($globalSlides)
            ->unique('id')
            ->values()
            ->all();
    }

    protected function resolveDoctorAdvertisements(Doctor $doctor, array $display): array
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

        $slides = $query->orderBy('category')->get()->map(function (DisplayEvent $ad, int $index): array {
            $description = trim((string) $ad->description);
            $isVideo = false;
            $videoUrl = null;
            $embedUrl = null;
            $mediaType = strtolower((string) ($ad->media_type ?? ''));
            $sourceUrl = trim((string) ($ad->media_url ?? ''));
            $categoryLabel = $ad->category_label ?? 'Advertisement';

            if ($mediaType === 'video' || ! empty($sourceUrl)) {
                $isVideo = $mediaType === 'video'
                    || str_contains($sourceUrl, 'youtube.com')
                    || str_contains($sourceUrl, 'youtu.be')
                    || str_contains($sourceUrl, 'vimeo.com')
                    || str_ends_with(strtolower($sourceUrl), '.mp4')
                    || str_ends_with(strtolower($sourceUrl), '.webm');

                if (str_contains($sourceUrl, 'youtube.com/watch')) {
                    parse_str((string) parse_url($sourceUrl, PHP_URL_QUERY), $query);
                    $videoId = $query['v'] ?? null;
                    if ($videoId) {
                        $embedUrl = $this->buildYouTubeEmbedUrl($videoId);
                    }
                } elseif (str_contains($sourceUrl, 'youtu.be/')) {
                    $videoId = basename(parse_url($sourceUrl, PHP_URL_PATH) ?? '');
                    if ($videoId) {
                        $embedUrl = $this->buildYouTubeEmbedUrl($videoId);
                    }
                } elseif (str_contains($sourceUrl, 'vimeo.com/')) {
                    $videoId = basename(parse_url($sourceUrl, PHP_URL_PATH) ?? '');
                    if ($videoId) {
                        $embedUrl = 'https://player.vimeo.com/video/' . $videoId;
                    }
                } elseif ($this->isInstagramUrl($sourceUrl)) {
                    $embedUrl = $this->buildInstagramEmbedUrl($sourceUrl);
                }

                $videoUrl = $isVideo && $embedUrl === null ? $sourceUrl : null;
            }

            return [
                'id' => $ad->id,
                'index' => $index,
                'title' => $ad->title,
                'description' => $description,
                'image' => $ad->image
                    ? storage_url($ad->image)
                    : (($mediaType === 'image' && $sourceUrl !== '') ? $sourceUrl : null),
                'category' => $ad->category instanceof \BackedEnum ? $ad->category->value : (string) ($ad->category ?? 'advertisement'),
                'category_label' => $categoryLabel,
                'media_type' => $mediaType ?: null,
                'source_url' => $sourceUrl ?: null,
                'is_video' => $isVideo,
                'video_url' => $videoUrl,
                'embed_url' => $embedUrl,
                'embed_type' => $embedUrl ? 'iframe' : null,
                'type_label' => $categoryLabel !== '' ? $categoryLabel : 'Notice',
                'is_paused' => ! (bool) $ad->is_active,
                'autoplay' => (bool) ($ad->autoplay ?? true),
                'loop' => (bool) ($ad->loop ?? true),
                'muted' => (bool) ($ad->muted ?? true),
            ];
        })->filter(fn(array $slide): bool => $this->isSlideVisible($slide, $display))->values()->all();

        return array_values($slides);
    }

    protected function resolveFallbackSlides(array $display): array
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

        $slides = $query->get()->map(function (DisplayEvent $ad, int $index): array {
            $description = trim((string) $ad->description);
            $isVideo = false;
            $videoUrl = null;
            $embedUrl = null;
            $mediaType = strtolower((string) ($ad->media_type ?? ''));
            $sourceUrl = trim((string) ($ad->media_url ?? ''));
            $categoryLabel = $ad->category_label ?? 'Display Content';

            if ($mediaType === 'video' || ! empty($sourceUrl)) {
                $isVideo = $mediaType === 'video'
                    || str_contains($sourceUrl, 'youtube.com')
                    || str_contains($sourceUrl, 'youtu.be')
                    || str_contains($sourceUrl, 'vimeo.com')
                    || str_ends_with(strtolower($sourceUrl), '.mp4')
                    || str_ends_with(strtolower($sourceUrl), '.webm');

                if (str_contains($sourceUrl, 'youtube.com/watch')) {
                    parse_str((string) parse_url($sourceUrl, PHP_URL_QUERY), $query);
                    $videoId = $query['v'] ?? null;
                    if ($videoId) {
                        $embedUrl = $this->buildYouTubeEmbedUrl($videoId);
                    }
                } elseif (str_contains($sourceUrl, 'youtu.be/')) {
                    $videoId = basename(parse_url($sourceUrl, PHP_URL_PATH) ?? '');
                    if ($videoId) {
                        $embedUrl = $this->buildYouTubeEmbedUrl($videoId);
                    }
                } elseif (str_contains($sourceUrl, 'vimeo.com/')) {
                    $videoId = basename(parse_url($sourceUrl, PHP_URL_PATH) ?? '');
                    if ($videoId) {
                        $embedUrl = 'https://player.vimeo.com/video/' . $videoId;
                    }
                } elseif ($this->isInstagramUrl($sourceUrl)) {
                    $embedUrl = $this->buildInstagramEmbedUrl($sourceUrl);
                }

                $videoUrl = $isVideo && $embedUrl === null ? $sourceUrl : null;
            }

            return [
                'id' => $ad->id,
                'index' => $index,
                'title' => $ad->title,
                'description' => $description,
                'image' => $ad->image
                    ? storage_url($ad->image)
                    : (($mediaType === 'image' && $sourceUrl !== '') ? $sourceUrl : null),
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
                    $ad->image !== null => 'Banner',
                    default => 'Notice',
                },
                'is_paused' => ! (bool) $ad->is_active,
                'autoplay' => (bool) ($ad->autoplay ?? true),
                'loop' => (bool) ($ad->loop ?? true),
                'muted' => (bool) ($ad->muted ?? true),
            ];
        })->filter(fn(array $slide): bool => $this->isSlideVisible($slide, $display))->values()->all();

        return array_values($slides);
    }

    protected function resolveDoctorQueue(Doctor $doctor): array
    {
        $appointments = Appointment::query()
            ->with(['patient.user:id,name', 'doctor:id,first_name,last_name', 'availability:id,doctor_room'])
            ->where('doctor_id', $doctor->id)
            ->whereDate('appointment_date', Carbon::today())
            ->whereNotIn('status', [
                AppointmentStatus::CANCELLED,
                AppointmentStatus::COMPLETED,
                AppointmentStatus::NO_SHOW,
                AppointmentStatus::FAILED,
            ])
            ->whereNotIn('queue_status', ['completed'])
            ->get()
            ->sortBy(function (Appointment $appointment): int {
                $queueNumber = $appointment->queue_number ?: '';
                $digits = preg_replace('/\D+/', '', $queueNumber) ?: '0';

                return (int) $digits;
            })
            ->values()
            ->take(12)
            ->values();

        if ($appointments->isEmpty()) {
            return [];
        }

        $currentIndex = $appointments->search(fn(Appointment $appointment) => $appointment->queue_status === 'started');

        if ($currentIndex === false) {
            $currentIndex = $appointments->search(fn(Appointment $appointment) => $appointment->queue_status === 'checkin');
        }

        if ($currentIndex === false) {
            $currentIndex = $appointments->search(function (Appointment $appointment): bool {
                return in_array($this->normalizeQueueStatus($appointment), ['scheduled', 'pending'], true);
            });
        }

        if ($currentIndex === false) {
            $currentIndex = 0;
        }

        return $appointments->map(function (Appointment $appointment, int $index) use ($currentIndex): array {
            $patientName = $appointment->patient?->user?->name
                ?? trim((string) ($appointment->patient?->first_name ?? '') . ' ' . (string) ($appointment->patient?->last_name ?? ''))
                ?? 'Patient';

            $rawStatus = $this->normalizeQueueStatus($appointment);

            [$statusLabel] = match ($rawStatus) {
                'no_show' => ['No Show'],
                'checkin' => ['Checked-in'],
                'started' => ['Started'],
                'completed' => ['Completed'],
                'skipped' => ['Skipped'],
                'scheduled', 'pending' => ['Scheduled'],
                default => ['Booked'],
            };

            $timeSlot = collect([
                $appointment->appointment_time ? Carbon::parse($appointment->appointment_time)->format('h:i A') : null,
                $appointment->appointment_end_time ? Carbon::parse($appointment->appointment_end_time)->format('h:i A') : null,
            ])->filter()->implode(' - ');

            return [
                'token' => $appointment->queue_number ?: 'TOK-000',
                'patient' => $patientName,
                'mobile' => $this->maskMobile($appointment->patient?->mobile_no ?? null),
                'time_slot' => $timeSlot !== '' ? $timeSlot : null,
                'status' => $statusLabel,
                'status_label' => $statusLabel,
                'status_code' => $rawStatus,
                'is_active' => $index === $currentIndex,
                'turn' => $this->turnLabel($index, $currentIndex),
                'room' => $appointment->availability?->doctor_room ?: null,
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
            'current_token' => $activeItem['token'] ?? null,
            'current_patient' => $activeItem['patient'] ?? null,
            'current_time_slot' => $activeItem['time_slot'] ?? null,
            'current_status' => $activeItem['status_code'] ?? null,
            'next_token' => $collection->get($activeIndex + 1)['token'] ?? null,
            'next_patient' => $collection->get($activeIndex + 1)['patient'] ?? null,
            'next_time_slot' => $collection->get($activeIndex + 1)['time_slot'] ?? null,
            'next_status' => $collection->get($activeIndex + 1)['status_code'] ?? null,
            'room_number' => $activeItem['room'] ?? null,
            'items_ahead' => $waitingCount,
            'waiting_count' => $waitingCount,
        ];
    }

    protected function normalizeQueueStatus(Appointment $appointment): string
    {
        $queueStatus = strtolower((string) ($appointment->queue_status ?? ''));

        if (in_array($queueStatus, ['scheduled', 'checkin', 'started', 'completed', 'skipped', 'no_show'], true)) {
            return $queueStatus;
        }

        if ($queueStatus === 'waiting' || $queueStatus === '') {
            return 'scheduled';
        }

        $statusValue = strtolower((string) ($appointment->status instanceof \BackedEnum
            ? $appointment->status->value
            : $appointment->status));

        return in_array($statusValue, ['confirmed', 'pending', 'rescheduled'], true)
            ? 'scheduled'
            : ($statusValue !== '' ? $statusValue : 'scheduled');
    }

    protected function isSlideVisible(array $slide, array $display): bool
    {
        $showImages = $this->toBool($display['show_media_images'] ?? true);
        $showVideos = $this->toBool($display['show_media_videos'] ?? true);

        $mediaType = strtolower((string) ($slide['media_type'] ?? ''));
        $hasImage = !empty($slide['image']);
        $hasVideo = !empty($slide['video_url']) || !empty($slide['embed_url']);

        if (($mediaType === 'video' || $hasVideo) && !$showVideos) {
            return false;
        }

        if (($mediaType === 'image' || $hasImage) && !$showImages) {
            return false;
        }

        return true;
    }

    protected function toBool(mixed $value, bool $default = false): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return (int) $value === 1;
        }

        if (is_string($value)) {
            $normalized = strtolower(trim($value));
            if (in_array($normalized, ['1', 'true', 'yes', 'on'], true)) {
                return true;
            }

            if (in_array($normalized, ['0', 'false', 'no', 'off', ''], true)) {
                return false;
            }
        }

        return $default;
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
            return 'Next';
        }

        return 'Waiting ' . ($index + 1);
    }

    protected function isAuthenticated(Request $request, array $display): bool
    {
        $password = (string) ($display['password'] ?? '');

        if ($password === '') {
            return true;
        }

        $fingerprint = $this->authFingerprint($display);

        return $request->session()->get($this->sessionKey()) === $fingerprint
            || $request->cookie($this->cookieKey()) === $fingerprint;
    }

    protected function authFingerprint(array $display): string
    {
        return sha1((string) ($display['password'] ?? ''));
    }

    protected function sessionKey(): string
    {
        return 'opd-token.display.auth';
    }

    protected function cookieKey(): string
    {
        return 'opd_token_display_auth';
    }
}
