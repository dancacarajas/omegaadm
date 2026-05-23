<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        if (! Schema::hasTable('colaborador_beneficio_webcard_solicitacoes')) {
            return;
        }

        Schema::table('colaborador_beneficio_webcard_solicitacoes', function (Blueprint $table) {
            if (! $this->foreignExists('colaborador_beneficio_webcard_solicitacoes', 'webcard_sol_vinculo_fk')) {
                $table->foreign('colaborador_beneficio_id', 'webcard_sol_vinculo_fk')
                    ->references('id')->on('colaborador_beneficios')->cascadeOnDelete();
            }
            if (! $this->foreignExists('colaborador_beneficio_webcard_solicitacoes', 'webcard_sol_user_fk')) {
                $table->foreign('registrado_por_id', 'webcard_sol_user_fk')
                    ->references('id')->on('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('colaborador_beneficio_webcard_solicitacoes')) {
            return;
        }

        Schema::table('colaborador_beneficio_webcard_solicitacoes', function (Blueprint $table) {
            $table->dropForeign('webcard_sol_vinculo_fk');
            $table->dropForeign('webcard_sol_user_fk');
        });
    }

    private function foreignExists(string $table, string $name): bool
    {
        $connection = Schema::getConnection();
        $database = $connection->getDatabaseName();
        $result = $connection->selectOne(
            'SELECT COUNT(*) AS c FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_NAME = ?',
            [$database, $table, $name]
        );

        return (int) ($result->c ?? 0) > 0;
    }
};
