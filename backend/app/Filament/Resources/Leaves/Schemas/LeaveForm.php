<?php

namespace App\Filament\Resources\Leaves\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Filament\Forms\Components\DatePicker;

class LeaveForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // User select dropdown
                Select::make('user_id')
                    ->label('User')
                    ->options(function () {
                        // Fetch all users with their roles
                        return \App\Models\User::with('roles')->get()->mapWithKeys(function ($user) {
                            $roles = $user->roles->pluck('name')->implode(', ');
                            $display = $user->name . ($roles ? " ($roles)" : '');
                            return [$user->id => $display];
                        });
                    })
                    ->searchable()
                    ->required(),
                DatePicker::make('start_date')
                    ->label('Start Date')
                    ->required(),
                DatePicker::make('end_date')
                    ->label('End Date')
                    ->required(),


                // Leave type enum
                Select::make('type')
                    ->label('Leave Type')
                    ->options([
                        'sick' => 'Sick Leave',
                        'casual' => 'Casual Leave',
                        'annual' => 'Annual Leave',
                        'telehealth' => 'Telehealth Leave', // custom type for CMC
                    ])
                    ->required(),

                Textarea::make('reason')
                    ->label('Reason')
                    ->columnSpanFull(),

                Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ])
                    ->default('pending')
                    ->required(),
            ]);
    }
}