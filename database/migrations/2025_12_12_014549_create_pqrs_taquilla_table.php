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
        Schema::create('pqrs_taquilla', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->date('fecha');
            $table->time('hora');
            $table->string('nombre');
            $table->string('sede')->nullable();
            $table->string('correo')->nullable();
            $table->string('telefono')->nullable();
            $table->enum('tipo', ['Peticiones', 'Quejas', 'Reclamos', 'Sugerencias', 'Otros'])->default('Peticiones');
            $table->tinyInteger('calificacion')->nullable()->comment('1-5 estrellas');
            $table->text('comentario')->nullable();
            $table->json('adjuntos')->nullable()->comment('Array de rutas de archivos adjuntos');
            $table->enum('estado', ['Radicada', 'En Trámite', 'En Espera de Información', 'Resuelta', 'Cerrada'])->default('Radicada');
            $table->foreignId('usuario_asignado_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pqrs_taquilla');
    }
};
