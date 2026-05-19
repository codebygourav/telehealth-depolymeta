<?php

namespace App\Filament\Resources\VaccinationTemplates;

use App\Enums\VaccinationProgramTargetType;
use App\Filament\Concerns\ConfiguresSlideOverSections;
use App\Filament\Resources\VaccinationTemplates\Pages\ListVaccinationTemplates;
use App\Models\Doctor;
use App\Models\Vaccination;
use App\Models\VaccinationProgram;
use App\Models\VaccinationTemplate;
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
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class VaccinationTemplateResource extends Resource
{
    use ConfiguresSlideOverSections;
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
            'group' => 'Clinical',
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
        return $schema->components(static::wrapSlideOverForm([
            static::slideOverSection('Template Details', [
                Select::make('doctor_id')
                        ->label('Doctor')
                        ->helperText('Doctor who owns this template.')
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
                        ->label('Schedule Program')
                        ->helperText('Choose or create the program for this template, for example Baby Immunization.')
                        ->options(fn() => VaccinationProgram::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id'))
                        ->searchable()
                        ->required()
                        ->createOptionForm([
                            TextInput::make('name')
                                ->label('Program Name')
                                ->helperText('Example: Baby Immunization, Pregnancy Vaccination, Adult Vaccine.')
                                ->required()
                                ->maxLength(255)
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn($state, callable $set) => $set('slug', Str::slug((string) $state))),
                            TextInput::make('slug')
                                ->helperText('Unique key. It is filled from the name.')
                                ->required()
                                ->maxLength(255)
                                ->unique(table: 'vaccination_programs', column: 'slug'),
                            Select::make('target_type')
                                ->label('Program Type')
                                ->helperText('Who this schedule is for.')
                                ->options(VaccinationProgramTargetType::options())
                                ->required(),
                            Textarea::make('description')
                                ->helperText('Short explanation for admins and apps.')
                                ->columnSpanFull(),
                            Toggle::make('is_active')
                                ->onColor('success')
                                ->onIcon('heroicon-o-check')
                                ->offColor('danger')
                                ->helperText('Keep on to allow this program in templates.')
                                ->default(true),
                        ])
                        ->createOptionUsing(fn(array $data): string => VaccinationProgram::create($data)->id),
                    TextInput::make('name')
                        ->label('Template Name')
                        ->helperText('Example: WHO Child Schedule 2026.')
                        ->required()
                        ->maxLength(255),
                    Toggle::make('is_active')
                        ->onColor('success')
                        ->offColor('danger')
                        ->helperText('Turn off to stop assigning this template.')
                        ->default(true),
                    Textarea::make('description')
                        ->helperText('Optional notes about this schedule.')
                        ->rows(3),
            ], 'Data entry creates the schedule for a doctor. The program is created here and stays linked to this template.'),
            static::slideOverSection('Template Doses', [
                Repeater::make('items')
                        ->relationship()
                        ->schema(static::slideOverFields([
                            Select::make('vaccination_id')
                                ->label('Vaccination')
                                ->helperText('Select the vaccine from Vaccine Master.')
                                ->options(fn() => Vaccination::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id'))
                                ->searchable()
                                ->required(),
                            TextInput::make('set_name')
                                ->label('Set Name')
                                ->placeholder('Pregnancy Month 1 / Baby Month 2')
                                ->helperText('Group name shown in the patient schedule, like Set 1 or 6 Weeks.')
                                ->maxLength(255),
                            Textarea::make('set_description')
                                ->label('Set Description')
                                ->helperText('Small note for this group.')
                                ->rows(2),
                            TextInput::make('dose_no')
                                ->label('Dose Number')
                                ->helperText('Dose number for this vaccine, such as 1, 2, or 3.')
                                ->integer()
                                ->minValue(1)
                                ->default(1)
                                ->required(),
                            Toggle::make('depends_on_previous_dose')
                                ->label('Depends On Previous Dose')
                                ->helperText('Turn on when this dose date should be counted from the previous dose date.')
                                ->default(false),
                            TextInput::make('interval_days')
                                ->label('Interval Days')
                                ->helperText('Days after the previous dose when dependency is on.')
                                ->integer()
                                ->minValue(0)
                                ->default(0),
                            TextInput::make('interval_months')
                                ->label('Interval Months')
                                ->helperText('Months after the previous dose when dependency is on.')
                                ->integer()
                                ->minValue(0)
                                ->default(0),
                            TextInput::make('minimum_age_days')
                                ->label('Min Age Days')
                                ->helperText('Minimum patient age in days for this dose.')
                                ->integer()
                                ->minValue(0),
                            TextInput::make('maximum_age_days')
                                ->label('Max Age Days')
                                ->helperText('Maximum patient age in days for this dose.')
                                ->integer()
                                ->minValue(0),
                            TextInput::make('recommended_age_label')
                                ->label('Recommended Age')
                                ->placeholder('At Birth / 6 Weeks / 6-12 Months')
                                ->helperText('Friendly text shown to patients.')
                                ->maxLength(255),
                            TextInput::make('due_after_months')
                                ->label('Due After Months From Start')
                                ->integer()
                                ->minValue(0)
                                ->default(0)
                                ->helperText('Months after assignment start date when dependency is off.'),
                            TextInput::make('due_after_days')
                                ->label('Due After Days From Start')
                                ->integer()
                                ->minValue(0)
                                ->default(0)
                                ->helperText('Days after assignment start date when dependency is off.'),
                            TextInput::make('sort_order')
                                ->label('Dose Sort Order')
                                ->integer()
                                ->minValue(0)
                                ->default(0)
                                ->helperText('Controls order of doses inside the set.'),
                        ]))
                        ->columns(1)
                        ->collapsible()
                        ->collapsed()
                        ->minItems(1)
                        ->reorderable()
                        ->itemLabel(fn (array $state): ?string => $state['set_name'] ?? $state['recommended_age_label'] ?? null),
            ], 'Add each vaccine dose in the order it should appear in the schedule.', icon: 'heroicon-o-queue-list'),
        ]));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('program.name')->label('Program')->searchable(),
                TextColumn::make('doctor_id')
                    ->label('Doctor')
                    ->formatStateUsing(fn ($state, VaccinationTemplate $record): string => static::doctorDisplayName($record->doctor))
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
                    EditAction::make()->slideOver(),
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
            'index' => ListVaccinationTemplates::route('/'),
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
