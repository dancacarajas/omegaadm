<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Evita join()->update() do query builder: no SQLite o SET não resolve colaboradores.data_admissao
        // (quebra phpunit com DB sqlite :memory:). Subquery correlacionada funciona em MySQL, SQLite e MariaDB.
        DB::statement('
            UPDATE colaborador_beneficios
            SET data_direito = (
                SELECT c.data_admissao
                FROM colaboradores c
                WHERE c.id = colaborador_beneficios.colaborador_id
                LIMIT 1
            )
            WHERE data_direito IS NULL
            AND EXISTS (
                SELECT 1
                FROM colaboradores c2
                WHERE c2.id = colaborador_beneficios.colaborador_id
                AND c2.data_admissao IS NOT NULL
            )
        ');
    }

    public function down(): void
    {
        //
    }
};
