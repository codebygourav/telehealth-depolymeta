<?php

namespace App\Filament\Resources\Vaccinations;

use App\Filament\Resources\Vaccinations\Pages\ListVaccinations;
use App\Models\Vaccination;
use App\Traits\HasCustomSidebar;
use App\Traits\HasResourcePermissions;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Illuminate\Database\Eloquent\Builder;

class VaccinationResource extends Resource
{
    use HasCustomSidebar;
    use HasResourcePermissions;

    protected static ?string $model = Vaccination::class;

    protected static ?string $navigationLabel = 'Vaccine Master';

    protected static ?string $slug = 'vaccinations';

    public static function getSidebarOptions(): array
    {
        return [
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
        return $schema->components([
            TextInput::make('name')
                ->required()
                ->maxLength(255),
            TextInput::make('short_name')
                ->maxLength(50),
            TextInput::make('manufacturer')
                ->maxLength(255),
            TextInput::make('disease_for')
                ->maxLength(255),
            Textarea::make('description')
                ->columnSpanFull(),
            Textarea::make('side_effects')
                ->columnSpanFull(),
            Textarea::make('contraindications')
                ->columnSpanFull(),
            Textarea::make('precautions')
                ->columnSpanFull(),
            Textarea::make('dosage_information')
                ->columnSpanFull(),
            Toggle::make('is_multi_dose')
                ->default(false),
            TextInput::make('total_doses')
                ->integer()
                ->minValue(1)
                ->default(1),
            Toggle::make('is_active')
                ->default(true),
        ]);
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
