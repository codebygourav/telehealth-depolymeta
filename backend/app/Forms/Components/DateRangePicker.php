<?php

namespace App\Forms\Components;

use Filament\Forms\Components\Field;
use Carbon\Carbon;

class DateRangePicker extends Field
{
    protected string $view = 'filament.forms.components.date-range-picker';

    protected ?string $startDateField = null;
    protected ?string $endDateField = null;
    protected ?Carbon $minDate = null;
    protected ?Carbon $maxDate = null;

    protected function setUp(): void
    {
        parent::setUp();

        // Default structure
        $this->default(function () {
            return [
                'start_date' => null,
                'end_date' => null,
            ];
        });

        // Don't dehydrate if using separate fields
        if (!$this->startDateField || !$this->endDateField) {
            // Convert value to array before saving
            $this->dehydrateStateUsing(function ($state) {
                if (is_array($state)) {
                    return [
                        'start_date' => $state['start_date'] ?? null,
                        'end_date'   => $state['end_date'] ?? null,
                    ];
                }
                return [
                    'start_date' => null,
                    'end_date' => null,
                ];
            });
        } else {
            // If using separate fields, we still need to dehydrate the date_range state
            // so it can be used as a fallback, but we'll sync to separate fields
            $this->dehydrateStateUsing(function ($state) {
                // Ensure we return an array format that can be easily extracted
                if (is_array($state)) {
                    return [
                        'start_date' => $state['start_date'] ?? null,
                        'end_date' => $state['end_date'] ?? null,
                    ];
                }
                // If state is a string (JSON), try to decode it
                if (is_string($state)) {
                    $decoded = json_decode($state, true);
                    if (is_array($decoded)) {
                        return [
                            'start_date' => $decoded['start_date'] ?? null,
                            'end_date' => $decoded['end_date'] ?? null,
                        ];
                    }
                }
                return [
                    'start_date' => null,
                    'end_date' => null,
                ];
            });

            // Sync values to separate fields when state changes
            $this->afterStateUpdated(function ($state, callable $set) {
                if (is_array($state)) {
                    $startDate = $state['start_date'] ?? null;
                    $endDate = $state['end_date'] ?? null;

                    if ($this->startDateField && $startDate) {
                        $set($this->startDateField, $startDate);
                    }
                    if ($this->endDateField && $endDate) {
                        $set($this->endDateField, $endDate);
                    }
                }
            });
        }
    }

    public function startDateField(string $field): static
    {
        $this->startDateField = $field;
        return $this;
    }

    public function endDateField(string $field): static
    {
        $this->endDateField = $field;
        return $this;
    }

    public function minDate(?Carbon $date): static
    {
        $this->minDate = $date;
        return $this;
    }

    public function maxDate(?Carbon $date): static
    {
        $this->maxDate = $date;
        return $this;
    }

    public function getStartDateField(): ?string
    {
        return $this->startDateField;
    }

    public function getEndDateField(): ?string
    {
        return $this->endDateField;
    }

    public function getMinDate(): ?Carbon
    {
        return $this->minDate;
    }

    public function getMaxDate(): ?Carbon
    {
        return $this->maxDate;
    }
}
