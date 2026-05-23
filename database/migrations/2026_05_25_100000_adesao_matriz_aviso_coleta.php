<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('colaborador_beneficios')) {
            return;
        }

        Schema::table('colaborador_beneficios', function (Blueprint $table) {
            if (! Schema::hasColumn('colaborador_beneficios', 'data_aviso_coleta_matriz')) {
                $table->date('data_aviso_coleta_matriz')->nullable()->after('protocolo_matriz');
            }
        });

        if (Schema::hasColumn('colaborador_beneficios', 'data_aviso_coleta_matriz')) {
            $driver = Schema::getConnection()->getDriverName();

            if ($driver === 'sqlite') {
                $rows = DB::table('colaborador_beneficios')->get(['id', 'data_retorno_matriz', 'data_previsao_cartao', 'data_aviso_coleta_matriz']);
                foreach ($rows as $row) {
                    $aviso = $row->data_aviso_coleta_matriz
                        ?? $row->data_retorno_matriz
                        ?? $row->data_previsao_cartao;
                    if ($aviso !== null) {
                        DB::table('colaborador_beneficios')->where('id', $row->id)->update(['data_aviso_coleta_matriz' => $aviso]);
                    }
                }
            } else {
                DB::table('colaborador_beneficios')
                    ->whereNull('data_aviso_coleta_matriz')
                    ->update([
                        'data_aviso_coleta_matriz' => DB::raw('COALESCE(data_retorno_matriz, data_previsao_cartao)'),
                    ]);
            }

            DB::table('colaborador_beneficios')
                ->whereNotNull('data_aviso_coleta_matriz')
                ->where('cartao_entregue', false)
                ->whereIn('status_adesao', ['enviado_matriz', 'aguardando_cartao'])
                ->update(['status_adesao' => 'cartao_disponivel_coleta']);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('colaborador_beneficios', 'data_aviso_coleta_matriz')) {
            Schema::table('colaborador_beneficios', function (Blueprint $table) {
                $table->dropColumn('data_aviso_coleta_matriz');
            });
        }
    }
};
