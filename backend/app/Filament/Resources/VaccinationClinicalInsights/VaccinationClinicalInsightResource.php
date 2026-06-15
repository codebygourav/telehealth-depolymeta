<?php

namespace App\Filament\Resources\VaccinationClinicalInsights;

use App\Filament\Concerns\ConfiguresSlideOverSections;
use App\Filament\Resources\VaccinationClinicalInsights\Pages\ListVaccinationClinicalInsights;
use App\Models\VaccinationClinicalInsight;
use App\Traits\HasCustomSidebar;
use App\Traits\HasResourcePermissions;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VaccinationClinicalInsightResource extends Resource
{
    use ConfiguresSlideOverSections;
    use HasCustomSidebar;
    use HasResourcePermissions;

    protected static ?string $model = VaccinationClinicalInsight::class;

    protected static ?string $navigationLabel = 'Clinical Insight';

    protected static ?string $slug = 'vaccination-clinical-insights';

    public static function getSidebarOptions(): array
    {
        return [
            'label' => 'Vaccination Clinical Insight',
            'icon' => 'heroicon-o-light-bulb',
            'sort' => 11,
            'group' => 'Vaccination',
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
            static::slideOverSection('Clinical Insight', [
                TextInput::make('title')
                    ->required()
                    ->maxLength(255)
                    ->default('Clinical Insight'),
                Textarea::make('message')
                    ->required()
                    ->rows(5),
                Toggle::make('is_active')
                    ->onColor('success')
                    ->offColor('danger')
                    ->default(true),
            ], 'Message shown below the vaccination schedule in the patient app.', icon: 'heroicon-o-light-bulb'),
        ]));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->searchable(),
                TextColumn::make('message')->limit(80),
                IconColumn::make('is_active')->boolean(),
                TextColumn::make('updated_at')->dateTime()->sortable(),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()->slideOver(),
                    DeleteAction::make(),
                ]),
            ])
            ->recordUrl(null);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVaccinationClinicalInsights::route('/'),
        ];
    }
}
