<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('colaborador_id')
                ->nullable()
                ->after('perfil_id')
                ->constrained('colaboradores')
                ->nullOnDelete();

            $table->unique('colaborador_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['colaborador_id']);
            $table->dropUnique(['colaborador_id']);
            $table->dropColumn('colaborador_id');
        });
    }
};
