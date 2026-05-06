<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contrato_pgu_acoes_recomendadas', function (Blueprint $table) {
            $table->id();
            $table->string('contrato')->index();
            $table->date('competencia')->index();
            $table->string('funcao', 255);
            $table->unsignedInteger('ordem')->default(0);
            $table->unsignedInteger('pendencias_snapshot')->default(0);
            $table->string('acao_recomendada', 255)->nullable();
            $table->string('responsavel', 120)->nullable();
            $table->timestamps();

            $table->unique(['contrato', 'competencia', 'funcao'], 'uniq_contrato_competencia_funcao');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contrato_pgu_acoes_recomendadas');
    }
};
