<?php

namespace App\Services;

use App\Models\Doctor;
use App\Models\DoctorAvailability;
use App\Models\ExternalBooking;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ExternalBookingSyncService
{
    private int $created = 0;

    private int $updated = 0;

    private int $skipped = 0;

    private int $unchanged = 0;

    /** @var array<int, string> */
    private array $errors = [];

    /** @var array<int, array<string, mixed>> */
    private array $createdSheetRows = [];

    /** @var array<int, array<string, mixed>> */
    private array $syncedSheetRows = [];

    /** @var array<string, array<int, string>> */
    private array $importedSourceRowIdsByDoctor = [];

    public function syncRows(
        iterable $rows,
        ?string $defaultDoctorId = null,
        ?string $batchId = null,
        bool $syncExisting = true,
        string $source = 'manual_sheet',
        bool $preferProvidedSourceId = false,
        bool $updateExisting = true,
    ): array {
        $this->reset();

        foreach ($rows as $index => $row) {
            $data = is_array($row) ? $row : (array) $row;
            $rowNumber = (int) ($data['__sheet_row_number'] ?? (is_int($index) ? $index + 2 : $index));

            if ($this->isBlankRow($data)) {
                continue;
            }

            try {
                $doctor = $this->resolveDoctor($data, $defaultDoctorId);
                $appointmentDate = $this->parseDate($this->value($data, 'appointment_date'));
                $startTime = $this->parseTime($this->value($data, 'time_slot'));

                if (! $doctor || ! $appointmentDate || ! $startTime) {
                    $this->skipped++;
                    $this->errors[] = "Row {$rowNumber}: missing doctor, appointment date, or time slot.";

                    continue;
                }

                [$availabilityId, $overrideId] = $this->resolveAvailability(
                    $doctor->id,
                    $appointmentDate,
                    $startTime,
                );

                $sourceRowId = $this->sourceRowId($data, $doctor->id, $appointmentDate, $startTime, $preferProvidedSourceId);
                $this->importedSourceRowIdsByDoctor[$doctor->id] ??= [];
                $this->importedSourceRowIdsByDoctor[$doctor->id][] = $sourceRowId;

                $payload = [
                    'doctor_id' => $doctor->id,
                    'availability_id' => $availabilityId,
                    'availability_override_id' => $overrideId,
                    'source' => $source,
                    'import_batch_id' => $batchId,
                    'source_row_id' => $sourceRowId,
                    'source_doctor_id' => $this->value($data, 'doctor_id'),
                    'doctor_name' => $this->value($data, 'doctor_name'),
                    'patient_name' => $this->value($data, 'patient_name'),
                    'patient_unit_number' => $this->value($data, 'patient_unit_number'),
                    'patient_email' => $this->value($data, 'patient_email'),
                    'mobile' => $this->value($data, 'mobile'),
                    'appointment_date' => $appointmentDate,
                    'start_time' => $startTime,
                    'end_time' => $this->parseOptionalTime($this->value($data, 'end_time')),
                    'consultation_type' => $this->value($data, 'consultation_type') ?: 'in-person',
                    'opd_type' => $this->value($data, 'opd_type') ?: 'private',
                    'track_upload_status' => $this->value($data, 'track_upload_status') ?: 'Uploaded',
                    'stack_upload_status' => $this->value($data, 'stack_upload_status') ?: 'Pending',
                    'source_created_at' => $this->parseDateTime($this->value($data, 'created_at')),
                    'raw_payload' => $this->payloadData($data),
                    'updated_by' => Auth::id(),
                ];

                $booking = $this->findExistingBooking($doctor->id, $source, $sourceRowId, $data, $appointmentDate, $startTime)
                    ?? new ExternalBooking;

                if ($booking->exists && ! $updateExisting) {
                    $this->unchanged++;

                    if ($source === 'google_sheet') {
                        $this->syncedSheetRows[] = $data;
                    }

                    continue;
                }

                $wasCreated = ! $booking->exists;

                if (! $booking->exists) {
                    $payload['id'] = (string) Str::uuid();
                    $payload['created_by'] = Auth::id();
                    $this->created++;
                } else {
                    $this->updated++;
                }

                if ($booking->trashed()) {
                    $booking->restore();
                }

                $booking->fill($payload)->save();

                if ($wasCreated && $source === 'google_sheet') {
                    $this->createdSheetRows[] = $data;
                }

                if ($source === 'google_sheet') {
                    $this->syncedSheetRows[] = $data;
                }
            } catch (\Throwable $exception) {
                $this->skipped++;
                $this->errors[] = "Row {$rowNumber}: {$exception->getMessage()}";
            }
        }

        $this->removeRowsMissingFromCurrentImport($source, $syncExisting);

        return $this->results();
    }

    public function results(): array
    {
        return [
            'created' => $this->created,
            'updated' => $this->updated,
            'skipped' => $this->skipped,
            'unchanged' => $this->unchanged,
            'errors' => $this->errors,
            'created_sheet_rows' => $this->createdSheetRows,
            'synced_sheet_rows' => $this->syncedSheetRows,
        ];
    }

    private function reset(): void
    {
        $this->created = 0;
        $this->updated = 0;
        $this->skipped = 0;
        $this->unchanged = 0;
        $this->errors = [];
        $this->createdSheetRows = [];
        $this->syncedSheetRows = [];
        $this->importedSourceRowIdsByDoctor = [];
    }

    private function resolveDoctor(array $data, ?string $defaultDoctorId): ?Doctor
    {
        if ($defaultDoctorId) {
            return Doctor::find($defaultDoctorId);
        }

        $sheetDoctorId = $this->value($data, 'doctor_id');
        if ($sheetDoctorId) {
            $doctor = Doctor::find((string) $sheetDoctorId);
            if ($doctor) {
                return $doctor;
            }

            $doctor = Doctor::where('google_sheet_doctor_id', (string) $sheetDoctorId)->first();
            if ($doctor) {
                return $doctor;
            }
        }

        $doctorName = trim((string) $this->value($data, 'doctor_name'));
        if ($doctorName === '') {
            return null;
        }

        $normalized = Str::of($doctorName)
            ->lower()
            ->replace('dr.', '')
            ->replace('dr ', '')
            ->squish()
            ->toString();

        return Doctor::query()
            ->get()
            ->first(function (Doctor $doctor) use ($normalized) {
                $name = Str::of(trim($doctor->first_name.' '.$doctor->last_name))
                    ->lower()
                    ->squish()
                    ->toString();

                return $name === $normalized;
            });
    }

    private function resolveAvailability(string $doctorId, string $date, string $startTime): array
    {
        $dayOfWeek = strtolower(Carbon::parse($date)->format('l'));

        $availability = DoctorAvailability::query()
            ->where('doctor_id', $doctorId)
            ->where('is_available', true)
            ->where('consultation_type', 'in-person')
            ->where('opd_type', 'private')
            ->whereTime('start_time', $startTime)
            ->where(function ($query) use ($date, $dayOfWeek) {
                $query->whereDate('date', $date)
                    ->orWhere(function ($query) use ($date, $dayOfWeek) {
                        $query->where('is_recurring', true)
                            ->where(function ($query) use ($dayOfWeek) {
                                $query->where('day_of_week', $dayOfWeek)
                                    ->orWhere('day_of_week', ucfirst($dayOfWeek));
                            })
                            ->where(function ($query) use ($date) {
                                $query->whereNull('recurring_start_date')
                                    ->orWhereDate('recurring_start_date', '<=', $date);
                            })
                            ->where(function ($query) use ($date) {
                                $query->whereNull('recurring_end_date')
                                    ->orWhereDate('recurring_end_date', '>=', $date);
                            });
                    });
            })
            ->first();

        if (! $availability) {
            return [null, null];
        }

        $override = $availability->overrides()
            ->whereDate('override_date', $date)
            ->whereNotIn('status', ['blocked', 'cancelled'])
            ->first();

        return [
            $availability->id,
            $override?->id,
        ];
    }

    private function value(array $data, string $key): mixed
    {
        return $data[$key] ?? $data[Str::of($key)->replace('_', ' ')->toString()] ?? null;
    }

    private function sourceRowId(
        array $data,
        string $doctorId,
        string $appointmentDate,
        string $startTime,
        bool $preferProvidedSourceId
    ): string {
        $providedId = $this->value($data, 'id');

        if ($preferProvidedSourceId && $providedId !== null && $providedId !== '') {
            return (string) $providedId;
        }

        return hash('sha256', implode('|', [
            $doctorId,
            $appointmentDate,
            $startTime,
            (string) $this->value($data, 'patient_unit_number'),
            (string) $this->value($data, 'mobile'),
            Str::of((string) $this->value($data, 'patient_name'))->lower()->squish()->toString(),
        ]));
    }

    private function findExistingBooking(
        string $doctorId,
        string $source,
        string $sourceRowId,
        array $data,
        string $appointmentDate,
        string $startTime
    ): ?ExternalBooking {
        $patientUnitNumber = (string) $this->value($data, 'patient_unit_number');
        $mobile = (string) $this->value($data, 'mobile');
        $legacySheetId = $this->value($data, 'id');

        return ExternalBooking::query()
            ->withTrashed()
            ->where('doctor_id', $doctorId)
            ->where('source', $source)
            ->where(function ($query) use ($sourceRowId, $legacySheetId, $appointmentDate, $startTime, $patientUnitNumber, $mobile) {
                $query->where('source_row_id', $sourceRowId);

                if ($legacySheetId !== null && $legacySheetId !== '') {
                    $query->orWhere('source_row_id', (string) $legacySheetId);
                }

                if ($patientUnitNumber !== '') {
                    $query->orWhere(function ($query) use ($appointmentDate, $startTime, $patientUnitNumber) {
                        $query->whereDate('appointment_date', $appointmentDate)
                            ->whereTime('start_time', $startTime)
                            ->where('patient_unit_number', $patientUnitNumber);
                    });
                }

                if ($mobile !== '') {
                    $query->orWhere(function ($query) use ($appointmentDate, $startTime, $mobile) {
                        $query->whereDate('appointment_date', $appointmentDate)
                            ->whereTime('start_time', $startTime)
                            ->where('mobile', $mobile);
                    });
                }
            })
            ->first();
    }

    private function removeRowsMissingFromCurrentImport(string $source, bool $syncExisting): void
    {
        if (! $syncExisting) {
            return;
        }

        foreach ($this->importedSourceRowIdsByDoctor as $doctorId => $sourceRowIds) {
            ExternalBooking::query()
                ->where('doctor_id', $doctorId)
                ->where('source', $source)
                ->whereNotIn('source_row_id', array_values(array_unique($sourceRowIds)))
                ->delete();
        }
    }

    private function parseDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value))->toDateString();
        }

        return Carbon::parse($value)->toDateString();
    }

    private function parseTime(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value))->format('H:i:s');
        }

        return Carbon::parse($value)->format('H:i:s');
    }

    private function parseOptionalTime(mixed $value): ?string
    {
        return $this->parseTime($value);
    }

    private function parseDateTime(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value));
        }

        return Carbon::parse($value);
    }

    private function isBlankRow(array $data): bool
    {
        foreach ($this->payloadData($data) as $value) {
            if ($value !== null && trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function payloadData(array $data): array
    {
        return collect($data)
            ->reject(fn ($value, string $key) => str_starts_with($key, '__sheet_'))
            ->all();
    }
}
