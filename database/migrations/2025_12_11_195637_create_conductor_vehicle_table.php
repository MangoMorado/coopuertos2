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
        Schema::create('conductor_vehicle', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conductor_id')->constrained('conductors')->onDelete('cascade');
            $table->foreignId('vehicle_id')->constrained('vehicles')->onDelete('cascade');
            $table->enum('estado', ['activo', 'inactivo'])->default('activo');
            $table->date('fecha_asignacion')->default(now());
            $table->date('fecha_desasignacion')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            // Nota: La validación de que un conductor solo tenga un vehículo activo
            // se manejará a nivel de aplicación, ya que MySQL no soporta índices únicos parciales
            // de manera directa. Se usará un índice compuesto para búsquedas rápidas.
            $table->index(['conductor_id', 'estado']);

            // Índices para búsquedas rápidas
            $table->index('conductor_id');
            $table->index('vehicle_id');
            $table->index('estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conductor_vehicle');
    }
};
