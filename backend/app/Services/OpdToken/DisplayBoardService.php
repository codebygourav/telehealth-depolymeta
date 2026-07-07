<?php

namespace App\Services\OpdToken;

use App\Enums\AppointmentStatus;
use App\Enums\DisplayEventCategory;
use App\Enums\DisplayMediaType;
use App\Services\AppointmentQueueService;
use App\Models\Appointment;
use App\Models\DisplayEvent;
use App\Models\Doctor;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use BackedEnum;

class DisplayBoardService
{
    protected function queueService(): AppointmentQueueService
    {
        return app(AppointmentQueueService::class);
    }

    public function buildBoard(array $display): array
    {
        $doctors = $this->resolveDoctors($display);
        $displayMode = $this->resolveDisplayMode($display, $doctors);
        $fallbackSlides = collect($this->resolveFallbackSlides($display))
            ->when(
                (bool) ($display['randomize_bottom_content'] ?? true),
                fn (Collection $collection) => $collection->shuffle()
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
            'voice_name' => (string) ($display['voice_name'] ?? ''),
            'announcement_template' => (string) ($display['announcement_template'] ?? ''),
            'default_notice' => (string) ($display['default_notice'] ?? ''),
            'screen_name' => (string) ($display['screen_name'] ?? 'Main OPD Waiting Hall'),
            'screen_location' => (string) ($display['screen_location'] ?? 'Ground Floor OPD'),
            'fallback_slides' => $fallbackSlides,
            'same_time_card_columns' => max(2, min(3, (int) ($display['same_time_card_columns'] ?? 2))),
        ];
    }

    public function resolveDoctors(array $display): array
    {
        $mode = (string) ($display['doctor_mode'] ?? 'all');
        $selectedIds = collect($display['selected_doctors'] ?? [])
            ->filter()
            ->values()
            ->all();

        $appointmentsToday = Appointment::query()
            ->with([
                'doctor.departments',
                'doctor.user:id,name',
                'availability:id,doctor_room',
            ])
            ->whereDate('appointment_date', Carbon::today())
            ->whereNotIn('status', [
                AppointmentStatus::CANCELLED->value,
                AppointmentStatus::FAILED->value,
            ])
            ->get();

        $appointmentDoctors = $appointmentsToday->pluck('doctor')->filter()->unique('id')->values();

        $query = Doctor::query()
            ->with(['departments:id,name', 'user:id,name'])
            ->active()
            ->withoutTestDoctors()
            ->orderBy('first_name')
            ->orderBy('last_name');

        $showAppointmentsFirst = filter_var($display['show_doctor_list_from_appointments'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $appointmentDoctorIds = $appointmentDoctors->pluck('id')->all();

        if ($mode === 'single') {
            $doctorId = $selectedIds[0] ?? ($appointmentDoctorIds[0] ?? null);

            if ($doctorId) {
                $query->whereKey($doctorId);
            } else {
                $query->whereRaw('1 = 0');
            }
        } elseif ($mode === 'multiple') {
            if (empty($selectedIds)) {
                $query->whereRaw('1 = 0');
            } else {
                $doctorIds = $showAppointmentsFirst && ! empty($appointmentDoctorIds)
                    ? array_values(array_intersect($selectedIds, $appointmentDoctorIds))
                    : $selectedIds;

                if (! empty($doctorIds)) {
                    $query->whereIn('id', $doctorIds);
                } else {
                    $query->whereRaw('1 = 0');
                }
            }
        } elseif ($showAppointmentsFirst && ! empty($appointmentDoctorIds)) {
            $query->whereIn('id', $appointmentDoctorIds);
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
                'sub_title' => $doctor->sub_title,
                'room' => $room,
                'experience' => $this->formatExperience($doctor),
                'bio' => $this->resolveDoctorBio($doctor),
                'education_list' => $this->resolveDoctorEducationList($doctor),
                'avatar' => storage_url($doctor->avatar ?? $doctor->user?->avatar),
                'queue_label' => $display['queue_label'],
                'queue_subtitle' => $display['queue_subtitle'],
                'ads_enabled' => filter_var($display['show_ads_panel'] ?? true, FILTER_VALIDATE_BOOLEAN),
                'is_checked_in' => (bool) $doctor->is_checked_in,
                'is_on_break' => (bool) $doctor->is_on_break,
                'queue_items' => $queueItems,
                'queue_summary' => $queueSummary,
                'current_slot_time' => $queueSummary['current_time_slot'] ?? null,
                'next_slot_time' => $queueSummary['next_time_slot'] ?? null,
                'has_ads' => ! empty($doctorAds),
                'slides' => $this->mergeDoctorSlides($doctorAds, $display),
            ];
        })->values()->all();
    }

