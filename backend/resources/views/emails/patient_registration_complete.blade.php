@php
    $primaryColor = primary_color();
    $appName = app_name();
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration complete</title>
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
                                Welcome to {{ $appName }}
                            </h1>
                            <p style="margin: 8px 0 0 0; color: rgba(255,255,255,0.9); font-size: 14px;">
                                Your patient profile is ready
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 40px;">
                            <p style="margin: 0 0 16px 0; color: #374151; font-size: 16px; line-height: 1.6;">
                                Dear <strong>{{ $patientName }}</strong>,
                            </p>
                            <p style="margin: 0 0 24px 0; color: #374151; font-size: 15px; line-height: 1.6;">
                                Thank you for registering. You can sign in with the email address below and the password you chose during registration.
                            </p>
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%"
                                style="background: #f9fafb; border-radius: 8px; margin-bottom: 24px;">
                                <tr>
                                    <td style="padding: 20px;">
                                        <p style="margin: 0 0 8px 0; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280;">Login email</p>
                                        <p style="margin: 0; font-size: 16px; font-weight: 600; color: #111827;">{{ $email }}</p>
                                        <p style="margin: 16px 0 8px 0; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280;">Password</p>
                                        @if ($actualPassword)
                                            <p style="margin: 0; font-size: 15px; color: #374151;">{{ $actualPassword }}</p>
                                        @else
                                            <p style="margin: 0; font-size: 15px; color: #374151;">{!! nl2br(e($passwordNote)) !!}</p>
                                        @endif
                                    </td>
                                </tr>
                            </table>

                            @if (!empty($appointmentSummary))
                                <h2 style="margin: 0 0 12px 0; font-size: 18px; color: #111827;">Appointment</h2>
                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%"
                                    style="border: 1px solid #e5e7eb; border-radius: 8px;">
                                    <tr>
                                        <td style="padding: 16px 20px;">
                                            @foreach ($appointmentSummary as $label => $value)
                                                @if ($value !== null && $value !== '')
                                                    <p style="margin: 0 0 8px 0; font-size: 14px; color: #374151;">
                                                        <strong style="color: #111827;">{{ $label }}:</strong> {{ $value }}
                                                    </p>
                                                @endif
                                            @endforeach
                                        </td>
                                    </tr>
                                </table>
                                @if (!empty($appointmentSummary['Payment']))
                                    <p style="margin: 16px 0 0 0; font-size: 13px; color: #6b7280; line-height: 1.5;">
                                        If payment is pending, complete checkout in the app using the Razorpay options shown after registration, or open the payment screen from your appointments list.
                                    </p>
                                @endif
                            @endif

                            <p style="margin: 32px 0 0 0; font-size: 13px; color: #9ca3af;">
                                This is an automated message from {{ $appName }}.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>
