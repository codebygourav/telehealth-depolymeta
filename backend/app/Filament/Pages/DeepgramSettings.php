<?php

namespace App\Filament\Pages;

use App\Services\DeepgramService;
use App\Traits\HasCustomSidebar;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Section;
use Illuminate\Support\HtmlString;

class DeepgramSettings extends Settings
{
    use HasCustomSidebar;

    protected string $view = 'filament.pages.settings-group';

    protected static ?string $title = 'Deepgram Voice AI';

    protected static ?string $slug = 'deepgram-settings';

    public string $pageHeading = 'Deepgram Voice AI Settings';

    public string $pageDescription = 'Configure Deepgram speech-to-text for prescription voice entry. Manage API keys, model, language, and budget.';

    public static function getSidebarOptions(): array
    {
        return [
            'label'   => 'Deepgram Voice AI',
            'icon'    => 'heroicon-o-microphone',
            'sort'    => 6,
            'group'   => 'Medicine',
            'visible' => true,
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    protected function buildFormSchema(): array
    {
        $usageStats = DeepgramService::getUsageStats();
        $isEnabled  = (bool) config('deepgram.enabled', false);
        $hasKey     = filled(config('deepgram.api_key', ''));

        return array_merge(
            $this->buildSectionsFromConfig(
                'deepgram',
                config('settings.deepgram.sections', [])
            ),
            [
                Section::make('Current Usage & Status')
                    ->description('Live statistics from voice transcription logs.')
                    ->schema([
                        Placeholder::make('deepgram_usage_status')
                            ->label('Usage Overview')
                            ->content(new HtmlString($this->usageStatusHtml($usageStats, $isEnabled, $hasKey))),
                    ])
                    ->columnSpanFull(),
            ]
        );
    }

    private function usageStatusHtml(array $stats, bool $isEnabled, bool $hasKey): string
    {
        $statusColor = ($isEnabled && $hasKey) ? '#166534' : '#991b1b';
        $statusBg    = ($isEnabled && $hasKey) ? '#dcfce7' : '#fee2e2';
        $statusText  = ($isEnabled && $hasKey) ? 'Active' : ($isEnabled && ! $hasKey ? 'API Key Missing' : 'Disabled');

        $budgetMinutes    = $stats['budget_minutes'];
        $usedMinutes      = $stats['monthly_duration_minutes'];
        $remainingMinutes = $stats['remaining_minutes'];

        $budgetRow = $budgetMinutes > 0
            ? "<span style=\"font-size:13px;color:#475569;\"><strong>Remaining:</strong> {$remainingMinutes} min of {$budgetMinutes} min budget</span>"
            : "<span style=\"font-size:13px;color:#475569;\"><strong>Budget:</strong> Not set</span>";

        $totalCost    = '$' . $stats['total_credits_usd'];
        $monthlyCost  = '$' . $stats['monthly_credits_usd'];
        $totalRec     = $stats['total_recordings'];
        $monthlyMin   = $stats['monthly_duration_minutes'];
        $totalFailed  = $stats['total_failed'];

        return <<<HTML
<div style="display:grid;gap:16px;">
    <div style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;">
        <span style="display:inline-flex;align-items:center;border-radius:999px;padding:6px 14px;font-size:12px;font-weight:700;background:{$statusBg};color:{$statusColor};">{$statusText}</span>
        <span style="font-size:13px;color:#475569;"><strong>Model:</strong> nova-2</span>
        <span style="font-size:13px;color:#475569;"><strong>Monthly usage:</strong> {$monthlyMin} min</span>
        <span style="font-size:13px;color:#475569;"><strong>Monthly cost:</strong> {$monthlyCost}</span>
        {$budgetRow}
    </div>
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;">
        <div style="padding:14px;border-radius:12px;border:1px solid #e2e8f0;background:#f8fafc;text-align:center;">
            <div style="font-size:22px;font-weight:900;color:#0f172a;">{$totalRec}</div>
            <div style="font-size:12px;color:#64748b;margin-top:2px;">Total Recordings</div>
        </div>
        <div style="padding:14px;border-radius:12px;border:1px solid #e2e8f0;background:#f8fafc;text-align:center;">
            <div style="font-size:22px;font-weight:900;color:#0f172a;">{$totalFailed}</div>
            <div style="font-size:12px;color:#64748b;margin-top:2px;">Failed</div>
        </div>
        <div style="padding:14px;border-radius:12px;border:1px solid #e2e8f0;background:#f8fafc;text-align:center;">
            <div style="font-size:22px;font-weight:900;color:#0f172a;">{$monthlyMin}m</div>
            <div style="font-size:12px;color:#64748b;margin-top:2px;">This Month</div>
        </div>
        <div style="padding:14px;border-radius:12px;border:1px solid #e2e8f0;background:#f8fafc;text-align:center;">
            <div style="font-size:22px;font-weight:900;color:#0f172a;">{$totalCost}</div>
            <div style="font-size:12px;color:#64748b;margin-top:2px;">Total Cost (USD)</div>
        </div>
    </div>
    <div style="padding:12px 14px;border-radius:12px;border:1px dashed #cbd5e1;background:#f8fafc;color:#334155;font-size:13px;line-height:1.6;">
        <strong>Required .env variables:</strong><br>
        <code>DEEPGRAM_ENABLED=true</code><br>
        <code>DEEPGRAM_API_KEY=your_key_here</code><br>
        <code>DEEPGRAM_MODEL=nova-2</code><br>
        <code>DEEPGRAM_LANGUAGE=en</code><br>
        <code>DEEPGRAM_MONTHLY_BUDGET_MINUTES=500</code>
    </div>
</div>
HTML;
    }
}
