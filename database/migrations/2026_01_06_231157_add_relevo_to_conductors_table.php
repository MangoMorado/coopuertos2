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
        Schema::table('conductors', function (Blueprint $table) {
            $table->boolean('relevo')->default(false)->after('nivel_estudios');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conductors', function (Blueprint $table) {
            $table->dropColumn('relevo');
        });
    }
};
