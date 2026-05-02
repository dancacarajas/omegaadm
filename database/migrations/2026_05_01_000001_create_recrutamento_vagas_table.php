<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recrutamento_vagas', function (Blueprint $table) {
            $table->id();
            $table->string('titulo')->nullable();
            $table->unsignedInteger('quantidade')->default(1);
            $table->string('prioridade')->nullable();
            $table->string('tipo')->nullable();
            $table->string('contrato')->nullable();
            $table->string('gestor')->nullable();
            $table->string('local')->nullable();
            $table->date('data_solicitacao')->nullable();
            $table->date('previsao_inicio')->nullable();
            $table->string('salario')->nullable();
            $table->string('status')->default('Em abertura');
            $table->text('descricao')->nullable();
            $table->text('requisitos')->nullable();
            $table->json('form_state')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recrutamento_vagas');
    }
};
