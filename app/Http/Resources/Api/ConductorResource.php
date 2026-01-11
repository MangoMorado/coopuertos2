<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConductorResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'nombres' => $this->nombres,
            'apellidos' => $this->apellidos,
            'cedula' => $this->cedula,
            'conductor_tipo' => $this->conductor_tipo,
            'rh' => $this->rh,
            'numero_interno' => $this->numero_interno,
            'celular' => $this->celular,
            'correo' => $this->correo,
            'fecha_nacimiento' => $this->fecha_nacimiento?->toDateString(),
            'otra_profesion' => $this->otra_profesion,
            'nivel_estudios' => $this->nivel_estudios,
            'relevo' => $this->relevo,
            'estado' => $this->estado,
            'foto' => $this->foto,
            'ruta_carnet' => $this->ruta_carnet,
            'vehiculo' => new VehicleResource($this->whenLoaded('asignacionActiva.vehicle')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
