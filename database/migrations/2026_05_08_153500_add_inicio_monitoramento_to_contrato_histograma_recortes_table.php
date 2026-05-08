<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contrato_histograma_recortes', function (Blueprint $table) {
            $table->date('inicio_monitoramento')->nullable()->after('competencia');
        });
    }

    public function down(): void
    {
        Schema::table('contrato_histograma_recortes', function (Blueprint $table) {
            $table->dropColumn('inicio_monitoramento');
        });
    }
};
