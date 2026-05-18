<?php

namespace App\Filament\Resources\DietTemplates;

use App\Filament\Concerns\ConfiguresSlideOverSections;
use App\Filament\Resources\DietTemplates\Pages\ListDietTemplates;
use App\Models\DietTemplate;
use App\Models\Doctor;
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
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class DietTemplateResource extends Resource
{
    use ConfiguresSlideOverSections;
    use HasCustomSidebar;
    use HasResourcePermissions;

    protected static ?string $model = DietTemplate::class;

    protected static ?string $navigationLabel = 'Diet Templates';

    protected static ?string $slug = 'diet-templates';

    public static function getSidebarOptions(): array
    {
        return [
            'label' => 'Diet Templates',
            'icon' => 'heroicon-o-clipboard-document-list',
            'sort' => 6,
            'group' => 'Clinical',
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();

        return check_permission(['diet-templates.view_any', 'diet-templates.view', 'diet-templates.manage_own'])
            || (is_object($user) && method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['super_admin', 'admin', 'doctor_manager', 'receptionist', 'doctor']));
    }

    public static function canViewAny(): bool
    {
        return check_permission(['diet-templates.view_any', 'diet-templates.view', 'diet-templates.manage_own'])
            || static::hasDietTemplateRole();
    }

    public static function canCreate(): bool
    {
        return check_permission(['diet-templates.create', 'diet-templates.manage_own']) || static::hasDietTemplateRole();
    }

    public static function canEdit($record): bool
    {
        return check_permission('diet-templates.update')
            || (check_permission('diet-templates.manage_own') && static::isOwnRecord($record))
            || static::hasDietTemplateRole();
    }

    public static function canDelete($record): bool
    {
        return check_permission('diet-templates.delete_any')
            || (check_permission(['diet-templates.delete', 'diet-templates.manage_own']) && static::isOwnRecord($record))
            || static::hasDietTemplateAdminRole();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components(static::wrapSlideOverForm([
            static::slideOverSection('Template Details', [
                Select::make('doctor_id')
                    ->label('Doctor')
                    ->helperText('Doctor who owns and can assign this diet template.')
                    ->options(fn() => Doctor::query()
                        ->orderBy('first_name')
                        ->orderBy('last_name')
                        ->get()
                        ->mapWithKeys(function (Doctor $doctor) {
                            // Try common combinations for full name, fallbacks if empty splits
                            $name = trim($doctor->first_name . ' ' . $doctor->last_name);
                            if (!$name || $name === '') {
                                $name = $doctor->name ?? '';
                            }
                            // Fallback to just user_id if still empty
                            if (!$name) {
                                $name = 'Doctor #' . $doctor->id;
                            }
                            return [$doctor->id => $name];
                        }))
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

                TextInput::make('name')
                    ->label('Template Name')
                    ->placeholder('Pregnancy Balanced Diet / Diabetes Diet Plan')
                    ->required()
                    ->maxLength(255),
                TextInput::make('duration_days')
                    ->label('Duration Days')
                    ->integer()
                    ->minValue(1)
                    ->maxValue(180)
                    ->default(7)
                    ->required(),
                Toggle::make('is_active')
                    ->onColor('success')
                    ->offColor('danger')
                    ->helperText('Turn off to stop assigning this template.')
                    ->default(true),
                Textarea::make('description')
                    ->helperText('Short explanation shown to doctor while assigning.')
                    ->rows(3),
                Textarea::make('restrictions')
                    ->helperText('Foods or ingredients to avoid.')
                    ->rows(3),
                Textarea::make('notes')
                    ->helperText('Additional clinical notes for the plan.')
                    ->rows(3),
            ], 'Create the diet template for a doctor. Doctor and assistant users can assign active templates to patients.'),
            static::slideOverSection('Weekly Meal Chart', [
                Repeater::make('days')
                    ->relationship('days')
                    ->schema(static::slideOverFields([
                        TextInput::make('day_number')
                            ->label('Day Number')
                            ->integer()
                            ->minValue(1)
                            ->maxValue(31)
                            ->required(),
                        Select::make('week_day')
                            ->label('Week Day')
                            ->options(static::weekDayOptions())
                            ->required(),
                        Repeater::make('meals')
                            ->relationship('meals')
                            ->schema(static::slideOverFields([
                                Select::make('meal_type')
                                    ->label('Meal Type')
                                    ->options(static::mealTypeOptions())
                                    ->required(),
                                TextInput::make('meal_name')
                                    ->label('Meal Name / Food Items')
                                    ->placeholder('Oats with fruit and milk')
                                    ->required()
                                    ->maxLength(255),
                                TimePicker::make('start_time')
                                    ->label('Meal Time')
                                    ->seconds(false),
                                Textarea::make('instructions')
                                    ->helperText('Preparation notes, serving guidance, or substitutions.')
                                    ->rows(2),
                                TextInput::make('calories')
                                    ->integer()
                                    ->minValue(0),
                                TextInput::make('protein_grams')
                                    ->label('Protein (g)')
                                    ->integer()
                                    ->minValue(0),
                                TextInput::make('carbs_grams')
                                    ->label('Carbs (g)')
                                    ->integer()
                                    ->minValue(0),
                                TextInput::make('fat_grams')
                                    ->label('Fat (g)')
                                    ->integer()
                                    ->minValue(0),
                                TextInput::make('sort_order')
                                    ->label('Sort Order')
                                    ->integer()
                                    ->minValue(0)
                                    ->default(0),
                            ]))
                            ->columns(1)
                            ->collapsible()
                            ->collapsed()
                            ->minItems(1)
                            ->reorderable()
                            ->itemLabel(fn(array $state): ?string => $state['meal_name'] ?? $state['meal_type'] ?? null)
                            ->addActionLabel('Add Meal'),
                    ]))
                    ->columns(1)
                    ->collapsible()
                    ->collapsed()
                    ->minItems(1)
                    ->reorderable()
                    ->itemLabel(fn(array $state): ?string => isset($state['day_number'], $state['week_day']) ? 'Day ' . $state['day_number'] . ' - ' . $state['week_day'] : null)
                    ->addActionLabel('Add Day'),
            ], 'Add meals for each day. These meals are copied into the patient diet plan when the template is assigned.', icon: 'heroicon-o-calendar-days'),
        ]));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('doctor_id')
                    ->label('Doctor')
                    ->formatStateUsing(fn ($state, DietTemplate $record): string => static::doctorDisplayName($record->doctor))
                    ->placeholder('-'),
                TextColumn::make('duration_days')->label('Days')->sortable(),
                TextColumn::make('days_count')->counts('days')->label('Chart Days')->sortable(),
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
            'index' => ListDietTemplates::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['doctor.user'])
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

    protected static function isOwnRecord($record): bool
    {
        return (bool) ($record?->doctor_id && Auth::user()?->doctor?->id === $record->doctor_id);
    }

    private static function hasDietTemplateRole(): bool
    {
        $user = Auth::user();

        return is_object($user)
            && method_exists($user, 'hasAnyRole')
            && $user->hasAnyRole(['super_admin', 'admin', 'doctor_manager', 'receptionist', 'doctor']);
    }

    private static function hasDietTemplateAdminRole(): bool
    {
        $user = Auth::user();

        return is_object($user)
            && method_exists($user, 'hasAnyRole')
            && $user->hasAnyRole(['super_admin', 'admin', 'doctor_manager', 'receptionist']);
    }

    private static function doctorDisplayName(?Doctor $doctor): string
    {
        if (! $doctor) {
            return '-';
        }

        return trim("{$doctor->first_name} {$doctor->last_name}")
            ?: (string) ($doctor->name ?: $doctor->user?->name ?: '-');
    }

    private static function weekDayOptions(): array
    {
        return [
            'MONDAY' => 'Monday',
            'TUESDAY' => 'Tuesday',
            'WEDNESDAY' => 'Wednesday',
            'THURSDAY' => 'Thursday',
            'FRIDAY' => 'Friday',
            'SATURDAY' => 'Saturday',
            'SUNDAY' => 'Sunday',
        ];
    }

    private static function mealTypeOptions(): array
    {
        return [
            'MORNING' => 'Morning',
            'BREAKFAST' => 'Breakfast',
            'MID_MEAL' => 'Mid Meal',
            'LUNCH' => 'Lunch',
            'EVENING_SNACK' => 'Evening Snack',
            'DINNER' => 'Dinner',
            'NIGHT' => 'Night',
        ];
    }
}
