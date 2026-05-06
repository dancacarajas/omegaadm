<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ssma_ambiental_registros', function (Blueprint $table) {
            $table->id();
            $table->date('competencia')->unique();
            $table->text('residuos_gerados')->nullable();
            $table->text('residuos_destinados')->nullable();
            $table->decimal('quantidade_residuos_destinados_corretamente', 14, 3)->nullable();
            $table->string('evidencia_destinacao_path')->nullable();
            $table->text('coleta_seletiva')->nullable();
            $table->unsignedInteger('vazamentos_derramamentos')->default(0);
            $table->text('produtos_quimicos')->nullable();
            $table->text('armazenamento_residuos')->nullable();
            $table->decimal('consumo_agua_m3', 14, 3)->nullable();
            $table->decimal('consumo_energia_kwh', 14, 3)->nullable();
            $table->unsignedInteger('ocorrencias_ambientais')->default(0);
            $table->text('licencas_condicionantes')->nullable();
            $table->text('acoes_ambientais_realizadas')->nullable();
            $table->unsignedInteger('acoes_ambientais_concluidas')->default(0);
            $table->text('campanhas_ambientais')->nullable();
            $table->unsignedInteger('nao_conformidades_ambientais')->default(0);
            $table->text('observacoes')->nullable();
            $table->timestamps();

            $table->index('competencia');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ssma_ambiental_registros');
    }
};