    protected function resolveDoctorBio(Doctor $doctor): string
    {
        $bio = trim((string) ($doctor->bio ?? ''));

        return $bio !== '' ? strip_tags($bio) : '';
    }

    protected function resolveDoctorEducationList(Doctor $doctor): array
    {
        $education = collect($doctor->education_info ?? [])
            ->map(function ($item): ?string {
                if (is_string($item)) {
                    return trim($item) !== '' ? trim($item) : null;
                }

                if (! is_array($item)) {
                    return null;
                }

                $degree = trim((string) ($item['degree'] ?? $item['title'] ?? ''));
                $institution = trim((string) ($item['institution'] ?? $item['university'] ?? ''));
                $yearRange = trim(implode(' - ', array_filter([
                    trim((string) ($item['start_date'] ?? $item['from'] ?? '')),
                    trim((string) ($item['end_date'] ?? $item['to'] ?? '')),
                ])));

                $parts = array_filter([
                    $degree,
                    $institution,
                    $yearRange,
                ]);

                return ! empty($parts) ? implode(' | ', $parts) : null;
            })
            ->filter()
            ->values();

        if ($education->isNotEmpty()) {
            return $education->all();
        }

        $fallbacks = array_filter([
            trim((string) ($doctor->qualification ?? '')),
            trim((string) ($doctor->availability_info ?? '')),
            trim((string) ($doctor->description ?? '')),
        ]);

        return array_values($fallbacks);
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

    public function resolveDoctorAdvertisements(Doctor $doctor, array $display): array
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
                ->orWhereHas('doctors', fn ($doctorQuery) => $doctorQuery->where('doctors.id', $doctor->id));
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

        $slides = $query
            ->orderBy('category')
            ->get()
            ->map(fn (DisplayEvent $ad, int $index) => $this->mapSlide($ad, $index, 'Advertisement'))
            ->filter(fn (array $slide): bool => $this->isSlideVisible($slide, $display))
            ->values()
            ->all();

        return array_values($slides);
    }

    public function resolveFallbackSlides(array $display): array
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

