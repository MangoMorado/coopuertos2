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
        // Índices para tabla conductors
        Schema::table('conductors', function (Blueprint $table) {
            // Índice compuesto para búsquedas comunes de nombre completo
            $table->index(['nombres', 'apellidos'], 'idx_conductors_nombre_completo');
            // Índice para número interno (búsquedas frecuentes)
            $table->index('numero_interno', 'idx_conductors_numero_interno');
            // Índices para búsquedas por celular y correo
            $table->index('celular', 'idx_conductors_celular');
            $table->index('correo', 'idx_conductors_correo');
            // Índice para fecha de nacimiento (usado en dashboard y filtros)
            $table->index('fecha_nacimiento', 'idx_conductors_fecha_nacimiento');
            // Índice compuesto para filtros por tipo y estado
            $table->index(['conductor_tipo', 'estado'], 'idx_conductors_tipo_estado');
        });

        // Índices para tabla vehicles
        Schema::table('vehicles', function (Blueprint $table) {
            // Nota: placa ya tiene índice único, pero agregamos índice adicional para búsquedas LIKE
            // MySQL puede usar el índice único para búsquedas, pero este índice puede ayudar en LIKE
            // Sin embargo, como ya existe índice único, no duplicamos
            // Índice compuesto para búsquedas por marca y modelo
            $table->index(['marca', 'modelo'], 'idx_vehicles_marca_modelo');
            // Índice para búsquedas por propietario
            $table->index('propietario_nombre', 'idx_vehicles_propietario');
            // Índice compuesto para filtros por tipo y estado
            $table->index(['tipo', 'estado'], 'idx_vehicles_tipo_estado');
        });

        // Índices para tabla propietarios
        Schema::table('propietarios', function (Blueprint $table) {
            // Nota: numero_identificacion ya tiene índice único
            // Índice para búsquedas por nombre completo
            $table->index('nombre_completo', 'idx_propietarios_nombre');
            // Índices para búsquedas por teléfono y correo
            $table->index('telefono_contacto', 'idx_propietarios_telefono');
            $table->index('correo_electronico', 'idx_propietarios_correo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conductors', function (Blueprint $table) {
            $table->dropIndex('idx_conductors_nombre_completo');
            $table->dropIndex('idx_conductors_numero_interno');
            $table->dropIndex('idx_conductors_celular');
            $table->dropIndex('idx_conductors_correo');
            $table->dropIndex('idx_conductors_fecha_nacimiento');
            $table->dropIndex('idx_conductors_tipo_estado');
        });

        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropIndex('idx_vehicles_marca_modelo');
            $table->dropIndex('idx_vehicles_propietario');
            $table->dropIndex('idx_vehicles_tipo_estado');
        });

        Schema::table('propietarios', function (Blueprint $table) {
            $table->dropIndex('idx_propietarios_nombre');
            $table->dropIndex('idx_propietarios_telefono');
            $table->dropIndex('idx_propietarios_correo');
        });
    }
};
