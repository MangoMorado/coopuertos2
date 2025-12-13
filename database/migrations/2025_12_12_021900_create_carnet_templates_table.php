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
        Schema::create('carnet_templates', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->default('Plantilla Principal');
            $table->string('imagen_plantilla')->nullable()->comment('Ruta de la imagen de plantilla');
            $table->json('variables_config')->nullable()->comment('Configuración de variables con posiciones (x, y, activo, etc)');
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carnet_templates');
    }
};
