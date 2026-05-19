<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('frequencia_justificativa_tipos', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('categoria', 30)->default('justificativa')->index();
            $table->boolean('limpa_batidas')->default(true);
            $table->boolean('ativo')->default(true)->index();
            $table->unsignedSmallInteger('ordem')->default(0);
            $table->timestamps();

            $table->unique('nome');
        });

        Schema::table('frequencia_registros', function (Blueprint $table) {
            $table->foreignId('justificativa_tipo_id')
                ->nullable()
                ->after('justificativa_tipo')
                ->constrained('frequencia_justificativa_tipos')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('frequencia_registros', function (Blueprint $table) {
            $table->dropConstrainedForeignId('justificativa_tipo_id');
        });

        Schema::dropIfExists('frequencia_justificativa_tipos');
    }
};
