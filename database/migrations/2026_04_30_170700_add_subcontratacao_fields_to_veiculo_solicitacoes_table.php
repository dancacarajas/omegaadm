<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('veiculo_solicitacoes', function (Blueprint $table) {
            $table->json('subcontratacao_checklist_data')->nullable()->after('tag_observacoes');
            $table->date('subcontratacao_data_analise')->nullable()->after('subcontratacao_checklist_data');
            $table->date('subcontratacao_data_autorizacao')->nullable()->after('subcontratacao_data_analise');
            $table->string('subcontratacao_protocolo')->nullable()->after('subcontratacao_data_autorizacao');
            $table->string('subcontratacao_evidencia_path')->nullable()->after('subcontratacao_protocolo');
            $table->text('subcontratacao_observacoes')->nullable()->after('subcontratacao_evidencia_path');
        });
    }

    public function down(): void
    {
        Schema::table('veiculo_solicitacoes', function (Blueprint $table) {
            $table->dropColumn([
                'subcontratacao_checklist_data',
                'subcontratacao_data_analise',
                'subcontratacao_data_autorizacao',
                'subcontratacao_protocolo',
                'subcontratacao_evidencia_path',
                'subcontratacao_observacoes',
            ]);
        });
    }
};
