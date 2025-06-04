<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plantilla extends Model
{
    protected $fillable = [
        'whatsapp_id',
        'name',
        'language',
        'category',
        'status',
        'parameter_format',
        'components',
    ];

    protected $casts = [
        'components' => 'array',
    ];
}
