<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('colaborador_beneficios', 'data_ativacao')) {
            Schema::table('colaborador_beneficios', function (Blueprint $table) {
                $table->dropColumn('data_ativacao');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('colaborador_beneficios', 'data_ativacao')) {
            Schema::table('colaborador_beneficios', function (Blueprint $table) {
                $table->date('data_ativacao')->nullable()->after('data_entrega_cartao');
            });
        }
    }
};
