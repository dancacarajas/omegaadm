<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sesmt_tarefas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('colaborador_id')->constrained('colaboradores')->cascadeOnDelete();
            $table->string('tipo', 80);
            $table->string('status', 40)->default('pendente');
            $table->date('data_prevista')->nullable();
            $table->date('data_conclusao')->nullable();
            $table->string('responsavel')->nullable();
            $table->text('observacoes')->nullable();
            $table->timestamps();

            $table->unique(['colaborador_id', 'tipo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sesmt_tarefas');
    }
};
