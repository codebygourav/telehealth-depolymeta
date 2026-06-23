<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class MedicineTemplateItem extends Model
{
    use HasFactory;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'medicine_template_id',
        'medicine_id',
        'medicine_name',
        'medicine_type',
        'dosage',
        'doses_per_day',
        'first_dose_time',
        'dose_interval_hours',
        'frequency',
        'frequency_times',
        'meal_timing',
        'duration_type',
        'duration_value',
        'instructions',
        'sort_order',
        'use_type',
        'take_when',
        'min_gap',
        'max_doses_per_day',
        'patient_instruction',
    ];

    protected $casts = [
        'frequency_times' => 'array',
        'doses_per_day' => 'integer',
        'dose_interval_hours' => 'integer',
        'duration_value' => 'integer',
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (MedicineTemplateItem $item) {
            if (! $item->getKey()) {
                $item->{$item->getKeyName()} = (string) Str::uuid();
            }

            $item->sort_order ??= 0;
            $item->duration_type ??= 'days';
            $item->use_type ??= 'regular';

            if ($item->use_type === 'sos') {
                $item->frequency = 'SOS';
                $item->frequency_times = null;
                $item->doses_per_day = 0;
                $item->dose_interval_hours = 0;
                $item->first_dose_time = null;
            } else {
                $item->doses_per_day ??= static::dosesFromFrequency($item->frequency);
                $item->dose_interval_hours ??= static::defaultDoseInterval($item->doses_per_day);
                $item->first_dose_time ??= '08:00';

                if (empty($item->frequency_times)) {
                    $item->frequency_times = static::autoTimings(
                        (int) $item->doses_per_day,
                        (string) $item->first_dose_time,
                        (int) $item->dose_interval_hours
                    );
                }

                $item->frequency = static::frequencyFromDoses((int) $item->doses_per_day);
            }
        });

        static::saving(function (MedicineTemplateItem $item) {
            $item->use_type ??= 'regular';

            if ($item->use_type === 'sos') {
                $item->frequency = 'SOS';
                $item->frequency_times = null;
                $item->doses_per_day = 0;
                $item->dose_interval_hours = 0;
                $item->first_dose_time = null;
            } else {
                $item->doses_per_day ??= static::dosesFromFrequency($item->frequency);
                $item->dose_interval_hours ??= static::defaultDoseInterval((int) $item->doses_per_day);
                $item->first_dose_time ??= '08:00';
                $item->frequency = static::frequencyFromDoses((int) $item->doses_per_day);
                $item->frequency_times = static::autoTimings(
                    (int) $item->doses_per_day,
                    (string) $item->first_dose_time,
                    (int) $item->dose_interval_hours
                );
            }
        });
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(MedicineTemplate::class, 'medicine_template_id');
    }

    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class);
    }

    public static function autoTimings(int $dosesPerDay, string $firstDoseTime = '08:00', int $intervalHours = 8): array
    {
        $dosesPerDay = max(1, min(6, $dosesPerDay));
        $intervalHours = max(1, min(24, $intervalHours));
        [$hour, $minute] = array_pad(explode(':', $firstDoseTime), 2, '00');
        $baseMinutes = (((int) $hour % 24) * 60) + ((int) $minute);

        return collect(range(0, $dosesPerDay - 1))
            ->map(function (int $index) use ($baseMinutes, $intervalHours): string {
                $minutes = ($baseMinutes + ($index * $intervalHours * 60)) % (24 * 60);

                return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
            })
            ->all();
    }

    public static function frequencyFromDoses(int $dosesPerDay): string
    {
        return match ($dosesPerDay) {
            1 => 'OD',
            2 => 'BD',
            3 => 'TDS',
            default => $dosesPerDay . '_TIMES',
        };
    }

    public static function dosesFromFrequency(?string $frequency): int
    {
        return match ($frequency) {
            'OD' => 1,
            'BD' => 2,
            'TDS' => 3,
            default => 1,
        };
    }

    public static function defaultDoseInterval(int $dosesPerDay): int
    {
        return match ($dosesPerDay) {
            1 => 24,
            2 => 12,
            3 => 8,
            4 => 6,
            5 => 4,
            default => 4,
        };
    }
}
