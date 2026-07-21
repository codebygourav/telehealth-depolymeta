# WhatsApp Notifier Package Setup

This project now includes a reusable local package:

- Package: `deploymeta/laravel-whatsapp-notifier`
- Path: `backend/packages/deploymeta/laravel-whatsapp-notifier`

## What it provides

- Reusable Laravel notification channel for WhatsApp Cloud API.
- Auto webhook route for Meta verification + callbacks.
- Database logs for outbound delivery and webhook status updates.
- Works with existing Laravel notifications (Expo/WebPush + WhatsApp together).

## Required env keys

Add these in `.env` (you already added most):

```env
WHATSAPP_NOTIFIER_ENABLED=true
WHATSAPP_API_VERSION=v23.0
WHATSAPP_ACCESS_TOKEN=...
WHATSAPP_PHONE_NUMBER_ID=...
WHATSAPP_BUSINESS_ACCOUNT_ID=...
WHATSAPP_VERIFY_TOKEN=choose-a-secret-token
WHATSAPP_WEBHOOK_PATH=api/v2/webhooks/whatsapp
WHATSAPP_WEBHOOK_ENABLED=true
WHATSAPP_DEFAULT_COUNTRY_CODE=91
```

## Webhook URL (required in Meta)

Set this callback URL in Meta App Dashboard > WhatsApp > Configuration:

`http://superadmin.deploymeta.com/api/v2/webhooks/whatsapp`

Use your `WHATSAPP_VERIFY_TOKEN` value as Verify Token in Meta dashboard.

## Laravel commands

Run after pulling code:

```bash
cd backend
composer install
php artisan migrate
php artisan config:clear
php artisan route:clear
```

## Reusing in other Laravel projects

Option 1: copy this package folder into that project and add a path repository in `composer.json`.

```json
"repositories": [
  {
    "type": "path",
    "url": "packages/deploymeta/laravel-whatsapp-notifier",
    "options": { "symlink": true }
  }
]
```

Then require it:

```bash
composer require deploymeta/laravel-whatsapp-notifier:* 
```

Option 2: publish this package to your private VCS registry and require it by version.

## Admin testing panel

In this project, open:

- Admin panel -> System & Settings -> WhatsApp Tester

From there you can:

- Send test message to any number
- View recent WhatsApp outbound and webhook logs
- Verify if credentials are loaded
