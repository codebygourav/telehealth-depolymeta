<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Vendor extends Model
{
    use SoftDeletes;

    // Table name (if not 'vendors', but in our case it matches, so not strictly required)
    protected $table = 'vendors';

    // Mass assignable attributes
    protected $fillable = [
        'user_id',
        // Basic Info
        'company_name',
        'vendor_type',
        'contact_person',
        'designation',
        'email',
        'alt_email',
        'mobile',
        'alt_mobile',
        'website',

        // Business Address
        'registered_office_address',
        'city',
        'state',
        'pin_code',
        'country',
        'branch_office_address',

        // Legal & Compliance
        'gst_number',
        'pan_number',
        'cin_number',
        'msme_number',
        'license_upload',
        'tax_exemption_upload',

        // Banking
        'bank_name',
        'branch_name',
        'account_holder',
        'account_number',
        'ifsc_code',
        'cancelled_cheque_upload',
        'preferred_payment_method',
        'billing_email',

        // Product / Service Info
        'service_description',
        'products_offered',
        'catalog_upload',
        'annual_business_volume',
        'years_in_business',
        'existing_clients',

        // Statutory Docs
        'pan_copy',
        'gst_certificate',
        'company_registration',
        'authorized_signatory_id',
        'address_proof',
        'other_documents',

        // Contact Preferences
        'preferred_communication',
        'primary_contact_name',
        'primary_contact_email',
        'primary_contact_phone',
        'secondary_contact_name',
        'secondary_contact_email',
        'secondary_contact_phone',

        // Status
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    // Casts
    protected $casts = [
        'other_documents' => 'array', // JSON column
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Set default status to pending for frontend registrations
            if (!$model->status) {
                $model->status = 'pending';
            }

            if (Auth::check()) {
                $model->created_by = Auth::id();
                // Link to user if not already set
                if (!$model->user_id) {
                    $model->user_id = Auth::id();
                }
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

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
