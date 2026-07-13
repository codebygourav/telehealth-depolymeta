<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                // -----------------------
                // PROFILE SECTION
                // -----------------------
                Section::make('Profile')
                    ->schema([
                        FileUpload::make('avatar')
                            ->label('Profile Photo')
                            ->image()
                            ->disk('public')
                            ->directory('user_avatar')
                            ->visibility('public')
                            ->preserveFilenames()
                            ->imageEditor()
                            ->imageEditorAspectRatios([
                                '1:1',
                            ])
                            ->imagePreviewHeight('150')
                            ->circleCropper()
                            ->maxSize(2048) // 2MB max file size
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            // Note: optimize() and imageResize methods don't exist in Filament v4
                            // Image editing is handled via imageEditor() above
                            ->columnSpanFull(),
                    ])
                    ->columns(1)
                    ->columnSpanFull(),

                // -----------------------
                // USER INFORMATION SECTION
                // -----------------------
                Section::make('User Information')
                    ->schema([

                        TextInput::make('name')
                            ->label('Full Name')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('phone')
                            ->tel()
                            ->maxLength(255),

                        \Filament\Forms\Components\Select::make('roles')
                            ->relationship('roles', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->visible(fn() => check_permission('users.assign_role')),

                        // ---- CHANGE PASSWORD TOGGLE (EDIT USER ONLY) ----
                        Toggle::make('change_password')
                            ->label('Change Password?')
                            ->reactive()
                            ->default(false)
                            ->visible(fn(string $operation) => $operation === 'edit'),

                        // ---- CURRENT PASSWORD (FAKE MASKED FIELD - EDIT USER, TOGGLE OFF) ----
                        TextInput::make('password_display')
                            ->label('Current Password')
                            ->disabled()
                            ->visible(fn($get, string $operation) => $operation === 'edit' && !$get('change_password'))
                            ->afterStateHydrated(fn($component) => $component->state('********')),

                        // ---- PASSWORD FIELD (CREATE NEW USER OR EDIT WITH TOGGLE ON) ----
                        TextInput::make('password')
                            ->label(fn(string $operation) => $operation === 'create' ? 'Password' : 'New Password')
                            ->password()
                            ->revealable()
                            ->minLength(6)
                            ->maxLength(255)
                            ->required(fn(string $operation) => $operation === 'create')
                            ->visible(fn($get, string $operation) => $operation === 'create' || ($operation === 'edit' && $get('change_password')))
                            ->dehydrated(fn($state) => filled($state))
                            ->dehydrateStateUsing(fn($state) => filled($state) ? $state : null),

                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        Toggle::make('email_verified_at')
                            ->label('Email Verified')
                            ->helperText('Mark as verified to set the email as confirmed')
                            ->inline(false)
                            ->onColor('success')
                            ->offColor('danger')
                            ->visible(fn(string $operation) => $operation === 'edit')
                            ->afterStateHydrated(function ($component, $state) {
                                $component->state(!empty($state));
                            })
                            ->dehydrateStateUsing(fn($state) => $state ? now() : null),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
            ]);
    }
}
