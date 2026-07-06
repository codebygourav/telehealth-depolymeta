<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomCronJobResource\Pages\ListCustomCronJobs;
use App\Models\CustomCronJob;
use App\Traits\HasCustomSidebar;
use Filament\Forms\Components\{
    TextInput,
    Toggle,
    Select,
    Placeholder
};
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Filament\Actions\{
    EditAction,
    DeleteAction,
    Action
};
use Filament\Notifications\Notification;
use Cron\CronExpression;

class CustomCronJobResource extends Resource
{
    use HasCustomSidebar;

    protected static ?string $slug = 'cron-jobs';
    protected static ?string $model = CustomCronJob::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cpu-chip';
    protected static string|\UnitEnum|null $navigationGroup = 'System & Settings';
    protected static ?int $navigationSort = 99;
    protected static ?string $label = 'Cron Job';
    protected static ?string $pluralLabel = 'Cron Jobs';

    public static function getSidebarOptions(): array
    {
        return [
            'label'   => 'Cron Jobs',
            'icon'    => 'heroicon-o-cpu-chip',
            'sort'    => 99,
            'group'   => 'System & Settings',
            'visible' => true,
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('command')
                    ->label('Artisan Command')
                    ->placeholder('e.g. queue:work --stop-when-empty')
                    ->required()
                    ->maxLength(255),

                Select::make('preset_schedule')
                    ->label('Preset Schedules (Simple Timing Selector)')
                    ->options([
                        'custom' => 'Custom Cron Expression (set manually below)',
                        '* * * * *' => 'Every Minute (* * * * *)',
                        '*/5 * * * *' => 'Every 5 Minutes (*/5 * * * *)',
                        '*/15 * * * *' => 'Every 15 Minutes (*/15 * * * *)',
                        '*/30 * * * *' => 'Every 30 Minutes (*/30 * * * *)',
                        '0 * * * *' => 'Every Hour (0 * * * *)',
                        '0 */2 * * *' => 'Every 2 Hours (0 */2 * * *)',
                        '0 0 * * *' => 'Daily at Midnight (0 0 * * *)',
                        '0 12 * * *' => 'Daily at Noon (0 12 * * *)',
                        '0 0 * * 0' => 'Weekly on Sunday (0 0 * * 0)',
                        '0 0 1 * *' => 'Monthly on 1st of Month (0 0 1 * *)',
                    ])
                    ->dehydrated(false)
                    ->reactive()
                    ->default('custom')
                    ->afterStateHydrated(function ($state, $set, $get) {
                        $schedule = $get('schedule');
                        $presets = [
                            '* * * * *',
                            '*/5 * * * *',
                            '*/15 * * * *',
                            '*/30 * * * *',
                            '0 * * * *',
                            '0 */2 * * *',
                            '0 0 * * *',
                            '0 12 * * *',
                            '0 0 * * 0',
                            '0 0 1 * *',
                        ];
                        if (in_array($schedule, $presets, true)) {
                            $set('preset_schedule', $schedule);
                        } else {
                            $set('preset_schedule', 'custom');
                        }
                    })
                    ->afterStateUpdated(function ($state, $set) {
                        if ($state !== 'custom' && $state !== null) {
                            $set('schedule', $state);
                        }
                    }),

                TextInput::make('schedule')
                    ->label('Cron Schedule (Expression)')
                    ->placeholder('e.g. * * * * *')
                    ->required()
                    ->maxLength(255)
                    ->reactive()
                    ->afterStateUpdated(function ($state, $set) {
                        $presets = [
                            '* * * * *',
                            '*/5 * * * *',
                            '*/15 * * * *',
                            '*/30 * * * *',
                            '0 * * * *',
                            '0 */2 * * *',
                            '0 0 * * *',
                            '0 12 * * *',
                            '0 0 * * 0',
                            '0 0 1 * *',
                        ];
                        if (!in_array($state, $presets, true)) {
                            $set('preset_schedule', 'custom');
                        } else {
                            $set('preset_schedule', $state);
                        }
                    })
                    ->helperText('Standard 5-field cron expression (Minute Hour Day Month Weekday).')
                    ->rules([
                        function () {
                            return function (string $attribute, $value, \Closure $fail) {
                                try {
                                    if (!CronExpression::isValidExpression($value)) {
                                        $fail('The schedule must be a valid cron expression.');
                                    }
                                } catch (\Throwable $e) {
                                    $fail('The schedule must be a valid cron expression.');
                                }
                            };
                        },
                    ]),

                Placeholder::make('next_run_dates')
                    ->label('Upcoming Executions (Next 3 Runs)')
                    ->content(function ($get) {
                        $schedule = $get('schedule');
                        if (empty($schedule)) {
                            return 'Please select a preset or type a custom schedule above.';
                        }

                        try {
                            if (!CronExpression::isValidExpression($schedule)) {
                                return new \Illuminate\Support\HtmlString('<span style="color: #ef4444; font-weight: 600;">Invalid cron expression format.</span>');
                            }

                            $cron = new CronExpression($schedule);
                            $nextRuns = [];
                            $currentTime = new \DateTime();

                            for ($i = 0; $i < 3; $i++) {
                                $currentTime = $cron->getNextRunDate($currentTime, 0, $i > 0);
                                $nextRuns[] = $currentTime->format('Y-m-d H:i:s') . ' (Server Time)';
                            }

                            $html = '<ul style="list-style-type: disc; padding-left: 20px; color: #475569; font-size: 13px; line-height: 1.6;">';
                            foreach ($nextRuns as $run) {
                                $html .= '<li><strong>' . $run . '</strong></li>';
                            }
                            $html .= '</ul>';

                            return new \Illuminate\Support\HtmlString($html);
                        } catch (\Throwable $e) {
                            return new \Illuminate\Support\HtmlString('<span style="color: #ef4444; font-size: 13px;">Error calculating next execution times: ' . e($e->getMessage()) . '</span>');
                        }
                    }),

                TextInput::make('description')
                    ->label('Description')
                    ->maxLength(255)
                    ->columnSpanFull(),

                Toggle::make('is_active')
                    ->label('Is Active')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('command')
                    ->label('Artisan Command')
                    ->fontFamily('monospace')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('description')
                    ->label('Description')
                    ->limit(40),

                TextColumn::make('schedule')
                    ->label('Schedule')
                    ->fontFamily('monospace')
                    ->sortable(),

                ToggleColumn::make('is_active')
                    ->label('Active'),

                TextColumn::make('last_run_at')
                    ->label('Last Run')
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable()
                    ->placeholder('Never'),

                TextColumn::make('next_run_at')
                    ->label('Next Run')
                    ->state(function (CustomCronJob $record): ?\DateTime {
                        if (!$record->is_active || !$record->schedule) {
                            return null;
                        }
                        try {
                            if (!CronExpression::isValidExpression($record->schedule)) {
                                return null;
                            }
                            $cron = new CronExpression($record->schedule);
                            return $cron->getNextRunDate();
                        } catch (\Throwable $e) {
                            return null;
                        }
                    })
                    ->dateTime('Y-m-d H:i:s')
                    ->placeholder('N/A'),

                TextColumn::make('last_run_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Success' => 'success',
                        'Running' => 'warning',
                        'Failed'  => 'danger',
                        default   => 'gray',
                    })
                    ->placeholder('Pending'),
            ])
            ->actions([
                Action::make('run_now')
                    ->label('Run Now')
                    ->icon('heroicon-m-play')
                    ->color('success')
                    ->action(function (CustomCronJob $record) {
                        $record->update([
                            'last_run_at' => now(),
                            'last_run_status' => 'Running',
                            'last_run_output' => 'Manual run initiated...'
                        ]);
                        
                        try {
                            $outputBuffer = new \Symfony\Component\Console\Output\BufferedOutput();
                            $exitCode = \Illuminate\Support\Facades\Artisan::call($record->command, [], $outputBuffer);
                            $output = $outputBuffer->fetch();
                            
                            $status = ($exitCode === 0) ? 'Success' : 'Failed';
                            $record->update([
                                'last_run_status' => $status,
                                'last_run_output' => $output ?: 'Executed successfully with no output.'
                            ]);
                            
                            Notification::make()
                                ->title("Command '{$record->command}' executed. Status: {$status}")
                                ->color($exitCode === 0 ? 'success' : 'danger')
                                ->send();
                        } catch (\Throwable $e) {
                            $record->update([
                                'last_run_status' => 'Failed',
                                'last_run_output' => $e->getMessage() . "\n" . $e->getTraceAsString()
                            ]);
                            
                            Notification::make()
                                ->title("Command '{$record->command}' execution failed")
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('view_log')
                    ->label('View Log')
                    ->icon('heroicon-m-eye')
                    ->color('info')
                    ->modalHeading('Task Execution Log Output')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->modalContent(fn (CustomCronJob $record) => view('filament.pages.cron-job-log-modal', [
                        'record' => $record,
                    ])),

                EditAction::make()->modal(),
                
                DeleteAction::make()
                    ->hidden(fn(CustomCronJob $record): bool => $record->is_system),
            ])
            ->defaultSort('command', 'asc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomCronJobs::route('/'),
        ];
    }
}
