<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use App\Enums\PaymentStatus;
use App\Traits\InteractsWithModuleDocuments;

class Payment extends Model
{
    use HasFactory, SoftDeletes, HasUuids;
    use InteractsWithModuleDocuments;
    protected $moduleDocumentKeys = ['receipt_pdf'];


    protected $fillable = [
        'appointment_id',
        'amount',
        'payment_method',
        'status',
        'transaction_id',
        'razorpay_order_id',
        'razorpay_payment_id',
        'razorpay_signature',
        'email',
        'contact',
        'bank',
        'card_details',
        'card_id',
        'vpa',
        'wallet',
        'invoice_id',
        'international',
        'amount_refunded',
        'refund_status',
        'captured',
        'fee',
        'tax',
        'error_code',
        'error_description',
        'error_source',
        'error_step',
        'error_reason',
        'acquirer_data',
        'notes',
        'razorpay_created_at',
        'full_response',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'international' => 'boolean',
        'captured' => 'boolean',
        'acquirer_data' => 'array',
        'notes' => 'array',
        'razorpay_created_at' => 'datetime',
        'card_details' => 'array',
        'status' => PaymentStatus::class,
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (Auth::check()) {
                $model->created_by = Auth::id();
            }
        });

        static::updating(function ($model) {
            if (Auth::check()) {
                $model->updated_by = Auth::id();
            }
        });

        static::deleting(function ($model) {
            if (Auth::check()) {
                $model->deleted_by = Auth::id();
                $model->save();
            }
        });
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }

    public function deleter()
    {
        return $this->belongsTo(User::class, 'deleted_by', 'id');
    }
}
