<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Infolists\Components\ImageEntry;
class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('User Overview')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('name')
                                    ->label('Full Name')
                                    ->weight('bold')
                                    ->size('lg')
                                    ->icon('heroicon-o-user')
                                    ->copyable()
                                    ->copyMessage('Name copied'),

                                TextEntry::make('email')
                                    ->label('Email')
                                    ->icon('heroicon-o-envelope')
                                    ->copyable()
                                    ->copyMessage('Email copied')
                                    ->badge()
                                    ->color('info'),

                                TextEntry::make('phone')
                                    ->label('Phone')
                                    ->icon('heroicon-o-phone')
                                    ->copyable()
                                    ->copyMessage('Phone copied')
                                    ->placeholder('—'),

                                TextEntry::make('slug')
                                    ->label('Slug')
                                    ->icon('heroicon-o-link')
                                    ->copyable()
                                    ->copyMessage('Slug copied')
                                    ->badge()
                                    ->color('gray')
                                    ->placeholder('—'),
                            ]),
                    ])
                    ->columnSpanFull(),

                Tabs::make('UserDetails')
                    ->tabs([
                        Tab::make('Account')
                            ->icon('heroicon-o-user-circle')
                            ->schema([
                                
                                Section::make('Account Information')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextEntry::make('role')
                                                    ->label('User Role')
                                                    ->badge()
                                                    ->color(function ($state) {
                                                        // $state might be string or object
                                                        $value = is_object($state) && property_exists($state, 'value') ? $state->value : $state;
                                                        return match ($value) {
                                                            'super_admin' => 'danger',
                                                            'doctor' => 'success',
                                                            'patient' => 'info',
                                                            default => 'gray',
                                                        };
                                                    })
                                                    ->formatStateUsing(function ($state) {
                                                        $value = is_object($state) && property_exists($state, 'value') ? $state->value : $state;
                                                        return $value ? ucfirst(str_replace('_', ' ', $value)) : '—';
                                                    }),

                                                TextEntry::make('email_verified_at')
                                                    ->label('Email Status')
                                                    ->badge()
                                                    ->color(fn($state) => $state ? 'success' : 'danger')
                                                    ->formatStateUsing(fn($state) => $state ? 'Verified' : 'Unverified')
                                                    ->icon(fn($state) => $state ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle'),
                                            ]),
                                    ])
                                    ->columnSpanFull(),

                                Section::make('Timestamps')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextEntry::make('created_at')
                                                    ->label('Created At')
                                                    ->dateTime('F j, Y \a\t g:i A')
                                                    ->icon('heroicon-o-calendar')
                                                    ->formatStateUsing(fn($state) => $state ? $state->format('F j, Y \a\t g:i A') : '—')
                                                    ->since(),

                                                TextEntry::make('updated_at')
                                                    ->label('Updated At')
                                                    ->dateTime('F j, Y \a\t g:i A')
                                                    ->icon('heroicon-o-clock')
                                                    ->formatStateUsing(fn($state) => $state ? $state->format('F j, Y \a\t g:i A') : '—')
                                                    ->since(),
                                            ]),
                                    ])
                                    ->columnSpanFull(),
                            ]),

                        Tab::make('Roles & Permissions')
                            ->icon('heroicon-o-shield-check')
                            ->badge(fn($record) => $record->roles()->count())
                            ->schema([
                                Section::make('Spatie Roles')
                                    ->description('Roles assigned via Spatie Permission package')
                                    ->schema([
                                        TextEntry::make('roles.name')
                                            ->label('Assigned Roles')
                                            ->badge()
                                            ->separator(',')
                                            ->color('primary')
                                            ->getStateUsing(function ($record) {
                                                $roles = $record->roles;
                                                if ($roles->isEmpty()) {
                                                    return ['No roles assigned'];
                                                }
                                                return $roles->pluck('name')->toArray();
                                            })
                                            ->placeholder('No roles assigned'),
                                    ])
                                    ->columnSpanFull(),

                                Section::make('Permissions')
                                    ->description('All permissions granted through assigned roles')
                                    ->schema([
                                        TextEntry::make('permissions_via_roles')
                                            ->label('Permissions')
                                            ->getStateUsing(function ($record) {
                                                $permissions = $record->getAllPermissions();
                                                if ($permissions->isEmpty()) {
                                                    return ['No permissions'];
                                                }
                                                return $permissions->pluck('name')->toArray();
                                            })
                                            ->badge()
                                            ->separator(',')
                                            ->color('success')
                                            ->copyable()
                                            ->copyMessage('Permission names copied')
                                            ->placeholder('No permissions assigned'),
                                    ])
                                    ->columnSpanFull(),
                            ])
                            ->visible(fn($record) => $record->roles()->count() > 0),

                        Tab::make('Profile')
                            ->icon('heroicon-o-photo')
                            ->schema([
                                Section::make('Profile Information')
                                    ->schema([
                                        ImageEntry::make('avatar')
                                            ->columnSpanFull(),
                                    ])
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
