<?php

namespace App\Filament\Resources\VaccinationDocuments\Tables;

use App\Filament\Resources\VaccinationDocuments\VaccinationDocumentResource;
use App\Models\VaccinationDocument;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class VaccinationDocumentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->extraAttributes([
                'class' => 'vaccination-documents-grid-table',
            ])
            ->columns([
                Stack::make([
                    ViewColumn::make('vaccination_document_card')
                        ->view('filament.vaccination-documents.table.card-view')
                        ->label(''),
                ])->extraAttributes(['class' => 'items-center!']),
            ])
            ->contentGrid([
                'sm' => 1,
                'md' => 2,
                'lg' => 3,
                'xl' => 4,
            ])
            ->filters([
                SelectFilter::make('document_type')
                    ->label('Document Type')
                    ->options(fn () => collect(\App\Enums\VaccinationDocumentType::cases())
                        ->mapWithKeys(fn ($type) => [$type->value => $type->label()])
                        ->all()),
            ])
            ->recordUrl(fn (VaccinationDocument $record): string => VaccinationDocumentResource::getUrl('view', ['record' => $record]));
    }
}
