<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Propietario>
 */
class PropietarioFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tipo_identificacion' => fake()->randomElement(['Cédula de Ciudadanía', 'RUC/NIT', 'Pasaporte']),
            'numero_identificacion' => fake()->unique()->numerify('##########'),
            'nombre_completo' => fake()->name(),
            'tipo_propietario' => fake()->randomElement(['Persona Natural', 'Persona Jurídica']),
            'direccion_contacto' => fake()->optional()->address(),
            'telefono_contacto' => fake()->optional()->phoneNumber(),
            'correo_electronico' => fake()->optional()->safeEmail(),
            'estado' => fake()->randomElement(['Activo', 'Inactivo']),
        ];
    }
}
