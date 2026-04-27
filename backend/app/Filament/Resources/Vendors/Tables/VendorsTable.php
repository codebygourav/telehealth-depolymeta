<?php

namespace App\Filament\Resources\Vendors\Tables;

use App\Filament\Resources\Vendors\VendorResource;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\Action;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use function App\Helpers\getUserAuditColumn;

class VendorsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company_name')->label('Company Name')->searchable()->sortable(),
                TextColumn::make('vendor_type')->label('Vendor Type')->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('contact_person')->label('Contact Person'),
                TextColumn::make('designation')->label('Designation'),
                TextColumn::make('email')->label('Email')->searchable(),
                TextColumn::make('mobile')->label('Mobile'),
                TextColumn::make('city')->label('City'),
                TextColumn::make('state')->label('State'),
                TextColumn::make('gst_number')->label('GST Number'),
                TextColumn::make('pan_number')->label('PAN Number'),
                TextColumn::make('bank_name')->label('Bank Name'),
                TextColumn::make('preferred_payment_method')->label('Preferred Payment'),
                TextColumn::make('service_description')->label('Service Description')->limit(30),
                TextColumn::make('years_in_business')->label('Years in Business'),
                TextColumn::make('preferred_communication')->label('Preferred Communication'),
                TextColumn::make('primary_contact_name')->label('Primary Contact Name'),
                getUserAuditColumn('creator', 'Created By'),
                getUserAuditColumn('updater', 'Updated By'),
                getUserAuditColumn('deleter', 'Deleted By'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Approve Vendor')
                    ->modalDescription('Are you sure you want to approve this vendor?')
                    ->visible(fn($record) => $record->status === 'pending' && Auth::user()?->hasRole('super_admin'))
                    ->action(function ($record) {
                        $record->update(['status' => 'approved']);
                        Notification::make()
                            ->title('Vendor Approved')
                            ->body('The vendor has been approved successfully.')
                            ->success()
                            ->send();
                    }),
                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Reject Vendor')
                    ->modalDescription('Are you sure you want to reject this vendor?')
                    ->visible(fn($record) => $record->status === 'pending' && Auth::user()?->hasRole('super_admin'))
                    ->action(function ($record) {
                        $record->update(['status' => 'rejected']);
                        Notification::make()
                            ->title('Vendor Rejected')
                            ->body('The vendor has been rejected.')
                            ->success()
                            ->send();
                    }),
                EditAction::make()
                    ->visible(fn($record) => VendorResource::canEdit($record)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->visible(fn() => VendorResource::canDelete(null)),
                    RestoreBulkAction::make()
                        ->visible(fn() => VendorResource::canEdit(null)),
                    ForceDeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->visible(fn() => VendorResource::canDelete(null)),
                ]),
            ]);
    }
}
