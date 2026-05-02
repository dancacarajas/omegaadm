<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rdo_relatorios', function (Blueprint $table) {
            $table->id();
            $table->uuid('offline_uuid')->nullable()->unique();
            $table->date('data')->index();
            $table->string('titulo')->nullable();
            $table->string('contrato')->nullable()->index();
            $table->string('frente')->nullable();
            $table->string('area')->nullable();
            $table->string('disciplina')->nullable();
            $table->string('supervisor_nome')->nullable();
            $table->string('supervisor_matricula')->nullable();
            $table->string('encarregado_nome')->nullable();
            $table->string('encarregado_matricula')->nullable();
            $table->string('condicao_climatica')->nullable();
            $table->json('atividades')->nullable();
            $table->json('equipe')->nullable();
            $table->text('observacoes')->nullable();
            $table->text('ocorrencias')->nullable();
            $table->string('evidencia_path')->nullable();
            $table->string('status', 30)->default('transmitido')->index();
            $table->timestamp('transmitido_em')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rdo_relatorios');
    }
};
