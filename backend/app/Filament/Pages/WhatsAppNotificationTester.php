<?php

namespace App\Filament\Pages;

use App\Traits\HasCustomSidebar;
use Deploymeta\WhatsAppNotifier\Client\MetaCloudWhatsAppClient;
use Deploymeta\WhatsAppNotifier\Messages\WhatsAppMessage;
use Deploymeta\WhatsAppNotifier\Models\WhatsAppMessageLog;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class WhatsAppNotificationTester extends Page implements HasForms
{
    use InteractsWithForms;
    use HasCustomSidebar;

    protected string $view = 'filament.pages.whatsapp-notification-tester';

    protected static ?string $slug = 'whatsapp-notification-tester';

    protected static ?string $navigationLabel = 'WhatsApp Tester';

    protected static ?string $title = 'WhatsApp Notification Tester';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?int $navigationSort = 99;

    public array $data = [];

    public array $recentLogs = [];

    public function mount(): void
    {
        $this->form->fill([
            'mode' => 'text',
            'phone' => '',
            'message' => 'Hello from Telehealth notification service.',
            'template_name' => '',
            'template_language' => 'en_US',
        ]);

        $this->refreshLogs();
    }

    public static function getSidebarOptions(): array
    {
        return [
            'label' => 'WhatsApp Tester',
            'icon' => 'heroicon-o-chat-bubble-left-right',
            'sort' => 99,
            'group' => 'System & Settings',
            'visible' => true,
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public static function canAccess(): bool
    {
        $user = \Filament\Facades\Filament::auth()->user();

        return $user && method_exists($user, 'hasRole')
            ? (bool) $user->hasRole('super_admin')
            : false;
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Send Test WhatsApp')
                    ->description('Send a test message using Meta Cloud API from this admin panel.')
                    ->schema([
                        Select::make('mode')
                            ->label('Message Mode')
                            ->options([
                                'text' => 'Text message',
                                'template' => 'Template message',
                            ])
                            ->default('text')
                            ->required(),
                        TextInput::make('phone')
                            ->label('Recipient Number')
                            ->placeholder('+919876543210 or 9876543210')
                            ->required(),
                        Textarea::make('message')
                            ->label('Message Body')
                            ->rows(4)
                            ->placeholder('Required for text mode'),
                        TextInput::make('template_name')
                            ->label('Template Name')
                            ->placeholder('Required for template mode'),
                        TextInput::make('template_language')
                            ->label('Template Language')
                            ->default('en_US')
                            ->placeholder('en_US'),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function sendTest(): void
    {
        $state = $this->form->getState();

        $mode = (string) ($state['mode'] ?? 'text');
        $phone = trim((string) ($state['phone'] ?? ''));
        $messageText = trim((string) ($state['message'] ?? ''));
        $templateName = trim((string) ($state['template_name'] ?? ''));
        $templateLanguage = trim((string) ($state['template_language'] ?? 'en_US'));

        if ($phone === '') {
            Notification::make()->title('Recipient number is required.')->danger()->send();
            return;
        }

        if ($mode === 'template' && $templateName === '') {
            Notification::make()->title('Template name is required in template mode.')->danger()->send();
            return;
        }

        if ($mode === 'text' && $messageText === '') {
            Notification::make()->title('Message body is required in text mode.')->danger()->send();
            return;
        }

        try {
            /** @var MetaCloudWhatsAppClient $client */
            $client = app(MetaCloudWhatsAppClient::class);

            $normalizedPhone = $client->normalizePhoneNumber($phone);

            if ($normalizedPhone === '') {
                Notification::make()->title('Invalid recipient number.')->danger()->send();
                return;
            }

            $message = $mode === 'template'
                ? WhatsAppMessage::template($templateName, $templateLanguage)
                : WhatsAppMessage::text($messageText);

            $client->send($normalizedPhone, $message);

            Notification::make()
                ->title('WhatsApp message queued to Meta Cloud API.')
                ->success()
                ->send();

            $this->refreshLogs();
        } catch (\Throwable $exception) {
            Notification::make()
                ->title('WhatsApp send failed: ' . $exception->getMessage())
                ->danger()
                ->send();

            $this->refreshLogs();
        }
    }

    public function refreshLogs(): void
    {
        $this->recentLogs = WhatsAppMessageLog::query()
            ->latest('id')
            ->limit(12)
            ->get()
            ->map(function (WhatsAppMessageLog $log) {
                return [
                    'created_at' => optional($log->created_at)->format('Y-m-d H:i:s'),
                    'channel' => $log->channel,
                    'to' => $log->to,
                    'message_type' => $log->message_type,
                    'status' => $log->status,
                    'wa_message_id' => $log->wa_message_id,
                    'error_message' => $log->error_message,
                ];
            })
            ->toArray();
    }

    public function isConfigured(): bool
    {
        return (string) config('whatsapp-notifier.access_token') !== ''
            && (string) config('whatsapp-notifier.phone_number_id') !== '';
    }

    public function getWebhookUrl(): string
    {
        $baseUrl = rtrim((string) config('app.url'), '/');
        $path = '/' . ltrim((string) config('whatsapp-notifier.webhook.path', 'api/v2/webhooks/whatsapp'), '/');

        return $baseUrl . $path;
    }
}
