<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('veiculo_solicitacoes', function (Blueprint $table) {
            $table->json('vistoria_checklist_data')->nullable()->after('svg_observacoes');
            $table->date('vistoria_previsao_inicio')->nullable()->after('vistoria_checklist_data');
            $table->date('vistoria_previsao_fim')->nullable()->after('vistoria_previsao_inicio');
            $table->date('vistoria_data_agendada')->nullable()->after('vistoria_previsao_fim');
            $table->string('vistoria_resultado')->nullable()->after('vistoria_data_agendada');
            $table->text('vistoria_observacoes')->nullable()->after('vistoria_resultado');
        });
    }

    public function down(): void
    {
        Schema::table('veiculo_solicitacoes', function (Blueprint $table) {
            $table->dropColumn([
                'vistoria_checklist_data',
                'vistoria_previsao_inicio',
                'vistoria_previsao_fim',
                'vistoria_data_agendada',
                'vistoria_resultado',
                'vistoria_observacoes',
            ]);
        });
    }
};
