<?php

namespace App\Http\Resources\Patient;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Enums\PaymentStatus;
use Carbon\Carbon;

class TransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $statusEnum = $this->status instanceof PaymentStatus
            ? $this->status
            : PaymentStatus::tryFrom($this->status);

        $method = strtolower($this->payment_method ?? '');

        $response = [
            'id' => $this->id,
            'patient_name' => $this->appointment?->patient?->user?->name ?? null,
            'doctor_name' => $this->appointment?->doctor?->user?->name ?? null,
            'amount' => $this->amount,
            'currency' => $this->currency,

            'status' => $statusEnum?->value ?? $this->status,
            'status_label' => $statusEnum?->label() ?? ucfirst($this->status),

            'transaction_id' => $this->transaction_id ?? $this->razorpay_payment_id,
            'order_id' => $this->razorpay_order_id,

            'date' => Carbon::parse($this->created_at)->format('d M, Y'),
        ];

        // -------- Method-specific display --------

        // CARD
        if ($method === 'card' && $this->card_id) {
            $response['payment_type'] = 'Card';
            $response['payment_method'] = strtoupper(optional($this->card_details)['network'] ?? 'CARD');
            $response['card_last4'] = optional($this->card_details)['last4']
                ?? substr($this->card_id, -4);
            $response['bank_name'] = optional($this->card_details)['issuer'] ?? 'Unknown';
            $response['card_type'] = optional($this->card_details)['type'] ?? null;
            $response['network']   = optional($this->card_details)['network'] ?? null;
        }


        // UPI
        elseif ($method === 'upi' && $this->vpa) {
            $response['payment_type'] = 'UPI';
            $response['payment_method'] = ucfirst($this->wallet ?? 'UPI');
            $response['upi_id'] = $this->vpa;
            $response['bank_name'] = $this->bank;
        }

        // NETBANKING
        elseif ($method === 'netbanking') {
            $response['payment_type'] = 'Net Banking';
            $response['payment_method'] = strtoupper($this->bank ?? 'Netbanking');
            $response['bank_name'] = $this->bank;
        }

        // WALLET
        elseif ($method === 'wallet') {
            $response['payment_type'] = 'Wallet';
            $response['payment_method'] = ucfirst($this->wallet ?? 'Wallet');
        }

        // FALLBACK
        else {
            $response['payment_type'] = ucfirst($method ?: 'Payment');
            $response['payment_method'] = ucfirst($method ?: 'Razorpay');
        }

        return $response;
    }
}
