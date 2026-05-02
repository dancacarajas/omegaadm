<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('colaborador_beneficios')
            ->join('colaboradores', 'colaborador_beneficios.colaborador_id', '=', 'colaboradores.id')
            ->whereNull('colaborador_beneficios.data_direito')
            ->whereNotNull('colaboradores.data_admissao')
            ->update([
                'colaborador_beneficios.data_direito' => DB::raw('colaboradores.data_admissao'),
            ]);
    }

    public function down(): void
    {
        //
    }
};
