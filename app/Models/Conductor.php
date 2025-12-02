<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Conductor extends Model
{
    use HasFactory;

    // Campos permitidos para asignación masiva
    protected $fillable = [
        'uuid',
        'nombres',
        'apellidos',
        'cedula',
        'conductor_tipo',
        'rh',
        'vehiculo_placa',
        'numero_interno',
        'celular',
        'correo',
        'fecha_nacimiento',
        'otra_profesion',
        'nivel_estudios',
        'foto',
        'estado',
    ];

    // Generar UUID automáticamente al crear un registro
    protected static function booted()
    {
        static::creating(function ($conductor) {
            $conductor->uuid = (string) Str::uuid();
        });
    }
}
