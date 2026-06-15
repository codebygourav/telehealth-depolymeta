<?php

namespace App\Jobs;

use App\Models\Appointment;
use App\Services\WherebyService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CreateVideoRoomJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [30, 120, 300];

    public function __construct(public string $appointmentId)
    {
    }

    public function handle(WherebyService $wherebyService): void
    {
        $appointment = Appointment::find($this->appointmentId);

        if (
            ! $appointment ||
            $appointment->consultation_type !== 'video' ||
            $appointment->whereby_room_url
        ) {
            return;
        }

        $room = $wherebyService->createVideoConsultation($appointment);

        if (! $room) {
            Log::warning('Video room creation failed for appointment: ' . $appointment->id);

            return;
        }

        if (\Illuminate\Support\Facades\Schema::hasColumn('appointments', 'whereby_room_url')) {
            $appointment->update([
                'whereby_room_url' => $room->room_url,
                'whereby_room_id' => $room->room_id,
            ]);
        }

        $appointment->load('videoConsultation');
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Video room creation job failed', [
            'appointment_id' => $this->appointmentId,
            'message' => $exception->getMessage(),
            'exception' => $exception,
        ]);
    }
}
