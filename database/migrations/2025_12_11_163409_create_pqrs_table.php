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
        Schema::create('pqrs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->date('fecha');
            $table->string('nombre');
            $table->string('vehiculo_placa')->nullable();
            $table->foreignId('vehiculo_id')->nullable()->constrained('vehicles')->nullOnDelete();
            $table->string('numero_tiquete')->nullable();
            $table->string('correo_electronico')->nullable();
            $table->string('numero_telefono')->nullable();
            $table->tinyInteger('calificacion')->nullable()->comment('1-5 estrellas');
            $table->text('comentarios')->nullable();
            $table->enum('tipo', ['Peticiones', 'Quejas', 'Reclamos', 'Sugerencias', 'Otros'])->default('Peticiones');
            $table->json('adjuntos')->nullable()->comment('Array de rutas de archivos adjuntos');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pqrs');
    }
};
