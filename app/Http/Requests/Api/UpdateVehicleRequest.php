<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVehicleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $vehicleId = $this->route('vehicle')?->id ?? $this->route('vehiculo')?->id ?? $this->route('id');

        return [
            'tipo' => ['required', 'in:Bus,Camioneta,Taxi'],
            'marca' => ['required', 'string', 'max:255'],
            'modelo' => ['required', 'string', 'max:255'],
            'anio_fabricacion' => ['required', 'integer', 'min:1900', 'max:'.now()->year],
            'placa' => ['required', 'string', 'max:20', Rule::unique('vehicles', 'placa')->ignore($vehicleId)],
            'chasis_vin' => ['nullable', 'string', 'max:255'],
            'capacidad_pasajeros' => ['nullable', 'integer', 'min:0'],
            'capacidad_carga_kg' => ['nullable', 'integer', 'min:0'],
            'combustible' => ['required', 'in:gasolina,diesel,hibrido,electrico'],
            'ultima_revision_tecnica' => ['nullable', 'date'],
            'estado' => ['required', 'in:Activo,En Mantenimiento,Fuera de Servicio'],
            'propietario_nombre' => ['required', 'string', 'max:255'],
            'conductor_id' => ['nullable', 'exists:conductors,id'],
            'foto' => ['nullable', 'string'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tipo.required' => 'El tipo de vehículo es requerido',
            'marca.required' => 'La marca es requerida',
            'modelo.required' => 'El modelo es requerido',
            'anio_fabricacion.required' => 'El año de fabricación es requerido',
            'placa.required' => 'La placa es requerida',
            'placa.unique' => 'La placa ya está registrada',
            'combustible.required' => 'El tipo de combustible es requerido',
            'estado.required' => 'El estado es requerido',
            'propietario_nombre.required' => 'El nombre del propietario es requerido',
        ];
    }
}