        return $query
            ->orderBy('category')
            ->get()
            ->map(fn (DisplayEvent $ad, int $index) => $this->mapSlide($ad, $index, 'Display Content'))
            ->filter(fn (array $slide): bool => $this->isSlideVisible($slide, $display))
            ->values()
            ->all();
    }

    public function resolveDoctorQueue(Doctor $doctor): array
    {
        $appointments = $this->queueService()->filterAppointmentsForDisplay(
            $this->queueService()->getDoctorQueueAppointments($doctor->id, Carbon::today())
        );

        if ($appointments->isEmpty()) {
            return [];
        }

        $currentIndex = $appointments->search(fn (Appointment $appointment) => $appointment->queue_status === 'started');

        if ($currentIndex === false) {
            $currentIndex = $appointments->search(fn (Appointment $appointment) => $appointment->queue_status === 'checkin');
        }

        if ($currentIndex === false) {
            $currentIndex = $appointments->search(fn (Appointment $appointment) => $this->queueService()->resolveQueueStatus($appointment) === 'scheduled');
        }

        if ($currentIndex === false) {
            $currentIndex = $appointments->search(fn (Appointment $appointment) => $this->queueService()->resolveQueueStatus($appointment) === 'completed');
        }

        if ($currentIndex === false) {
            return [];
        }

        $currentAppointment = $appointments->get($currentIndex);
        $currentStatus = $this->queueService()->resolveQueueStatus($currentAppointment);
        $popupAppointment = $currentAppointment
            ? $this->queueService()->getPopupAppointment($currentAppointment, $appointments)
            : null;

        $nextIndex = $appointments
            ->map(function (Appointment $appointment, int $index) use ($currentIndex): ?int {
                if ($index === $currentIndex) {
                    return null;
                }

                $status = $this->queueService()->resolveQueueStatus($appointment);

                return in_array($status, ['checkin', 'scheduled'], true) ? $index : null;
            })
            ->filter(fn (?int $index): bool => $index !== null)
            ->values()
            ->first(fn (int $index): bool => $index > $currentIndex);

        if ($nextIndex === null) {
            $nextIndex = $appointments
                ->map(function (Appointment $appointment, int $index) use ($currentIndex): ?int {
                    if ($index === $currentIndex) {
                        return null;
                    }

                    $status = $this->queueService()->resolveQueueStatus($appointment);

                    return in_array($status, ['checkin', 'scheduled'], true) ? $index : null;
                })
                ->filter(fn (?int $index): bool => $index !== null)
                ->values()
                ->first();
        }

        return $appointments->map(function (Appointment $appointment, int $index) use ($appointments, $currentIndex, $nextIndex, $popupAppointment): array {
            $patientName = $this->resolvePatientName($appointment);
            $turnText = $this->queueService()->getNextInQueueText($appointment, $appointments);

            $rawStatus = $this->queueService()->resolveQueueStatus($appointment);

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
                'appointment_id' => $appointment->id,
                'token' => $appointment->queue_number ?: 'TOK-000',
                'patient' => $patientName,
                'mobile' => $this->maskMobile($appointment->patient?->mobile_no ?? null),
                'time_slot' => $timeSlot !== '' ? $timeSlot : null,
                'status' => $statusLabel,
                'status_label' => $statusLabel,
                'status_code' => $rawStatus,
                'status_pill_class' => match ($rawStatus) {
                    'completed' => 'status-completed',
                    'started' => 'status-started',
                    'scheduled' => 'status-scheduled',
                    default => 'status-checkin',
                },
                'is_active' => $index === $currentIndex,
                'is_next' => $nextIndex !== null && $index === $nextIndex,
                'turn' => $turnText,
                'next_in_queue_text' => $turnText,
                'room' => $appointment->availability?->doctor_room ?: null,
                'popup_token' => $popupAppointment?->queue_number ?? null,
                'popup_patient' => $popupAppointment ? $this->resolvePatientName($popupAppointment) : null,
                'popup_time_slot' => $popupAppointment?->appointment_time ? Carbon::parse($popupAppointment->appointment_time)->format('h:i A') : null,
                'popup_room' => $popupAppointment?->availability?->doctor_room ?: null,
                'popup_status' => $popupAppointment ? $this->queueService()->resolveQueueStatus($popupAppointment) : null,
            ];
        })->all();
    }

    protected function mapSlide(DisplayEvent $ad, int $index, string $defaultCategoryLabel): array
    {
        $description = trim((string) $ad->description);
        $mediaType = DisplayMediaType::normalize((string) ($ad->media_type ?? ''));
        $sourceUrl = trim((string) ($ad->media_url ?: $ad->link));
        $categoryLabel = $ad->category_label ?? $defaultCategoryLabel;
        $isVideo = false;
        $videoUrl = null;
        $embedUrl = null;

        if ($mediaType?->isVisual() || $sourceUrl !== '') {
            $isVideo = $mediaType === DisplayMediaType::VIDEO
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
                : (($mediaType === DisplayMediaType::IMAGE && $sourceUrl !== '') ? $sourceUrl : null),
            'category' => $ad->category instanceof BackedEnum ? $ad->category->value : (string) ($ad->category ?? DisplayEventCategory::ADVERTISEMENT->value),
            'category_label' => $categoryLabel,
            'media_type' => $mediaType?->value,
            'source_url' => $sourceUrl ?: null,
            'is_video' => $isVideo,
            'video_url' => $videoUrl,
            'embed_url' => $embedUrl,
            'embed_type' => $embedUrl ? 'iframe' : null,
            'type_label' => $categoryLabel !== '' ? $categoryLabel : ($mediaType?->label() ?? 'Notice'),
            'is_paused' => ! (bool) $ad->is_active,
            'autoplay' => (bool) ($ad->autoplay ?? true),
            'loop' => (bool) ($ad->loop ?? true),
            'muted' => (bool) ($ad->muted ?? true),
        ];
    }

    protected function buildQueueSummary(array $queueItems): array
    {
        $collection = collect($queueItems);
        $activeIndex = $collection->search(fn (array $item): bool => (bool) ($item['is_active'] ?? false));
        $activeIndex = $activeIndex === false ? 0 : (int) $activeIndex;
        $activeItem = $collection->get($activeIndex, []);
        $nextItem = $collection->get($activeIndex + 1, []);
        $waitingCount = $collection->filter(fn (array $item, int $index): bool => $index > $activeIndex)->count();
        $popupItem = $this->resolvePopupQueueItem($collection, $activeIndex, $activeItem) ?? $activeItem;

        return [
            'current_token' => $activeItem['token'] ?? null,
            'current_patient' => $activeItem['patient'] ?? null,
            'current_time_slot' => $activeItem['time_slot'] ?? null,
            'current_status' => $activeItem['status_code'] ?? null,
            'next_token' => $nextItem['token'] ?? null,
            'next_patient' => $nextItem['patient'] ?? null,
            'next_time_slot' => $nextItem['time_slot'] ?? null,
            'next_status' => $nextItem['status_code'] ?? null,
            'popup_token' => $popupItem['token'] ?? null,
            'popup_patient' => $popupItem['patient'] ?? null,
            'popup_time_slot' => $popupItem['time_slot'] ?? null,
            'popup_status' => $popupItem['status_code'] ?? null,
            'popup_room' => $popupItem['room'] ?? null,
            'popup_turn' => $popupItem['turn'] ?? null,
            'room_number' => $activeItem['room'] ?? null,
            'items_ahead' => $waitingCount,
            'waiting_count' => $waitingCount,
        ];
    }

    protected function resolvePopupQueueItem(Collection $queueItems, int $activeIndex, array $activeItem): ?array
    {
        if ($queueItems->isEmpty()) {
            return null;
        }

        $activeStatus = (string) ($activeItem['status_code'] ?? 'scheduled');

        if ($activeStatus === 'started' || $activeStatus === 'completed') {
            return $queueItems->first(fn (array $item): bool => (string) ($item['status_code'] ?? '') === 'checkin');
        }

        if ($activeStatus === 'checkin') {
            return $queueItems->first(fn (array $item): bool => (string) ($item['status_code'] ?? '') === 'started') ?? $activeItem;
        }

        if (in_array($activeStatus, ['scheduled', 'pending'], true)) {
            return $queueItems
                ->slice($activeIndex + 1)
                ->first(fn (array $item): bool => in_array((string) ($item['status_code'] ?? ''), ['checkin', 'scheduled'], true))
                ?: $activeItem;
        }

        return $activeItem;
    }

    protected function resolvePatientName(Appointment $appointment): string
    {
        $name = $appointment->patient?->user?->name
            ?? trim((string) ($appointment->patient?->first_name ?? '') . ' ' . (string) ($appointment->patient?->last_name ?? ''));

        return $name !== '' ? $name : 'Patient';
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

        $hasAnyAds = collect($doctors)->contains(fn (array $doctor): bool => ! empty($doctor['slides'] ?? []));

        return $hasAnyAds && $showAds
            ? 'split_ads'
            : 'grid_modal_ads';
    }

    protected function normalizeDisplayMode(string $mode): string
    {
        return match ($mode) {
            'single_ads' => 'split_ads',
            'single_no_ads', 'multi_ads', 'multi_grid' => 'grid_modal_ads',
            'doctor_schedule', 'doctor_schedule_sidebar', 'doctor_opd_schedule' => 'doctor_schedule_sidebar',
            'no_doctor_ads', 'no_doctor' => 'events_only',
            default => $mode,
        };
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

        $statusValue = strtolower((string) ($appointment->status instanceof BackedEnum
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

        $mediaType = DisplayMediaType::normalize((string) ($slide['media_type'] ?? ''));
        $hasImage = ! empty($slide['image']);
        $hasVideo = ! empty($slide['video_url']) || ! empty($slide['embed_url']);

        if (($mediaType === DisplayMediaType::VIDEO || $hasVideo) && ! $showVideos) {
            return false;
        }

        if (($mediaType === DisplayMediaType::IMAGE || $hasImage) && ! $showImages) {
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
}
