<?php

namespace App\Filament\Pages;

use App\Models\CronSetting;
use App\Traits\HasCustomSidebar;
use Filament\Forms\Components\{
    Select,
    Toggle,
    Placeholder
};
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Illuminate\Support\HtmlString;
use Carbon\Carbon;

class CronSettings extends Page
{
    use HasCustomSidebar;

    protected string $view = 'filament.pages.cron-settings-group';

    protected static ?string $title = 'Cron & System Settings';

    protected static ?string $slug = 'cron-settings';

    protected static ?string $navigationLabel = 'Cron Settings';

    protected static string|\UnitEnum|null $navigationGroup = 'System & Settings';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    protected static ?int $navigationSort = 98;

    public ?array $data = [];

    public string $pageHeading = 'Cron & System Settings';

    public string $pageDescription = 'Configure Hostinger cron job schedules, memory limits, and monitor background task execution states.';

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public static function getSidebarOptions(): array
    {
        return [
            'label' => 'Cron Settings',
            'icon' => 'heroicon-o-clock',
            'sort' => 98,
            'group' => 'System & Settings',
            'visible' => true,
        ];
    }

    public function mount(): void
    {
        $settings = CronSetting::first();
        if ($settings) {
            $this->form->fill($settings->toArray());
        } else {
            $this->form->fill([
                'is_enabled' => true,
                'memory_limit' => '512M',
            ]);
        }
    }

