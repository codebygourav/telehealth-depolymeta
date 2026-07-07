@php
    $primaryColor = primary_color();
    $appName = app_name();
    $patientName = trim(($appointment->patient->first_name ?? '') . ' ' . ($appointment->patient->last_name ?? ''));
    $doctorName = trim(($appointment->doctor->first_name ?? '') . ' ' . ($appointment->doctor->last_name ?? ''));
    $dateStr = $appointment->appointment_date instanceof \Carbon\Carbon
        ? $appointment->appointment_date->format('M d, Y')
        : \Carbon\Carbon::parse($appointment->appointment_date)->format('M d, Y');
    $timeStr = \Carbon\Carbon::parse($appointment->appointment_time)->format('h:i A');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmed</title>
</head>
<body style="margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f7fa;">
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #f4f7fa;">
        <tr>
            <td style="padding: 40px 20px;">
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%"
                    style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);">
                    <tr>
                        <td style="background-color: {{ $primaryColor }}; padding: 32px 40px; border-radius: 12px 12px 0 0;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 24px; font-weight: 600;">
                                Booking Confirmed!
                            </h1>
                            <p style="margin: 8px 0 0 0; color: rgba(255,255,255,0.9); font-size: 14px;">
                                Your appointment is successfully booked and confirmed
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 40px;">
                            <p style="margin: 0 0 16px 0; color: #374151; font-size: 16px; line-height: 1.6;">
                                Dear <strong>{{ $patientName }}</strong>,
                            </p>
                            <p style="margin: 0 0 24px 0; color: #374151; font-size: 15px; line-height: 1.6;">
                                Your appointment with <strong>{{ $doctorName }}</strong> has been successfully scheduled. Below are the details of your appointment:
                            </p>    

                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%"
                                style="border: 1px solid #e5e7eb; border-radius: 8px; margin-bottom: 24px;">
                                <tr>
                                    <td style="padding: 20px;">
                                        <p style="margin: 0 0 12px 0; font-size: 14px; color: #374151;">
                                            <strong style="color: #111827;">Doctor:</strong> {{ $doctorName }}
                                        </p>
                                        <p style="margin: 0 0 12px 0; font-size: 14px; color: #374151;">
                                            <strong style="color: #111827;">Date:</strong> {{ $dateStr }}
                                        </p>
                                        <p style="margin: 0 0 12px 0; font-size: 14px; color: #374151;">
                                            <strong style="color: #111827;">Time:</strong> {{ $timeStr }}
                                        </p>
                                        <p style="margin: 0 0 12px 0; font-size: 14px; color: #374151;">
                                            <strong style="color: #111827;">Consultation Type:</strong> {{ ucfirst($appointment->consultation_type) }}
                                        </p>
                                        @if(strtolower($appointment->consultation_type) === 'in-person' && $appointment->availability?->opd_type)
                                            <p style="margin: 0 0 12px 0; font-size: 14px; color: #374151;">
                                                <strong style="color: #111827;">Consultation OPD Type:</strong> {{ ucfirst($appointment->availability->opd_type) }}
                                            </p>
                                        @endif
                                        @if($payment)
                                            <p style="margin: 0 0 12px 0; font-size: 14px; color: #374151;">
                                                <strong style="color: #111827;">Amount Paid:</strong> {{ currency_symbol() ?? '₹' }}{{ number_format($payment->amount, 2) }}
                                            </p>
                                            <p style="margin: 0; font-size: 14px; color: #374151;">
                                                <strong style="color: #111827;">Transaction ID:</strong> {{ $payment->razorpay_payment_id ?? $payment->transaction_id }}
                                            </p>
                                        @endif
                                    </td>
                                </tr>
                            </table>

                            @if(strtolower($appointment->consultation_type) === 'in-person')
                                <div style="background-color: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; padding: 20px; margin-bottom: 24px;">
                                    <p style="margin: 0; font-size: 14px; color: #b45309; line-height: 1.5;">
                                        <strong>Note:</strong> Please reach the clinic at least 45 minutes before your scheduled appointment.
                                    </p>
                                </div>
                            @endif

                            @if($appointment->consultation_type === 'video')
                                <div style="background-color: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 20px; margin-bottom: 24px;">
                                    <h3 style="margin: 0 0 8px 0; font-size: 15px; color: #1e40af;">Video Consultation Link</h3>
                                    <p style="margin: 0 0 12px 0; font-size: 14px; color: #1e3a8a; line-height: 1.5;">
                                        You can join the video consultation using the button below at the scheduled time.
                                    </p>
                                    @if($appointment->whereby_room_url)
                                        <a href="{{ $appointment->whereby_room_url }}" target="_blank"
                                           style="display: inline-block; background-color: #2563eb; color: #ffffff; padding: 10px 18px; border-radius: 6px; text-decoration: none; font-size: 14px; font-weight: 500;">
                                            Join Consultation
                                        </a>
                                    @else
                                        <p style="margin: 0; font-size: 13px; color: #4b5563; font-style: italic;">
                                            Link will be available in the app/website dashboard.
                                        </p>
                                    @endif
                                </div>
                            @endif

                            <p style="margin: 0 0 24px 0; color: #374151; font-size: 14px; line-height: 1.5;">
                                A PDF copy of your payment receipt has been attached to this email.
                            </p>

                            <p style="margin: 32px 0 0 0; font-size: 13px; color: #9ca3af; border-top: 1px solid #e5e7eb; padding-top: 16px;">
                                This is an automated message from {{ $appName }}. If you have any questions, please contact support.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
