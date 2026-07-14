<?php

namespace App\Filament\Resources\Medicines\Schemas;

use Filament\Forms\Components\{CheckboxList, Select, TagsInput, TextInput, Toggle};
use Filament\Schemas\Components\{Grid, Section};
use Filament\Schemas\Schema;
use App\Models\{MedicineCategory, MedicineType};

class MedicineForm
{
    private const FREQUENCIES = [
        'OD' => 'Once daily (OD)',
        'BD' => 'Twice daily (BD)',
        'TDS' => 'Three times daily (TDS)',
        'SOS' => 'SOS / As needed',
    ];

    private const TIMINGS = [
        'morning' => 'Morning',
        'afternoon' => 'Afternoon',
        'evening' => 'Evening',
        'night' => 'Night',
    ];

    private const MEALS = [
        'before_meal' => 'Before meal',
        'after_meal' => 'After meal',
        'with_meal' => 'With meal',
        'empty_stomach' => 'Empty stomach',
    ];

    private const FIELD_RULES = [
        'strength' => 'Strength',
        'dosage' => 'Dosage quantity',
        'frequency' => 'Frequency',
        'timing' => 'Timing',
        'meal' => 'Meal instruction',
        'duration' => 'Duration',
        'application_area' => 'Application area',
        'sos' => 'SOS / PRN',
        'remarks' => 'Remarks',
    ];

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Medicine details')
                    ->description('Admin managed medicine values are the preferred source for the prescription assistant. Doctors can still submit custom medicines through the API when a medicine is not present here.')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Medicine name')
                                    ->required()
                                    ->maxLength(255),
                                Select::make('category_id')
                                    ->label('Category')
                                    ->options(fn() => MedicineCategory::query()->orderBy('name')->pluck('name', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->createOptionForm([
                                        TextInput::make('name')
                                            ->label('Category Name')
                                            ->required()
                                            ->maxLength(255),
                                    ])
                                    ->createOptionUsing(function (array $data): string {
                                        $category = MedicineCategory::create($data);
                                        return $category->id;
                                    }),
                                Select::make('type_id')
                                    ->label('Medicine type')
                                    ->options(fn() => MedicineType::query()->orderBy('name')->pluck('name', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->createOptionForm([
                                        TextInput::make('name')
                                            ->label('Type Name')
                                            ->required()
                                            ->maxLength(255),
                                    ])
                                    ->createOptionUsing(function (array $data): string {
                                        $type = MedicineType::create($data);
                                        return $type->id;
                                    }),
                            ]),
                    ])
                    ->columnSpanFull(),

                Section::make('Voice dictation setup')
                    ->description('These options are returned to the doctor app and used by speech-to-text parsing to auto-fill prescription fields.')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TagsInput::make('spoken_aliases')
                                    ->label('Spoken aliases')
                                    ->placeholder('crocin, dolo six fifty, calpol')
                                    ->helperText('Add brand names, abbreviations and common speech-recognition variations.'),
                                TagsInput::make('strength_options')
                                    ->label('Strength options')
                                    ->placeholder('500 mg, 650 mg, 200 mg/5 ml'),
                            ]),
                        Grid::make(2)
                            ->schema([
                                TagsInput::make('dosage_options')
                                    ->label('Dosage options')
                                    ->placeholder('1 tablet, 5 ml, 2 drops'),
                                TagsInput::make('application_area_options')
                                    ->label('Application areas')
                                    ->placeholder('Both eyes, Left ear, Affected area')
                                    ->helperText('Use mainly for drops, creams, ointments and topical medicines.'),
                            ]),
                        Grid::make(3)
                            ->schema([
                                CheckboxList::make('frequency_options')
                                    ->label('Allowed frequencies')
                                    ->options(self::FREQUENCIES)
                                    ->columns(2)
                                    ->default(['OD', 'BD', 'TDS', 'SOS']),
                                CheckboxList::make('timing_options')
                                    ->label('Allowed timings')
                                    ->options(self::TIMINGS)
                                    ->columns(2)
                                    ->default(['morning', 'afternoon', 'evening', 'night']),
                                CheckboxList::make('meal_options')
                                    ->label('Meal instructions')
                                    ->options(self::MEALS)
                                    ->columns(1)
                                    ->default(['before_meal', 'after_meal', 'with_meal']),
                            ]),
                        Grid::make(2)
                            ->schema([
                                CheckboxList::make('field_rules')
                                    ->label('Fields shown to doctor')
                                    ->options(self::FIELD_RULES)
                                    ->columns(2)
                                    ->default(['strength', 'dosage', 'frequency', 'timing', 'meal', 'duration', 'remarks']),
                                Toggle::make('speech_enabled')
                                    ->label('Enable speech matching')
                                    ->default(true),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
