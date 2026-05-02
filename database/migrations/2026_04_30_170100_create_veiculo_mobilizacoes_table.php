<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('veiculo_mobilizacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('veiculo_id')->constrained('veiculos')->cascadeOnDelete();
            $table->string('etapa');
            $table->string('status')->default('pendente');
            $table->date('data_prevista')->nullable();
            $table->date('data_realizada')->nullable();
            $table->string('numero_solicitacao')->nullable();
            $table->string('responsavel')->nullable();
            $table->string('link_evidencia')->nullable();
            $table->text('observacoes')->nullable();
            $table->timestamps();

            $table->unique(['veiculo_id', 'etapa']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('veiculo_mobilizacoes');
    }
};
