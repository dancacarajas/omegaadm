<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ssma_riscos', function (Blueprint $table) {
            $table->id();
            $table->text('risco_identificado');
            $table->string('area_local', 255)->nullable();
            $table->text('atividade');
            $table->string('categoria', 40);
            $table->unsignedTinyInteger('probabilidade');
            $table->unsignedTinyInteger('severidade');
            $table->string('classificacao_final', 20);
            $table->text('medida_controle_existente')->nullable();
            $table->text('medida_adicional_necessaria')->nullable();
            $table->string('responsavel', 255)->nullable();
            $table->date('prazo')->nullable();
            $table->string('status', 40)->default('identificado');
            $table->string('evidencia_path')->nullable();
            $table->date('tratado_em')->nullable();
            $table->timestamps();

            $table->index('categoria');
            $table->index('classificacao_final');
            $table->index('area_local');
            $table->index('status');
            $table->index('tratado_em');
            $table->index(['probabilidade', 'severidade']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ssma_riscos');
    }
};
