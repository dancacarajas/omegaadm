<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('veiculo_solicitacoes', function (Blueprint $table) {
            $table->id();
            $table->string('status')->default('em_andamento');
            $table->date('data_inicio_atividade')->nullable();
            $table->date('data_fim_atividade')->nullable();
            $table->date('data_liberacao_inspecao')->nullable();
            $table->string('contrato')->nullable();
            $table->string('linha_contratual')->nullable();
            $table->string('criterio_tecnico')->nullable();
            $table->string('finalidade')->nullable();
            $table->string('responsavel')->nullable();
            $table->json('checklist_data')->nullable();
            $table->text('observacoes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('veiculo_solicitacoes');
    }
};
