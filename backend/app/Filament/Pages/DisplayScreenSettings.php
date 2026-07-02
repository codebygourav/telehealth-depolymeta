<?php

namespace App\Filament\Pages;

use App\Models\DisplayScreenSetting;
use App\Traits\HasCustomSidebar;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Illuminate\Support\Facades\Artisan;

class DisplayScreenSettings extends Settings
{
    use HasCustomSidebar;

    protected string $view = 'filament.pages.display-group-settings';

    protected static ?string $title = 'Display Settings';
    protected static ?string $slug = 'display-settings';
    protected static ?string $navigationLabel = 'Display Settings';
    protected static string|\UnitEnum|null $navigationGroup = 'Token Queue Display';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-tv';
    protected static ?int $navigationSort = 97;

    public string $settingsGroup = 'display';

    public string $pageHeading = 'Display Settings';

    public string $pageDescription = 'Password, doctor scope, queue rules, and public display copy.';

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public static function getSidebarOptions(): array
    {
        return [
            'label' => 'Display Settings',
            'icon' => 'heroicon-o-tv',
            'sort' => 97,
            'group' => 'Token Queue Display',
            'visible' => true,
        ];
    }

    public function mount(): void
    {
        if ($this->activeTab === 'app' || blank($this->activeTab)) {
            $this->activeTab = 'general';
        }

        $this->form->fill($this->loadSettings());
    }

    public function getDisplaySettingsTabs(): array
    {
        return [
            'general' => [
                'label' => 'General',
                'description' => 'Screen name, notice, and public copy.',
                'icon' => 'heroicon-o-swatch',
            ],
            'access' => [
                'label' => 'Access',
                'description' => 'Password protection and doctor targeting rules.',
                'icon' => 'heroicon-o-lock-closed',
            ],
            'layout' => [
                'label' => 'Queue Layout',
                'description' => 'Automatic rotation and ad behavior.',
                'icon' => 'heroicon-o-rectangle-group',
            ],
            'voice' => [
                'label' => 'Voice',
                'description' => 'Speech announcement language and template.',
                'icon' => 'heroicon-o-speaker-wave',
            ],
            'content' => [
                'label' => 'Content',
                'description' => 'Default labels and empty-state slide text.',
                'icon' => 'heroicon-o-photo',
            ],
        ];
    }

    protected function loadSettings(): array
    {
        $data = [];
        $config = config('settings', []);

        foreach (['display', 'display_ads'] as $group) {
            foreach (DisplayScreenSetting::getGroup($group) as $key => $value) {
                data_set($data, "{$group}.{$key}", $value);
            }
        }

        foreach (['display', 'display_ads'] as $groupKey) {
            foreach (($config[$groupKey]['sections'] ?? []) as $section) {
                foreach (($section['fields'] ?? []) as $fieldKey => $field) {
                    if (isset($field['default']) && data_get($data, "{$groupKey}.{$fieldKey}") === null) {
                        data_set($data, "{$groupKey}.{$fieldKey}", $field['default']);
                    }
                }
            }
        }

        return $data;
    }

    protected function buildFormSchema(): array
    {
        return [
            Section::make('General Screen Settings')
                ->description('Display name and core screen content.')
                ->schema($this->buildSectionsFromConfig('display', [
                    'general' => config('settings.display.sections.general', []),
                    'copy' => config('settings.display.sections.copy', []),
                ]))
                ->visible(fn () => $this->activeTab === 'general')
                ->columnSpanFull(),
            Section::make('Display Access')
                ->description('Password protection and doctor targeting rules for the token queue screen.')
                ->schema($this->buildSectionsFromConfig('display', [
                    'access' => config('settings.display.sections.access', []),
                ]))
                ->visible(fn () => $this->activeTab === 'access')
                ->columnSpanFull(),
            Section::make('Queue Layout')
                ->description('Doctor rotation and refresh cadence.')
                ->schema($this->buildSectionsFromConfig('display_ads', [
                    'layout' => config('settings.display_ads.sections.layout', []),
                    'media' => config('settings.display_ads.sections.media', []),
                ]))
                ->visible(fn () => $this->activeTab === 'layout')
                ->columnSpanFull(),
            Section::make('Voice Announcement')
                ->description('Configure browser-based token announcements.')
                ->schema($this->buildSectionsFromConfig('display_ads', [
                    'voice' => config('settings.display_ads.sections.voice', []),
                ]))
                ->visible(fn () => $this->activeTab === 'voice')
                ->columnSpanFull(),
            Section::make('Content Defaults')
                ->description('Slide labels and helper text for the ad carousel.')
                ->schema($this->buildSectionsFromConfig('display_ads', [
                    'content' => config('settings.display_ads.sections.content', []),
                ]))
                ->visible(fn () => $this->activeTab === 'content')
                ->columnSpanFull(),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        foreach ($data as $group => $groupSettings) {
            if (! is_array($groupSettings)) {
                continue;
            }

            foreach ($groupSettings as $settingKey => $value) {
                $type = 'string';

                if (is_bool($value)) {
                    $type = 'boolean';
                    $value = $value ? '1' : '0';
                } elseif (is_int($value)) {
                    $type = 'integer';
                } elseif (is_array($value)) {
                    $type = 'json';
                    $value = json_encode($value);
                }

                $fieldConfig = $this->getFieldConfig($group, $settingKey);

                DisplayScreenSetting::updateOrCreate(
                    ['group' => $group, 'key' => $settingKey],
                    [
                        'value' => $value,
                        'type' => $type,
                        'is_public' => (bool) ($fieldConfig['is_public'] ?? false),
                        'description' => $fieldConfig['description'] ?? null,
                    ]
                );
            }
        }

        DisplayScreenSetting::clearCache();
        Artisan::call('config:clear');

        Notification::make()
            ->title('Display settings saved successfully')
            ->success()
            ->send();
    }
}
