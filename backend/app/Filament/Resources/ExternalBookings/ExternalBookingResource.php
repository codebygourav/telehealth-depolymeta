<?php

namespace App\Filament\Resources\ExternalBookings;

use App\Filament\Resources\ExternalBookings\Pages\ListExternalBookings;
use App\Models\ExternalBooking;
use App\Traits\{HasCustomSidebar, HasResourcePermissions};
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ExternalBookingResource extends Resource
{
    use HasResourcePermissions;
    use HasCustomSidebar;
    protected static ?string $model = ExternalBooking::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected static string|\UnitEnum|null $navigationGroup = 'Appointments & Finance';

    protected static ?int $navigationSort = 41;

    protected static ?string $modelLabel = 'External Booking';

    protected static ?string $pluralModelLabel = 'External Bookings';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    protected static function isSidebarItemVisible(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['doctor.user', 'availability']))
            ->defaultSort('appointment_date', 'desc')
            ->recordClasses(fn (ExternalBooking $record) => $record->availability_id ? null : 'bg-danger-500/10 dark:bg-danger-500/20')
            ->paginationPageOptions([10, 25, 50, 100, 200, 'all'])
            ->defaultPaginationPageOption(50)
            ->columns([
                TextColumn::make('appointment_date')
                    ->label('OPD Visit Date')
                    ->date('M d, Y')
                    ->sortable(),
                TextColumn::make('start_time')
                    ->label('Time')
                    ->time('h:i A')
                    ->sortable(),
                TextColumn::make('doctor.user.name')
                    ->label('Doctor')
                    ->searchable(),
                TextColumn::make('patient_name')
                    ->label('Patient')
                    ->searchable(),
                TextColumn::make('patient_unit_number')
                    ->label('Unit No.')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('mobile')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('availability_status')
                    ->label('Slot Match')
                    ->badge()
                    ->state(fn (ExternalBooking $record) => $record->availability_id ? 'Matched' : 'No Platform Slot')
                    ->color(fn (ExternalBooking $record) => $record->availability_id ? 'success' : 'danger')
                    ->icon(fn (ExternalBooking $record) => $record->availability_id ? 'heroicon-o-check-circle' : 'heroicon-o-exclamation-triangle'),
                TextColumn::make('source_doctor_id')
                    ->label('Sheet Doctor ID')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('track_upload_status')
                    ->label('Track Status')
                    ->badge()
                    ->toggleable(),
                TextColumn::make('stack_upload_status')
                    ->label('Stack Status')
                    ->badge()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Imported At')
                    ->dateTime('M d, Y h:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('appointment_date')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('date')->label('OPD Visit Date'),
                    ])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['date'] ?? null, fn (Builder $query, $date) => $query->whereDate('appointment_date', $date))),
                Filter::make('no_platform_slot')
                    ->label('No Platform Slot')
                    ->query(fn (Builder $query) => $query->whereNull('availability_id')),
                SelectFilter::make('doctor_id')
                    ->label('Doctor')
                    ->relationship('doctor', 'first_name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->user?->name ?: trim("{$record->first_name} {$record->last_name}"))
                    ->searchable()
                    ->preload(),
                SelectFilter::make('start_time')
                    ->label('Time Slot')
                    ->options(fn () => ExternalBooking::query()
                        ->select('start_time')
                        ->whereNotNull('start_time')
                        ->distinct()
                        ->orderBy('start_time')
                        ->pluck('start_time', 'start_time')
                        ->map(fn ($time) => \Carbon\Carbon::parse($time)->format('h:i A'))
                        ->toArray()),
            ])
            ->recordActions([
                Action::make('updateTrackUploadStatus')
                    ->label('Update Track Status')
                    ->icon('heroicon-o-pencil-square')
                    ->form([
                        Select::make('track_upload_status')
                            ->label('Track upload status')
                            ->options([
                                'Pending' => 'Pending',
                                'Uploaded' => 'Uploaded',
                                'Failed' => 'Failed',
                            ])
                            ->native(false),
                    ])
                    ->fillForm(fn (ExternalBooking $record): array => [
                        'track_upload_status' => $record->track_upload_status,
                    ])
                    ->action(fn (ExternalBooking $record, array $data) => $record->update($data)),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListExternalBookings::route('/'),
        ];
    }
}