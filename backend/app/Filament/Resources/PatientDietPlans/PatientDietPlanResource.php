<?php

namespace App\Filament\Resources\PatientDietPlans;

use App\Filament\Resources\PatientDietPlans\Pages;
use App\Models\PatientDietPlan;
use App\Traits\HasCustomSidebar;
use App\Traits\HasResourcePermissions;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\ViewEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class PatientDietPlanResource extends Resource
{
    use HasCustomSidebar;
    use HasResourcePermissions;

    protected static ?string $model = PatientDietPlan::class;

    protected static ?string $navigationLabel = 'Patient Diet Plans';

    protected static ?string $slug = 'patient-diet-plans';

    public static function getSidebarOptions(): array
    {
        return [
            'label' => 'Patient Diet Plans',
            'icon' => 'heroicon-o-clipboard-document-check',
            'sort' => 7,
            'group' => 'Diet',
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();

        return check_permission(['patient-diet-plans.view_any', 'patient-diet-plans.view', 'patient-diet-plans.manage_own'])
            || (is_object($user) && method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['super_admin', 'admin', 'doctor_manager', 'receptionist', 'doctor']));
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            ViewEntry::make('diet_plan_details')
                ->view('filament.patient-diet-plans.plan-view')
                ->state(fn($record) => $record)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('patient.first_name')
                    ->label('Patient')
                    ->formatStateUsing(fn($state, PatientDietPlan $record): string => static::patientDisplayName($record))
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('patient', function (Builder $patientQuery) use ($search) {
                            $patientQuery
                                ->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->orWhere('mobile_no', 'like', "%{$search}%");
                        });
                    }),
                TextColumn::make('template_name')
                    ->label('Assigned Diet')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('doctor.first_name')
                    ->label('Doctor')
                    ->formatStateUsing(fn($state, PatientDietPlan $record): string => static::doctorDisplayName($record))
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('doctor', function (Builder $doctorQuery) use ($search) {
                            $doctorQuery
                                ->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        });
                    }),
                TextColumn::make('start_date')->date()->sortable(),
                TextColumn::make('end_date')->date()->sortable(),
                TextColumn::make('duration_days')
                    ->label('Days')
                    ->sortable(),
                TextColumn::make('days_count')
                    ->counts('days')
                    ->label('Chart Days')
                    ->sortable(),
                TextColumn::make('meals_count')
                    ->counts('meals')
                    ->label('Meals')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn($state): string => match ((string) $state) {
                        'active' => 'success',
                        'paused' => 'warning',
                        'completed' => 'info',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn($state): string => ucfirst((string) $state))
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(static::statusOptions()),
                SelectFilter::make('doctor_id')
                    ->label('Doctor')
                    ->relationship('doctor', 'first_name'),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                ]),
            ])
            ->recordUrl(null);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPatientDietPlans::route('/'),
            'view' => Pages\ViewPatientDietPlan::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['patient', 'doctor.user', 'template'])
            ->withCount(['days', 'meals'])
            ->withoutGlobalScopes();

        $user = Auth::user();
        $isPrivileged = is_object($user)
            && method_exists($user, 'hasAnyRole')
            && $user->hasAnyRole(['super_admin', 'admin', 'doctor_manager', 'receptionist']);

        if (! $isPrivileged && $user?->doctor?->id) {
            return $query->where('doctor_id', $user->doctor->id);
        }

        return $query;
    }

    private static function statusOptions(): array
    {
        return [
            'draft' => 'Draft',
            'active' => 'Active',
            'paused' => 'Paused',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
        ];
    }

    private static function patientDisplayName(PatientDietPlan $record): string
    {
        return trim(($record->patient?->first_name ?? '') . ' ' . ($record->patient?->last_name ?? ''))
            ?: (string) ($record->patient?->email ?: $record->patient_id ?: '-');
    }

    private static function doctorDisplayName(PatientDietPlan $record): string
    {
        return trim(($record->doctor?->first_name ?? '') . ' ' . ($record->doctor?->last_name ?? ''))
            ?: (string) ($record->doctor?->name ?: $record->doctor?->user?->name ?: '-');
    }
}
