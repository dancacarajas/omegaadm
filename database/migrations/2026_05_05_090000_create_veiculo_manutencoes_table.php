<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('veiculo_manutencoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('veiculo_solicitacao_id')->nullable()->constrained('veiculo_solicitacoes')->nullOnDelete();
            $table->string('contrato')->nullable()->index();
            $table->string('veiculo_equipamento', 255);
            $table->string('placa_tag', 60)->nullable()->index();
            $table->string('tipo', 120)->nullable();
            $table->date('data_solicitacao');
            $table->string('responsavel_solicitacao', 255)->nullable();
            $table->string('motivo', 30)->default('preventiva');
            $table->date('data_envio')->nullable();
            $table->date('data_retorno')->nullable();
            $table->unsignedInteger('dias_parado')->default(0);
            $table->string('status', 30)->default('aberto')->index();
            $table->string('evidencia_path')->nullable();
            $table->text('impacto_operacao')->nullable();
            $table->decimal('impacto_financeiro', 12, 2)->nullable();
            $table->text('observacao')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('veiculo_manutencoes');
    }
};
