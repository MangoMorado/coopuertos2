<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Propietario extends Model
{
    use HasFactory;

    protected $fillable = [
        'tipo_identificacion',
        'numero_identificacion',
        'nombre_completo',
        'tipo_propietario',
        'direccion_contacto',
        'telefono_contacto',
        'correo_electronico',
        'estado',
    ];
}
