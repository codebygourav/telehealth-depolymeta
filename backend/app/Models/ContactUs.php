<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ContactUs extends Model
{
    use SoftDeletes, HasUuids;
    
    public $incrementing = false;

    protected $keyType = 'string';
    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'email',
        'message',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}