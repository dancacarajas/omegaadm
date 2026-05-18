<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ssma_tst_atividades', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 255);
            $table->boolean('ativo')->default(true);
            $table->unsignedSmallInteger('ordem')->default(0);
            $table->timestamps();

            $table->unique('nome');
        });

        Schema::create('ssma_tst_registros', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ssma_tst_atividade_id')
                ->nullable()
                ->constrained('ssma_tst_atividades')
                ->nullOnDelete();
            $table->date('data');
            $table->foreignId('colaborador_id')
                ->constrained('colaboradores')
                ->restrictOnDelete();
            $table->text('descricao');
            $table->string('arquivo_path', 500);
            $table->string('arquivo_nome', 255)->nullable();
            $table->string('arquivo_mime', 120)->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();

            $table->index('data');
            $table->index(['colaborador_id', 'data']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ssma_tst_registros');
        Schema::dropIfExists('ssma_tst_atividades');
    }
};
