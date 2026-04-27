<?php

namespace App\Filament\Imports;

use App\Models\Department;
use App\Models\DepartmentDoctor;
use App\Models\Symptom;
use App\Models\User;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DepartmentImporter extends Importer
{
    protected static ?string $model = Department::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')
                ->label('Department Name')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('description')
                ->label('Description')
                ->rules(['required']),
            ImportColumn::make('status')
                ->rules(['nullable', 'in:active,inactive']),
            ImportColumn::make('is_tab_layout')
                ->label('Tab Layout')
                ->boolean()
                ->rules(['nullable'])
                ->fillRecordUsing(fn () => null), // Handled in beforeSave
            ImportColumn::make('symptom_ids')
                ->label('Symptoms')
                ->rules(['nullable'])
                ->fillRecordUsing(fn () => null), // Handled in beforeSave
            ImportColumn::make('additional_information')
                ->label('Additional Information')
                ->rules(['nullable'])
                ->fillRecordUsing(fn () => null), // Handled in beforeSave
            ImportColumn::make('faqs')
                ->label('FAQs')
                ->rules(['nullable'])
                ->fillRecordUsing(fn () => null), // Handled in beforeSave
            ImportColumn::make('publications')
                ->label('Publications')
                ->rules(['nullable'])
                ->fillRecordUsing(fn () => null), // Handled in beforeSave
            ImportColumn::make('tabs')
                ->label('Tabs')
                ->rules(['nullable'])
                ->fillRecordUsing(fn () => null), // Handled in afterSave
            ImportColumn::make('department_featured')
                ->label('Featured Image')
                ->rules(['nullable']),
            ImportColumn::make('department_stamp')
                ->label('Department Stamp')
                ->rules(['nullable']),
            ImportColumn::make('doctors')
                ->label('Doctors')
                ->rules(['nullable'])
                ->fillRecordUsing(fn () => null), // Handled in afterSave
        ];
    }

    public function resolveRecord(): ?Department
    {
        $name = trim($this->data['name'] ?? $this->data['Department Name'] ?? '');

        if (empty($name)) {
            Log::warning('Import Warning: Empty department name encountered.');
            return null;
        }

        // Search for existing record to support "Override/Update"
        $record = Department::where('name', $name)->first();
        
        if ($record) {
            Log::info("Import: Updating existing department: {$name}");
        } else {
            Log::info("Import: Creating new department: {$name}");
            $record = new Department(['name' => $name]);
        }

        return $record;
    }

    protected function beforeSave(): void
    {
        // Set User for background jobs
        if (empty($this->record->created_by) && auth()->guest() && $this->import->user) {
            $this->record->created_by = $this->import->user->id;
        }
        if ($this->import->user) {
            $this->record->updated_by = $this->import->user->id;
        }

        // Handle is_tab_layout
        $isTabLayout = $this->getColumnValue('is_tab_layout');
        if ($isTabLayout !== null) {
            $val = $isTabLayout;
            $this->record->is_tab_layout = in_array(strtolower((string)$val), ['1', 'yes', 'true', 'on']);
        }

        // Handle symptom mapping
        try {
            $symptomsValue = $this->getColumnValue('symptom_ids');

            if (!empty($symptomsValue)) {
                $symptomNamesOrIds = array_map('trim', explode(',', (string) $symptomsValue));
                $ids = [];
                
                foreach ($symptomNamesOrIds as $item) {
                    if (empty($item)) continue;
                    
                    if (is_numeric($item) && strlen($item) < 5) {
                        $ids[] = $item;
                    } else {
                        $symptom = Symptom::where('name', 'LIKE', $item)->first();
                        if ($symptom) {
                            $ids[] = $symptom->id;
                        }
                    }
                }
                
                $this->record->symptom_ids = array_unique($ids);
            }
        } catch (\Exception $e) {
            Log::warning('Symptom Import Warning: ' . $e->getMessage());
        }

        foreach (['additional_information', 'faqs', 'publications'] as $field) {
            try {
                $value = $this->getColumnValue($field);

                if ($this->hasImportValue($value)) {
                    $this->record->{$field} = match ($field) {
                        'additional_information' => static::parseAdditionalInformation($value),
                        'faqs' => static::parseFaqs($value),
                        'publications' => static::parsePublications($value),
                    };
                }
            } catch (\Exception $e) {
                Log::warning("Structured Field Import Warning ({$field}): " . $e->getMessage());
            }
        }

        // Ensure slug is generated
        if (empty($this->record->slug) && !empty($this->record->name)) {
            $this->record->slug = Str::slug($this->record->name);
        }

        // Handle Images
        if (!empty($this->data['department_featured'])) {
            $this->record->department_featured = $this->data['department_featured'];
        }
        if (!empty($this->data['department_stamp'])) {
            $this->record->department_stamp = $this->data['department_stamp'];
        }
    }

    protected function afterSave(): void
    {
        // Handle Tabs relationship
        $tabsValue = $this->getColumnValue('tabs');
        if ($this->hasImportValue($tabsValue)) {
            try {
                $tabs = static::parseTabs($tabsValue);

                if ($tabs !== []) {
                    $this->record->tabs()->delete();

                    foreach ($tabs as $tabData) {
                        $this->record->tabs()->create([
                            'tab_title' => $tabData['title'] ?? $tabData['tab_title'] ?? 'Untitled Tab',
                            'tab_content' => $tabData['content'] ?? $tabData['tab_content'] ?? '',
                            'order' => (int) ($tabData['order'] ?? 0),
                            'tab_gallery' => $tabData['gallery'] ?? $tabData['tab_gallery'] ?? [],
                            'created_by' => $this->import->user->id ?? null,
                        ]);
                    }
                }
            } catch (\Exception $e) {
                Log::error('Department Import Tab Error for ' . $this->record->name . ': ' . $e->getMessage());
                throw new \Exception("Tabs Error in {$this->record->name}: " . $e->getMessage());
            }
        }

        $doctorsValue = $this->getColumnValue('doctors');
        if ($this->hasImportValue($doctorsValue)) {
            try {
                $decodedDoctors = static::parseDoctors($doctorsValue);

                if ($decodedDoctors !== []) {
                    DepartmentDoctor::where('department_id', $this->record->id)->delete();

                    foreach ($decodedDoctors as $dData) {
                        $email = $dData['email'] ?? $dData['doctor_email'] ?? null;
                        if (!$email) {
                            continue;
                        }

                        $user = User::where('email', $email)->first();
                        if ($user && $user->doctor) {
                            DepartmentDoctor::create([
                                'department_id' => $this->record->id,
                                'doctor_id' => $user->doctor->id,
                                'role' => $dData['role'] ?? null,
                                'order' => (int)($dData['order'] ?? 1),
                                'created_by' => $this->import->user->id ?? null,
                            ]);
                        } else {
                            Log::warning("Import Warning: Doctor with email {$email} not found or active for department {$this->record->name}");
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::error('Department Import Doctor Error for ' . $this->record->name . ': ' . $e->getMessage());
                throw new \Exception("Doctors Mapping Error in {$this->record->name}: " . $e->getMessage());
            }
        }
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your department import has completed and ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $failedNames = $import->failedRows()
                ->get()
                ->map(function ($failedRow) {
                    $data = $failedRow->data ?? [];
                    return $data['name'] ?? $data['Department Name'] ?? $data['department_name'] ?? 'Unknown record';
                })
                ->filter()
                ->unique()
                ->take(5)
                ->join(', ');

            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';

            if ($failedNames) {
                $body .= " (Failed: {$failedNames}" . ($failedRowsCount > 5 ? '...' : '') . ")";
            }

            $body .= ' Please check the failed rows CSV for specific errors.';
        }

        return $body;
    }

    protected function getColumnValue(string $column): mixed
    {
        return $this->data[$column]
            ?? $this->data[Str::headline($column)]
            ?? null;
    }

    protected function hasImportValue(mixed $value): bool
    {
        if (is_array($value)) {
            return $value !== [];
        }

        if ($value === null) {
            return false;
        }

        $stringValue = trim((string) $value);

        return $stringValue !== '' && strtolower($stringValue) !== 'null' && $stringValue !== '[]';
    }

    protected static function parseAdditionalInformation(mixed $value): array
    {
        $decoded = static::decodeJsonArray($value);

        if ($decoded !== null) {
            return collect($decoded)
                ->map(fn ($item) => ['content' => trim((string) Arr::get($item, 'content', ''))])
                ->filter(fn (array $item) => $item['content'] !== '')
                ->values()
                ->all();
        }

        return static::splitItems($value)
            ->map(fn (string $item) => ['content' => $item])
            ->values()
            ->all();
    }

    protected static function parseFaqs(mixed $value): array
    {
        $decoded = static::decodeJsonArray($value);

        if ($decoded !== null) {
            return collect($decoded)
                ->map(function ($item): array {
                    return [
                        'question' => trim((string) Arr::get($item, 'question', '')),
                        'answer' => trim((string) Arr::get($item, 'answer', '')),
                    ];
                })
                ->filter(fn (array $item) => $item['question'] !== '' || $item['answer'] !== '')
                ->values()
                ->all();
        }

        return static::splitItems($value)
            ->map(function (string $item): array {
                [$question, $answer] = array_pad(explode(':', $item, 2), 2, '');

                return [
                    'question' => trim($question),
                    'answer' => trim($answer),
                ];
            })
            ->filter(fn (array $item) => $item['question'] !== '' || $item['answer'] !== '')
            ->values()
            ->all();
    }

    protected static function parsePublications(mixed $value): array
    {
        $decoded = static::decodeJsonArray($value);

        if ($decoded !== null) {
            return collect($decoded)
                ->map(function ($item): array {
                    return [
                        'publication_name' => trim((string) Arr::get($item, 'publication_name', '')),
                        'publication_date' => trim((string) Arr::get($item, 'publication_date', '')),
                        'publication_description' => trim((string) Arr::get($item, 'publication_description', '')),
                    ];
                })
                ->filter(fn (array $item) => implode('', $item) !== '')
                ->values()
                ->all();
        }

        return static::splitItems($value)
            ->map(function (string $item): array {
                $name = $item;
                $date = '';
                $description = '';

                if (preg_match('/^(.*?)\s*\((\d{4}-\d{2}-\d{2})\)\s*:\s*(.*)$/', $item, $matches)) {
                    $name = trim($matches[1]);
                    $date = trim($matches[2]);
                    $description = trim($matches[3]);
                } elseif (preg_match('/^(.*?)\s*:\s*(.*)$/', $item, $matches)) {
                    $name = trim($matches[1]);
                    $description = trim($matches[2]);
                }

                return [
                    'publication_name' => $name,
                    'publication_date' => $date,
                    'publication_description' => $description,
                ];
            })
            ->filter(fn (array $item) => implode('', $item) !== '')
            ->values()
            ->all();
    }

    protected static function parseTabs(mixed $value): array
    {
        $decoded = static::decodeJsonArray($value);

        if ($decoded !== null) {
            return collect($decoded)
                ->map(function ($item): array {
                    return [
                        'title' => trim((string) Arr::get($item, 'title', Arr::get($item, 'tab_title', 'Untitled Tab'))),
                        'content' => trim((string) Arr::get($item, 'content', Arr::get($item, 'tab_content', ''))),
                        'order' => (int) Arr::get($item, 'order', 0),
                        'gallery' => array_values(array_filter((array) Arr::get($item, 'gallery', Arr::get($item, 'tab_gallery', [])))),
                    ];
                })
                ->filter(fn (array $item) => $item['title'] !== '' || $item['content'] !== '' || $item['gallery'] !== [])
                ->values()
                ->all();
        }

        return static::splitItems($value)
            ->map(function (string $item, int $index): array {
                $order = $index + 1;
                $gallery = [];

                if (preg_match('/\s*\[order:(\d+)\]\s*/i', $item, $matches)) {
                    $order = (int) $matches[1];
                    $item = preg_replace('/\s*\[order:\d+\]\s*/i', ' ', $item) ?? $item;
                }

                if (preg_match('/\s*\[gallery:([^\]]+)\]\s*/i', $item, $matches)) {
                    $gallery = collect(explode(',', $matches[1]))
                        ->map(fn (string $path) => trim($path))
                        ->filter()
                        ->values()
                        ->all();
                    $item = preg_replace('/\s*\[gallery:[^\]]+\]\s*/i', ' ', $item) ?? $item;
                }

                [$title, $content] = array_pad(explode(':', trim($item), 2), 2, '');

                return [
                    'title' => trim($title) !== '' ? trim($title) : 'Untitled Tab',
                    'content' => trim($content),
                    'order' => $order,
                    'gallery' => $gallery,
                ];
            })
            ->filter(fn (array $item) => $item['title'] !== '' || $item['content'] !== '' || $item['gallery'] !== [])
            ->values()
            ->all();
    }

    protected static function parseDoctors(mixed $value): array
    {
        $decoded = static::decodeJsonArray($value);

        if ($decoded !== null) {
            return collect($decoded)
                ->map(function ($item): array {
                    return [
                        'email' => trim((string) Arr::get($item, 'email', Arr::get($item, 'doctor_email', ''))),
                        'role' => trim((string) Arr::get($item, 'role', '')),
                        'order' => (int) Arr::get($item, 'order', 1),
                    ];
                })
                ->filter(fn (array $item) => $item['email'] !== '')
                ->values()
                ->all();
        }

        return static::splitItems($value)
            ->map(function (string $item, int $index): array {
                $email = trim($item);
                $role = '';
                $order = $index + 1;

                if (preg_match('/^(.*?)\s*\((.*)\)$/', $item, $matches)) {
                    $email = trim($matches[1]);
                    $meta = trim($matches[2]);

                    foreach (explode(',', $meta) as $part) {
                        $part = trim($part);

                        if (str_starts_with(strtolower($part), 'role:')) {
                            $role = trim(substr($part, 5));
                        } elseif (str_starts_with(strtolower($part), 'order:')) {
                            $order = (int) trim(substr($part, 6));
                        } elseif ($role === '') {
                            $role = $part;
                        }
                    }
                }

                return [
                    'email' => $email,
                    'role' => $role,
                    'order' => $order > 0 ? $order : ($index + 1),
                ];
            })
            ->filter(fn (array $item) => $item['email'] !== '')
            ->values()
            ->all();
    }

    protected static function decodeJsonArray(mixed $value): ?array
    {
        if (is_array($value)) {
            return $value;
        }

        $stringValue = trim((string) $value);

        if ($stringValue === '' || !str_starts_with($stringValue, '[')) {
            return null;
        }

        $decoded = json_decode($stringValue, true);

        return json_last_error() === JSON_ERROR_NONE && is_array($decoded)
            ? $decoded
            : null;
    }

    protected static function splitItems(mixed $value): Collection
    {
        return collect(preg_split('/\s*\|\s*/', trim((string) $value)) ?: [])
            ->map(fn (string $item) => trim($item))
            ->filter();
    }
}
