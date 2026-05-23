<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('colaborador_beneficios')) {
            return;
        }

        Schema::table('colaborador_beneficios', function (Blueprint $table) {
            if (! Schema::hasColumn('colaborador_beneficios', 'email_solicitacao_matriz_enviado_em')) {
                $table->timestamp('email_solicitacao_matriz_enviado_em')->nullable()->after('protocolo_matriz');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('colaborador_beneficios')) {
            return;
        }

        if (Schema::hasColumn('colaborador_beneficios', 'email_solicitacao_matriz_enviado_em')) {
            Schema::table('colaborador_beneficios', function (Blueprint $table) {
                $table->dropColumn('email_solicitacao_matriz_enviado_em');
            });
        }
    }
};
