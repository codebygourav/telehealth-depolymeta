<?php

namespace App\Filament\Resources\VaccinationGeneralFaqs;

use App\Filament\Concerns\ConfiguresSlideOverSections;
use App\Filament\Resources\VaccinationGeneralFaqs\Pages\ListVaccinationGeneralFaqs;
use App\Models\VaccinationGeneralFaq;
use App\Traits\HasCustomSidebar;
use App\Traits\HasResourcePermissions;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VaccinationGeneralFaqResource extends Resource
{
    use ConfiguresSlideOverSections;
    use HasCustomSidebar;
    use HasResourcePermissions;

    protected static ?string $model = VaccinationGeneralFaq::class;

    protected static ?string $navigationLabel = 'Vaccination FAQs';

    protected static ?string $slug = 'vaccination-general-faqs';

    public static function getSidebarOptions(): array
    {
        return [
            'label' => 'Vaccination Screen FAQs',
            'icon' => 'heroicon-o-question-mark-circle',
            'sort' => 10,
            'group' => 'Vaccination',
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();

        return check_permission(['vaccinations.view_any', 'vaccinations.view', 'vaccinations.manage_own'])
            || $user?->hasAnyRole(['super_admin', 'admin', 'doctor_manager', 'receptionist', 'doctor']);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components(static::wrapSlideOverForm([
            static::slideOverSection('FAQ', [
                TextInput::make('question')
                    ->required()
                    ->maxLength(255),
                Textarea::make('answer')
                    ->required()
                    ->rows(4),
                TextInput::make('sort_order')
                    ->numeric()
                    ->minValue(0)
                    ->default(0),
                Toggle::make('is_active')
                    ->onColor('success')
                    ->offColor('danger')
                    ->default(true),
            ], 'Shown on the patient vaccination overview screen.', icon: 'heroicon-o-question-mark-circle'),
        ]));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('question')->searchable()->limit(60),
                TextColumn::make('sort_order')->sortable(),
                IconColumn::make('is_active')->boolean(),
            ])
            ->defaultSort('sort_order')
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()->slideOver(),
                    DeleteAction::make(),
                ]),
            ])
            ->recordUrl(null);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVaccinationGeneralFaqs::route('/'),
        ];
    }
}
