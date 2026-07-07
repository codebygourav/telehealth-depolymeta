@php
    $primaryColor = primary_color();
    $appName = app_name();
    $patientName = trim(($patient->first_name ?? '') . ' ' . ($patient->last_name ?? '')) ?: 'Patient';

    $appointmentDetails = function ($appointment): array {
        if (! $appointment) {
            return [];
        }

        $doctorName = $appointment->doctor
            ? trim(($appointment->doctor->first_name ?? '') . ' ' . ($appointment->doctor->last_name ?? ''))
            : 'Doctor';

        return [
            'Doctor' => $doctorName,
            'Date' => $appointment->appointment_date
                ? \Carbon\Carbon::parse($appointment->appointment_date)->format('M d, Y')
                : '—',
            'Time' => $appointment->appointment_time
                ? \Carbon\Carbon::parse($appointment->appointment_time)->format('h:i A')
                : '—',
            'Consultation Type' => \Illuminate\Support\Str::of((string) $appointment->consultation_type)->replace('-', ' ')->title()->toString(),
            'Status' => $appointment->status instanceof \BackedEnum
                ? \Illuminate\Support\Str::of($appointment->status->value)->replace('_', ' ')->title()->toString()
                : \Illuminate\Support\Str::of((string) $appointment->status)->replace('_', ' ')->title()->toString(),
        ];
    };

    $nextDetails = [
        'Doctor' => $nextSlot['doctor_name'] ?? 'Doctor',
        'Date' => !empty($nextSlot['date']) ? \Carbon\Carbon::parse($nextSlot['date'])->format('M d, Y') : '—',
        'Time' => !empty($nextSlot['start_time'])
            ? \Carbon\Carbon::parse($nextSlot['start_time'])->format('h:i A') . (!empty($nextSlot['end_time']) ? ' - ' . \Carbon\Carbon::parse($nextSlot['end_time'])->format('h:i A') : '')
            : '—',
        'Consultation Type' => \Illuminate\Support\Str::of((string) ($nextSlot['consultation_type'] ?? ''))->replace('-', ' ')->title()->toString(),
        'OPD Type' => \Illuminate\Support\Str::of((string) ($nextSlot['opd_type'] ?? ''))->replace('-', ' ')->title()->toString(),
    ];
    $previousDetails = $appointmentDetails($previousAppointment);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Next Appointment</title>
