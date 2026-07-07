<?php

namespace App\Services;

use App\Models\VideoConsultation;
use App\Models\Appointment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class WherebyService
{
    protected string $apiKey;
    protected string $baseUrl = 'https://api.whereby.dev/v1';

    public function __construct()
    {
        $this->apiKey = config('services.whereby.api_key', '');

        if (empty($this->apiKey)) {
            Log::warning('Whereby API key is not configured in config/services.php');
        }
    }

    /**
     * Check if service is properly configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Create a video consultation for an appointment
     *
     * @param Appointment $appointment
     * @return VideoConsultation|null
     */
    public function createVideoConsultation(Appointment $appointment): ?VideoConsultation
    {
        if (!$this->isConfigured()) {
            Log::error('Whereby service not configured - cannot create video consultation');
            return null;
        }

        // Check if video consultation already exists
        if ($appointment->videoConsultation) {
            return $appointment->videoConsultation;
        }

        // Calculate room end date - room expires 1 day after appointment
        $endDate = Carbon::parse($appointment->appointment_date)->addDay()->toIso8601String();

        // Create Whereby room
        $roomData = $this->createRoom([
            'endDate' => $endDate,
            'roomMode' => 'normal',
            'isLocked' => true,
            'liveTranscription' => [
                'liveCaptions' => true,
                'language' => 'en',
                'startTrigger' => 'manual',
            ],
            'fields' => ['hostRoomUrl'],
        ]);

        if (!$roomData) {
            Log::error('Failed to create Whereby room', [
                'appointment_id' => $appointment->id,
            ]);
            return null;
        }

        // Parse room data
        $roomUrl = $roomData['roomUrl'] ?? null;
        $hostRoomUrl = $roomData['hostRoomUrl'] ?? null;
        $meetingId = $roomData['meetingId'] ?? null;


        // Extract room name from URL
        $roomName = null;
        if ($roomUrl) {
            $parsedUrl = parse_url($roomUrl);
            $roomName = trim($parsedUrl['path'] ?? '', '/');
        }

        // Extract room key from host URL
        $roomKey = $hostRoomUrl ? $this->extractRoomKey($hostRoomUrl) : null;
        $startDate = isset($roomData['startDate'])
            ? Carbon::parse($roomData['startDate'])
            : null;

        $endDate = isset($roomData['endDate'])
            ? Carbon::parse($roomData['endDate'])
            : null;

        // Create video consultation record
        $videoConsultation = VideoConsultation::create([
            'appointment_id' => $appointment->id,
            'patient_id' => $appointment->patient_id,
            'doctor_id' => $appointment->doctor_id,
            'room_url' => $roomUrl,
            'host_url' => $hostRoomUrl,
            'started_at' => $startDate,
            'ended_at' => $endDate,
            'participate_url' => $roomUrl, // Participants use base room URL
            'room_id' => $roomName ?? $meetingId ?? 'room_' . Str::random(16),
            'status' => 'pending',
            'metadata' => [
                'whereby_meeting_id' => $meetingId,
                'whereby_room_name' => $roomName,
                'whereby_room_key' => $roomKey,
                'whereby_start_date' => $startDate,
                'whereby_end_date'   => $endDate,
                'created_at' => now()->toIso8601String(),
            ],

        ]);

        // Log::info('Video consultation created successfully', [
        //     'video_consultation_id' => $videoConsultation->id,
        //     'appointment_id' => $appointment->id,
        //     'room_id' => $videoConsultation->room_id,
        // ]);

        return $videoConsultation;
    }

    /**
     * Regenerate URLs for an existing video consultation
     *
     * @param VideoConsultation $videoConsultation
     * @return VideoConsultation|null
     */
    public function regenerateUrls(VideoConsultation $videoConsultation): ?VideoConsultation
    {
        if (!$this->isConfigured()) {
            Log::error('Whereby service not configured - cannot regenerate URLs');
            return null;
        }

        // Delete old room if exists
        $oldMeetingId = $videoConsultation->metadata['whereby_meeting_id'] ?? null;
        if ($oldMeetingId) {
            $this->deleteRoom($oldMeetingId);
        }

        // Calculate new end date
        $endDate = $videoConsultation->appointment
            ? Carbon::parse($videoConsultation->appointment->appointment_date)->addDay()->toIso8601String()
            : now()->addDay()->toIso8601String();

        // Create new room
        $roomData = $this->createRoom([
            'endDate' => $endDate,
            'roomMode' => 'normal',
            'isLocked' => true,
            'liveTranscription' => [
                'liveCaptions' => true,
                'language' => 'en',
                'startTrigger' => 'manual',
            ],
            'fields' => ['hostRoomUrl'],
        ]);

        if (!$roomData) {
            Log::error('Failed to regenerate Whereby room', [
                'video_consultation_id' => $videoConsultation->id,
            ]);
            return null;
        }

        // Parse room data
        $roomUrl = $roomData['roomUrl'] ?? null;
        $hostRoomUrl = $roomData['hostRoomUrl'] ?? null;
        $meetingId = $roomData['meetingId'] ?? null;

        $startDate = isset($roomData['startDate'])
            ? Carbon::parse($roomData['startDate'])
            : null;

        $endDate = isset($roomData['endDate'])
            ? Carbon::parse($roomData['endDate'])
            : null;


        // Extract room name
        $roomName = null;
        if ($roomUrl) {
            $parsedUrl = parse_url($roomUrl);
            $roomName = trim($parsedUrl['path'] ?? '', '/');
        }

        // Extract room key
        $roomKey = $hostRoomUrl ? $this->extractRoomKey($hostRoomUrl) : null;

        // Update video consultation
        $videoConsultation->update([
            'room_url' => $roomUrl,
            'host_url' => $hostRoomUrl,
            'participate_url' => $roomUrl,
            'started_at' => $startDate,
            'ended_at' => $endDate,
            'room_id' => $roomName ?? $meetingId ?? $videoConsultation->room_id,
            'metadata' => [
                'whereby_meeting_id' => $meetingId,
                'whereby_room_name' => $roomName,
                'whereby_room_key' => $roomKey,
                'whereby_end_date' => $endDate,
                'whereby_start_date' => $startDate,
                'regenerated_at' => now()->toIso8601String(),
                'previous_meeting_id' => $oldMeetingId,
            ],
        ]);

        Log::info('Video consultation URLs regenerated', [
            'video_consultation_id' => $videoConsultation->id,
            'new_room_id' => $videoConsultation->room_id,
        ]);

        return $videoConsultation->fresh();
    }

    /**
     * Get join URL for a user (doctor or patient)
     *
     * @param VideoConsultation $videoConsultation
     * @param string $userType 'doctor' or 'patient'
     * @param string|null $displayName User's display name
     * @return string|null
     */
    public function getJoinUrl(VideoConsultation $videoConsultation, string $userType, ?string $displayName = null): ?string
    {
        $baseUrl = $userType === 'doctor'
            ? $videoConsultation->host_url
            : $videoConsultation->participate_url;

        if (!$baseUrl) {
            return null;
        }

        if ($displayName) {
            $parsed = parse_url($baseUrl);
            parse_str($parsed['query'] ?? '', $query);

            // preserve roomKey and all params
            $query['displayName'] = $displayName;

            $baseUrl =
                ($parsed['scheme'] ?? 'https') . '://' .
                $parsed['host'] .
                ($parsed['path'] ?? '') .
                '?' . http_build_query($query);
        }

        return $baseUrl;
    }
    /**
     * Start a video consultation
     *
     * @param VideoConsultation $videoConsultation
     * @return VideoConsultation
     */
    public function startConsultation(VideoConsultation $videoConsultation): VideoConsultation
    {
        $videoConsultation->update([
            'status' => 'active',
            'started_at' => now(),
        ]);

        // Log::info('Video consultation started', [
        //     'video_consultation_id' => $videoConsultation->id,
        // ]);

        return $videoConsultation->fresh();
    }

    /**
     * End a video consultation
     *
     * @param VideoConsultation $videoConsultation
     * @return VideoConsultation
     */
    public function endConsultation(VideoConsultation $videoConsultation): VideoConsultation
    {
        $videoConsultation->update([
            'status' => 'completed',
            'ended_at' => now(),
        ]);

        // Optionally delete the room
        $meetingId = $videoConsultation->metadata['whereby_meeting_id'] ?? null;
        if ($meetingId) {
            $this->deleteRoom($meetingId);
        }

        // Log::info('Video consultation ended', [
        //     'video_consultation_id' => $videoConsultation->id,
        //     'duration_minutes' => $videoConsultation->started_at
        //         ? $videoConsultation->started_at->diffInMinutes($videoConsultation->ended_at)
        //         : null,
        // ]);

        return $videoConsultation->fresh();
    }

    /**
     * Cancel a video consultation
     *
     * @param VideoConsultation $videoConsultation
     * @return VideoConsultation
     */
    public function cancelConsultation(VideoConsultation $videoConsultation): VideoConsultation
    {
        // Delete the room if exists
        $meetingId = $videoConsultation->metadata['whereby_meeting_id'] ?? null;
        if ($meetingId) {
            $this->deleteRoom($meetingId);
        }

        $videoConsultation->update([
            'status' => 'cancelled',
        ]);

        // Log::info('Video consultation cancelled', [
        //     'video_consultation_id' => $videoConsultation->id,
        // ]);

        return $videoConsultation->fresh();
    }

    /**
     * Create a Whereby room via API
     *
     * @param array $options Room configuration options
     * @return array|null Room data or null on failure
     */
    public function createRoom(array $options = []): ?array
    {
        if (!$this->isConfigured()) {
            Log::error('Whereby API key not configured');
            return null;
        }

        $defaultOptions = [
            'isLocked' => true,
            'roomMode' => 'normal',
            'endDate' => now()->addDays(1)->toIso8601String(),
            'fields' => ['hostRoomUrl'],
            'liveTranscription' => [
                'startTrigger' => 'manual',
                'liveCaptions' => true,
                'language' => 'en',
            ],
        ];

        $roomOptions = array_merge($defaultOptions, $options);
        Log::info('Whereby request', $roomOptions);
        // Ensure hostRoomUrl is in fields
        if (!in_array('hostRoomUrl', $roomOptions['fields'] ?? [])) {
            $roomOptions['fields'] = array_merge($roomOptions['fields'] ?? [], ['hostRoomUrl']);
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/meetings", $roomOptions);

            if ($response->successful()) {
                $data = $response->json();

                // Log::info('Whereby room created', [
                //     'meeting_id' => $data['meetingId'] ?? null,
                //     'room_url' => $data['roomUrl'] ?? null,
                // ]);

                return $data;
            }

            $errorBody = $response->json() ?? $response->body();
            $errorMessage = is_array($errorBody)
                ? ($errorBody['message'] ?? json_encode($errorBody))
                : $errorBody;

            Log::error('Whereby API error', [
                'status' => $response->status(),
                'error' => $errorMessage,
                'body' => $response->body(),
                'request' => $roomOptions,
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Whereby service exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    /**
     * Get room information from Whereby API
     *
     * @param string $meetingId Whereby meeting ID
     * @return array|null Room data or null on failure
     */
    public function getRoom(string $meetingId): ?array
    {
        if (!$this->isConfigured()) {
            return null;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->get("{$this->baseUrl}/meetings/{$meetingId}");

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Whereby get room error', [
                'meeting_id' => $meetingId,
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Delete a Whereby room
     *
     * @param string $meetingId Whereby meeting ID
     * @return bool Success status
     */
    public function deleteRoom(string $meetingId): bool
    {
        if (!$this->isConfigured()) {
            return false;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->delete("{$this->baseUrl}/meetings/{$meetingId}");

            $success = $response->successful();

            if ($success) {
                // Log::info('Whereby room deleted', ['meeting_id' => $meetingId]);
            }

            return $success;
        } catch (\Exception $e) {
            Log::error('Whereby delete room error', [
                'meeting_id' => $meetingId,
                'message' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Extract roomKey from hostRoomUrl
     *
     * @param string $hostRoomUrl The host room URL with roomKey parameter
     * @return string|null The roomKey token or null
     */
    public function extractRoomKey(string $hostRoomUrl): ?string
    {
        $parsedUrl = parse_url($hostRoomUrl);
        if (isset($parsedUrl['query'])) {
            parse_str($parsedUrl['query'], $params);
            return $params['roomKey'] ?? null;
        }
        return null;
    }

    /**
     * Check if a video consultation can be started
     *
     * @param VideoConsultation $videoConsultation
     * @return array ['can_start' => bool, 'reason' => string|null]
     */
    public function canStartConsultation(VideoConsultation $videoConsultation): array
    {
        if ($videoConsultation->status === 'active') {
            return ['can_start' => true, 'reason' => 'already_active'];
        }

        if ($videoConsultation->status === 'completed') {
            return ['can_start' => false, 'reason' => 'Consultation already completed'];
        }

        if ($videoConsultation->status === 'cancelled') {
            return ['can_start' => false, 'reason' => 'Consultation was cancelled'];
        }

        if (!$videoConsultation->appointment) {
            return ['can_start' => false, 'reason' => 'No appointment associated'];
        }

        $appointment = $videoConsultation->appointment;
        $appointmentDate = Carbon::parse($appointment->appointment_date)->startOfDay();
        $today = Carbon::today();

        // Allow starting on appointment date
        if (!$appointmentDate->equalTo($today)) {
            if ($appointmentDate->gt($today)) {
                return ['can_start' => false, 'reason' => 'Consultation date has not arrived yet'];
            }
            return ['can_start' => false, 'reason' => 'Consultation date has passed'];
        }

        // Check appointment status
        if (!in_array($appointment->status, ['confirmed', 'success'])) {
            return ['can_start' => false, 'reason' => 'Appointment not confirmed'];
        }

        return ['can_start' => true, 'reason' => null];
    }

    /**
     * Get video consultation data for API response
     *
     * @param VideoConsultation $videoConsultation
     * @param string $userType 'doctor' or 'patient'
     * @return array
     */
    public function getConsultationData(VideoConsultation $videoConsultation, string $userType): array
    {
        $canStart = $this->canStartConsultation($videoConsultation);

        $displayName = null;
        if ($userType === 'doctor' && $videoConsultation->doctor) {
            $displayName = $videoConsultation->doctor->first_name . ' ' . $videoConsultation->doctor->last_name;
        } elseif ($userType === 'patient' && $videoConsultation->patient) {
            $displayName = $videoConsultation->patient->first_name . ' ' . $videoConsultation->patient->last_name;
        }

        return [
            'id' => $videoConsultation->id,
            'room_id' => $videoConsultation->room_id,
            'status' => $videoConsultation->status,
            'join_url' => $this->getJoinUrl($videoConsultation, $userType, $displayName),
            'can_start' => $canStart['can_start'],
            'start_reason' => $canStart['reason'],
            'started_at' => $videoConsultation->started_at?->toIso8601String(),
            'ended_at' => $videoConsultation->ended_at?->toIso8601String(),
        ];
    }
}