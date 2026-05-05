<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contrato_histograma_linhas', function (Blueprint $table) {
            $table->id();
            $table->string('contrato')->index();
            $table->date('competencia')->index();
            $table->string('tipo_linha', 15)->default('item');
            $table->unsignedInteger('ordem')->default(0);
            $table->string('item_codigo', 30)->nullable();
            $table->string('descricao', 255);
            $table->string('unidade', 20)->default('Unid.');
            $table->decimal('mobilizacao', 12, 2)->default(0);
            $table->decimal('pre_pgu', 12, 2)->default(0);
            $table->decimal('pgu', 12, 2)->default(0);
            $table->decimal('pos_pgu', 12, 2)->default(0);
            $table->decimal('desmobilizacao', 12, 2)->default(0);
            $table->timestamps();

            $table->index(['contrato', 'competencia', 'ordem']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contrato_histograma_linhas');
    }
};
