<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('veiculo_solicitacoes', function (Blueprint $table) {
            $table->json('svg_checklist_data')->nullable()->after('subcontratacao_observacoes');
            $table->date('svg_data_postagem')->nullable()->after('svg_checklist_data');
            $table->string('svg_protocolo')->nullable()->after('svg_data_postagem');
            $table->string('svg_evidencia_path')->nullable()->after('svg_protocolo');
            $table->text('svg_observacoes')->nullable()->after('svg_evidencia_path');
        });
    }

    public function down(): void
    {
        Schema::table('veiculo_solicitacoes', function (Blueprint $table) {
            $table->dropColumn([
                'svg_checklist_data',
                'svg_data_postagem',
                'svg_protocolo',
                'svg_evidencia_path',
                'svg_observacoes',
            ]);
        });
    }
};
