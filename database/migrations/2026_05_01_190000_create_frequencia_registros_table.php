<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('frequencia_registros', function (Blueprint $table) {
            $table->id();
            $table->foreignId('colaborador_id')->constrained('colaboradores')->cascadeOnDelete();
            $table->date('data');
            $table->time('entrada_1')->nullable();
            $table->time('saida_1')->nullable();
            $table->time('entrada_2')->nullable();
            $table->time('saida_2')->nullable();
            $table->string('status', 30)->default('falta')->index();
            $table->string('origem', 30)->default('manual');
            $table->string('justificativa_tipo')->nullable();
            $table->text('justificativa_texto')->nullable();
            $table->string('anexo_path')->nullable();
            $table->timestamp('importado_em')->nullable();
            $table->timestamps();

            $table->unique(['colaborador_id', 'data']);
            $table->index(['data', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('frequencia_registros');
    }
};
