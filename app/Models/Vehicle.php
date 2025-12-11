<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'tipo',
        'marca',
        'modelo',
        'anio_fabricacion',
        'placa',
        'chasis_vin',
        'capacidad_pasajeros',
        'capacidad_carga_kg',
        'combustible',
        'ultima_revision_tecnica',
        'estado',
        'propietario_nombre',
        'conductor_id',
        'foto',
    ];

    public function conductor()
    {
        return $this->belongsTo(Conductor::class);
    }
}
