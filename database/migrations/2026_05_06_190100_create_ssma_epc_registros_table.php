<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ssma_epc_registros', function (Blueprint $table) {
            $table->id();
            $table->string('local', 255);
            $table->string('tipo_epc', 255);
            $table->string('condicao', 40);
            $table->boolean('necessita_correcao')->default(false);
            $table->text('risco_associado')->nullable();
            $table->string('responsavel', 255)->nullable();
            $table->date('prazo')->nullable();
            $table->string('evidencia_foto_path')->nullable();
            $table->timestamps();

            $table->index('local');
            $table->index('condicao');
            $table->index('necessita_correcao');
            $table->index('prazo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ssma_epc_registros');
    }
};
