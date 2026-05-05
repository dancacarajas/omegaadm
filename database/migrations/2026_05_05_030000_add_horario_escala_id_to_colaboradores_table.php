<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('colaboradores', function (Blueprint $table) {
            $table->foreignId('horario_escala_id')
                ->nullable()
                ->after('horario')
                ->constrained('horario_escalas')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('colaboradores', function (Blueprint $table) {
            $table->dropConstrainedForeignId('horario_escala_id');
        });
    }
};
