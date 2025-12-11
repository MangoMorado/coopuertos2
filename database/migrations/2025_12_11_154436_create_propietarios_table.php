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
        Schema::create('propietarios', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo_identificacion', ['Cédula de Ciudadanía', 'RUC/NIT', 'Pasaporte'])->default('Cédula de Ciudadanía');
            $table->string('numero_identificacion')->unique();
            $table->string('nombre_completo');
            $table->enum('tipo_propietario', ['Persona Natural', 'Persona Jurídica'])->default('Persona Natural');
            $table->text('direccion_contacto')->nullable();
            $table->string('telefono_contacto')->nullable();
            $table->string('correo_electronico')->nullable();
            $table->enum('estado', ['Activo', 'Inactivo'])->default('Activo');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('propietarios');
    }
};
