<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CarnetTemplate extends Model
{
    protected $fillable = [
        'nombre',
        'imagen_plantilla',
        'variables_config',
        'activo',
    ];

    protected $casts = [
        'variables_config' => 'array',
        'activo' => 'boolean',
    ];
}
