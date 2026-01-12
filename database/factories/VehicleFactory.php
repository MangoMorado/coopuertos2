<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Vehicle>
 */
class VehicleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tipo' => fake()->randomElement(['Bus', 'Camioneta', 'Taxi']),
            'marca' => fake()->randomElement(['Toyota', 'Ford', 'Chevrolet', 'Mercedes-Benz', 'Nissan']),
            'modelo' => fake()->words(2, true),
            'anio_fabricacion' => fake()->numberBetween(2000, 2024),
            'placa' => fake()->unique()->regexify('[A-Z]{3}[0-9]{3}'),
            'chasis_vin' => fake()->optional()->regexify('[A-Z0-9]{17}'),
            'capacidad_pasajeros' => fake()->optional()->numberBetween(4, 50),
            'capacidad_carga_kg' => fake()->optional()->numberBetween(500, 5000),
            'combustible' => fake()->randomElement(['gasolina', 'diesel', 'hibrido', 'electrico']),
            'ultima_revision_tecnica' => fake()->optional()->date(),
            'estado' => fake()->randomElement(['Activo', 'En Mantenimiento', 'Fuera de Servicio']),
            'propietario_nombre' => fake()->name(),
            'conductor_id' => null,
            'foto' => null,
        ];
    }
}
