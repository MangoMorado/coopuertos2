<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Pqr extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'fecha',
        'nombre',
        'vehiculo_placa',
        'vehiculo_id',
        'numero_tiquete',
        'correo_electronico',
        'numero_telefono',
        'calificacion',
        'comentarios',
        'tipo',
        'adjuntos',
    ];

    protected $casts = [
        'fecha' => 'date',
        'adjuntos' => 'array',
        'calificacion' => 'integer',
    ];

    protected static function booted()
    {
        static::creating(function ($pqr) {
            $pqr->uuid = (string) Str::uuid();
            if (empty($pqr->fecha)) {
                $pqr->fecha = now();
            }
        });
    }

    public function vehiculo()
    {
        return $this->belongsTo(Vehicle::class);
    }
}
