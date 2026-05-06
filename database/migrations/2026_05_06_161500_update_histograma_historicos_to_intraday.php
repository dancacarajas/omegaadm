<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contrato_histograma_historicos', function (Blueprint $table) {
            $table->dateTime('snapshot_at')->nullable()->after('snapshot_date');
        });

        DB::table('contrato_histograma_historicos')
            ->whereNull('snapshot_at')
            ->update([
                'snapshot_at' => DB::raw("CONCAT(snapshot_date, ' 12:00:00')"),
            ]);

        Schema::table('contrato_histograma_historicos', function (Blueprint $table) {
            $table->dropUnique('hist_historico_unique');
            $table->index(['contrato', 'competencia', 'snapshot_at'], 'hist_historico_intraday');
        });
    }

    public function down(): void
    {
        Schema::table('contrato_histograma_historicos', function (Blueprint $table) {
            $table->dropIndex('hist_historico_intraday');
            $table->unique(['contrato', 'competencia', 'snapshot_date'], 'hist_historico_unique');
            $table->dropColumn('snapshot_at');
        });
    }
};

