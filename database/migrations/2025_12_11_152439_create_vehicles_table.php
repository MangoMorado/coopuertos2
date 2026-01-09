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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id(); // Identificador Único del Vehículo (ID)
            $table->enum('tipo', ['Bus', 'Camioneta', 'Taxi']);
            $table->string('marca');
            $table->string('modelo');
            $table->year('anio_fabricacion');
            $table->string('placa')->unique(); // Número de Placa (único y clave)
            $table->string('chasis_vin')->nullable();
            $table->unsignedInteger('capacidad_pasajeros')->nullable();
            $table->unsignedInteger('capacidad_carga_kg')->nullable();
            $table->enum('combustible', ['gasolina', 'diesel', 'hibrido', 'electrico']);
            $table->date('ultima_revision_tecnica')->nullable();
            $table->enum('estado', ['Activo', 'En Mantenimiento', 'Fuera de Servicio'])->default('Activo');
            $table->string('propietario_nombre');
            $table->foreignId('conductor_id')->nullable()->constrained('conductors')->nullOnDelete();
            $table->longText('foto')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
