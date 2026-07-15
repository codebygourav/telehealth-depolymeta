<?php

namespace App\Filament\Resources\Doctors\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DoctorAiTrainingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Doctor Profile')
                ->schema([
                    Grid::make(3)
                        ->schema([
                            Placeholder::make('doctor_name_preview')
                                ->label('Name')
                                ->content(fn($record) => trim(($record?->first_name ?? '') . ' ' . ($record?->last_name ?? '')) ?: '-'),
                            Placeholder::make('doctor_locale_preview')
                                ->label('Accent')
                                ->content(fn($record) => $record?->speech_locale ?: 'en-IN'),
                            Placeholder::make('doctor_voice_preview')
                                ->label('Voice Profile')
                                ->content(fn($record) => $record?->voice_name ?: 'System Default'),
                        ]),
                ])
                ->columns(1),

            Section::make('SpeechSynthesis Training Profile')
                ->description('Train pronunciation, shortcuts, instructions, and common phrases for faster and safer prescription dictation.')
                ->schema([
                    Repeater::make('ai_training_profile.pronunciation_dictionary')
                        ->label('Pronunciation Dictionary')
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    TextInput::make('doctor_says')
                                        ->label('Doctor Says')
                                        ->placeholder('Example: Panta')
                                        ->required()
                                        ->maxLength(255),
                                    TextInput::make('ai_converts_to')
                                        ->label('AI Converts To')
                                        ->placeholder('Example: Pantoprazole')
                                        ->required()
                                        ->maxLength(255),
                                ]),
                        ])
                        ->default([])
                        ->addActionLabel('Add Pronunciation Rule'),

                    Repeater::make('ai_training_profile.medicine_shortcuts')
                        ->label('Frequently Used Medicines & Shortcuts')
                        ->schema([
                            Grid::make(3)
                                ->schema([
                                    TextInput::make('medicine')
                                        ->label('Medicine')
                                        ->placeholder('Example: Paracetamol 650')
                                        ->required()
                                        ->maxLength(255),
                                    TextInput::make('shortcut')
                                        ->label('Shortcut')
                                        ->placeholder('Example: PCM')
                                        ->required()
                                        ->maxLength(80),
                                    Select::make('priority')
                                        ->label('Priority')
                                        ->helperText('Set 5 for very frequently used medicines.')
                                        ->options([
                                            1 => '1 Star',
                                            2 => '2 Stars',
                                            3 => '3 Stars',
                                            4 => '4 Stars',
                                            5 => '5 Stars',
                                        ])
                                        ->default(3)
                                        ->required(),
                                ]),
                        ])
                        ->default([])
                        ->addActionLabel('Add Medicine Shortcut'),

                    Repeater::make('ai_training_profile.common_diagnoses')
                        ->label('Common Diagnoses')
                        ->schema([
                            TextInput::make('value')
                                ->label('Diagnosis')
                                ->placeholder('Example: Hypertension')
                                ->required()
                                ->maxLength(255),
                        ])
                        ->default([])
                        ->addActionLabel('Add Diagnosis'),

                    Repeater::make('ai_training_profile.frequently_used_instructions')
                        ->label('Frequently Used Instructions')
                        ->schema([
                            TextInput::make('value')
                                ->label('Instruction')
                                ->placeholder('Example: Take after food')
                                ->required()
                                ->maxLength(500),
                        ])
                        ->default([])
                        ->addActionLabel('Add Instruction'),

                    Repeater::make('ai_training_profile.procedures_investigations')
                        ->label('Procedures & Investigations')
                        ->schema([
                            TextInput::make('value')
                                ->label('Procedure / Investigation')
                                ->placeholder('Example: CBC')
                                ->required()
                                ->maxLength(255),
                        ])
                        ->default([])
                        ->addActionLabel('Add Procedure'),
                ])
                ->columns(1),
        ]);
    }
}
