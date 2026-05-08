<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patrimonial_histograma_linhas', function (Blueprint $table) {
            $table->id();
            $table->string('contrato');
            $table->date('competencia');
            $table->string('tipo_linha', 20)->default('item');
            $table->unsignedInteger('ordem')->default(0);
            $table->string('item_codigo', 30)->nullable();
            $table->string('descricao');
            $table->string('unidade', 20)->nullable();
            $table->decimal('mobilizacao', 10, 2)->default(0);
            $table->decimal('pre_pgu', 10, 2)->default(0);
            $table->decimal('pgu', 10, 2)->default(0);
            $table->decimal('pos_pgu', 10, 2)->default(0);
            $table->decimal('desmobilizacao', 10, 2)->default(0);
            $table->timestamps();

            $table->index(['contrato', 'competencia']);
        });

        Schema::create('patrimonial_histograma_recortes', function (Blueprint $table) {
            $table->id();
            $table->string('contrato');
            $table->date('competencia');
            $table->date('inicio_monitoramento')->nullable();
            $table->date('data_limite_etapa_2')->nullable();
            $table->timestamps();

            $table->unique(['contrato', 'competencia']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patrimonial_histograma_recortes');
        Schema::dropIfExists('patrimonial_histograma_linhas');
    }
};

