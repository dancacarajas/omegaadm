<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ssma_tst_registros', function (Blueprint $table) {
            $table->dropForeign(['colaborador_id']);
            $table->foreign('colaborador_id')
                ->references('id')
                ->on('colaboradores')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ssma_tst_registros', function (Blueprint $table) {
            $table->dropForeign(['colaborador_id']);
            $table->foreign('colaborador_id')
                ->references('id')
                ->on('colaboradores')
                ->restrictOnDelete();
        });
    }
};
