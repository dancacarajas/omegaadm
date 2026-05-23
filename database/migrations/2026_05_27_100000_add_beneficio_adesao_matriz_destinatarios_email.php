<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sistema_configuracao_email')) {
            return;
        }

        Schema::table('sistema_configuracao_email', function (Blueprint $table) {
            if (! Schema::hasColumn('sistema_configuracao_email', 'notificacao_beneficio_adesao_matriz_destinatarios')) {
                $table->json('notificacao_beneficio_adesao_matriz_destinatarios')
                    ->nullable()
                    ->after('notificacao_registro_tst_destinatarios');
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('sistema_configuracao_email', 'notificacao_beneficio_adesao_matriz_destinatarios')) {
            Schema::table('sistema_configuracao_email', function (Blueprint $table) {
                $table->dropColumn('notificacao_beneficio_adesao_matriz_destinatarios');
            });
        }
    }
};
