<?php

namespace App\Filament\Resources\Payments\Tables;

use App\Enums\PaymentStatus;
use Filament\Tables\Table;
use Filament\Tables\Columns\{TextColumn, BadgeColumn};
use Filament\Tables\Filters\SelectFilter;
use App\Models\Payment;
use Filament\Actions\{ActionGroup, BulkActionGroup, DeleteBulkAction, ForceDeleteBulkAction, RestoreBulkAction, ViewAction, DeleteAction};
use App\Filament\Resources\Payments\PaymentResource;

class PaymentsTable
{
    public static function configure(Table $table): Table
    {
        $user = auth()->user();
        $canView = $user?->hasRole('super_admin') || $user?->can('payments.view') || $user?->can('payments.view_any');
        $canViewAny = $user?->hasRole('super_admin') || $user?->can('payments.view_any');
        $canDelete = $user?->hasRole('super_admin') || $user?->can('payments.delete');
        $canRestore = $user?->hasRole('super_admin') || $user?->can('payments.restore');
        $canForceDelete = $user?->hasRole('super_admin') || $user?->can('payments.force_delete');

        return $table
            ->columns([
                TextColumn::make('appointment.patient.first_name')
                    ->label('Patient')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('appointment.doctor.user.name')
                    ->label('Doctor')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('amount')
                    ->money('INR')
                    ->sortable(),

                TextColumn::make('payment_method')
                    ->label('Method')
                    ->sortable(),

                BadgeColumn::make('status')
                    ->formatStateUsing(fn($state) => $state instanceof PaymentStatus ? $state->label() : ucfirst($state))
                    ->color(fn($state) => match ($state instanceof PaymentStatus ? $state->value : $state) {
                        'paid', 'captured' => 'success',
                        'pending', 'created' => 'warning',
                        'failed' => 'danger',
                        'refunded' => 'gray',
                        default => 'info',
                    })
                    ->sortable(),

                TextColumn::make('razorpay_payment_id')
                    ->label('Razorpay ID')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('appointment_id')
                    ->label('Appointment ID')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(collect(PaymentStatus::cases())->mapWithKeys(fn($status) => [$status->value => $status->label()])->toArray()),
                SelectFilter::make('payment_method')
                    ->options([
                        'card' => 'Card',
                        'upi' => 'UPI',
                        'netbanking' => 'Net Banking',
                        'wallet' => 'Wallet',
                    ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn() => PaymentResource::canDeleteAny()),
                    ForceDeleteBulkAction::make()
                        ->visible(fn() => PaymentResource::canDeleteAny()),
                    RestoreBulkAction::make()
                        ->visible(fn() => PaymentResource::canEdit(null)),
                ]),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()->visible(fn() => PaymentResource::canView(null)),
                    DeleteAction::make()->visible(fn() => PaymentResource::canDeleteAny())->requiresConfirmation(),
                ]),
            ])
            ->recordUrl(null);
    }
}
