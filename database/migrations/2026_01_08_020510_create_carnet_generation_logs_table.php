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
        Schema::create('carnet_generation_logs', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('tipo', ['masivo', 'individual', 'plantilla'])->default('masivo');
            $table->enum('estado', ['pendiente', 'procesando', 'completado', 'error'])->default('pendiente');
            $table->integer('total')->default(0);
            $table->integer('procesados')->default(0);
            $table->integer('exitosos')->default(0);
            $table->integer('errores')->default(0);
            $table->text('mensaje')->nullable();
            $table->json('logs')->nullable();
            $table->string('archivo_zip')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carnet_generation_logs');
    }
};
