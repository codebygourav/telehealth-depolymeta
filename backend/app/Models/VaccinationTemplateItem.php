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
        'interval_days',
        'interval_months',
        'minimum_age_days',
        'maximum_age_days',
        'recommended_age_label',
        'due_after_days',
        'due_after_months',
        'sort_order',
    ];

    protected $casts = [
        'set_sort_order' => 'integer',
        'dose_no' => 'integer',
        'depends_on_previous_dose' => 'boolean',
        'interval_days' => 'integer',
        'interval_months' => 'integer',
        'minimum_age_days' => 'integer',
        'maximum_age_days' => 'integer',
        'due_after_days' => 'integer',
        'due_after_months' => 'integer',
        'sort_order' => 'integer',
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
            $item->interval_days ??= 0;
            $item->interval_months ??= 0;
            $item->due_after_days ??= 0;
            $item->due_after_months ??= 0;
            $item->sort_order ??= 0;
        });
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
