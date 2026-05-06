<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ssma_registro_mensal_prazos', function (Blueprint $table) {
            $table->dropUnique(['competencia']);
        });

        Schema::table('ssma_registro_mensal_prazos', function (Blueprint $table) {
            $table->boolean('recorrente')->default(false)->after('competencia');
            $table->index('competencia');
        });
    }

    public function down(): void
    {
        Schema::table('ssma_registro_mensal_prazos', function (Blueprint $table) {
            $table->dropIndex(['competencia']);
            $table->dropColumn('recorrente');
        });

        Schema::table('ssma_registro_mensal_prazos', function (Blueprint $table) {
            $table->unique('competencia');
        });
    }
};
