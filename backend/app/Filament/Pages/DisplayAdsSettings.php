<?php

namespace App\Filament\Pages;

use App\Traits\HasCustomSidebar;
use Filament\Schemas\Components\Section;

class DisplayAdsSettings extends Settings
{
    use HasCustomSidebar;

    protected string $view = 'filament.pages.display-group-settings';

    protected static ?string $title = 'Display Ads Settings';
    protected static ?string $slug = 'display-ads-settings';

    public string $settingsGroup = 'display_ads';

    public string $pageHeading = 'Display Ads Settings';

    public string $pageDescription = 'Template mode, rotation timing, and ad content defaults.';

    public static function getSidebarOptions(): array
    {
        return [
            'label' => 'Display Ads',
            'icon' => 'heroicon-o-photo',
            'sort' => 97,
            'group' => 'Token Queue Display',
            'visible' => false,
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public function mount(): void
    {
        parent::mount();
    }

    protected function buildFormSchema(): array
    {
        return [];
    }
}