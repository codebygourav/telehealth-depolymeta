<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class MedicineTemplate extends Model
{
    use HasFactory, SoftDeletes;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'scope_type',
        'department_id',
        'doctor_id',
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public const SCOPE_GLOBAL = 'global';
    public const SCOPE_DOCTOR = 'doctor';
    public const SCOPE_DEPARTMENT = 'department';

    protected static function booted(): void
    {
        static::creating(function (MedicineTemplate $template) {
            if (! $template->getKey()) {
                $template->{$template->getKeyName()} = (string) Str::uuid();
            }
        });

        static::saving(function (MedicineTemplate $template) {
            $template->scope_type ??= $template->doctor_id ? self::SCOPE_DOCTOR : self::SCOPE_GLOBAL;

            if ($template->scope_type === self::SCOPE_GLOBAL) {
                $template->doctor_id = null;
                $template->department_id = null;
            }

            if ($template->scope_type === self::SCOPE_DEPARTMENT) {
                $template->doctor_id = null;
            }
        });
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class)->withTrashed();
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class)->withTrashed();
    }

    public function items(): HasMany
    {
        return $this->hasMany(MedicineTemplateItem::class)->orderBy('sort_order');
    }
}