    public function form(Schema $form): Schema
    {
        $settings = CronSetting::first();
        $lastRun = $settings?->last_run_at;
        $lastStatus = $settings?->last_run_status;
        $lastOutput = $settings?->last_run_output;

        return $form
            ->schema([
                Section::make('Hostinger Cron Setup & Live Heartbeat')
                    ->description('Monitor and configure Hostinger Advanced Cron Jobs connected to the Docker application container.')
                    ->schema([
                        Placeholder::make('cron_status')
                            ->label('Scheduler Status')
                            ->content(new HtmlString($this->getCronStatusHtml($lastRun, $lastStatus, $lastOutput))),
                    ])
                    ->columnSpanFull(),

                Section::make('Cron Job Manager')
                    ->description('Manage individual background task schedules, execution switches, and view outputs like WP Crontrol.')
                    ->schema([
                        Placeholder::make('manage_link')
                            ->label('')
                            ->content(new HtmlString('<div style="padding: 10px 0;"><a href="' . route('filament.admin.resources.cron-jobs.index') . '" style="background:var(--primary); color:white; padding:10px 20px; border-radius:8px; text-decoration:none; font-weight:700; font-size:13px; box-shadow:0 2px 4px rgba(5,91,217,0.2); transition:all 0.2s;" onmouseover="this.style.background=\'var(--primary-hover)\'" onmouseout="this.style.background=\'var(--primary)\'">Manage Cron Jobs & Logs &rarr;</a></div>')),
                    ])
                    ->columnSpanFull(),

                Section::make('General Settings')
                    ->description('Master switches for the application scheduler.')
                    ->schema([
                        Toggle::make('is_enabled')
                            ->label('Enable Scheduler')
                            ->helperText('If disabled, no scheduled commands will execute.')
                            ->default(true),
                        Select::make('memory_limit')
                            ->label('PHP Memory Limit')
                            ->options([
                                '128M' => '128 MB',
                                '256M' => '256 MB',
                                '512M' => '512 MB',
                                '1024M' => '1024 MB (1 GB)',
                                '2048M' => '2048 MB (2 GB)',
                            ])
                            ->default('512M')
                            ->helperText('Raise the memory limit dynamically for all web requests and cron jobs.'),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $settings = CronSetting::first() ?? new CronSetting();
        $settings->fill($this->form->getState());
        $settings->save();

        Notification::make()
            ->title('Cron settings saved successfully')
            ->success()
            ->send();
    }

    private function getCronStatusHtml(?Carbon $lastRun, ?string $status, ?string $output): string
    {
        $isCronWorking = $lastRun && $lastRun->gt(now()->subMinutes(3));
        
        $statusColor = $isCronWorking ? '#166534' : '#991b1b';
        $statusBg = $isCronWorking ? '#dcfce7' : '#fee2e2';
        $statusText = $isCronWorking ? 'Active & Working' : 'Inactive / Needs Setup';
        
        $lastRunText = $lastRun ? $lastRun->format('Y-m-d H:i:s') . ' (' . $lastRun->diffForHumans() . ')' : 'Never';
        $lastStatusText = $status ?: 'N/A';
        $lastOutputText = $output ?: 'No run log output recorded yet.';

        return <<<HTML
<div style="display:grid;gap:16px;">
    <!-- Status Alert Banner -->
    <div style="display:flex;flex-wrap:wrap;gap:12px;align-items:center;padding:12px 16px;border-radius:8px;background:{$statusBg};border:1px solid {$statusColor}40;">
        <span style="display:inline-flex;align-items:center;border-radius:999px;padding:4px 10px;font-size:11px;font-weight:700;background:{$statusColor}15;color:{$statusColor};text-transform:uppercase;letter-spacing:0.05em;">
            {$statusText}
        </span>
        <span style="font-size:13px;color:#334155;"><strong>Last Heartbeat:</strong> {$lastRunText}</span>
        <span style="font-size:13px;color:#334155;"><strong>Last Status:</strong> {$lastStatusText}</span>
    </div>

    <!-- Execution Logs -->
    <div style="padding:12px;border-radius:8px;background:#0f172a;color:#94a3b8;font-family:monospace;font-size:12px;line-height:1.5;max-height:80px;overflow-y:auto;border:1px solid #1e293b;">
        <div><strong>Heartbeat Output Log:</strong></div>
        <div style="color:#34d399;">{$lastOutputText}</div>
    </div>

    <!-- Hostinger hPanel Cron Setup Guide -->
    <div style="padding:18px;border-radius:12px;border:1px dashed #cbd5e1;background:#f8fafc;color:#334155;font-size:13px;line-height:1.65;">
        <div style="font-weight:700;margin-bottom:8px;color:#0f172a;font-size:14px;">How to Setup Cron in Hostinger Control Panel (hPanel) with Docker</div>
        <p style="margin-bottom:12px;color:#64748b;">You do not need to register separate cron jobs for different times. You only need to register <strong>ONE single cron job</strong> in Hostinger hPanel. Laravel will automatically run all other tasks at their scheduled times based on the custom table.</p>
        
        <div style="font-weight:600;margin-bottom:4px;color:#1e293b;">Step 1: Open Hostinger hPanel</div>
        <div style="margin-bottom:12px;color:#475569;">Go to your Hostinger dashboard &rarr; Websites &rarr; Advanced &rarr; <strong>Cron Jobs</strong>.</div>

        <div style="font-weight:600;margin-bottom:4px;color:#1e293b;">Step 2: Create a New Cron Job</div>
        <ul style="margin:0 0 12px 18px;padding:0;list-style-type:disc;color:#475569;">
            <li style="margin-bottom:4px;">Select type: <strong>Custom</strong></li>
            <li style="margin-bottom:4px;">In <strong>Command to Run</strong>, enter the following command based on your Hostinger Docker environment:
                <pre style="background:#0f172a;color:#34d399;padding:8px 12px;border-radius:6px;font-family:monospace;font-size:12px;margin:6px 0;overflow-x:auto;">docker exec -i dr-sushil-backend-app php artisan schedule:run >> /dev/null 2>&1</pre>
            </li>
            <li style="margin-bottom:4px;">Under <strong>Common Options</strong>, select: <code>Once per minute (* * * * *)</code> (or ensure Minute, Hour, Day, Month, Weekday are all set to <code>*</code>).</li>
        </ul>

        <div style="font-weight:600;margin-bottom:4px;color:#1e293b;">Step 3: Click Save</div>
        <div style="margin-bottom:12px;color:#475569;">Hostinger will now trigger the Laravel scheduler every minute. As soon as the first minute passes, this page's status indicator and individual task logs will update dynamically.</div>
    </div>
</div>
HTML;
    }

    public static function canAccess(): bool
    {
        return \Filament\Facades\Filament::auth()->user()?->hasRole('super_admin') ?? false;
    }
}
