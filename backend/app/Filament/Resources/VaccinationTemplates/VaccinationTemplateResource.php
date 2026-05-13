<?php

namespace App\Filament\Resources\VaccinationTemplates;

use App\Filament\Resources\VaccinationTemplates\Pages\ListVaccinationTemplates;
use App\Models\Doctor;
use App\Models\Vaccination;
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
        return $schema->components([
            Select::make('doctor_id')
                ->label('Doctor')
                ->options(fn () => Doctor::query()
                    ->orderBy('first_name')
                    ->get()
                    ->mapWithKeys(fn (Doctor $doctor) => [$doctor->id => trim("{$doctor->first_name} {$doctor->last_name}") ?: $doctor->name ?: $doctor->id]))
                ->searchable()
                ->required()
                ->default(fn () => Auth::user()?->doctor?->id)
                ->disabled(function () {
                    $role = Auth::user()?->role;
                    $isDoctor = $role === 'doctor';
                    $isPrivileged = in_array($role, ['super_admin', 'doctor_manager', 'receptionist'], true);

                    return $isDoctor && ! $isPrivileged;
                })
                ->dehydrated(),
            TextInput::make('name')
                ->required()
                ->maxLength(255),
            Textarea::make('description')
                ->columnSpanFull(),
            Toggle::make('is_active')
                ->default(true),
            Repeater::make('items')
                ->relationship()
                ->schema([
                    Select::make('vaccination_id')
                        ->label('Vaccination')
                        ->options(fn () => Vaccination::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id'))
                        ->searchable()
                        ->required(),
                    TextInput::make('set_name')
                        ->label('Set Name')
                        ->placeholder('Pregnancy Month 1 / Baby Month 2')
                        ->helperText('Use this as vaccination group name (optional)')
                        ->maxLength(255),
                    Textarea::make('set_description')
                        ->label('Set Description')
                        ->rows(2)
                        ->columnSpan(2),
                    TextInput::make('dose_no')
                        ->label('Dose No.')
                        ->integer()
                        ->minValue(1)
                        ->default(1)
                        ->required(),
                    TextInput::make('recommended_age_label')
                        ->label('Recommended Age')
                        ->placeholder('At Birth / 6 Weeks / 6-12 Months')
                        ->maxLength(255),
                    TextInput::make('due_after_months')
                        ->label('Due After Months')
                        ->integer()
                        ->minValue(0)
                        ->default(0)
                        ->helperText('Gap in months from previous dose / start date'),
                ])
                ->columns(4)
                ->minItems(1)
                ->reorderable()
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('doctor.name')->label('Doctor')->searchable(),
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
            ->with(['doctor'])
            ->withoutGlobalScopes();

        $user = Auth::user();
        if ((is_object($user) && method_exists($user, 'hasRole') && $user->hasRole('doctor')) && ! (is_object($user) && method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['super_admin', 'doctor_manager', 'receptionist']))) {
            return $query->where('doctor_id', $user->doctor?->id);
        }

        return $query;
    }
}
