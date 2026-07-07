@php
    $primaryColor = primary_color() ?? '#1e3a8a';
    $appName = app_name() ?? 'CMC Telehealth';
    $patientName = trim(($appointment->patient->first_name ?? '') . ' ' . ($appointment->patient->last_name ?? ''));
    $doctorName = trim(($appointment->doctor->first_name ?? '') . ' ' . ($appointment->doctor->last_name ?? ''));
    $dateStr = $appointment->appointment_date instanceof \Carbon\Carbon
        ? $appointment->appointment_date->format('M d, Y')
        : \Carbon\Carbon::parse($appointment->appointment_date)->format('M d, Y');
    $timeStr = \Carbon\Carbon::parse($appointment->appointment_time)->format('h:i A');

    $method = strtolower($payment->payment_method ?? '');
    $paymentDetails = [];

    if ($method === 'card' && $payment->card_id) {
        $paymentDetails['Payment Type'] = 'Card';
        $paymentDetails['Payment Method'] = strtoupper(optional($payment->card_details)['network'] ?? 'CARD');
        $paymentDetails['Card Last 4'] = optional($payment->card_details)['last4'] ?? substr($payment->card_id, -4);
        $paymentDetails['Bank Name'] = optional($payment->card_details)['issuer'] ?? 'Unknown';
        if (optional($payment->card_details)['type']) {
            $paymentDetails['Card Type'] = ucfirst(optional($payment->card_details)['type']);
        }
    }
    elseif ($method === 'upi' && $payment->vpa) {
        $paymentDetails['Payment Type'] = 'UPI';
        $paymentDetails['Payment Method'] = ucfirst($payment->wallet ?? 'UPI');
        $paymentDetails['UPI ID'] = $payment->vpa;
        if ($payment->bank) {
            $paymentDetails['Bank Name'] = $payment->bank;
        }
    }
    elseif ($method === 'netbanking') {
        $paymentDetails['Payment Type'] = 'Net Banking';
        $paymentDetails['Payment Method'] = strtoupper($payment->bank ?? 'Netbanking');
        if ($payment->bank) {
            $paymentDetails['Bank Name'] = $payment->bank;
        }
    }
    elseif ($method === 'wallet') {
        $paymentDetails['Payment Type'] = 'Wallet';
        $paymentDetails['Payment Method'] = ucfirst($payment->wallet ?? 'Wallet');
    }
    else {
        $paymentDetails['Payment Type'] = ucfirst($method ?: 'Payment');
        $paymentDetails['Payment Method'] = ucfirst($method ?: 'Razorpay');
    }
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction Notification - Paid</title>
</head>
<body style="margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f7fa;">
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #f4f7fa;">
        <tr>
            <td style="padding: 40px 20px;">
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%"
                    style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);">
                    <tr>
                        <td style="background-color: #059669; padding: 32px 40px; border-radius: 12px 12px 0 0;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 24px; font-weight: 600;">
                                Transaction Paid Successfully
                            </h1>
                            <p style="margin: 8px 0 0 0; color: rgba(255,255,255,0.9); font-size: 14px;">
                                Notification for Telemedicine Desk
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 40px;">
                            <p style="margin: 0 0 16px 0; color: #374151; font-size: 16px; line-height: 1.6;">
                                Hello Telemedicine Team,
                            </p>
                            <p style="margin: 0 0 24px 0; color: #374151; font-size: 15px; line-height: 1.6;">
                                A payment has been successfully processed and verified for the following appointment:
                            </p>

                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%"
                                style="border: 1px solid #e5e7eb; border-radius: 8px; margin-bottom: 24px;">
                                <tr>
                                    <td style="padding: 20px;">
                                        <p style="margin: 0 0 12px 0; font-size: 14px; color: #374151;">
                                            <strong style="color: #111827;">Patient Name:</strong> {{ $patientName }}
                                        </p>
                                        <p style="margin: 0 0 12px 0; font-size: 14px; color: #374151;">
                                            <strong style="color: #111827;">Doctor Name:</strong> {{ $doctorName }}
                                        </p>
                                        <p style="margin: 0 0 12px 0; font-size: 14px; color: #374151;">
                                            <strong style="color: #111827;">Appointment Date:</strong> {{ $dateStr }}
                                        </p>
                                        <p style="margin: 0 0 12px 0; font-size: 14px; color: #374151;">
                                            <strong style="color: #111827;">Appointment Time:</strong> {{ $timeStr }}
                                        </p>
                                        <p style="margin: 0 0 12px 0; font-size: 14px; color: #374151;">
                                            <strong style="color: #111827;">Consultation Type:</strong> {{ ucfirst($appointment->consultation_type) }}
                                        </p>
                                        <p style="margin: 0 0 12px 0; font-size: 14px; color: #374151;">
                                            <strong style="color: #111827;">Amount Paid:</strong> {{ currency_symbol() ?? '₹' }}{{ number_format($payment->amount, 2) }}
                                        </p>
                                        <p style="margin: 0 0 12px 0; font-size: 14px; color: #374151;">
                                            <strong style="color: #111827;">Payment ID:</strong> {{ $payment->razorpay_payment_id ?? $payment->transaction_id ?? 'N/A' }}
                                        </p>
                                        <p style="margin: 0 0 12px 0; font-size: 14px; color: #374151;">
                                            <strong style="color: #111827;">Order ID:</strong> {{ $payment->razorpay_order_id ?? 'N/A' }}
                                        </p>

                                        @foreach($paymentDetails as $label => $value)
                                            <p style="margin: 0 0 12px 0; font-size: 14px; color: #374151;">
                                                <strong style="color: #111827;">{{ $label }}:</strong> {{ $value }}
                                            </p>
                                        @endforeach

                                        <p style="margin: 0; font-size: 14px; color: #374151;">
                                            <strong style="color: #111827;">Transaction Status:</strong> <span style="color: #059669; font-weight: bold;">Paid</span>
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin: 0 0 24px 0; color: #374151; font-size: 14px; line-height: 1.5;">
                                The payment receipt has been generated and attached to this email. Please review the transaction details in your admin console.
                            </p>

                            <p style="margin: 32px 0 0 0; font-size: 13px; color: #9ca3af; border-top: 1px solid #e5e7eb; padding-top: 16px;">
                                This is an automated message from {{ $appName }} System.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
