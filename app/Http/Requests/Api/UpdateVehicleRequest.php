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
        $currentYear = now()->year;
        $minYear = 1990; // Año mínimo configurable

        return [
            'tipo' => ['required', 'in:Bus,Camioneta,Taxi'],
            'marca' => ['required', 'string', 'max:255'],
            'modelo' => ['required', 'string', 'max:255'],
            'anio_fabricacion' => [
                'required',
                'integer',
                'min:'.$minYear,
                'max:'.$currentYear,
            ],
            'placa' => ['required', 'string', 'max:20', Rule::unique('vehicles', 'placa')->ignore($vehicleId)],
            'chasis_vin' => ['nullable', 'string', 'max:255'],
            'capacidad_pasajeros' => [
                'nullable',
                'integer',
                'min:0',
                'max:80',
            ],
            'capacidad_carga_kg' => ['nullable', 'integer', 'min:0'],
            'combustible' => ['required', 'in:gasolina,diesel,hibrido,electrico'],
            'ultima_revision_tecnica' => [
                'nullable',
                'date',
                'before_or_equal:today',
            ],
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
        $currentYear = now()->year;
        $minYear = 1990;

        return [
            'tipo.required' => 'El tipo de vehículo es requerido',
            'marca.required' => 'La marca es requerida',
            'modelo.required' => 'El modelo es requerido',
            'anio_fabricacion.required' => 'El año de fabricación es requerido',
            'anio_fabricacion.min' => 'El año de fabricación no puede ser menor a '.$minYear.'.',
            'anio_fabricacion.max' => 'El año de fabricación no puede ser mayor al año actual ('.$currentYear.').',
            'placa.required' => 'La placa es requerida',
            'placa.unique' => 'La placa ya está registrada',
            'capacidad_pasajeros.max' => 'La capacidad de pasajeros no puede ser mayor a 80.',
            'ultima_revision_tecnica.before_or_equal' => 'La fecha de revisión técnica no puede ser una fecha futura.',
            'combustible.required' => 'El tipo de combustible es requerido',
            'estado.required' => 'El estado es requerido',
            'propietario_nombre.required' => 'El nombre del propietario es requerido',
        ];
    }
}
