<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('colaborador_movimentacoes', function (Blueprint $table) {
            $table->string('situacao', 20)->default('pendente')->after('tipo');
            $table->timestamp('finalizada_em')->nullable()->after('observacoes');
            $table->foreignId('finalizada_por_user_id')->nullable()->after('finalizada_em')->constrained('users')->nullOnDelete();

            $table->index(['situacao', 'tipo']);
        });

        DB::table('colaborador_movimentacoes')->update([
            'situacao' => 'finalizada',
            'finalizada_em' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::table('colaborador_movimentacoes', function (Blueprint $table) {
            $table->dropForeign(['finalizada_por_user_id']);
            $table->dropIndex(['situacao', 'tipo']);
            $table->dropColumn(['situacao', 'finalizada_em', 'finalizada_por_user_id']);
        });
    }
};