</head>
<body style="margin:0;padding:0;font-family:Segoe UI,Tahoma,Geneva,Verdana,sans-serif;background:#f3f6f8;color:#111827;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f3f6f8;">
        <tr>
            <td style="padding:24px 12px;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:680px;margin:0 auto;background:#ffffff;border-radius:14px;overflow:hidden;border:1px solid #e5e7eb;">
                    <tr>
                        <td style="background:{{ $primaryColor }};padding:30px 28px;">
                            <p style="margin:0 0 8px;color:rgba(255,255,255,.78);font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;">{{ $appName }}</p>
                            <h1 style="margin:0;color:#ffffff;font-size:24px;line-height:1.25;font-weight:800;">Your Next Appointment Details</h1>
                            <p style="margin:10px 0 0;color:rgba(255,255,255,.9);font-size:15px;line-height:1.5;">Please review the appointment below and book/confirm your visit as early as possible.</p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:28px;">
                            <p style="margin:0 0 14px;color:#374151;font-size:16px;line-height:1.6;">
                                Dear <strong>{{ $patientName }}</strong>,
                            </p>
                            <p style="margin:0 0 22px;color:#4b5563;font-size:15px;line-height:1.7;">
                                We are sharing your next available appointment information for your reference. Kindly review the date, time, and doctor details. If this appointment needs any change, please contact the clinic/admin team promptly.
                            </p>

                            @if ($adminMessage)
                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-bottom:22px;background:#ecfdf5;border:1px solid #bbf7d0;border-radius:10px;">
                                    <tr>
                                        <td style="padding:14px 16px;color:#065f46;font-size:14px;line-height:1.6;">
                                            <strong>Message from admin:</strong><br>
                                            {{ $adminMessage }}
                                        </td>
                                    </tr>
                                </table>
                            @endif

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-bottom:22px;border:1px solid #dbeafe;background:#eff6ff;border-radius:12px;">
                                <tr>
                                    <td style="padding:18px;">
                                        <p style="margin:0 0 12px;color:#1e3a8a;font-size:13px;font-weight:800;text-transform:uppercase;letter-spacing:.04em;">Selected Next Appointment</p>
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                            <tr>
                                                <td style="padding:8px 0;width:34%;color:#475569;font-size:13px;font-weight:700;">Date</td>
                                                <td style="padding:8px 0;color:#111827;font-size:15px;font-weight:800;text-align:right;">{{ $nextDetails['Date'] ?? '—' }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding:8px 0;width:34%;color:#475569;font-size:13px;font-weight:700;">Time</td>
                                                <td style="padding:8px 0;color:#111827;font-size:15px;font-weight:800;text-align:right;">{{ $nextDetails['Time'] ?? '—' }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding:8px 0;width:34%;color:#475569;font-size:13px;font-weight:700;">Doctor</td>
                                                <td style="padding:8px 0;color:#111827;font-size:15px;font-weight:800;text-align:right;">{{ $nextDetails['Doctor'] ?? '—' }}</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <h2 style="margin:0 0 10px;color:#111827;font-size:17px;font-weight:800;">Appointment Information</h2>
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="border:1px solid #e5e7eb;border-radius:10px;margin-bottom:22px;">
                                <tr>
                                    <td style="padding:16px;">
                                        @foreach ($nextDetails as $label => $value)
                                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                                <tr>
                                                    <td style="padding:7px 0;color:#6b7280;font-size:13px;font-weight:700;width:42%;">{{ $label }}</td>
                                                    <td style="padding:7px 0;color:#111827;font-size:14px;font-weight:800;text-align:right;">{{ $value ?: '—' }}</td>
                                                </tr>
                                            </table>
                                        @endforeach
                                    </td>
                                </tr>
                            </table>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-bottom:22px;background:#fffbeb;border:1px solid #fde68a;border-radius:10px;">
                                <tr>
                                    <td style="padding:14px 16px;color:#92400e;font-size:14px;line-height:1.6;">
                                        <strong>Important:</strong> Please keep this appointment date reserved. For in-person visits, arrive early enough to complete registration and preliminary formalities.
                                    </td>
                                </tr>
                            </table>

                            <h2 style="margin:0 0 10px;color:#111827;font-size:16px;font-weight:800;">Previous Appointment Reference</h2>
                            @if ($previousAppointment)
                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="border:1px solid #e5e7eb;border-radius:10px;margin-bottom:22px;">
                                    <tr>
                                        <td style="padding:16px;">
                                            @foreach ($previousDetails as $label => $value)
                                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                                    <tr>
                                                        <td style="padding:6px 0;color:#6b7280;font-size:13px;font-weight:700;width:42%;">{{ $label }}</td>
                                                        <td style="padding:6px 0;color:#111827;font-size:14px;font-weight:800;text-align:right;">{{ $value ?: '—' }}</td>
                                                    </tr>
                                                </table>
                                            @endforeach
                                        </td>
                                    </tr>
                                </table>
                            @else
                                <p style="margin:0 0 22px;color:#6b7280;font-size:14px;line-height:1.6;">No previous appointment was found in the system.</p>
                            @endif

                            <p style="margin:26px 0 0;color:#9ca3af;font-size:13px;line-height:1.6;border-top:1px solid #e5e7eb;padding-top:16px;">
                                This email was sent by {{ $appName }} admin. If you have questions or need to request a change, please contact the clinic support team.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
