<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('horario_escala_excecoes')) {
            Schema::create('horario_escala_excecoes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('horario_escala_id')->constrained('horario_escalas')->cascadeOnDelete();
                $table->foreignId('colaborador_ausente_id')->constrained('colaboradores')->cascadeOnDelete();
                $table->foreignId('colaborador_cobertura_id')->nullable()->constrained('colaboradores')->nullOnDelete();
                $table->date('data_inicio');
                $table->date('data_fim');
                $table->string('motivo', 500)->nullable();
                $table->timestamps();

                $table->index(['horario_escala_id', 'data_inicio', 'data_fim'], 'he_excecoes_escala_periodo_idx');
            });

            return;
        }

        Schema::table('horario_escala_excecoes', function (Blueprint $table) {
            $table->index(['horario_escala_id', 'data_inicio', 'data_fim'], 'he_excecoes_escala_periodo_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('horario_escala_excecoes');
    }
};
