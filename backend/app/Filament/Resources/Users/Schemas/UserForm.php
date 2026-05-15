<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

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
                            ->unique(ignoreRecord: true)
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

                        // ---- CHANGE PASSWORD TOGGLE ----
                        Toggle::make('change_password')
                            ->label('Change Password?')
                            ->onColor('success')
                            ->onIcon('heroicon-o-check')
                            ->offColor('danger')
                            ->offIcon('heroicon-o-x')
                            ->reactive()
                            ->default(false),

                        // ---- CURRENT PASSWORD (FAKE MASKED FIELD) ----
                        TextInput::make('password_display')
                            ->label('Current Password')
                            ->disabled()
                            ->visible(fn($get) => !$get('change_password'))
                            ->afterStateHydrated(fn($component) => $component->state('********')),

                        // ---- NEW PASSWORD FIELD ----
                        TextInput::make('password')
                            ->label('New Password')
                            ->password()
                            ->revealable()
                            ->minLength(6)
                            ->maxLength(255)
                            ->visible(fn($get) => $get('change_password'))
                            ->dehydrated(fn($state) => filled($state))
                            ->dehydrateStateUsing(fn($state) => filled($state) ? Hash::make($state) : null),

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
                                ->onIcon('heroicon-o-check')
                                ->offColor('danger')
                                ->offIcon('heroicon-o-x')
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
