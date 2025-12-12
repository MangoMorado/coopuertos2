<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PqrTaquilla extends Model
{
    use HasFactory;

    protected $table = 'pqrs_taquilla';

    protected $fillable = [
        'uuid',
        'fecha',
        'hora',
        'nombre',
        'sede',
        'correo',
        'telefono',
        'tipo',
        'calificacion',
        'comentario',
        'adjuntos',
        'estado',
        'usuario_asignado_id',
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
            if (empty($pqr->hora)) {
                $pqr->hora = now();
            }
        });
    }

    public function usuarioAsignado()
    {
        return $this->belongsTo(User::class, 'usuario_asignado_id');
    }
}
