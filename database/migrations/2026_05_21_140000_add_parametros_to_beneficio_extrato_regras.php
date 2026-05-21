<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('beneficio_extrato_regras', function (Blueprint $table) {
            $table->unsignedSmallInteger('ano_vigencia')->nullable()->after('tipo_regra');
            $table->json('parametros')->nullable()->after('ano_vigencia');
            $table->boolean('configurado')->default(false)->after('parametros');
        });
    }

    public function down(): void
    {
        Schema::table('beneficio_extrato_regras', function (Blueprint $table) {
            $table->dropColumn(['ano_vigencia', 'parametros', 'configurado']);
        });
    }
};
