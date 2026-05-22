<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rh_movimentacao_chamados', function (Blueprint $table) {
            $table->foreignId('chamado_origem_id')
                ->nullable()
                ->after('colaborador_id')
                ->constrained('rh_movimentacao_chamados')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('rh_movimentacao_chamados', function (Blueprint $table) {
            $table->dropConstrainedForeignId('chamado_origem_id');
        });
    }
};
