<?php

namespace App\Services;

use App\Models\VoiceTranscriptionLog;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DeepgramService
{
    private string $apiKey;
    private string $model;
    private bool $enabled;

    // Pay-As-You-Go cost per minute in USD (Nova-2 pre-recorded)
    public const COST_PER_MINUTE = 0.0043;

    public function __construct()
    {
        $this->apiKey  = (string) config('deepgram.api_key', '');
        $this->model   = (string) config('deepgram.model', 'nova-2');
        $this->enabled = (bool)   config('deepgram.enabled', false);
    }

    public function isEnabled(): bool
    {
        return $this->enabled && $this->apiKey !== '';
    }

    /**
     * Transcribe an uploaded audio file via Deepgram pre-recorded API.
     *
     * Returns structured result array or throws on failure.
     */
    public function transcribeAudio(
        UploadedFile $file,
        string $language = 'en',
        ?string $module = null,
        ?string $moduleRecordId = null,
        ?string $appointmentId = null,
        ?string $doctorId = null,
        ?string $patientId = null,
    ): array {
        if (! $this->isEnabled()) {
            throw new \RuntimeException('Deepgram speech-to-text is not enabled. Enable it from admin settings.');
        }

        $url = 'https://api.deepgram.com/v1/listen?' . http_build_query([
            'model'      => $this->model,
            'language'   => $language,
            'smart_format' => 'true',
            'punctuate'  => 'true',
            'diarize'    => 'false',
            'utterances' => 'false',
        ]);

        $audioContent = file_get_contents($file->getRealPath());
        $mimeType     = $file->getMimeType() ?: 'audio/webm';

        $response = Http::withHeaders([
            'Authorization' => 'Token ' . $this->apiKey,
            'Content-Type'  => $mimeType,
        ])->withBody($audioContent, $mimeType)
          ->timeout(60)
          ->post($url);

        $log = new VoiceTranscriptionLog([
            'module'           => $module ?? 'prescription',
            'module_record_id' => $moduleRecordId,
            'appointment_id'   => $appointmentId,
            'doctor_id'        => $doctorId,
            'patient_id'       => $patientId,
            'language'         => $language,
            'model'            => $this->model,
            'audio_mime_type'  => $mimeType,
        ]);

        if (! $response->successful()) {
            $log->status        = 'failed';
            $log->error_message = 'Deepgram API error: HTTP ' . $response->status() . ' — ' . $response->body();
            $log->save();

            Log::error('DeepgramService: API failure', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            throw new \RuntimeException('Speech transcription failed. Please try again.');
        }

        $body = $response->json();

        $channel    = $body['results']['channels'][0] ?? [];
        $alternative = $channel['alternatives'][0] ?? [];
        $transcript = trim($alternative['transcript'] ?? '');
        $confidence = round((float) ($alternative['confidence'] ?? 0) * 100, 1);
        $duration   = round((float) ($body['metadata']['duration'] ?? 0), 2);
        $requestId  = $body['metadata']['request_id'] ?? null;

        // Cost estimate: duration in minutes × rate
        $creditsUsed = round(($duration / 60) * self::COST_PER_MINUTE, 6);

        $log->transcript          = $transcript;
        $log->audio_duration_seconds = $duration;
        $log->confidence          = $confidence;
        $log->credits_used        = $creditsUsed;
        $log->deepgram_request_id = $requestId;
        $log->deepgram_response   = $body;
        $log->status              = 'success';
        $log->save();

        return [
            'transcript'       => $transcript,
            'confidence'       => $confidence,
            'duration_seconds' => $duration,
            'credits_used'     => $creditsUsed,
            'language'         => $language,
            'model'            => $this->model,
            'log_id'           => $log->id,
        ];
    }

    /**
     * Get aggregated usage stats.
     */
    public static function getUsageStats(): array
    {
        $totalDuration = VoiceTranscriptionLog::where('status', 'success')->sum('audio_duration_seconds');
        $totalCredits  = VoiceTranscriptionLog::where('status', 'success')->sum('credits_used');
        $totalCount    = VoiceTranscriptionLog::where('status', 'success')->count();
        $failedCount   = VoiceTranscriptionLog::where('status', 'failed')->count();

        $monthlyDuration = VoiceTranscriptionLog::where('status', 'success')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('audio_duration_seconds');

        $monthlyCredits = VoiceTranscriptionLog::where('status', 'success')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('credits_used');

        $budgetMinutes  = (float) config('deepgram.monthly_budget_minutes', 0);
        $usedMinutes    = round($monthlyDuration / 60, 2);
        $remainingMinutes = $budgetMinutes > 0 ? max(0, $budgetMinutes - $usedMinutes) : null;

        return [
            'total_recordings'         => $totalCount,
            'total_failed'             => $failedCount,
            'total_duration_seconds'   => $totalDuration,
            'total_duration_minutes'   => round($totalDuration / 60, 2),
            'total_credits_usd'        => round($totalCredits, 4),
            'monthly_duration_minutes' => $usedMinutes,
            'monthly_credits_usd'      => round($monthlyCredits, 4),
            'budget_minutes'           => $budgetMinutes,
            'remaining_minutes'        => $remainingMinutes,
        ];
    }
}
