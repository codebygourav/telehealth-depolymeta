<?php

namespace App\Filament\Pages;

use App\Enums\AppointmentStatus;
use App\Filament\Resources\Appointments\AppointmentResource;
use App\Models\Appointment;
use App\Models\VideoConsultation;
use App\Services\WherebyService;
use App\Traits\HasCustomSidebar;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class ManageVideoLinks extends Page implements HasTable
{
    use HasCustomSidebar;
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedVideoCamera;

    protected string $view = 'filament.pages.manage-video-links';

    protected static ?string $slug = 'manage-video-links';

    protected static ?string $navigationLabel = 'Video Links';

    protected static ?string $title = 'Manage Video Links';

    public string $scope = 'today';

    public static function getSidebarOptions(): array
    {
        return [
            'label' => 'Video Links',
            'icon' => 'heroicon-o-video-camera',
            'sort' => 11,
            'group' => 'Appointments & Finance',
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function canAccess(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if ($user && method_exists($user, 'hasRole') && $user->hasRole('super_admin')) {
            return true;
        }

        $module = static::$slug ?? strtolower(class_basename(static::class));

        return check_permission([
            "{$module}.view",
            "{$module}.view_any",
            "{$module}.manage_own",
            'appointments.view',
            'appointments.view_any',
        ]);
    }

    public function setScope(string $scope): void
    {
        $this->scope = $scope;

        if (method_exists($this, 'resetTable')) {
            $this->resetTable();
        }
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->defaultSort('appointment_date')
            ->columns([
                TextColumn::make('appointment_date')
                    ->label('Visit Date')
                    ->html()
                    ->formatStateUsing(function (Appointment $record): string {
                        if (! $record->appointment_date) {
                            return '-';
                        }

                        $date = Carbon::parse($record->appointment_date)->format('M d, Y');
                        $startTime = $record->appointment_time
                            ? Carbon::parse($record->appointment_time)->format('h:i A')
                            : '';
                        $endTime = $record->appointment_end_time
                            ? Carbon::parse($record->appointment_end_time)->format('h:i A')
                            : '';
                        $timeRange = trim("{$startTime} - {$endTime}", ' -');
                        $todayBadge = Carbon::parse($record->appointment_date)->isToday()
                            ? "<span class='ml-1 inline-flex items-center rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-800'>Today</span>"
                            : '';

                        return "<div class='flex flex-col gap-1'>
                                    <div class='flex items-center'>
                                        <span class='text-sm font-semibold'>{$date}</span>
                                        {$todayBadge}
                                    </div>
                                    <span class='text-xs text-gray-500'>{$timeRange}</span>
                                </div>";
                    })
                    ->sortable(),

                TextColumn::make('patient.first_name')
                    ->label('Patient')
                    ->formatStateUsing(fn (Appointment $record): string => trim(
                        ($record->patient?->first_name ?? '') . ' ' . ($record->patient?->last_name ?? '')
                    ) ?: 'N/A')
                    ->searchable(['patient.first_name', 'patient.last_name']),

                TextColumn::make('doctor.first_name')
                    ->label('Doctor')
                    ->formatStateUsing(fn (Appointment $record): string => $record->doctor
                        ? trim($record->doctor->first_name . ' ' . $record->doctor->last_name)
                        : 'N/A')
                    ->searchable(['doctor.first_name', 'doctor.last_name']),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof AppointmentStatus
                        ? $state->label()
                        : ucfirst((string) $state)),

                TextColumn::make('video_link_status')
                    ->label('Video Link')
                    ->badge()
                    ->getStateUsing(fn (Appointment $record): string => $this->getVideoLinkStatus($record))
                    ->color(fn (string $state): string => match ($state) {
                        'Ready' => 'success',
                        'Partial' => 'warning',
                        default => 'danger',
                    }),

                TextColumn::make('id')
                    ->label('Appointment ID')
                    ->limit(12)
                    ->copyable()
                    ->searchable(),
            ])
            ->filters([
                Filter::make('scope')
                    ->label('Date Scope')
                    ->form([])
                    ->query(fn (Builder $query): Builder => $query),

                SelectFilter::make('status')
                    ->label('Appointment Status')
                    ->options(collect(AppointmentStatus::cases())->mapWithKeys(
                        fn (AppointmentStatus $status) => [$status->value => $status->label()]
                    )->toArray()),
            ])
            ->recordActions([
                Action::make('generate_link')
                    ->label('Generate Link')
                    ->icon('heroicon-o-link')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Generate video link')
                    ->modalDescription('This will create a Whereby room and save host and participant URLs for this appointment.')
                    ->visible(fn (Appointment $record): bool => ! $this->hasCompleteVideoLinks($record))
                    ->action(function (Appointment $record): void {
                        $this->generateVideoLink($record);
                    }),

                Action::make('view_links')
                    ->label('View Links')
                    ->icon('heroicon-o-eye')
                    ->color('success')
                    ->visible(fn (Appointment $record): bool => $this->hasCompleteVideoLinks($record))
                    ->modalHeading('Video consultation links')
                    ->modalContent(fn (Appointment $record) => view('filament.pages.video-consultation-urls', [
                        'videoConsultation' => $record->videoConsultation,
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),

                Action::make('view_appointment')
                    ->label('View Appointment')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->url(fn (Appointment $record): string => AppointmentResource::getUrl('view', ['record' => $record])),
            ])
            ->toolbarActions([
                BulkAction::make('generate_links')
                    ->label('Generate Links')
                    ->icon('heroicon-o-link')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Generate video links')
                    ->modalDescription('Generate Whereby video links for all selected appointments that are missing links.')
                    ->action(function (Collection $records): void {
                        $success = 0;
                        $failed = 0;

                        foreach ($records as $record) {
                            if ($this->hasCompleteVideoLinks($record)) {
                                continue;
                            }

                            if ($this->generateVideoLink($record, notify: false)) {
                                $success++;
                            } else {
                                $failed++;
                            }
                        }

                        if ($success > 0) {
                            Notification::make()
                                ->title("Generated {$success} video link(s)")
                                ->success()
                                ->send();
                        }

                        if ($failed > 0) {
                            Notification::make()
                                ->title("Failed to generate {$failed} video link(s)")
                                ->body('Check that WHEREBY_API_KEY is configured in Settings.')
                                ->danger()
                                ->send();
                        }
                    })
                    ->deselectRecordsAfterCompletion(),
            ])
            ->emptyStateHeading('No video appointments found')
            ->emptyStateDescription('There are no video appointments for the selected scope.');
    }

    protected function getTableQuery(): Builder
    {
        $query = Appointment::query()
            ->with(['patient', 'doctor', 'videoConsultation'])
            ->where('consultation_type', 'video')
            ->whereNotIn('status', [
                AppointmentStatus::CANCELLED->value,
                AppointmentStatus::FAILED->value,
            ]);

        return match ($this->scope) {
            'today' => $query->whereDate('appointment_date', Carbon::today()),
            'upcoming' => $query->whereDate('appointment_date', '>=', Carbon::today()),
            default => $query,
        };
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generate_all_in_scope')
                ->label(fn() => match($this->scope) {
                    'today' => 'Generate All Today',
                    'upcoming' => 'Generate All Upcoming',
                    default => 'Generate All Missing',
                })
                ->icon('heroicon-o-bolt')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading(fn() => 'Generate all missing links for ' . $this->scope)
                ->modalDescription(fn() => 'This will create video links for every video appointment under the current scope (' . $this->scope . ') that is missing a link.')
                ->action(function (): void {
                    $appointments = $this->getTableQuery()->get();

                    $success = 0;
                    $failed = 0;

                    foreach ($appointments as $appointment) {
                        if ($this->hasCompleteVideoLinks($appointment)) {
                            continue;
                        }

                        if ($this->generateVideoLink($appointment, notify: false)) {
                            $success++;
                        } else {
                            $failed++;
                        }
                    }

                    if ($success > 0) {
                        Notification::make()
                            ->title("Generated {$success} video link(s) for {$this->scope}")
                            ->success()
                            ->send();
                    } elseif ($failed === 0) {
                        Notification::make()
                            ->title("No missing links for {$this->scope}")
                            ->info()
                            ->send();
                    }

                    if ($failed > 0) {
                        Notification::make()
                            ->title("Failed to generate {$failed} video link(s)")
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }

    protected function hasCompleteVideoLinks(Appointment $appointment): bool
    {
        $videoConsultation = $appointment->videoConsultation;

        return $videoConsultation
            && filled($videoConsultation->host_url)
            && (filled($videoConsultation->participate_url) || filled($videoConsultation->room_url));
    }

    protected function getVideoLinkStatus(Appointment $appointment): string
    {
        $videoConsultation = $appointment->videoConsultation;

        if (! $videoConsultation) {
            return 'Missing';
        }

        if ($this->hasCompleteVideoLinks($appointment)) {
            return 'Ready';
        }

        return 'Partial';
    }

    protected function generateVideoLink(Appointment $appointment, bool $notify = true): ?VideoConsultation
    {
        $wherebyService = app(WherebyService::class);

        if (! $wherebyService->isConfigured()) {
            if ($notify) {
                Notification::make()
                    ->title('Whereby API not configured')
                    ->body('Add WHEREBY_API_KEY in Settings > Third Party API, then try again.')
                    ->danger()
                    ->send();
            }

            return null;
        }

        $appointment->load('videoConsultation');

        $videoConsultation = $appointment->videoConsultation
            ? $wherebyService->regenerateUrls($appointment->videoConsultation)
            : $wherebyService->createVideoConsultation($appointment);

        if (! $videoConsultation) {
            if ($notify) {
                Notification::make()
                    ->title('Failed to generate video link')
                    ->body('The Whereby API request failed. Check logs for details.')
                    ->danger()
                    ->send();
            }

            return null;
        }

        if (Schema::hasColumn('appointments', 'whereby_room_url')) {
            $appointment->update([
                'whereby_room_url' => $videoConsultation->room_url,
                'whereby_room_id' => $videoConsultation->room_id,
            ]);
        }

        if ($notify) {
            Notification::make()
                ->title('Video link generated')
                ->body('Host and participant URLs are now available for this appointment.')
                ->success()
                ->send();
        }

        return $videoConsultation;
    }
}