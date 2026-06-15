<?php

namespace App\Jobs;

use App\Services\PaymentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessRazorpayWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [30, 120, 300];

    public function __construct(public array $payload)
    {
    }

    public function handle(PaymentService $paymentService): void
    {
        $paymentService->processWebhook($this->payload);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Razorpay webhook job failed', [
            'message' => $exception->getMessage(),
            'event' => $this->payload['event'] ?? null,
            'payment_id' => $this->payload['payload']['payment']['entity']['id'] ?? null,
            'order_id' => $this->payload['payload']['payment']['entity']['order_id']
                ?? $this->payload['payload']['order']['entity']['id']
                ?? null,
            'exception' => $exception,
        ]);
    }
}
