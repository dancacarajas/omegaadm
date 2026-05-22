<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sistema_configuracao_email', function (Blueprint $table) {
            $table->json('notificacao_registro_tst_emails')->nullable()->after('mail_from_address');
        });
    }

    public function down(): void
    {
        Schema::table('sistema_configuracao_email', function (Blueprint $table) {
            $table->dropColumn('notificacao_registro_tst_emails');
        });
    }
};
