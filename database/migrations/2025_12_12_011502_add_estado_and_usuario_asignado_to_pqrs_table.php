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
        Schema::table('pqrs', function (Blueprint $table) {
            $table->enum('estado', ['Radicada', 'En Trámite', 'En Espera de Información', 'Resuelta', 'Cerrada'])->default('Radicada')->after('tipo');
            $table->foreignId('usuario_asignado_id')->nullable()->constrained('users')->nullOnDelete()->after('estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pqrs', function (Blueprint $table) {
            $table->dropForeign(['usuario_asignado_id']);
            $table->dropColumn(['estado', 'usuario_asignado_id']);
        });
    }
};
