<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Conductor extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid', 'nombre', 'documento', 'licencia', 'vencimiento_licencia',
        'telefono', 'email', 'empresa', 'foto', 'estado'
    ];

    protected static function booted()
    {
        static::creating(function ($conductor) {
            $conductor->uuid = Str::uuid();
        });
    }
}
