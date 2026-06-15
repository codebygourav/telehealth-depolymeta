<?php

namespace App\Http\Controllers\Api\V2\Common\VideoConsultation;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\VideoConsultation;
use App\Services\NotificationService;
use App\Enums\AppointmentStatus;
use App\Services\ApiResponseService;

class WherebyWebhookController extends Controller
{
    /**
     * Handle incoming Whereby webhook events
     */
    public function handle(Request $request)
    {
        $payload = $request->getContent();
        $signatureHeader = $request->header('Whereby-Signature');
        $secret = config('services.whereby.webhook_secret', 'x7c2dxr60y4jxkqfdgg9vkes4bksz6bv');

        // Log::info('Whereby Webhook Payload:', ['payload' => json_decode($payload, true)]);

        if (!$this->verifySignature($payload, $signatureHeader, $secret)) {
            Log::warning('Whereby Webhook: Invalid signature', ['header' => $signatureHeader]);
            return ApiResponseService::error('Invalid signature', ['message' => 'Invalid signature'], 400);
        }

        $event = json_decode($payload, true);

        if (!isset($event['type']) || !isset($event['data'])) {
            return ApiResponseService::error('Invalid payload structure', ['message' => 'Invalid payload structure'], 400);
        }

        $type = $event['type'];
        $data = $event['data'];

        // Log::info('Whereby Webhook Event:', ['type' => $type, 'meetingId' => $data['meetingId'] ?? null]);

        $roomName = isset($data['roomName']) ? ltrim($data['roomName'], '/') : null;

        if (!$roomName) {
            return ApiResponseService::error('No roomName in payload', ['message' => 'No roomName in payload'], 400);
        }

        // Find the video consultation by room_id
        $videoConsultation = VideoConsultation::where('room_id', $roomName)
            ->with(['appointment.doctor.user', 'appointment.patient.user'])
            ->first();

        if (!$videoConsultation) {
            Log::warning('Whereby Webhook: Video consultation not found', ['roomName' => $roomName]);
            return ApiResponseService::error('Video consultation not found', ['message' => 'Video consultation not found'], 400);
        }

        $appointment = $videoConsultation->appointment;
        if (!$appointment) {
            Log::warning('Whereby Webhook: Appointment not found for consultation', ['roomName' => $roomName]);
            return ApiResponseService::error('Appointment not found', ['message' => 'Appointment not found'], 400);
        }

        // Save webhook event to metadata
        $metadata = $videoConsultation->metadata ?? [];
        // Keep only recent events if needed, but array append is fine
        $metadata['webhook_events'][] = [
            'type' => $type,
            'timestamp' => now()->toIso8601String(),
            'client_display_name' => $data['displayName'] ?? null,
            'client_role_name' => $data['roleName'] ?? null,
            'participant_id' => $data['participantId'] ?? null,
        ];
        $videoConsultation->metadata = $metadata;
        $videoConsultation->save();

        switch ($type) {
            case 'room.client.knocked':

                $statusValue = $appointment->status instanceof AppointmentStatus
                    ? $appointment->status->value
                    : $appointment->status;

                if (in_array($statusValue, [AppointmentStatus::CONFIRMED->value, AppointmentStatus::RESCHEDULED->value, AppointmentStatus::PENDING->value])) {
                    NotificationService::notifyPatientKnocks($appointment);
                }
                break;

            case 'room.client.joined':
                $isPatientPresent = false;
                $isDoctorPresent = false;

                // Check metadata to see who is currently in the room
                $metadata = $videoConsultation->metadata ?? [];
                $events = $metadata['webhook_events'] ?? [];

                // Reconstruct room state to determine who is currently present
                $participants = [];
                foreach ($events as $evt) {
                    $pid = $evt['participant_id'] ?? null;
                    if (!$pid) continue;

                    if ($evt['type'] === 'room.client.joined') {
                        $participants[$pid] = $evt['client_role_name'] ?? '';
                    } elseif ($evt['type'] === 'room.client.left') {
                        unset($participants[$pid]);
                    }
                }

                foreach ($participants as $roleName) {
                    if ($roleName === 'owner' || $roleName === 'host') {
                        $isDoctorPresent = true;
                    } else {
                        $isPatientPresent = true;
                    }
                }

                $joinedRole = $data['roleName'] ?? '';

                // If Doctor joined AND Patient is present, notify Patient
                if (($joinedRole === 'owner' || $joinedRole === 'host') && $isPatientPresent) {
                    NotificationService::notifyVideoCallJoined(
                        $appointment,
                        $joinedRole,
                        $data['displayName'] ?? ''
                    );
                }
                // If Patient joined AND Doctor is present, notify Doctor
                elseif (($joinedRole !== 'owner' && $joinedRole !== 'host') && $isDoctorPresent) {
                    NotificationService::notifyVideoCallJoined(
                        $appointment,
                        $joinedRole,
                        $data['displayName'] ?? ''
                    );
                }
                break;

            case 'room.client.left':
                // Check if the OTHER person is still present before notifying
                // We use the same constructed participants array from metadata since the webhook
                // runs after we already appended the left event to $metadata.

                $leftRole = $data['roleName'] ?? '';
                $isPatientStillPresent = false;
                $isDoctorStillPresent = false;

                // Construct participants currently in room (after this left event)
                $events = $videoConsultation->metadata['webhook_events'] ?? [];
                $participants = [];
                foreach ($events as $evt) {
                    $pid = $evt['participant_id'] ?? null;
                    if (!$pid) continue;

                    if ($evt['type'] === 'room.client.joined') {
                        $participants[$pid] = $evt['client_role_name'] ?? '';
                    } elseif ($evt['type'] === 'room.client.left') {
                        unset($participants[$pid]);
                    }
                }

                foreach ($participants as $roleName) {
                    if ($roleName === 'owner' || $roleName === 'host') {
                        $isDoctorStillPresent = true;
                    } else {
                        $isPatientStillPresent = true;
                    }
                }

                // If Doctor left AND Patient is STILL present, notify Patient
                if (($leftRole === 'owner' || $leftRole === 'host') && $isPatientStillPresent) {
                    NotificationService::notifyVideoCallLeft(
                        $appointment,
                        $leftRole,
                        $data['displayName'] ?? ''
                    );
                }
                // If Patient left AND Doctor is STILL present, notify Doctor
                elseif (($leftRole !== 'owner' && $leftRole !== 'host') && $isDoctorStillPresent) {
                    NotificationService::notifyVideoCallLeft(
                        $appointment,
                        $leftRole,
                        $data['displayName'] ?? ''
                    );
                }
                break;

            case 'room.session.started':
                // Session started
                $videoConsultation->update([
                    'status' => 'active',
                    'started_at' => now()
                ]);
                break;

            case 'room.session.ended':
                // Session ended
                $videoConsultation->update([
                    'status' => 'completed',
                    'ended_at' => now()
                ]);

                if ($appointment) {
                    $appointment->update([
                        'status' => AppointmentStatus::COMPLETED->value,
                    ]);
                    NotificationService::notifyAppointmentCompleted($appointment);
                }
                break;

            case 'room.client.knockCancelled':
                NotificationService::notifyVideoCallKnockCancelled($appointment);
                break;

            case 'recording.finished':
            case 'transcription.started':
                // Future use
                // Log::info("Whereby Webhook: Other event triggered {$type}");
                break;

            default:
                // Log::info("Whereby Webhook: Unhandled event type {$type}");
                break;
        }

        return ApiResponseService::success('Success', [], null, 'WEBHOOK_SUCCESS');
    }

    /**
     * Verify Whereby Webhook Signature
     */
    private function verifySignature($payload, $signatureHeader, $secret)
    {
        if (!$signatureHeader || !$secret) {
            return false;
        }

        // Parse signature string (e.g., t=1612443496,v1=...)
        $parts = explode(',', $signatureHeader);
        $timestamp = null;
        $signature = null;

        foreach ($parts as $part) {
            if (str_starts_with($part, 't=')) {
                $timestamp = substr($part, 2);
            } elseif (str_starts_with($part, 'v1=')) {
                $signature = substr($part, 3);
            }
        }

        if (!$timestamp || !$signature) {
            return false;
        }
        // Construct the expected signature
        $signedPayload = $timestamp . '.' . $payload;
        $expectedSignature = hash_hmac('sha256', $signedPayload, $secret);

        return hash_equals($expectedSignature, $signature);
    }
}
