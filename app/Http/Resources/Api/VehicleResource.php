<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VehicleResource extends JsonResource
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
            'tipo' => $this->tipo,
            'marca' => $this->marca,
            'modelo' => $this->modelo,
            'anio_fabricacion' => $this->anio_fabricacion,
            'placa' => $this->placa,
            'chasis_vin' => $this->chasis_vin,
            'capacidad_pasajeros' => $this->capacidad_pasajeros,
            'capacidad_carga_kg' => $this->capacidad_carga_kg,
            'combustible' => $this->combustible,
            'ultima_revision_tecnica' => $this->ultima_revision_tecnica?->toDateString(),
            'estado' => $this->estado,
            'propietario_nombre' => $this->propietario_nombre,
            'foto' => $this->foto,
            'conductor' => new ConductorResource($this->whenLoaded('conductor')),
            'conductores' => ConductorResource::collection($this->whenLoaded('conductores')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
