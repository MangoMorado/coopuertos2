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
        Schema::create('carnet_downloads', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->unique();
            $table->integer('total')->default(0);
            $table->integer('procesados')->default(0);
            $table->enum('estado', ['procesando', 'completado', 'error'])->default('procesando');
            $table->string('archivo_zip')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carnet_downloads');
    }
};
