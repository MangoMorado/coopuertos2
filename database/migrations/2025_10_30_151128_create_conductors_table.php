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
        $table->string('nombre');
        $table->string('documento')->nullable();
        $table->string('licencia')->nullable();
        $table->date('vencimiento_licencia')->nullable();
        $table->string('telefono')->nullable();
        $table->string('email')->nullable();
        $table->string('empresa')->nullable();
        $table->string('foto')->nullable();
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
