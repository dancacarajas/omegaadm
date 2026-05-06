<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ssma_planos_acao', function (Blueprint $table) {
            $table->id();
            $table->string('origem', 40);
            $table->string('origem_detalhe', 500)->nullable();
            $table->string('tipo', 30);
            $table->text('descricao_desvio');
            $table->text('acao_necessaria');
            $table->string('responsavel', 255)->nullable();
            $table->date('prazo');
            $table->string('status', 40)->default('aberta');
            $table->string('prioridade', 20)->default('media');
            $table->string('nivel_risco', 20)->default('medio');
            $table->date('data_conclusao')->nullable();
            $table->string('evidencia_conclusao_path')->nullable();
            $table->text('validacao_ssma')->nullable();
            $table->date('validacao_ssma_em')->nullable();
            $table->text('justificativa_atraso')->nullable();
            $table->text('observacoes')->nullable();
            $table->timestamps();

            $table->index('prazo');
            $table->index('status');
            $table->index('origem');
            $table->index('responsavel');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ssma_planos_acao');
    }
};
