<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Crear usuario Test User
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('secret'), // Importante hashear la contraseña
        ]);

        // Crear usuario Andrés Gonzalez
        User::factory()->create([
            'name' => 'Andres Gonzalez',
            'email' => 'andres.david8.8@gmail.com',
            'password' => Hash::make('Andres1990'), // Importante hashear la contraseña
        ]);
        // Crear usuario Gerencia Coopuertos
        User::factory()->create([
            'name' => 'Gerencia',
            'email' => 'coopuertosgerencia@gmail.com',
            'password' => Hash::make('Coopuertos1'), // Importante hashear la contraseña
        ]);
    }
}
