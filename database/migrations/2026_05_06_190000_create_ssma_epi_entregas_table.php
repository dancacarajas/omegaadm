<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ssma_epi_entregas', function (Blueprint $table) {
            $table->id();
            $table->string('colaborador', 255);
            $table->string('cargo', 255)->nullable();
            $table->string('epi_obrigatorio', 500);
            $table->string('ca_numero', 120)->nullable();
            $table->date('validade_ca')->nullable();
            $table->date('data_entrega')->nullable();
            $table->date('data_substituicao')->nullable();
            $table->string('status', 40)->default('pendente');
            $table->string('evidencia_path')->nullable();
            $table->text('observacao')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('colaborador');
            $table->index('validade_ca');
            $table->index('data_entrega');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ssma_epi_entregas');
    }
};
