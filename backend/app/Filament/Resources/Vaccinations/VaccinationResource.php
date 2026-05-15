<?php

namespace App\Filament\Resources\Vaccinations;

use App\Enums\VaccinationGenderRestriction;
use App\Filament\Concerns\ConfiguresSlideOverSections;
use App\Filament\Resources\Vaccinations\Pages\ListVaccinations;
use App\Models\Vaccination;
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

class VaccinationResource extends Resource
{
    use ConfiguresSlideOverSections;
    use HasCustomSidebar;
    use HasResourcePermissions;

    protected static ?string $model = Vaccination::class;

    protected static ?string $navigationLabel = 'Vaccine Master';

    protected static ?string $slug = 'vaccinations';

    public static function getSidebarOptions(): array
    {
        return [
            'label' => 'Vaccine Master',
            'icon' => 'heroicon-o-shield-check',
            'sort' => 4,
            'group' => 'Clinical',
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();

        return check_permission(['vaccinations.view_any', 'vaccinations.view', 'vaccinations.manage_own'])
            || $user?->hasAnyRole(['super_admin', 'admin', 'doctor_manager', 'receptionist', 'doctor']);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components(static::wrapSlideOverForm([
            static::slideOverSection('Vaccine Details', [
                TextInput::make('name')
                    ->helperText('Full vaccine name, for example Hepatitis B.')
                    ->required()
                    ->maxLength(255),
                TextInput::make('short_name')
                    ->helperText('Short display name, for example HepB.')
                    ->maxLength(50),
                TextInput::make('manufacturer')
                    ->helperText('Company or manufacturer name, if known.')
                    ->maxLength(255),
                TextInput::make('disease_for')
                    ->helperText('Disease this vaccine protects against.')
                    ->maxLength(255),
                Toggle::make('is_active')
                    ->onColor('success')
                    ->onIcon('heroicon-o-check')
                    ->offColor('danger')
                    ->helperText('Turn off to hide this vaccine from new templates.')
                    ->default(true),
            ], 'Add the vaccine master record used inside templates and patient schedules.'),
            static::slideOverSection('Dose And Eligibility Rules', [
                Toggle::make('is_multi_dose')
                    ->onColor('success')
                    ->offColor('danger')
                    ->helperText('Turn on when this vaccine normally has more than one dose.')
                    ->default(false),
                TextInput::make('total_doses')
                    ->helperText('Total doses normally needed for this vaccine.')
                    ->integer()
                    ->minValue(1)
                    ->default(1),
                TextInput::make('minimum_age_days')
                    ->helperText('Minimum patient age in days. Leave blank if no minimum.')
                    ->integer()
                    ->minValue(0),
                TextInput::make('maximum_age_days')
                    ->helperText('Maximum patient age in days. Leave blank if no maximum.')
                    ->integer()
                    ->minValue(0),
                Select::make('gender_restriction')
                    ->helperText('Choose if this vaccine is for all patients or only one gender.')
                    ->options(VaccinationGenderRestriction::options())
                    ->default(VaccinationGenderRestriction::ALL->value)
                    ->required(),
            ], 'Set basic dose count and patient eligibility rules.'),
            static::slideOverSection('Medical Notes', [
                Textarea::make('description')
                    ->helperText('Simple explanation of the vaccine.')
                    ->rows(3),
                Textarea::make('side_effects')
                    ->helperText('Common side effects, one per line if possible.')
                    ->rows(3),
                Textarea::make('contraindications')
                    ->helperText('When this vaccine should not be given.')
                    ->rows(3),
                Textarea::make('precautions')
                    ->helperText('Important warnings before giving the vaccine.')
                    ->rows(3),
                Textarea::make('dosage_information')
                    ->helperText('Dose amount or dosage notes.')
                    ->rows(3),
            ], 'Optional information shown to doctors and patients.'),
            static::slideOverSection('Vaccination FAQs', [
                Repeater::make('faqs')
                    ->relationship('faqs')
                    ->schema(static::slideOverFields([
                        TextInput::make('question')
                            ->placeholder('Enter the question...')
                            ->required()
                            ->maxLength(255),
                        Textarea::make('answer')
                            ->placeholder('Enter the answer...')
                            ->rows(3)
                            ->required(),
                    ]))
                    ->columns(1)
                    ->collapsible()
                    ->itemLabel(fn (array $state): ?string => $state['question'] ?? null)
                    ->reorderable()
                    ->orderColumn('sort_order')
                    ->addActionLabel('Add FAQ')
                    ->defaultItems(0),
            ], 'Questions and answers shown with this vaccine in the patient app.', icon: 'heroicon-o-question-mark-circle'),
        ]));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('short_name')->searchable(),
                TextColumn::make('manufacturer')->searchable(),
                TextColumn::make('disease_for')->searchable(),
                TextColumn::make('total_doses')->sortable(),
                TextColumn::make('gender_restriction')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof VaccinationGenderRestriction ? $state->label() : (VaccinationGenderRestriction::tryFrom((string) $state)?->label() ?? ucfirst((string) $state))),
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
            'index' => ListVaccinations::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes();
    }
}
