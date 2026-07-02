<?php

namespace App\Filament\Resources\Doctors;

use App\Filament\Resources\Doctors\Pages\ManageDoctorAvailabilities;
use App\Models\DoctorAvailability;
use App\Traits\HasCustomSidebar;
use App\Traits\HasResourcePermissions;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\ToggleColumn;
use Illuminate\Database\Eloquent\Builder;

class DoctorAvailabilityResource extends Resource
{
    use HasResourcePermissions;
    use HasCustomSidebar;

    protected static ?string $model = DoctorAvailability::class;

    protected static ?string $navigationLabel = 'Doctor Availabilities';

    protected static ?string $slug = 'doctors-availbiltties';

    protected static ?string $pluralLabel = 'Doctor Availabilities';

    protected static ?string $modelLabel = 'Doctor Slot';

    public static function getSidebarOptions(): array
    {
        return [
            'icon'  => 'heroicon-o-calendar-days',
            'group' => 'Doctor Management',
            'sort'  => 12,
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return check_permission('doctor-availability.view_any');
    }

    public static function requiredPermission(): string
    {
        return 'doctor_manager';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('doctor.user.name')
                    ->label('Doctor')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('day_of_week')
                    ->label('Day')
                    ->formatStateUsing(fn(string $state): string => ucfirst($state))
                    ->sortable(),
                TextColumn::make('date')
                    ->label('Date')
                    ->date()
                    ->sortable(),
                TextColumn::make('start_time')
                    ->label('Start')
                    ->time('H:i')
                    ->sortable(),
                TextColumn::make('end_time')
                    ->label('End')
                    ->time('H:i')
                    ->sortable(),
                TextColumn::make('consultation_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'in-person' => 'success',
                        'video' => 'primary',
                        default => 'gray',
                    }),
                TextColumn::make('capacity')
                    ->label('Cap')
                    ->numeric()
                    ->sortable(),
                ToggleColumn::make('is_available')
                    ->label('Available'),
                ToggleColumn::make('is_recurring')
                    ->label('Recurring'),
                ToggleColumn::make('is_auto_recurring')
                    ->label('Auto Recur'),
            ])
            ->filters([
                // Add filters for doctor, date, etc.
            ])
            ->actions([
                // Edit/Delete actions
            ])
            ->bulkActions([
                // Bulk delete
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageDoctorAvailabilities::route('/'),
        ];
    }
}
