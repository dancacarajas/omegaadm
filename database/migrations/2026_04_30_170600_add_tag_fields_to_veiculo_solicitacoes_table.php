<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('veiculo_solicitacoes', function (Blueprint $table) {
            $table->json('tag_checklist_data')->nullable()->after('documentos_adicionais');
            $table->string('tag_numero_protocolo')->nullable()->after('tag_checklist_data');
            $table->date('tag_data_solicitacao')->nullable()->after('tag_numero_protocolo');
            $table->string('tag_evidencia_path')->nullable()->after('tag_data_solicitacao');
            $table->text('tag_observacoes')->nullable()->after('tag_evidencia_path');
        });
    }

    public function down(): void
    {
        Schema::table('veiculo_solicitacoes', function (Blueprint $table) {
            $table->dropColumn([
                'tag_checklist_data',
                'tag_numero_protocolo',
                'tag_data_solicitacao',
                'tag_evidencia_path',
                'tag_observacoes',
            ]);
        });
    }
};
