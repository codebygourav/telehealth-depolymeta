<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Verify Your Email</title>
    <style>
        body {font-family: 'Helvetica', 'Arial', sans-serif; background: #f9f9f9; padding: 20px;}
        .container {background: #ffffff; padding: 30px; border-radius: 8px; max-width: 600px; margin: auto; box-shadow: 0 2px 8px rgba(0,0,0,0.1);}
        .header {font-size: 24px; font-weight: bold; color: #333; margin-bottom: 20px;}
        .content {font-size: 16px; color: #555; line-height: 1.5;}
        .code {display: inline-block; background: #f0f0f0; padding: 10px 15px; border-radius: 4px; font-weight: bold; margin: 10px 0;}
        .footer {margin-top: 30px; font-size: 14px; color: #999;}
    </style>
</head>
<body>
    <div class="container">
        <div class="header">Verify Your Email – Telehealth Deploymeta</div>
        <div class="content">
            <p>Thank you for registering with Telehealth Deploymeta.</p>
            <p>Your verification code is:</p>
            <div class="code">{{ $token }}</div>
            <p>Please use this code to verify your email address and continue with your profile.</p>
            <p>If you did not request this email, you can safely ignore it.</p>
        </div>
        <div class="footer">
            © {{ date('Y') }} Telehealth Deploymeta. All rights reserved.
        </div>
    </div>
</body>
</html>
