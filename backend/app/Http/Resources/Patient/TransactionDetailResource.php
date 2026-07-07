<?php

namespace App\Http\Resources\Patient;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Enums\PaymentStatus;
use Carbon\Carbon;

class TransactionDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $statusEnum = $this->status instanceof PaymentStatus
            ? $this->status
            : PaymentStatus::tryFrom($this->status);

        $appointment = $this->appointment;
        $doctor = $appointment?->doctor;

        // Detect method
        $method = strtolower($this->payment_method ?? '');

        // Base response
        $response = [
            'id' => $this->id,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'status' => $statusEnum->value,
            'status_label' => $statusEnum->label(),
            'date' => Carbon::parse($this->created_at)->format('d M, Y, h:i A'),

            'transaction_id' => $this->transaction_id ?? $this->razorpay_payment_id,
            'order_id' => $this->razorpay_order_id,

            'paid_to' => $doctor
                ? $doctor->first_name . ' ' . $doctor->last_name
                : 'Medical Service',

            'receipt_url' => $this->receipt_pdf
                ? asset('storage/' . $this->receipt_pdf)
                : null,
        ];

        // --------------------
        // METHOD-SPECIFIC DATA
        // --------------------

        // CARD
        if ($method === 'card') {
            $response['payment_type'] = 'Card';
            $response['payment_method'] = $this->payment_method
                ? ucfirst($this->payment_method)
                : 'Unknown';
            $response['card_last4'] = optional($this->card_details)['last4']
                ?? substr($this->card_id, -4);
            $response['bank_name'] = optional($this->card_details)['issuer'] ?? 'Unknown';
            $response['card_type'] = optional($this->card_details)['type'] ?? 'Unknown';
            $response['network']   = optional($this->card_details)['network'] ?? 'Unknown';
        }

        // UPI
        elseif ($method === 'upi') {
            $response['payment_type'] = 'UPI Payment';
            $response['payment_method'] = $this->payment_method
                ? ucfirst($this->payment_method)
                : 'Unknown';
            $response['upi_id'] = $this->vpa;
            $response['bank_name'] = $this->bank;
        }

        // NETBANKING
        elseif ($method === 'netbanking') {
            $response['payment_type'] = 'Net Banking';
            $response['payment_method'] = ucfirst($this->payment_method ?? 'Unknown');
            $response['bank_name'] = $this->bank;
        }

        // WALLET
        elseif ($method === 'wallet') {
            $response['payment_type'] = 'Wallet';
            $response['payment_method'] = ucfirst($this->payment_method ?? 'Unknown');
        }

        // FALLBACK
        else {
            $response['payment_type'] = ucfirst($method ?: 'Unknown');
            $response['payment_method'] = ucfirst($method ?: 'Unknown');
        }

        return $response;
    }
}