<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('conductors', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('nombres');
            $table->string('apellidos');
            $table->string('cedula')->unique();
            $table->enum('conductor_tipo', ['A', 'B']); // Tipo A (camionetas), Tipo B (busetas)
            $table->enum('rh', ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']);
            $table->string('vehiculo_placa')->nullable();
            $table->string('numero_interno')->nullable();
            $table->string('celular')->nullable();
            $table->string('correo')->nullable();
            $table->date('fecha_nacimiento')->nullable();
            $table->string('otra_profesion')->nullable();
            $table->string('foto')->nullable();
            $table->string('nivel_estudios')->nullable();
            $table->enum('estado', ['activo', 'inactivo'])->default('activo');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conductors');
    }
};

