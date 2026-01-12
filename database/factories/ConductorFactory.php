<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Conductor>
 */
class ConductorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombres' => fake()->firstName(),
            'apellidos' => fake()->lastName(),
            'cedula' => fake()->unique()->numerify('##########'),
            'conductor_tipo' => fake()->randomElement(['A', 'B']),
            'rh' => fake()->randomElement(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']),
            'numero_interno' => fake()->optional()->numerify('###'),
            'celular' => fake()->optional()->numerify('3##########'),
            'correo' => fake()->optional()->safeEmail(),
            'fecha_nacimiento' => fake()->optional()->date(),
            'otra_profesion' => fake()->optional()->jobTitle(),
            'nivel_estudios' => fake()->optional()->randomElement(['Primaria', 'Secundaria', 'Técnico', 'Universitario']),
            'relevo' => fake()->boolean(20),
            'estado' => fake()->randomElement(['activo', 'inactivo']),
        ];
    }
}
