<?php

namespace App\Http\Controllers\Api\V2\Common\Prescription;

use App\Http\Controllers\Controller;
use App\Models\{Appointment, PrescriptionDraft};
use App\Services\{ApiResponseService, DeepgramService, PrescriptionDraftParser};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VoiceTranscriptionController extends Controller
{
    public function __construct(
        private readonly DeepgramService $deepgram,
        private readonly PrescriptionDraftParser $parser,
    ) {}

    /**
     * Accept audio upload → Deepgram STT → parse transcript → return draft.
     * POST /doctor/{appointmentId}/prescription-drafts/voice
     */
    public function transcribeAndParse(Request $request, string $appointmentId): \Illuminate\Http\JsonResponse
    {
        if (! $this->deepgram->isEnabled()) {
            return ApiResponseService::error(
                'responses.validation_failed',
                ['message' => 'Voice speech-to-text is not enabled. Please contact the administrator.'],
                422,
                null,
                'DEEPGRAM_DISABLED'
            );
        }

        $appointment = Appointment::findOrFail($appointmentId);

        $validator = Validator::make($request->all(), [
            'audio'    => 'required|file|mimes:webm,ogg,wav,mp4,m4a,mp3,flac|max:20480',
            'language' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return ApiResponseService::error(
                'responses.validation_failed',
                ['message' => implode(' ', $validator->errors()->all())],
                422,
                null,
                'VALIDATION_FAILED'
            );
        }

        $doctor    = $request->user();
        $doctorId  = $this->resolveDoctorId($doctor);
        $patientId = $appointment->patient_id;
        $language  = $request->input('language', 'en');

        try {
            $result = $this->deepgram->transcribeAudio(
                file: $request->file('audio'),
                language: $language,
                module: 'prescription',
                appointmentId: $appointmentId,
                doctorId: $doctorId,
                patientId: $patientId,
            );
        } catch (\Throwable $e) {
            return ApiResponseService::error(
                'responses.server_error',
                ['message' => $e->getMessage()],
                500,
                null,
                'TRANSCRIPTION_FAILED'
            );
        }

        $transcript = $result['transcript'] ?? '';

        // If transcript is empty we still return the log but skip parsing
        if (blank($transcript)) {
            return ApiResponseService::success('responses.success', [
                'transcript'       => '',
                'confidence'       => $result['confidence'],
                'duration_seconds' => $result['duration_seconds'],
                'credits_used'     => $result['credits_used'],
                'log_id'           => $result['log_id'],
                'draft_id'         => null,
                'form'             => null,
                'warnings'         => ['No speech detected. Please speak clearly and try again.'],
                'missing_fields'   => [],
            ]);
        }

        // Parse transcript into prescription fields
        $parsed = $this->parser->parse($transcript, $doctorId);

        $draft = PrescriptionDraft::create([
            'appointment_id'  => $appointmentId,
            'doctor_id'       => $doctorId,
            'patient_id'      => $patientId,
            'source_type'     => 'speech',
            'status'          => PrescriptionDraft::STATUS_PARSED,
            'input_text'      => $transcript,
            'parsed_payload'  => $parsed['form'] ?? null,
            'warnings'        => $parsed['warnings'] ?? [],
            'missing_fields'  => $parsed['missing_fields'] ?? [],
            'confidence_score' => $parsed['confidence_score'] ?? null,
        ]);

        // Link the voice log to the draft
        \App\Models\VoiceTranscriptionLog::where('id', $result['log_id'])
            ->update(['module_record_id' => $draft->id]);

        return ApiResponseService::success('responses.success', [
            'transcript'       => $transcript,
            'confidence'       => $result['confidence'],
            'duration_seconds' => $result['duration_seconds'],
            'credits_used'     => $result['credits_used'],
            'log_id'           => $result['log_id'],
            'draft_id'         => $draft->id,
            'form'             => $parsed['form'] ?? null,
            'warnings'         => $parsed['warnings'] ?? [],
            'missing_fields'   => $parsed['missing_fields'] ?? [],
        ]);
    }

    private function resolveDoctorId(mixed $user): ?string
    {
        if (! $user) return null;

        $doctor = \App\Models\Doctor::where('user_id', $user->id)->first();
        return $doctor?->id;
    }
}
