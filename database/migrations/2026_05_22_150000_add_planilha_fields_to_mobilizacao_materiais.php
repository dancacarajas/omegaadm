<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mobilizacao_materiais', function (Blueprint $table) {
            $table->string('disciplina', 80)->nullable()->after('categoria_id');
            $table->string('categoria_descricao', 120)->nullable()->after('disciplina');
            $table->string('situacao_tratativa', 120)->nullable()->after('categoria_descricao');
            $table->string('situacao_sigo_descricao', 120)->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('mobilizacao_materiais', function (Blueprint $table) {
            $table->dropColumn([
                'disciplina',
                'categoria_descricao',
                'situacao_tratativa',
                'situacao_sigo_descricao',
            ]);
        });
    }
};
