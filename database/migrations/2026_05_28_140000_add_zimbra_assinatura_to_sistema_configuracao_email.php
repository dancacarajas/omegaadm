<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sistema_configuracao_email', function (Blueprint $table) {
            if (! Schema::hasColumn('sistema_configuracao_email', 'zimbra_assinatura')) {
                $table->json('zimbra_assinatura')->nullable()->after('beneficio_adesao_copia_email');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sistema_configuracao_email', function (Blueprint $table) {
            if (Schema::hasColumn('sistema_configuracao_email', 'zimbra_assinatura')) {
                $table->dropColumn('zimbra_assinatura');
            }
        });
    }
};
