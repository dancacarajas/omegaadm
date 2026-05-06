<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contrato_histograma_linhas', function (Blueprint $table) {
            $table->string('acao_recomendada', 255)->nullable()->after('descricao');
            $table->string('responsavel', 120)->nullable()->after('acao_recomendada');
        });
    }

    public function down(): void
    {
        Schema::table('contrato_histograma_linhas', function (Blueprint $table) {
            $table->dropColumn(['acao_recomendada', 'responsavel']);
        });
    }
};
