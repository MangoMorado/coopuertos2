<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropietarioResource extends JsonResource
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
            'tipo_identificacion' => $this->tipo_identificacion,
            'numero_identificacion' => $this->numero_identificacion,
            'nombre_completo' => $this->nombre_completo,
            'tipo_propietario' => $this->tipo_propietario,
            'direccion_contacto' => $this->direccion_contacto,
            'telefono_contacto' => $this->telefono_contacto,
            'correo_electronico' => $this->correo_electronico,
            'estado' => $this->estado,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
