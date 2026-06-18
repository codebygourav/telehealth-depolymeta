<?php

namespace App\Filament\Resources\Vaccinations;

use App\Filament\Resources\Vaccinations\Pages;
use App\Models\Vaccination;
use App\Traits\HasCustomSidebar;
use App\Traits\HasResourcePermissions;
use Filament\Actions\{ActionGroup,Action,BulkActionGroup,DeleteAction,DeleteBulkAction,EditAction,ForceDeleteBulkAction,RestoreBulkAction};
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Filament\Infolists\Components\ViewEntry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

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
            'label' => 'Vaccine Master',
            'icon' => 'heroicon-o-shield-check',
            'sort' => 4,
            'group' => 'Vaccination',
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();

        return check_permission(['vaccinations.view_any', 'vaccinations.view', 'vaccinations.manage_own'])
            || (is_object($user) && method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['super_admin', 'admin', 'doctor_manager', 'receptionist', 'doctor']));
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Vaccine Overview')
                ->schema([
                    TextInput::make('name')
                        ->label('Vaccine Name')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('short_name')
                        ->label('Short Name')
                        ->maxLength(50)
                        ->helperText('Optional short identifier for quick lookup.'),
                    TextInput::make('disease_for')
                        ->label('Protects Against')
                        ->maxLength(255)
                        ->helperText('Describe the disease or condition this vaccine is used for.'),
                    Toggle::make('is_active')
                        ->label('Active')
                        ->onColor('success')
                        ->offColor('danger')
                        ->default(true)
                        ->helperText('When active, this vaccine can be assigned to schedules.'),
                ])
                ->columns(1),

            Section::make('Clinical Details')
                ->schema([
                    Textarea::make('description')
                        ->label('Clinical Description')
                        ->rows(4)
                        ->helperText('A concise medical description of the vaccine and its indication.'),
                    Textarea::make('dosage_information')
                        ->label('Dosage Information')
                        ->rows(4)
                        ->helperText('Provide the standard dose, administration route, and any key instructions.'),
                    Textarea::make('side_effects')
                        ->label('Common Side Effects')
                        ->rows(4)
                        ->helperText('List expected side effects or post-vaccination reactions.'),
                    Textarea::make('contraindications')
                        ->label('Contraindications')
                        ->rows(4)
                        ->helperText('List medical conditions or patient histories that contraindicate this vaccine.'),
                    Textarea::make('precautions')
                        ->label('Precautions')
                        ->rows(4)
                        ->helperText('List any special precautions to verify before administration.'),
                ])
                ->columns(1),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            ViewEntry::make('vaccination_details')
                ->view('filament.vaccinations.vaccination-view')
                ->state(fn($record) => $record)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Vaccine Name')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),
                TextColumn::make('short_name')
                    ->label('Short Name')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('disease_for')
                    ->label('Protects Against')
                    ->searchable(),
                TextColumn::make('is_active')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn($state): string => ((bool) $state) ? 'Active' : 'Inactive')
                    ->color(fn($state): string => ((bool) $state) ? 'success' : 'gray'),
                TextColumn::make('updated_at')->label('Updated')->dateTime()->sortable(),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    \Filament\Actions\ViewAction::make(),
                    EditAction::make(),
                    Action::make('assign')
                        ->label('Assign to Patient')
                        ->icon('heroicon-o-user-plus')
                        ->url(fn($record) => \App\Filament\Resources\PatientVaccinations\PatientVaccinationResource::getUrl('create', ['vaccination_id' => $record->id])),
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
            'index' => Pages\ListVaccinations::route('/'),
            'create' => Pages\CreateVaccination::route('/create'),
            'view' => Pages\ViewVaccination::route('/{record}'),
            'edit' => Pages\EditVaccination::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes();
    }
}
