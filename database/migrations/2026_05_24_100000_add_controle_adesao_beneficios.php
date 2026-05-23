<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('beneficios', function (Blueprint $table) {
            if (! Schema::hasColumn('beneficios', 'requer_controle_adesao')) {
                $table->boolean('requer_controle_adesao')->default(true)->after('status');
            }
            if (! Schema::hasColumn('beneficios', 'adesao_automatica_admissao')) {
                $table->boolean('adesao_automatica_admissao')->default(false)->after('requer_controle_adesao');
            }
            if (! Schema::hasColumn('beneficios', 'exige_formulario_colaborador')) {
                $table->boolean('exige_formulario_colaborador')->default(true)->after('adesao_automatica_admissao');
            }
        });

        Schema::table('colaborador_beneficios', function (Blueprint $table) {
            if (! Schema::hasColumn('colaborador_beneficios', 'status_adesao')) {
                $table->string('status_adesao', 40)->default('pendente_formulario')->after('beneficio_ativo');
            }
            if (! Schema::hasColumn('colaborador_beneficios', 'data_formulario_recebido')) {
                $table->date('data_formulario_recebido')->nullable()->after('status_adesao');
            }
            if (! Schema::hasColumn('colaborador_beneficios', 'data_envio_matriz')) {
                $table->date('data_envio_matriz')->nullable()->after('data_formulario_recebido');
            }
            if (! Schema::hasColumn('colaborador_beneficios', 'protocolo_matriz')) {
                $table->string('protocolo_matriz', 120)->nullable()->after('data_envio_matriz');
            }
            if (! Schema::hasColumn('colaborador_beneficios', 'data_retorno_matriz')) {
                $table->date('data_retorno_matriz')->nullable()->after('protocolo_matriz');
            }
            if (! Schema::hasColumn('colaborador_beneficios', 'data_previsao_cartao')) {
                $table->date('data_previsao_cartao')->nullable()->after('data_retorno_matriz');
            }
            if (! Schema::hasColumn('colaborador_beneficios', 'adesao_atualizado_por_id')) {
                $table->unsignedBigInteger('adesao_atualizado_por_id')->nullable()->after('data_previsao_cartao');
                $table->foreign('adesao_atualizado_por_id', 'cb_adesao_user_fk')
                    ->references('id')->on('users')->nullOnDelete();
            }
        });

        if (Schema::hasTable('colaborador_beneficios') && Schema::hasColumn('colaborador_beneficios', 'status_adesao')) {
            \Illuminate\Support\Facades\DB::table('colaborador_beneficios')
                ->where('cartao_entregue', true)
                ->update(['status_adesao' => 'cartao_entregue']);

            \Illuminate\Support\Facades\DB::table('colaborador_beneficios')
                ->where('beneficio_ativo', true)
                ->where('cartao_entregue', false)
                ->update(['status_adesao' => 'beneficio_ativo']);

            \Illuminate\Support\Facades\DB::table('colaborador_beneficios')
                ->where('cartao_entregue', false)
                ->where('beneficio_ativo', false)
                ->update(['status_adesao' => 'adesao_automatica']);
        }
    }

    public function down(): void
    {
        Schema::table('colaborador_beneficios', function (Blueprint $table) {
            if (Schema::hasColumn('colaborador_beneficios', 'adesao_atualizado_por_id')) {
                $table->dropForeign('cb_adesao_user_fk');
                $table->dropColumn('adesao_atualizado_por_id');
            }
            foreach (['data_previsao_cartao', 'data_retorno_matriz', 'protocolo_matriz', 'data_envio_matriz', 'data_formulario_recebido', 'status_adesao'] as $col) {
                if (Schema::hasColumn('colaborador_beneficios', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('beneficios', function (Blueprint $table) {
            foreach (['exige_formulario_colaborador', 'adesao_automatica_admissao', 'requer_controle_adesao'] as $col) {
                if (Schema::hasColumn('beneficios', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
