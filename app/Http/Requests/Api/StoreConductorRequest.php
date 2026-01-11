<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreConductorRequest extends FormRequest
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
            'nombres' => ['required', 'string', 'max:255'],
            'apellidos' => ['required', 'string', 'max:255'],
            'cedula' => ['required', 'string', 'unique:conductors,cedula', 'max:50'],
            'conductor_tipo' => ['required', 'in:A,B'],
            'rh' => ['required', 'in:A+,A-,B+,B-,AB+,AB-,O+,O-'],
            'numero_interno' => ['nullable', 'string', 'max:50'],
            'celular' => ['nullable', 'string', 'max:20'],
            'correo' => ['nullable', 'email', 'max:255'],
            'fecha_nacimiento' => ['nullable', 'date'],
            'otra_profesion' => ['nullable', 'string', 'max:255'],
            'nivel_estudios' => ['nullable', 'string', 'max:255'],
            'relevo' => ['nullable', 'boolean'],
            'foto' => ['nullable', 'string'],
            'estado' => ['required', 'in:activo,inactivo'],
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
            'nombres.required' => 'Los nombres son requeridos',
            'apellidos.required' => 'Los apellidos son requeridos',
            'cedula.required' => 'La cédula es requerida',
            'cedula.unique' => 'La cédula ya está registrada',
            'conductor_tipo.required' => 'El tipo de conductor es requerido',
            'rh.required' => 'El RH es requerido',
            'estado.required' => 'El estado es requerido',
        ];
    }
}
