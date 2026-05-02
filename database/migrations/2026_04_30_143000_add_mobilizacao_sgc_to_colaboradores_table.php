<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('colaboradores', function (Blueprint $table) {
            $table->string('mobilizacao_status', 60)->default('pendente')->after('status');
            $table->date('sgc_data_postagem')->nullable()->after('mobilizacao_status');
            $table->string('sgc_numero_solicitacao', 80)->nullable()->after('sgc_data_postagem');
            $table->date('sgc_data_aprovacao')->nullable()->after('sgc_numero_solicitacao');
            $table->date('sgc_data_entrega_cracha')->nullable()->after('sgc_data_aprovacao');
            $table->text('sgc_observacoes')->nullable()->after('sgc_data_entrega_cracha');
        });
    }

    public function down(): void
    {
        Schema::table('colaboradores', function (Blueprint $table) {
            $table->dropColumn([
                'mobilizacao_status',
                'sgc_data_postagem',
                'sgc_numero_solicitacao',
                'sgc_data_aprovacao',
                'sgc_data_entrega_cracha',
                'sgc_observacoes',
            ]);
        });
    }
};
