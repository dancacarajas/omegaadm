<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ssma_registros_mensais', function (Blueprint $table) {
            $table->string('contrato', 255)->nullable()->after('responsavel');
            $table->string('local_base', 500)->nullable()->after('contrato');
            $table->unsignedInteger('efetivo_ativo_mes')->nullable()->after('local_base');
            $table->decimal('hht_mes', 15, 2)->nullable()->after('efetivo_ativo_mes');
            $table->text('comentario_executivo')->nullable()->after('hht_mes');
            $table->text('observacoes_gerais_mes')->nullable()->after('comentario_executivo');
        });
    }

    public function down(): void
    {
        Schema::table('ssma_registros_mensais', function (Blueprint $table) {
            $table->dropColumn([
                'contrato',
                'local_base',
                'efetivo_ativo_mes',
                'hht_mes',
                'comentario_executivo',
                'observacoes_gerais_mes',
            ]);
        });
    }
};
