<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mobilizacao_material_categorias', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->text('descricao')->nullable();
            $table->string('cor', 20)->default('#6F1731');
            $table->unsignedSmallInteger('ordem')->default(0);
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });

        Schema::create('mobilizacao_materiais', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contrato_id');
            $table->unsignedBigInteger('categoria_id')->nullable();
            $table->foreign('contrato_id', 'mob_mat_contrato_fk')->references('id')->on('contratos')->cascadeOnDelete();
            $table->foreign('categoria_id', 'mob_mat_categoria_fk')->references('id')->on('mobilizacao_material_categorias')->nullOnDelete();
            $table->string('codigo_material')->nullable();
            $table->text('descricao_material');
            $table->string('unidade_medida', 30)->nullable();
            $table->decimal('quantidade_necessaria', 12, 2)->default(0);
            $table->decimal('quantidade_pedida_sigo', 12, 2)->default(0);
            $table->decimal('quantidade_em_compra', 12, 2)->default(0);
            $table->decimal('quantidade_recebida', 12, 2)->default(0);
            $table->decimal('saldo_a_comprar', 12, 2)->default(0);
            $table->decimal('saldo_a_receber', 12, 2)->default(0);
            $table->string('status', 40)->default('SEM_TRATATIVA');
            $table->string('acao_do_dia', 255)->nullable();
            $table->string('numero_pm')->nullable();
            $table->string('numero_oc')->nullable();
            $table->string('fornecedor')->nullable();
            $table->string('comprador_responsavel')->nullable();
            $table->date('data_pedido_sigo')->nullable();
            $table->date('data_inicio_compra')->nullable();
            $table->date('previsao_entrega')->nullable();
            $table->date('data_recebimento_total')->nullable();
            $table->text('observacao_almoxarife')->nullable();
            $table->text('observacao_gestao')->nullable();
            $table->string('prioridade', 20)->nullable();
            $table->string('origem_cadastro', 40)->default('MANUAL');
            $table->boolean('ativo')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->foreign('created_by', 'mob_mat_created_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by', 'mob_mat_updated_fk')->references('id')->on('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['contrato_id', 'status']);
            $table->index(['contrato_id', 'prioridade']);
            $table->index('numero_pm');
            $table->index('numero_oc');
        });

        Schema::create('mobilizacao_material_recebimentos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('mobilizacao_material_id');
            $table->foreign('mobilizacao_material_id', 'mob_mat_rec_mat_fk')->references('id')->on('mobilizacao_materiais')->cascadeOnDelete();
            $table->date('data_recebimento');
            $table->decimal('quantidade_recebida', 12, 2);
            $table->string('responsavel_recebimento')->nullable();
            $table->string('numero_nf')->nullable();
            $table->text('observacao')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->foreign('created_by', 'mob_mat_rec_user_fk')->references('id')->on('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('mobilizacao_material_anexos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('mobilizacao_material_id');
            $table->foreign('mobilizacao_material_id', 'mob_mat_anx_mat_fk')->references('id')->on('mobilizacao_materiais')->cascadeOnDelete();
            $table->string('tipo_anexo', 40);
            $table->string('nome_arquivo');
            $table->string('caminho_arquivo');
            $table->text('observacao')->nullable();
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->foreign('uploaded_by', 'mob_mat_anx_user_fk')->references('id')->on('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('mobilizacao_material_historicos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('mobilizacao_material_id');
            $table->foreign('mobilizacao_material_id', 'mob_mat_hist_mat_fk')->references('id')->on('mobilizacao_materiais')->cascadeOnDelete();
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->foreign('usuario_id', 'mob_mat_hist_user_fk')->references('id')->on('users')->nullOnDelete();
            $table->string('campo_alterado');
            $table->text('valor_anterior')->nullable();
            $table->text('valor_novo')->nullable();
            $table->text('observacao')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('mobilizacao_material_importacoes', function (Blueprint $table) {
            $table->id();
            $table->string('tipo_importacao', 40);
            $table->string('nome_arquivo');
            $table->unsignedInteger('total_linhas')->default(0);
            $table->unsignedInteger('total_importado')->default(0);
            $table->unsignedInteger('total_erro')->default(0);
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->foreign('usuario_id', 'mob_mat_imp_user_fk')->references('id')->on('users')->nullOnDelete();
            $table->string('status', 30)->default('PROCESSANDO');
            $table->text('observacao')->nullable();
            $table->timestamps();
        });

        $now = now();
        $categorias = [
            ['nome' => 'Ferramentas / Equipamentos', 'ordem' => 1, 'cor' => '#6F1731'],
            ['nome' => 'Consumíveis / Insumos', 'ordem' => 2, 'cor' => '#2563eb'],
            ['nome' => 'Canteiro / Mobiliário / Infra', 'ordem' => 3, 'cor' => '#ca8a04'],
            ['nome' => 'Material elétrico', 'ordem' => 4, 'cor' => '#eab308'],
            ['nome' => 'EPI / EPC / Segurança', 'ordem' => 5, 'cor' => '#16a34a'],
            ['nome' => 'Solda / Oxicorte', 'ordem' => 6, 'cor' => '#ea580c'],
            ['nome' => 'Içamento / Rigging', 'ordem' => 7, 'cor' => '#7c3aed'],
            ['nome' => 'Instrumentos de medição / teste', 'ordem' => 8, 'cor' => '#0891b2'],
            ['nome' => 'Segurança / Meio ambiente', 'ordem' => 9, 'cor' => '#059669'],
            ['nome' => 'Comunicação / TI / Apoio ADM', 'ordem' => 10, 'cor' => '#64748b'],
            ['nome' => 'Outros / Validar', 'ordem' => 11, 'cor' => '#71717a'],
        ];

        foreach ($categorias as $cat) {
            DB::table('mobilizacao_material_categorias')->insert([
                'nome' => $cat['nome'],
                'descricao' => null,
                'cor' => $cat['cor'],
                'ordem' => $cat['ordem'],
                'ativo' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('mobilizacao_material_importacoes');
        Schema::dropIfExists('mobilizacao_material_historicos');
        Schema::dropIfExists('mobilizacao_material_anexos');
        Schema::dropIfExists('mobilizacao_material_recebimentos');
        Schema::dropIfExists('mobilizacao_materiais');
        Schema::dropIfExists('mobilizacao_material_categorias');
    }
};
