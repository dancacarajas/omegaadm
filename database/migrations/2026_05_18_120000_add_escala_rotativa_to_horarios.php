<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('horario_escalas', function (Blueprint $table) {
            $table->unsignedTinyInteger('ciclo_dias')->nullable()->after('tipo');
            $table->date('data_inicio_ciclo')->nullable()->after('ciclo_dias');
        });

        Schema::table('colaboradores', function (Blueprint $table) {
            $table->unsignedTinyInteger('horario_escala_ciclo_offset')->default(0)->after('horario_escala_id');
        });
    }

    public function down(): void
    {
        Schema::table('colaboradores', function (Blueprint $table) {
            $table->dropColumn('horario_escala_ciclo_offset');
        });

        Schema::table('horario_escalas', function (Blueprint $table) {
            $table->dropColumn(['ciclo_dias', 'data_inicio_ciclo']);
        });
    }
};
