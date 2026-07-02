@php
    $primaryColor = primary_color() ?? '#1e3a8a';
    $appName = app_name() ?? 'Telehealth Deploymeta';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Account Credentials</title>
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
                                Account Registered Successfully
                            </h1>
                            <p style="margin: 8px 0 0 0; color: rgba(255,255,255,0.9); font-size: 14px;">
                                Welcome to {{ $appName }}
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 40px;">
                            <p style="margin: 0 0 16px 0; color: #374151; font-size: 16px; line-height: 1.6;">
                                Dear <strong>{{ $patientName }}</strong>,
                            </p>
                            <p style="margin: 0 0 24px 0; color: #374151; font-size: 15px; line-height: 1.6;">
                                Your patient account has been successfully registered. You can now use these credentials to log in to our mobile application:
                            </p>

                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%"
                                style="background: #f9fafb; border-radius: 8px; margin-bottom: 28px; border: 1px solid #e5e7eb;">
                                <tr>
                                    <td style="padding: 20px;">
                                        <p style="margin: 0 0 6px 0; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280; font-weight: bold;">Login Email</p>
                                        <p style="margin: 0; font-size: 16px; font-weight: 600; color: #111827;">{{ $email }}</p>
                                        
                                        <p style="margin: 20px 0 6px 0; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280; font-weight: bold;">Your Password</p>
                                        <p style="margin: 0; font-size: 16px; font-weight: 600; color: #059669; font-family: monospace;">{{ $password }}</p>
                                    </td>
                                </tr>
                            </table>

                            <div style="background-color: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 20px; margin-bottom: 24px;">
                                <h3 style="margin: 0 0 8px 0; color: #1e3a8a; font-size: 15px; font-weight: 600;">
                                    Access Your Appointments on Mobile App
                                </h3>
                                <p style="margin: 0 0 16px 0; color: #1e40af; font-size: 14px; line-height: 1.5;">
                                    Please download our official mobile app. You can log in using the email and password above to check your upcoming appointments, view medical records, and consult with doctors seamlessly.
                                </p>
                                <a href="{{ play_store_url() }}" target="_blank" style="display: inline-block; background-color: {{ $primaryColor }}; color: #ffffff; padding: 10px 18px; font-size: 14px; font-weight: 600; text-decoration: none; border-radius: 6px;">
                                    Download on Google Play Store
                                </a>
                            </div>

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
