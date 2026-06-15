<?php

namespace App\Jobs;

use App\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateReceiptJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [30, 120, 300];

    public function __construct(public string $paymentId)
    {
    }

    public function handle(): void
    {
        $payment = Payment::with(['appointment.patient'])->find($this->paymentId);

        if (! $payment || ! $payment->appointment || $payment->receipt_pdf) {
            return;
        }

        $appointment = $payment->appointment;
        $pdfHtml = view('ReceiptTemplate.receipt', [
            'payment' => $payment,
            'appointment' => $appointment,
        ])->render();

        $patientName = trim(
            ($appointment->patient->first_name ?? '') . '_' .
            ($appointment->patient->last_name ?? '')
        );

        $patientName = preg_replace(
            '/[^A-Za-z0-9_]/',
            '',
            str_replace(' ', '_', $patientName)
        ) ?: 'patient';

        $date = $appointment->appointment_date
            ? $appointment->appointment_date->format('Y-m-d')
            : now()->format('Y-m-d');

        $filename = "CMCTele_{$patientName}_{$date}_{$appointment->appointment_time}.pdf";
        $path = 'receipts/' . $filename;
        $fullPath = storage_path('app/public/' . $path);

        try {
            if (! file_exists(dirname($fullPath))) {
                mkdir(dirname($fullPath), 0775, true);
            }

            Pdf::loadHTML($pdfHtml)->save($fullPath);

            $payment->receipt_pdf = $path;
            $payment->save();
        } catch (\Throwable $e) {
            Log::error('Failed to generate receipt PDF: ' . $e->getMessage(), [
                'payment_id' => $this->paymentId,
                'exception' => $e,
            ]);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Receipt generation job failed', [
            'payment_id' => $this->paymentId,
            'message' => $exception->getMessage(),
            'exception' => $exception,
        ]);
    }
}
