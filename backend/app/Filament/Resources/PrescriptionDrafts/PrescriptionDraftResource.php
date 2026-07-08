<?php
namespace App\Filament\Resources\PrescriptionDrafts;

use App\Filament\Resources\PrescriptionDrafts\Pages\ListPrescriptionDrafts;
use App\Models\PrescriptionDraft;
use App\Traits\{HasCustomSidebar, HasResourcePermissions};
use Filament\Schemas\Components\Section as InfoSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Actions\ViewAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class PrescriptionDraftResource extends Resource
{
    use HasResourcePermissions;
    use HasCustomSidebar;

    protected static ?string $slug = 'prescription-logs';
    protected static ?string $model = PrescriptionDraft::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-microphone';
    protected static string|\BackedEnum|null $activeNavigationIcon = 'heroicon-s-microphone';
    protected static string|\UnitEnum|null $navigationGroup = 'System & Settings';
    protected static ?int $navigationSort = 96;
    protected static ?string $label = 'Prescription Log';
    protected static ?string $pluralLabel = 'Prescription Logs';

    public static function getSidebarOptions(): array
    {
        return [
            'label'   => 'Prescription Logs',
            'icon'    => 'heroicon-o-microphone',
            'sort'    => 96,
            'group'   => 'System & Settings',
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

    // ── Table ───────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Captured At')
                    ->dateTime('d M Y, H:i:s')
                    ->sortable(),

                TextColumn::make('doctor_name')
                    ->label('Doctor')
                    ->state(fn($record) => $record->doctor ? 'Dr. ' . trim($record->doctor->first_name . ' ' . $record->doctor->last_name) : 'N/A')
                    ->searchable(query: function ($query, $search) {
                        $query->whereHas('doctor', function ($q) use ($search) {
                            $q->where('first_name', 'like', "%{$search}%")
                              ->orWhere('last_name', 'like', "%{$search}%");
                        });
                    }),

                TextColumn::make('patient_name')
                    ->label('Patient')
                    ->state(fn($record) => $record->patient ? trim($record->patient->first_name . ' ' . $record->patient->last_name) : 'N/A')
                    ->searchable(query: function ($query, $search) {
                        $query->whereHas('patient', function ($q) use ($search) {
                            $q->where('first_name', 'like', "%{$search}%")
                              ->orWhere('last_name', 'like', "%{$search}%");
                        });
                    }),

                TextColumn::make('input_text')
                    ->label('Voice / Text Transcript')
                    ->limit(60)
                    ->searchable()
                    ->wrap(),

                TextColumn::make('source_type')
                    ->label('Input Mode')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'speech' => 'Voice',
                        'text' => 'Typed',
                        default => strtoupper((string) ($state ?: 'unknown')),
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'speech' => 'success',
                        'text' => 'info',
                        default => 'gray',
                    }),

                TextColumn::make('confidence_score')
                    ->label('Confidence')
                    ->badge()
                    ->color(fn(int $state): string => $state >= 80 ? 'success' : ($state >= 50 ? 'warning' : 'danger'))
                    ->suffix('%')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'applied' => 'success',
                        'parsed' => 'warning',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('saved_medicines_count')
                    ->label('Saved Medicines')
                    ->state(fn (PrescriptionDraft $record): int => static::extractCreatedMedicines($record)->count())
                    ->badge()
                    ->color('primary'),

                TextColumn::make('doctor_added_medicines_count')
                    ->label('Doctor Added')
                    ->state(fn (PrescriptionDraft $record): int => static::extractCreatedMedicines($record)
                        ->where('medicine_source', 'doctor_added')
                        ->count())
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'warning' : 'gray'),

                TextColumn::make('medicine_breakdown')
                    ->label('Medicine Breakdown')
                    ->state(function (PrescriptionDraft $record): string {
                        $createdMedicines = static::extractCreatedMedicines($record);
                        $inventoryCount = $createdMedicines->where('medicine_source', 'inventory')->count();
                        $doctorAddedCount = $createdMedicines->where('medicine_source', 'doctor_added')->count();

                        return "{$inventoryCount} stock / {$doctorAddedCount} doctor-added";
                    })
                    ->wrap(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'parsed' => 'Parsed Only',
                        'applied' => 'Applied to Prescription',
                        'rejected' => 'Rejected',
                    ]),

                SelectFilter::make('source_type')
                    ->label('Input Mode')
                    ->options([
                        'speech' => 'Voice',
                        'text' => 'Typed',
                    ]),
            ])
            ->actions([
                ViewAction::make()
                    ->modalWidth('4xl'),
            ])
            ->striped()
            ->paginated([25, 50, 100]);
    }

    // ── Infolist ─────────────────────────────────────────────────────────────

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            InfoSection::make('Metadata')
                ->columns(2)
                ->schema([
                    TextEntry::make('created_at')
                        ->label('Captured At')
                        ->dateTime('d M Y, H:i:s'),

                    TextEntry::make('status')
                        ->label('Status')
                        ->badge()
                        ->color(fn(string $state): string => match ($state) {
                            'applied' => 'success',
                            'parsed' => 'warning',
                            'rejected' => 'danger',
                            default => 'gray',
                        }),

                    TextEntry::make('doctor_name')
                        ->label('Doctor')
                        ->state(fn($record) => $record->doctor ? 'Dr. ' . trim($record->doctor->first_name . ' ' . $record->doctor->last_name) : 'N/A'),

                    TextEntry::make('patient_name')
                        ->label('Patient')
                        ->state(fn($record) => $record->patient ? trim($record->patient->first_name . ' ' . $record->patient->last_name) : 'N/A'),

                    TextEntry::make('confidence_score')
                        ->label('AI Confidence Score')
                        ->badge()
                        ->color(fn(int $state): string => $state >= 80 ? 'success' : ($state >= 50 ? 'warning' : 'danger'))
                        ->suffix('%'),

                    TextEntry::make('source_type')
                        ->label('Source Type')
                        ->formatStateUsing(fn (?string $state): string => match ($state) {
                            'speech' => 'Voice',
                            'text' => 'Typed',
                            default => strtoupper((string) ($state ?: 'unknown')),
                        }),
                ]),

            InfoSection::make('Raw Voice Transcript / Text Speech')
                ->schema([
                    TextEntry::make('input_text')
                        ->label('')
                        ->columnSpanFull()
                        ->extraAttributes([
                            'class' => 'max-h-[120px] overflow-y-auto block bg-gray-50/50 dark:bg-gray-900/50 p-3 rounded-lg border border-gray-100 dark:border-white/10 text-sm leading-relaxed scrollbar-thin'
                        ])
                        ->placeholder('No transcription captured.'),
                ]),

            InfoSection::make('AI Parsed Output (Medication Details)')
                ->schema([
                    TextEntry::make('parsed_payload')
                        ->label('')
                        ->columnSpanFull()
                        ->formatStateUsing(fn($state) => is_array($state) ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : $state)
                        ->fontFamily('monospace')
                        ->extraAttributes([
                            'class' => 'max-h-[250px] overflow-y-auto block bg-gray-50/50 dark:bg-gray-900/50 p-3 rounded-lg border border-gray-100 dark:border-white/10 text-xs font-mono scrollbar-thin'
                        ])
                        ->placeholder('No parsed payload available.'),
                ]),

            InfoSection::make('Warnings & Missing Fields')
                ->columns(2)
                ->schema([
                    TextEntry::make('warnings')
                        ->label('Warnings')
                        ->formatStateUsing(fn($state) => is_array($state) && count($state) > 0 ? implode("\n", $state) : 'None')
                        ->extraAttributes([
                            'class' => 'max-h-[100px] overflow-y-auto block scrollbar-thin'
                        ])
                        ->placeholder('No warnings.'),

                    TextEntry::make('missing_fields')
                        ->label('Missing Fields')
                        ->formatStateUsing(fn($state) => is_array($state) && count($state) > 0 ? implode(', ', $state) : 'None')
                        ->extraAttributes([
                            'class' => 'max-h-[100px] overflow-y-auto block scrollbar-thin'
                        ])
                        ->placeholder('No missing fields.'),
                ]),

            InfoSection::make('Final Submitted Prescription Payload')
                ->schema([
                    TextEntry::make('submitted_payload')
                        ->label('')
                        ->columnSpanFull()
                        ->formatStateUsing(fn($state) => is_array($state) ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : $state)
                        ->fontFamily('monospace')
                        ->extraAttributes([
                            'class' => 'max-h-[250px] overflow-y-auto block bg-gray-50/50 dark:bg-gray-900/50 p-3 rounded-lg border border-gray-100 dark:border-white/10 text-xs font-mono scrollbar-thin'
                        ])
                        ->placeholder('Not submitted/applied yet.'),
                ])
                ->visible(fn($record) => !empty($record->submitted_payload)),

            InfoSection::make('Saved Medicines')
                ->schema([
                    TextEntry::make('saved_medicines_summary')
                        ->label('')
                        ->state(function (PrescriptionDraft $record): string {
                            $createdMedicines = static::extractCreatedMedicines($record);

                            if ($createdMedicines->isEmpty()) {
                                return 'No saved medicines recorded.';
                            }

                            return $createdMedicines
                                ->map(function (array $medicine, int $index): string {
                                    $name = (string) ($medicine['medicine_name'] ?? 'Unknown medicine');
                                    $source = match ($medicine['medicine_source'] ?? null) {
                                        'doctor_added' => 'Doctor-added',
                                        'inventory' => 'Stock medicine',
                                        default => 'Unknown source',
                                    };

                                    return ($index + 1) . '. ' . $name . ' (' . $source . ')';
                                })
                                ->implode("\n");
                        })
                        ->columnSpanFull()
                        ->extraAttributes([
                            'class' => 'max-h-[150px] overflow-y-auto block bg-gray-50/50 dark:bg-gray-900/50 p-3 rounded-lg border border-gray-100 dark:border-white/10 text-xs leading-relaxed whitespace-pre-line scrollbar-thin'
                        ])
                        ->placeholder('No saved medicines recorded.'),
                ])
                ->visible(fn (PrescriptionDraft $record): bool => static::extractCreatedMedicines($record)->isNotEmpty()),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['doctor', 'patient']);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPrescriptionDrafts::route('/'),
        ];
    }

    protected static function extractCreatedMedicines(PrescriptionDraft $record): Collection
    {
        return collect($record->submitted_payload['created_medicines'] ?? [])
            ->filter(fn ($medicine) => is_array($medicine));
    }
}
