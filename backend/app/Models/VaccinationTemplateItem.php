<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class VaccinationTemplateItem extends Model
{
    use HasFactory;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'vaccination_template_id',
        'vaccination_id',
        'set_name',
        'set_description',
        'set_sort_order',
        'dose_no',
        'depends_on_previous_dose',
        'timing_type',
        'interval_days',
        'interval_months',
        'interval_value',
        'interval_unit',
        'doctor_manual_date',
        'minimum_age_days',
        'maximum_age_days',
        'recommended_age_label',
        'due_after_days',
        'due_after_months',
        'offset_value',
        'offset_unit',
        'sort_order',
        'grace_period_before_days',
        'grace_period_after_days',
    ];

    protected $casts = [
        'set_sort_order' => 'integer',
        'dose_no' => 'integer',
        'depends_on_previous_dose' => 'boolean',
        'interval_days' => 'integer',
        'interval_months' => 'integer',
        'interval_value' => 'integer',
        'doctor_manual_date' => 'boolean',
        'minimum_age_days' => 'integer',
        'maximum_age_days' => 'integer',
        'due_after_days' => 'integer',
        'due_after_months' => 'integer',
        'offset_value' => 'integer',
        'sort_order' => 'integer',
        'grace_period_before_days' => 'integer',
        'grace_period_after_days' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (VaccinationTemplateItem $item) {
            if (! $item->getKey()) {
                $item->{$item->getKeyName()} = (string) Str::uuid();
            }

            $item->set_sort_order ??= 0;
            $item->dose_no ??= 1;
            $item->depends_on_previous_dose ??= false;
            $item->timing_type ??= $item->depends_on_previous_dose ? 'previous_dose' : 'base_date';
            $item->interval_days ??= 0;
            $item->interval_months ??= 0;
            $item->interval_value ??= $item->interval_months ?: $item->interval_days;
            $item->interval_unit ??= $item->interval_months ? 'months' : 'days';
            $item->doctor_manual_date ??= $item->timing_type === 'doctor_manual_date';
            $item->due_after_days ??= 0;
            $item->due_after_months ??= 0;
            $item->offset_value ??= $item->due_after_months ?: $item->due_after_days;
            $item->offset_unit ??= $item->due_after_months ? 'months' : 'days';
            $item->sort_order ??= 0;
            $item->grace_period_before_days ??= 0;
            $item->grace_period_after_days ??= 0;
        });
    }

    public function effectiveTimingType(): string
    {
        if ($this->doctor_manual_date || $this->timing_type === 'doctor_manual_date') {
            return 'doctor_manual_date';
        }

        if ($this->timing_type) {
            return $this->timing_type;
        }

        return $this->depends_on_previous_dose ? 'previous_dose' : 'base_date';
    }

    public function effectiveOffsetValue(): int
    {
        if (($this->offset_value ?? 0) > 0 || (($this->due_after_months ?? 0) === 0 && ($this->due_after_days ?? 0) === 0)) {
            return (int) ($this->offset_value ?? 0);
        }

        return (int) (($this->due_after_months ?: $this->due_after_days) ?? 0);
    }

    public function effectiveOffsetUnit(): string
    {
        if (($this->offset_value ?? 0) > 0 && $this->offset_unit) {
            return (string) $this->offset_unit;
        }

        if (($this->due_after_months ?? 0) > 0) {
            return $this->isPregnancyProgram() ? 'weeks' : 'months';
        }

        return 'days';
    }

    public function effectiveIntervalValue(): int
    {
        if (($this->interval_value ?? 0) > 0 || (($this->interval_months ?? 0) === 0 && ($this->interval_days ?? 0) === 0)) {
            return (int) ($this->interval_value ?? 0);
        }

        return (int) (($this->interval_months ?: $this->interval_days) ?? 0);
    }

    public function effectiveIntervalUnit(): string
    {
        if (($this->interval_value ?? 0) > 0 && $this->interval_unit) {
            return (string) $this->interval_unit;
        }

        if (($this->interval_months ?? 0) > 0) {
            return $this->isPregnancyProgram() ? 'weeks' : 'months';
        }

        return 'days';
    }

    private function isPregnancyProgram(): bool
    {
        $targetType = $this->template?->program?->target_type;
        if ($targetType instanceof \App\Enums\VaccinationProgramTargetType) {
            return $targetType->value === 'pregnancy';
        }

        return $targetType === 'pregnancy';
    }

    public function vaccination(): BelongsTo
    {
        return $this->belongsTo(Vaccination::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(VaccinationTemplate::class, 'vaccination_template_id');
    }
}
