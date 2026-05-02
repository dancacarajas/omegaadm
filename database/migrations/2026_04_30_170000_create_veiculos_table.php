<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('veiculos', function (Blueprint $table) {
            $table->id();
            $table->string('placa')->unique();
            $table->string('renavam')->nullable();
            $table->string('tipo')->nullable();
            $table->string('marca')->nullable();
            $table->string('modelo')->nullable();
            $table->string('ano_fabricacao', 4)->nullable();
            $table->string('ano_modelo', 4)->nullable();
            $table->string('cor')->nullable();
            $table->string('proprietario')->nullable();
            $table->string('fornecedor')->nullable();
            $table->string('contrato')->nullable();
            $table->string('linha_contratual')->nullable();
            $table->string('criterio_tecnico')->nullable();
            $table->date('data_inicio_atividade')->nullable();
            $table->date('data_fim_atividade')->nullable();
            $table->date('data_liberacao_inspecao')->nullable();
            $table->string('status')->default('ativo');
            $table->string('mobilizacao_status')->default('pendente');
            $table->text('observacoes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('veiculos');
    }
};
