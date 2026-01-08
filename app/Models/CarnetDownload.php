<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CarnetDownload extends Model
{
    protected $fillable = [
        'session_id',
        'total',
        'procesados',
        'estado',
        'archivo_zip',
        'error',
        'logs',
    ];

    protected $casts = [
        'total' => 'integer',
        'procesados' => 'integer',
        'logs' => 'array',
    ];
}
