<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiAllowedIp extends Model
{
    protected $table = 'api_allowed_ips';

    protected $fillable = [
        'ip',
        'label',
        'active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];
}
