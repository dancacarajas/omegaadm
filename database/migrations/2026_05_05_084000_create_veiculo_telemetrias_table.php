<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('veiculo_telemetrias', function (Blueprint $table) {
            $table->id();
            $table->date('data')->index();
            $table->string('contrato')->nullable()->index();
            $table->foreignId('veiculo_solicitacao_id')->nullable()->constrained('veiculo_solicitacoes')->nullOnDelete();
            $table->string('veiculo', 255);
            $table->string('placa_tag', 60)->nullable();
            $table->string('motorista_responsavel', 255)->nullable();
            $table->decimal('km_inicial', 10, 2)->nullable();
            $table->decimal('km_final', 10, 2)->nullable();
            $table->decimal('km_rodado', 10, 2)->default(0);
            $table->string('horas_operacao', 5)->nullable();
            $table->string('tempo_ocioso', 5)->nullable();
            $table->string('tempo_parado', 5)->nullable();
            $table->text('rota_prevista')->nullable();
            $table->text('rota_realizada')->nullable();
            $table->boolean('desvio_rota')->default(false)->index();
            $table->text('desvio_justificativa')->nullable();
            $table->decimal('velocidade_media', 6, 2)->nullable();
            $table->unsignedInteger('excesso_velocidade')->default(0);
            $table->unsignedInteger('frenagens_bruscas')->default(0);
            $table->unsignedInteger('aceleracoes_bruscas')->default(0);
            $table->text('localizacao')->nullable();
            $table->decimal('consumo_estimado', 10, 2)->nullable();
            $table->unsignedInteger('alertas_gerados')->default(0);
            $table->text('eventos_criticos')->nullable();
            $table->unsignedInteger('eventos_criticos_qtd')->default(0);
            $table->string('evidencia_path')->nullable();
            $table->text('observacao')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('veiculo_telemetrias');
    }
};
