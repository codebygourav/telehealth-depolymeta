<?php

namespace App\Filament\Resources\VaccinationTemplates;

use App\Enums\VaccinationProgramTargetType;
use App\Filament\Resources\VaccinationTemplates\Pages;
use App\Models\Doctor;
use App\Models\Vaccination;
use App\Models\VaccinationProgram;
use App\Models\VaccinationTemplate;
use App\Models\VaccinationTemplateItem;
use App\Traits\HasCustomSidebar;
use App\Traits\HasResourcePermissions;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Filament\Infolists\Components\ViewEntry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class VaccinationTemplateResource extends Resource
{
    use HasCustomSidebar;
    use HasResourcePermissions;

    protected static ?string $model = VaccinationTemplate::class;

    protected static ?string $navigationLabel = 'Vaccination Templates';

    protected static ?string $slug = 'vaccination-templates';

    public static function getSidebarOptions(): array
    {
        return [
            'label' => 'Schedule Templates',
            'icon' => 'heroicon-o-clipboard-document-list',
            'sort' => 5,
            'group' => 'Vaccination',
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();

        return check_permission(['vaccination-templates.view_any', 'vaccination-templates.view', 'vaccination-templates.manage_own'])
            || (is_object($user) && method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['super_admin', 'admin', 'doctor_manager', 'receptionist', 'doctor']));
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Template Details')
                ->description('Create or edit the vaccination schedule template in one clear form.')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            Select::make('doctor_id')
                                ->label('Doctor')
                                ->options(fn() => Doctor::query()
                                    ->orderBy('first_name')
                                    ->get()
                                    ->mapWithKeys(fn(Doctor $doctor) => [$doctor->id => trim("{$doctor->first_name} {$doctor->last_name}") ?: $doctor->name ?: $doctor->id]))
                                ->searchable()
                                ->required()
                                ->default(fn() => Auth::user()?->doctor?->id)
                                ->disabled(function () {
                                    $role = Auth::user()?->role;
                                    $isDoctor = $role === 'doctor';
                                    $isPrivileged = in_array($role, ['super_admin', 'doctor_manager', 'receptionist'], true);

                                    return $isDoctor && ! $isPrivileged;
                                })
                                ->dehydrated(),
                            Select::make('vaccination_program_id')
                                ->label('Vaccination Program')
                                ->options(fn() => VaccinationProgram::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id'))
                                ->searchable()
                                ->required()
                                ->createOptionForm([
                                    TextInput::make('name')
                                        ->label('Program Name')
                                        ->required()
                                        ->maxLength(255)
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(fn($state, callable $set) => $set('slug', Str::slug((string) $state))),
                                    TextInput::make('slug')
                                        ->required()
                                        ->maxLength(255)
                                        ->unique(table: 'vaccination_programs', column: 'slug'),
                                    Select::make('target_type')
                                        ->label('Program Type')
                                        ->options(VaccinationProgramTargetType::options())
                                        ->required(),
                                    Textarea::make('description')
                                        ->label('Program Description')
                                        ->rows(3)
                                        ->columnSpanFull(),
                                    Toggle::make('is_active')
                                        ->label('Program Active')
                                        ->onColor('success')
                                        ->onIcon('heroicon-o-check')
                                        ->offColor('danger')
                                        ->default(true),
                                ])
                                ->createOptionUsing(fn(array $data): string => VaccinationProgram::create($data)->id),
                            TextInput::make('name')
                                ->label('Template Name')
                                ->required()
                                ->maxLength(255),
                            Toggle::make('is_active')
                                ->label('Template Active')
                                ->onColor('success')
                                ->offColor('danger')
                                ->default(true),
                        ]),
                ])
                ->columnSpanFull(),

            Section::make('Vaccination Schedule')
                ->description('Group doses into schedule sets. Each set can contain multiple doses and dependency rules.')
                ->extraAttributes(['class' => 'vaccination-template-builder'])
                ->schema([
                    Repeater::make('sets')
                        ->label('Vaccination Sets')
                        ->minItems(1)
                        ->defaultItems(1)
                        ->reorderable()
                        ->collapsible()
                        ->addActionLabel('Add vaccination set')
                        ->extraAttributes(['class' => 'vaccination-set-repeater'])
                        ->itemLabel(fn(array $state): ?string => $state['set_name'] ?? 'New Set')
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    TextInput::make('set_name')
                                        ->label('Set Name')
                                        ->placeholder('Pregnancy Month 1, Baby Month 2, Adult Booster')
                                        ->required(),
                                    Textarea::make('set_description')
                                        ->label('Set Description')
                                        ->rows(1)
                                        ->autosize()
                                        ->helperText('Optional note for this set.'),
                                ]),
                            Repeater::make('doses')
                                ->label('Doses')
                                ->minItems(1)
                                ->defaultItems(1)
                                ->reorderable()
                                ->collapsible()
                                ->addActionLabel('Add dose to this set')
                                ->extraAttributes(['class' => 'vaccination-dose-repeater'])
                                ->itemLabel(fn(array $state): ?string => 'Dose ' . ($state['dose_no'] ?? 1))
                                ->schema([
                                    Grid::make(12)
                                        ->schema([
                                            Select::make('vaccination_id')
                                                ->label('Vaccine')
                                                ->options(fn() => Vaccination::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id'))
                                                ->searchable()
                                                ->preload()
                                                ->optionsLimit(1000)
                                                ->required()
                                                ->columnSpan(4),
                                            TextInput::make('dose_no')
                                                ->label('Dose No.')
                                                ->numeric()
                                                ->minValue(1)
                                                ->default(1)
                                                ->required()
                                                ->columnSpan(2),
                                            Toggle::make('depends_on_previous_dose')
                                                ->label('Depends on previous dose')
                                                ->helperText('Off means timing is from schedule start.')
                                                ->default(false)
                                                ->live()
                                                ->columnSpan(3),
                                            TextInput::make('interval_months')
                                                ->label('Months after previous dose')
                                                ->numeric()
                                                ->minValue(0)
                                                ->default(0)
                                                ->visible(fn($get) => (bool) $get('depends_on_previous_dose'))
                                                ->columnSpan(3),
                                            TextInput::make('due_after_months')
                                                ->label('Months from schedule start')
                                                ->numeric()
                                                ->minValue(0)
                                                ->default(0)
                                                ->hidden(fn($get) => (bool) $get('depends_on_previous_dose'))
                                                ->columnSpan(3),
                                            TextInput::make('interval_days')
                                                ->label('Days after previous dose')
                                                ->numeric()
                                                ->minValue(0)
                                                ->default(0)
                                                ->visible(fn($get) => (bool) $get('depends_on_previous_dose'))
                                                ->columnSpan(3),
                                            TextInput::make('due_after_days')
                                                ->label('Days from schedule start')
                                                ->numeric()
                                                ->minValue(0)
                                                ->default(0)
                                                ->hidden(fn($get) => (bool) $get('depends_on_previous_dose'))
                                                ->columnSpan(3),
                                        ]),
                                ]),
                        ])
                        ->afterStateHydrated(function ($component, $record) {
                            if (! $record) {
                                $component->state([]);
                                return;
                            }

                            $items = $record->items()
                                ->orderBy('set_sort_order')
                                ->orderBy('sort_order')
                                ->get();

                            $grouped = [];
                            foreach ($items as $item) {
                                $setKey = $item->set_name ?? 'General';
                                if (! isset($grouped[$setKey])) {
                                    $grouped[$setKey] = [
                                        'set_name' => $item->set_name,
                                        'set_description' => $item->set_description,
                                        'set_sort_order' => $item->set_sort_order ?? 0,
                                        'doses' => [],
                                    ];
                                }

                                $grouped[$setKey]['doses'][] = [
                                    'vaccination_id' => $item->vaccination_id,
                                    'dose_no' => $item->dose_no,
                                    'depends_on_previous_dose' => (bool) $item->depends_on_previous_dose,
                                    'interval_days' => $item->interval_days ?? 0,
                                    'interval_months' => $item->interval_months ?? 0,
                                    'due_after_months' => $item->due_after_months ?? 0,
                                    'due_after_days' => $item->due_after_days ?? 0,
                                ];
                            }

                            $component->state(array_values($grouped));
                        })
                        ->saveRelationshipsUsing(function ($record, $state) {
                            $record->items()->delete();

                            if (! is_array($state)) {
                                return;
                            }

                            foreach ($state as $setIndex => $setData) {
                                $setName = $setData['set_name'] ?? 'General';
                                $setDescription = $setData['set_description'] ?? null;
                                $setSortOrder = (int) ($setData['set_sort_order'] ?? $setIndex);

                                foreach ($setData['doses'] ?? [] as $doseData) {
                                    if (empty($doseData['vaccination_id'])) {
                                        continue;
                                    }

                                    $record->items()->create([
                                        'vaccination_id' => $doseData['vaccination_id'],
                                        'set_name' => $setName,
                                        'set_description' => $setDescription,
                                        'set_sort_order' => $setSortOrder,
                                        'dose_no' => (int) ($doseData['dose_no'] ?? 1),
                                        'depends_on_previous_dose' => (bool) ($doseData['depends_on_previous_dose'] ?? false),
                                        'interval_days' => (int) ($doseData['interval_days'] ?? 0),
                                        'interval_months' => (int) ($doseData['interval_months'] ?? 0),
                                        'due_after_months' => (int) ($doseData['due_after_months'] ?? 0),
                                        'due_after_days' => (int) ($doseData['due_after_days'] ?? 0),
                                    ]);
                                }
                            }
                        }),
                ])
                ->columnSpanFull(),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            ViewEntry::make('template_details')
                ->view('filament.vaccination-templates.template-view')
                ->state(fn($record) => $record)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('program.name')->label('Program')->searchable(),
                TextColumn::make('doctor_id')
                    ->label('Doctor')
                    ->formatStateUsing(fn($state, VaccinationTemplate $record): string => static::doctorDisplayName($record->doctor))
                    ->placeholder('-'),
                TextColumn::make('items_count')->counts('items')->label('Vaccines')->sortable(),
                IconColumn::make('is_active')->boolean(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    \Filament\Actions\ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->requiresConfirmation(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make()->requiresConfirmation(),
                ]),
            ])
            ->recordUrl(null);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVaccinationTemplates::route('/'),
            'create' => Pages\CreateVaccinationTemplate::route('/create'),
            'view' => Pages\ViewVaccinationTemplate::route('/{record}'),
            'edit' => Pages\EditVaccinationTemplate::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['doctor.user', 'program'])
            ->withoutGlobalScopes();

        $user = Auth::user();
        $isPrivileged = is_object($user)
            && method_exists($user, 'hasAnyRole')
            && $user->hasAnyRole(['super_admin', 'admin', 'doctor_manager', 'receptionist']);

        if (! $isPrivileged && $user?->doctor?->id) {
            return $query->where('doctor_id', $user->doctor?->id);
        }

        return $query;
    }

    private static function doctorDisplayName(?Doctor $doctor): string
    {
        if (! $doctor) {
            return '-';
        }

        return trim("{$doctor->first_name} {$doctor->last_name}")
            ?: (string) ($doctor->name ?: $doctor->user?->name ?: '-');
    }
}
