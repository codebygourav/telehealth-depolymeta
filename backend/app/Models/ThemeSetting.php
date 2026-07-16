<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ThemeSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'frontend',
        'primary_color',
        'primary_light_color',
        'secondary_color',
        'logo_path',
        'favicon_path',
    ];
}
?>
