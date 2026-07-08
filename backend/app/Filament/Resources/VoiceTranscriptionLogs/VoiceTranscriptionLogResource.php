<?php

namespace App\Filament\Resources\VoiceTranscriptionLogs;

use App\Filament\Resources\VoiceTranscriptionLogs\Pages\ListVoiceTranscriptionLogs;
use App\Filament\Resources\VoiceTranscriptionLogs\Pages\ViewVoiceTranscriptionLog;
use App\Filament\Resources\VoiceTranscriptionLogs\Widgets\VoiceTranscriptionStatsWidget;
use App\Models\VoiceTranscriptionLog;
use App\Traits\HasCustomSidebar;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section as InfoSection;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class VoiceTranscriptionLogResource extends Resource
{
    use HasCustomSidebar;

    protected static ?string $slug  = 'voice-transcription-logs';
    protected static ?string $model = VoiceTranscriptionLog::class;

    protected static string|\BackedEnum|null $navigationIcon       = 'heroicon-o-microphone';
    protected static string|\BackedEnum|null $activeNavigationIcon = 'heroicon-s-microphone';
    protected static string|\UnitEnum|null $navigationGroup        = 'Medicine';
    protected static ?int    $navigationSort       = 36;
    protected static ?string $label                = 'Voice Log';
    protected static ?string $pluralLabel          = 'Voice Transcription Logs';

    public static function getSidebarOptions(): array
    {
        return [
            'label'   => 'Voice Logs',
            'icon'    => 'heroicon-o-microphone',
            'sort'    => 36,
            'group'   => 'Medicine',
            'visible' => true,
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function canCreate(): bool
    {
        return false;
    }
    public static function canEdit($record): bool
    {
        return false;
    }
    public static function canDelete($record): bool
    {
        return false;
    }

    // ── Table ──────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Date & Time')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),

                TextColumn::make('doctor.user.name')
                    ->label('Doctor')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('patient.first_name')
                    ->label('Patient')
                    ->formatStateUsing(
                        fn($state, $record) =>
                        trim(($record->patient?->first_name ?? '') . ' ' . ($record->patient?->last_name ?? '')) ?: '—'
                    )
                    ->searchable()
                    ->placeholder('—'),

                TextColumn::make('module')
                    ->label('Module')
                    ->badge()
                    ->color(fn(string $state) => match ($state) {
                        'prescription' => 'info',
                        default        => 'gray',
                    })
                    ->formatStateUsing(fn(string $state) => ucfirst($state)),

                TextColumn::make('transcript')
                    ->label('Transcript')
                    ->limit(60)
                    ->wrap()
                    ->placeholder('No transcript'),

                TextColumn::make('audio_duration_seconds')
                    ->label('Duration')
                    ->formatStateUsing(
                        fn($state) => $state < 60
                            ? round($state, 1) . 's'
                            : round($state / 60, 1) . 'm'
                    )
                    ->sortable(),

                TextColumn::make('credits_used')
                    ->label('Cost (USD)')
                    ->formatStateUsing(fn($state) => '$' . number_format((float) $state, 5))
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('confidence')
                    ->label('Confidence')
                    ->formatStateUsing(fn($state) => $state ? $state . '%' : '—')
                    ->color(fn($state) => match (true) {
                        $state >= 85 => 'success',
                        $state >= 60 => 'warning',
                        default      => 'danger',
                    })
                    ->badge()
                    ->toggleable(),

                TextColumn::make('model')
                    ->label('Model')
                    ->badge()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state) => match ($state) {
                        'success' => 'success',
                        'failed'  => 'danger',
                        default   => 'gray',
                    })
                    ->icon(fn(string $state) => match ($state) {
                        'success' => 'heroicon-m-check-circle',
                        'failed'  => 'heroicon-m-x-circle',
                        default   => 'heroicon-m-question-mark-circle',
                    })
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'success' => 'Success',
                        'failed'  => 'Failed',
                    ]),

                SelectFilter::make('module')
                    ->label('Module')
                    ->options([
                        'prescription' => 'Prescription',
                    ]),

                SelectFilter::make('period')
                    ->label('Date Range')
                    ->options([
                        'today'      => 'Today',
                        'this_week'  => 'This Week',
                        'this_month' => 'This Month',
                    ])
                    ->query(fn(Builder $query, array $state): Builder => match ($state['value'] ?? null) {
                        'today'      => $query->today(),
                        'this_month' => $query->thisMonth(),
                        'this_week'  => $query->whereDate('created_at', '>=', now()->startOfWeek()),
                        default      => $query,
                    }),

                Filter::make('failed_only')
                    ->label('Failed Only')
                    ->query(fn(Builder $q, $state) => (
                        (is_array($state) ? ($state['value'] ?? $state) : $state)
                    ) ? $q->where('status', 'failed') : $q)
                    ->toggle(),
            ])
            ->striped()
            ->paginated([25, 50, 100])
            ->poll('60s');
    }

    // ── Infolist ───────────────────────────────────────────────────────────

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            InfoSection::make('Transcription Details')
                ->icon('heroicon-o-microphone')
                ->columns(3)
                ->schema([
                    TextEntry::make('status')
                        ->label('Status')
                        ->badge()
                        ->color(fn(string $state) => match ($state) {
                            'success' => 'success',
                            'failed'  => 'danger',
                            default   => 'gray',
                        }),

                    TextEntry::make('module')
                        ->label('Module')
                        ->badge()
                        ->color('info')
                        ->formatStateUsing(fn(string $state) => ucfirst($state)),

                    TextEntry::make('model')
                        ->label('AI Model')
                        ->badge()
                        ->color('gray'),

                    TextEntry::make('language')
                        ->label('Language'),

                    TextEntry::make('audio_duration_seconds')
                        ->label('Audio Duration')
                        ->formatStateUsing(fn($state) => round((float)$state, 2) . ' seconds'),

                    TextEntry::make('confidence')
                        ->label('Confidence Score')
                        ->formatStateUsing(fn($state) => $state ? $state . '%' : '—')
                        ->badge()
                        ->color(fn($state) => match (true) {
                            $state >= 85 => 'success',
                            $state >= 60 => 'warning',
                            default      => 'danger',
                        }),

                    TextEntry::make('credits_used')
                        ->label('Cost Estimate (USD)')
                        ->formatStateUsing(fn($state) => '$' . number_format((float)$state, 6)),

                    TextEntry::make('deepgram_request_id')
                        ->label('Deepgram Request ID')
                        ->copyable()
                        ->placeholder('—'),

                    TextEntry::make('created_at')
                        ->label('Recorded At')
                        ->dateTime('d M Y H:i:s'),
                ]),

            InfoSection::make('People')
                ->icon('heroicon-o-users')
                ->columns(3)
                ->schema([
                    TextEntry::make('doctor.user.name')
                        ->label('Doctor')
                        ->placeholder('—'),

                    TextEntry::make('patient_name')
                        ->label('Patient')
                        ->getStateUsing(
                            fn($record) =>
                            trim(($record->patient?->first_name ?? '') . ' ' . ($record->patient?->last_name ?? '')) ?: '—'
                        ),

                    TextEntry::make('appointment_id')
                        ->label('Appointment ID')
                        ->copyable()
                        ->placeholder('—'),
                ]),

            InfoSection::make('Voice Transcript')
                ->icon('heroicon-o-document-text')
                ->schema([
                    TextEntry::make('transcript')
                        ->label('Transcribed Text')
                        ->columnSpanFull()
                        ->prose()
                        ->placeholder('No transcript captured.')
                        ->formatStateUsing(
                            fn(?string $state) => $state
                                ? '<div class="p-4 bg-blue-50 border border-blue-200 rounded-lg text-sm leading-relaxed">' . e($state) . '</div>'
                                : '<p class="text-gray-500 italic">No transcript available.</p>'
                        )
                        ->html(),
                ]),

            InfoSection::make('Error Details')
                ->icon('heroicon-o-exclamation-triangle')
                ->schema([
                    TextEntry::make('error_message')
                        ->label('Error')
                        ->columnSpanFull()
                        ->color('danger')
                        ->placeholder('No error.')
                        ->prose(),
                ])
                ->visible(fn($record) => $record?->status === 'failed'),
        ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['doctor.user', 'patient', 'appointment']);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVoiceTranscriptionLogs::route('/'),
            'view'  => ViewVoiceTranscriptionLog::route('/{record}'),
        ];
    }

    public static function getWidgets(): array
    {
        return [VoiceTranscriptionStatsWidget::class];
    }
}
