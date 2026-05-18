<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ssma_tst_atividades', function (Blueprint $table) {
            $table->boolean('exibir_no_app')->default(true)->after('ativo');
        });
    }

    public function down(): void
    {
        Schema::table('ssma_tst_atividades', function (Blueprint $table) {
            $table->dropColumn('exibir_no_app');
        });
    }
};
