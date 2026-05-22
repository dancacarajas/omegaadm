<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rh_movimentacao_nada_consta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chamado_id')->unique()->constrained('rh_movimentacao_chamados')->cascadeOnDelete();
            $table->foreignId('colaborador_id')->constrained('colaboradores')->cascadeOnDelete();
            $table->date('data_emissao')->nullable();
            $table->string('status', 40)->default('pendente_preenchimento');
            $table->boolean('validado_rh')->default(false);
            $table->foreignId('validado_rh_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('validado_rh_em')->nullable();
            $table->string('assinatura_colaborador', 500)->nullable();
            $table->string('assinatura_gestor', 500)->nullable();
            $table->string('gestor_contrato', 120)->nullable();
            $table->string('responsavel_rh', 120)->nullable();
            $table->text('observacao')->nullable();
            $table->unsignedBigInteger('arquivo_pdf_id')->nullable();
            $table->timestamps();

            $table->foreign('arquivo_pdf_id', 'nc_fk_pdf')->references('id')->on('rh_movimentacao_anexos')->nullOnDelete();
        });

        Schema::create('rh_movimentacao_nada_consta_itens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nada_consta_id')->constrained('rh_movimentacao_nada_consta')->cascadeOnDelete();
            $table->string('area', 40);
            $table->string('item', 60);
            $table->boolean('tem_debito')->nullable();
            $table->text('descricao_pendencia')->nullable();
            $table->decimal('valor_pendencia', 12, 2)->nullable();
            $table->string('status_tratativa', 40)->default('sem_pendencia');
            $table->string('responsavel_nome', 120)->nullable();
            $table->foreignId('validado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('validado_em')->nullable();
            $table->unsignedBigInteger('anexo_evidencia_id')->nullable();
            $table->unsignedBigInteger('anexo_termo_baixa_id')->nullable();
            $table->unsignedBigInteger('anexo_autorizacao_desconto_id')->nullable();
            $table->text('observacao')->nullable();
            $table->timestamps();

            $table->unique(['nada_consta_id', 'area', 'item'], 'nc_item_area_unique');

            $table->foreign('anexo_evidencia_id', 'nc_item_fk_evidencia')->references('id')->on('rh_movimentacao_anexos')->nullOnDelete();
            $table->foreign('anexo_termo_baixa_id', 'nc_item_fk_baixa')->references('id')->on('rh_movimentacao_anexos')->nullOnDelete();
            $table->foreign('anexo_autorizacao_desconto_id', 'nc_item_fk_desc')->references('id')->on('rh_movimentacao_anexos')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rh_movimentacao_nada_consta_itens');
        Schema::dropIfExists('rh_movimentacao_nada_consta');
    }
};
