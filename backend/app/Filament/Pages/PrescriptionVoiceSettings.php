<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Support\PrescriptionDictation;
use App\Traits\HasCustomSidebar;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Section;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\HtmlString;

class PrescriptionVoiceSettings extends Settings
{
    use HasCustomSidebar;

    protected string $view = 'filament.pages.settings-group';

    protected static ?string $title = 'Prescription Voice & Dictation';

    protected static ?string $slug = 'prescription-voice-settings';

    public string $pageHeading = 'Prescription Voice & Dictation';

    public string $pageDescription = 'Browser speech playback templates plus env-driven prescription dictation status for doctor testing.';

    public static function getSidebarOptions(): array
    {
        return [
            'label' => 'Voice & Dictation',
            'icon' => 'heroicon-o-speaker-wave',
            'sort' => 5,
            'group' => 'Medicine',
            'visible' => true,
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    protected function buildFormSchema(): array
    {
        $dictationSettings = PrescriptionDictation::settings();

        return array_merge(
            $this->buildSectionsFromConfig(
                'prescription_voice',
                config('settings.prescription_voice.sections', [])
            ),
            [
                Section::make('Prescription Dictation Status')
                    ->description('Overview of current environment variables.')
                    ->schema([
                        Placeholder::make('prescription_dictation_status')
                            ->label('Current Status')
                            ->content(new HtmlString($this->dictationStatusHtml($dictationSettings))),
                    ])
                    ->columnSpanFull()
            ]
        );
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $envUpdates = [];

        foreach (config('settings.prescription_voice.sections', []) as $section) {
            $dbGroup = $section['db_group'] ?? 'prescription_voice';

            foreach (($section['fields'] ?? []) as $fieldKey => $field) {
                $value = data_get($data, "{$dbGroup}.{$fieldKey}");

                $type = 'string';
                if (is_bool($value)) {
                    $type = 'boolean';
                } elseif (is_int($value)) {
                    $type = 'integer';
                } elseif (is_array($value)) {
                    $type = 'json';
                }

                Setting::setValue(
                    group: $dbGroup,
                    key: $fieldKey,
                    value: $value,
                    type: $type,
                    isPublic: (bool) ($field['is_public'] ?? false),
                );

                if (isset($field['env_key']) && $value !== null && $value !== '') {
                    $envValue = is_array($value) ? implode(',', $value) : $value;

                    if (is_bool($value)) {
                        $envValue = $value ? 'true' : 'false';
                    }

                    $envUpdates[$field['env_key']] = $envValue;
                }
            }
        }

        if (! empty($envUpdates)) {
            $this->updateEnvFile($envUpdates);
        }

        Setting::clearCache();
        Artisan::call('config:clear');

        Notification::make()
            ->title('Settings saved successfully')
            ->success()
            ->send();
    }

    private function dictationStatusHtml(array $settings): string
    {
        $enabled = (bool) ($settings['enabled'] ?? false);
        $mode = (string) ($settings['input_mode'] ?? PrescriptionDictation::MODE_OFF);
        $maxChars = (int) ($settings['text_mode_max_chars'] ?? 1000);
        $speechLocale = (string) ($settings['speech_locale'] ?? 'en-IN');
        $supportedLocales = implode(', ', $settings['supported_locales'] ?? ['auto', 'en-IN', 'en-US', 'hi-IN', 'pa-IN']);

        $statusColor = $enabled ? '#166534' : '#991b1b';
        $statusBg = $enabled ? '#dcfce7' : '#fee2e2';
        $statusText = $enabled
            ? 'Enabled'
            : 'Disabled';

        $modeLabel = match ($mode) {
            PrescriptionDictation::MODE_TEXT => 'Text mode',
            PrescriptionDictation::MODE_SPEECH => 'Speech mode',
            default => 'Off',
        };

        return <<<HTML
<div style="display:grid;gap:14px;">
    <div style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;">
        <span style="display:inline-flex;align-items:center;border-radius:999px;padding:6px 12px;font-size:12px;font-weight:700;background:{$statusBg};color:{$statusColor};">{$statusText}</span>
        <span style="font-size:13px;color:#475569;"><strong>Detected mode:</strong> {$modeLabel}</span>
        <span style="font-size:13px;color:#475569;"><strong>Text limit:</strong> {$maxChars} chars</span>
        <span style="font-size:13px;color:#475569;"><strong>Speech locale:</strong> {$speechLocale}</span>
        <span style="font-size:13px;color:#475569;"><strong>Supported locales:</strong> {$supportedLocales}</span>
    </div>
    <div style="padding:12px 14px;border-radius:12px;border:1px dashed #cbd5e1;background:#f8fafc;color:#334155;font-size:13px;line-height:1.6;">
        <div><strong>Required backend env values</strong></div>
        <div><code>PRESCRIPTION_DICTATION_ENABLED=true</code></div>
        <div><code>PRESCRIPTION_DICTATION_INPUT_MODE=text</code> or <code>PRESCRIPTION_DICTATION_INPUT_MODE=speech</code></div>
        <div><code>PRESCRIPTION_DICTATION_TEXT_MAX_CHARS=1000</code></div>
        <div><code>PRESCRIPTION_DICTATION_SPEECH_LOCALE=en-IN</code></div>
        <div><code>PRESCRIPTION_DICTATION_SUPPORTED_LOCALES=auto,en-IN,en-US,hi-IN,pa-IN</code></div>
        <div><code>PRESCRIPTION_DICTATION_ALLOW_CUSTOM_LOCALE=true</code></div>
    </div>
</div>
HTML;
    }
}
