<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('veiculo_solicitacoes', function (Blueprint $table) {
            $table->string('subcontratacao_cartao_cnpj_path')->nullable()->after('subcontratacao_protocolo');
            $table->string('subcontratacao_minuta_path')->nullable()->after('subcontratacao_cartao_cnpj_path');
            $table->string('subcontratacao_contrato_social_path')->nullable()->after('subcontratacao_minuta_path');
            $table->string('subcontratacao_documento_veiculo_path')->nullable()->after('subcontratacao_contrato_social_path');
        });
    }

    public function down(): void
    {
        Schema::table('veiculo_solicitacoes', function (Blueprint $table) {
            $table->dropColumn([
                'subcontratacao_cartao_cnpj_path',
                'subcontratacao_minuta_path',
                'subcontratacao_contrato_social_path',
                'subcontratacao_documento_veiculo_path',
            ]);
        });
    }
};
