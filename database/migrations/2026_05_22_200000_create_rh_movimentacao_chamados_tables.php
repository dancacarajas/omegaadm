<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rh_movimentacao_chamados', function (Blueprint $table) {
            $table->id();
            $table->string('protocolo', 32)->unique();
            $table->foreignId('colaborador_id')->constrained('colaboradores')->cascadeOnDelete();
            $table->string('tipo', 40);
            $table->string('status', 40)->default('aberto');
            $table->foreignId('etapa_atual_id')->nullable();
            $table->foreignId('solicitante_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('responsavel_atual_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('contrato_origem_id')->nullable()->constrained('contratos')->nullOnDelete();
            $table->foreignId('contrato_destino_id')->nullable()->constrained('contratos')->nullOnDelete();
            $table->date('data_abertura');
            $table->date('data_prevista')->nullable();
            $table->date('data_efetiva')->nullable();
            $table->string('motivo', 500)->nullable();
            $table->text('observacao')->nullable();
            $table->json('dados_antes_json');
            $table->json('dados_depois_json')->nullable();
            $table->foreignId('colaborador_movimentacao_id')->nullable()->constrained('colaborador_movimentacoes')->nullOnDelete();
            $table->timestamp('finalizado_em')->nullable();
            $table->foreignId('finalizado_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelado_em')->nullable();
            $table->foreignId('cancelado_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('motivo_cancelamento')->nullable();
            $table->timestamps();

            $table->index(['status', 'tipo']);
            $table->index(['colaborador_id', 'status']);
            $table->index('data_prevista');
        });

        Schema::create('rh_movimentacao_etapas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chamado_id')->constrained('rh_movimentacao_chamados')->cascadeOnDelete();
            $table->unsignedSmallInteger('ordem');
            $table->string('slug', 60);
            $table->string('nome');
            $table->text('descricao')->nullable();
            $table->string('status', 30)->default('pendente');
            $table->boolean('obrigatoria')->default(true);
            $table->string('papel_responsavel', 40)->nullable();
            $table->foreignId('responsavel_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('prazo')->nullable();
            $table->timestamp('iniciado_em')->nullable();
            $table->timestamp('concluido_em')->nullable();
            $table->foreignId('concluido_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('observacao')->nullable();
            $table->boolean('bloqueia_finalizacao')->default(true);
            $table->json('dados_etapa_json')->nullable();
            $table->timestamps();

            $table->unique(['chamado_id', 'slug']);
            $table->index(['chamado_id', 'ordem']);
        });

        Schema::table('rh_movimentacao_chamados', function (Blueprint $table) {
            $table->foreign('etapa_atual_id')->references('id')->on('rh_movimentacao_etapas')->nullOnDelete();
        });

        Schema::create('rh_movimentacao_checklist_itens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etapa_id')->constrained('rh_movimentacao_etapas')->cascadeOnDelete();
            $table->string('slug', 80);
            $table->string('nome');
            $table->string('status', 20)->default('pendente');
            $table->boolean('obrigatorio')->default(true);
            $table->text('observacao')->nullable();
            $table->foreignId('concluido_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('concluido_em')->nullable();
            $table->timestamps();
        });

        Schema::create('rh_movimentacao_anexos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chamado_id')->constrained('rh_movimentacao_chamados')->cascadeOnDelete();
            $table->foreignId('etapa_id')->nullable()->constrained('rh_movimentacao_etapas')->nullOnDelete();
            $table->string('nome_arquivo');
            $table->string('caminho');
            $table->string('tipo_documento', 80)->nullable();
            $table->boolean('obrigatorio')->default(false);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('rh_movimentacao_aprovacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chamado_id')->constrained('rh_movimentacao_chamados')->cascadeOnDelete();
            $table->foreignId('etapa_id')->nullable()->constrained('rh_movimentacao_etapas')->nullOnDelete();
            $table->foreignId('aprovador_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('papel', 40)->nullable();
            $table->string('status', 20)->default('pendente');
            $table->text('observacao')->nullable();
            $table->timestamp('aprovado_em')->nullable();
            $table->timestamps();
        });

        Schema::create('rh_movimentacao_comentarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chamado_id')->constrained('rh_movimentacao_chamados')->cascadeOnDelete();
            $table->foreignId('etapa_id')->nullable()->constrained('rh_movimentacao_etapas')->nullOnDelete();
            $table->foreignId('usuario_id')->constrained('users')->cascadeOnDelete();
            $table->text('comentario');
            $table->timestamps();
        });

        Schema::create('rh_movimentacao_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chamado_id')->constrained('rh_movimentacao_chamados')->cascadeOnDelete();
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('acao', 80);
            $table->string('campo', 120)->nullable();
            $table->text('valor_anterior')->nullable();
            $table->text('valor_novo')->nullable();
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['chamado_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('rh_movimentacao_chamados', function (Blueprint $table) {
            $table->dropForeign(['etapa_atual_id']);
        });
        Schema::dropIfExists('rh_movimentacao_logs');
        Schema::dropIfExists('rh_movimentacao_comentarios');
        Schema::dropIfExists('rh_movimentacao_aprovacoes');
        Schema::dropIfExists('rh_movimentacao_anexos');
        Schema::dropIfExists('rh_movimentacao_checklist_itens');
        Schema::dropIfExists('rh_movimentacao_etapas');
        Schema::dropIfExists('rh_movimentacao_chamados');
    }
};
