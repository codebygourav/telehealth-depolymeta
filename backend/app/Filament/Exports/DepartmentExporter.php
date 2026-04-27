<?php

namespace App\Filament\Exports;

use App\Models\Department;
use Illuminate\Support\Collection;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

class DepartmentExporter extends Exporter
{
    protected static ?string $model = Department::class;

    public function getFormats(): array
    {
        return [
            ExportFormat::Csv,
        ];
    }

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('name')
                ->label('Department Name'),
            ExportColumn::make('status')
                ->label('Status'),
            ExportColumn::make('description')
                ->label('Description'),
            ExportColumn::make('is_tab_layout')
                ->label('Tab Layout')
                ->state(fn ($record) => $record->is_tab_layout ? 'Yes' : 'No'),
            ExportColumn::make('symptom_ids')
                ->label('Symptoms')
                ->state(fn ($record) => \App\Models\Symptom::whereIn('id', (array)($record->symptom_ids ?? []))->pluck('name')->join(', ')),

            ExportColumn::make('department_featured')
                ->label('Featured Image')
                ->state(fn ($record) => $record->department_featured),
            ExportColumn::make('department_stamp')
                ->label('Department Stamp')
                ->state(fn ($record) => $record->department_stamp),
            ExportColumn::make('additional_information')
                ->label('Additional Information')
                ->state(fn ($record) => static::formatAdditionalInformation($record->additional_information)),
            ExportColumn::make('faqs')
                ->label('FAQs')
                ->state(fn ($record) => static::formatFaqs($record->faqs)),
            ExportColumn::make('publications')
                ->label('Publications')
                ->state(fn ($record) => static::formatPublications($record->publications)),
            ExportColumn::make('tabs')
                ->label('Tabs')
                ->state(fn ($record) => static::formatTabs($record->tabs)),
            ExportColumn::make('doctors')
                ->label('Doctors')
                ->state(fn ($record) => static::formatDoctors($record->doctors)),
        ];
    }

    protected static function formatAdditionalInformation(?array $sections): ?string
    {
        return static::normalizeItems($sections)
            ->map(fn (array $section) => trim(strip_tags((string) ($section['content'] ?? ''))))
            ->filter()
            ->join(' | ') ?: null;
    }

    protected static function formatFaqs(?array $faqs): ?string
    {
        return static::normalizeItems($faqs)
            ->map(function (array $faq): ?string {
                $question = trim((string) ($faq['question'] ?? ''));
                $answer = trim(strip_tags((string) ($faq['answer'] ?? '')));

                if ($question === '' && $answer === '') {
                    return null;
                }

                if ($question === '') {
                    return $answer;
                }

                return $question . ': ' . $answer;
            })
            ->filter()
            ->join(' | ') ?: null;
    }

    protected static function formatPublications(?array $publications): ?string
    {
        return static::normalizeItems($publications)
            ->map(function (array $publication): ?string {
                $title = trim((string) ($publication['publication_name'] ?? ''));
                $date = trim((string) ($publication['publication_date'] ?? ''));
                $summary = trim(strip_tags((string) ($publication['publication_description'] ?? '')));

                if ($title === '' && $date === '' && $summary === '') {
                    return null;
                }

                $label = $title;

                if ($date !== '') {
                    $label .= ($label !== '' ? ' (' . $date . ')' : $date);
                }

                if ($summary !== '') {
                    $label .= ($label !== '' ? ': ' : '') . $summary;
                }

                return $label !== '' ? $label : null;
            })
            ->filter()
            ->join(' | ') ?: null;
    }

    protected static function formatTabs(Collection $tabs): ?string
    {
        return $tabs
            ->map(function ($tab): ?string {
                $parts = array_filter([
                    trim((string) $tab->tab_title),
                    trim(strip_tags((string) $tab->tab_content)),
                ]);

                $label = implode(': ', $parts);

                if ($tab->order !== null) {
                    $label .= ($label !== '' ? ' ' : '') . '[order:' . $tab->order . ']';
                }

                $gallery = collect($tab->tab_gallery ?? [])
                    ->filter()
                    ->join(', ');

                if ($gallery !== '') {
                    $label .= ($label !== '' ? ' ' : '') . '[gallery:' . $gallery . ']';
                }

                return $label !== '' ? $label : null;
            })
            ->filter()
            ->join(' | ') ?: null;
    }

    protected static function formatDoctors(Collection $doctors): ?string
    {
        return $doctors
            ->map(function ($doctor): ?string {
                $email = trim((string) ($doctor->user?->email ?? ''));

                if ($email === '') {
                    return null;
                }

                $meta = array_filter([
                    $doctor->pivot?->role ? 'role: ' . $doctor->pivot->role : null,
                    $doctor->pivot?->order !== null ? 'order: ' . $doctor->pivot->order : null,
                ]);

                return $meta !== []
                    ? $email . ' (' . implode(', ', $meta) . ')'
                    : $email;
            })
            ->filter()
            ->join(' | ') ?: null;
    }

    protected static function normalizeItems(mixed $items): Collection
    {
        return collect(is_array($items) ? $items : []);
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your department export has completed and ' . Number::format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
