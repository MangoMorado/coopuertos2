<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StorePropietarioRequest extends FormRequest
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
        return [
            'tipo_identificacion' => ['required', 'in:Cédula de Ciudadanía,RUC/NIT,Pasaporte'],
            'numero_identificacion' => ['required', 'string', 'unique:propietarios,numero_identificacion', 'max:50', 'regex:/^[0-9]+$/'],
            'nombre_completo' => ['required', 'string', 'max:255'],
            'tipo_propietario' => ['required', 'in:Persona Natural,Persona Jurídica'],
            'direccion_contacto' => ['nullable', 'string', 'max:500'],
            'telefono_contacto' => ['nullable', 'string', 'max:20', 'regex:/^[0-9]+$/'],
            'correo_electronico' => ['nullable', 'email', 'max:255'],
            'estado' => ['required', 'in:Activo,Inactivo'],
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
            'tipo_identificacion.required' => 'El tipo de identificación es requerido',
            'numero_identificacion.required' => 'El número de identificación es requerido',
            'numero_identificacion.unique' => 'El número de identificación ya está registrado',
            'numero_identificacion.regex' => 'El número de identificación solo puede contener números.',
            'telefono_contacto.regex' => 'El teléfono de contacto solo puede contener números.',
            'nombre_completo.required' => 'El nombre completo es requerido',
            'tipo_propietario.required' => 'El tipo de propietario es requerido',
            'estado.required' => 'El estado es requerido',
        ];
    }
}
