<?php

namespace App\Imports;

use App\Services\ExternalBookingSyncService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ExternalBookingsImport implements ToCollection, WithHeadingRow
{
    private array $results = [
        'created' => 0,
        'updated' => 0,
        'skipped' => 0,
        'unchanged' => 0,
        'errors' => [],
        'created_sheet_rows' => [],
        'synced_sheet_rows' => [],
    ];

    public function __construct(
        private readonly ?string $defaultDoctorId = null,
        private readonly ?string $batchId = null,
        private readonly bool $syncExisting = true,
    ) {}

    public function collection(Collection $rows): void
    {
        $this->results = app(ExternalBookingSyncService::class)->syncRows(
            rows: $rows->map(fn ($row) => $row->toArray()),
            defaultDoctorId: $this->defaultDoctorId,
            batchId: $this->batchId,
            syncExisting: $this->syncExisting,
            source: 'manual_sheet',
            preferProvidedSourceId: false,
        );
    }

    public function results(): array
    {
        return $this->results;
    }
}
