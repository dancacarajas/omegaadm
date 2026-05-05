<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medicao_contratual_itens', function (Blueprint $table) {
            $table->id();
            $table->date('competencia')->index();
            $table->string('contrato')->nullable()->index();
            $table->string('item_contratual', 255);
            $table->string('descricao', 255)->nullable();
            $table->decimal('valor_unitario_previsto', 12, 2)->default(0);
            $table->decimal('quantidade_prevista', 12, 2)->default(0);
            $table->decimal('valor_previsto', 14, 2)->default(0);
            $table->decimal('quantidade_medida', 12, 2)->default(0);
            $table->decimal('valor_medido', 14, 2)->default(0);
            $table->decimal('diferenca', 14, 2)->default(0);
            $table->decimal('desvio_percentual', 8, 2)->default(0);
            $table->text('justificativa')->nullable();
            $table->string('evidencia_path')->nullable();

            $table->decimal('valor_glosado', 14, 2)->default(0);
            $table->decimal('valor_nao_executado', 14, 2)->default(0);
            $table->decimal('valor_executado_nao_medido', 14, 2)->default(0);
            $table->decimal('valor_hora_extra', 14, 2)->default(0);
            $table->decimal('valor_adicional', 14, 2)->default(0);
            $table->decimal('valor_mobilizacao', 14, 2)->default(0);
            $table->decimal('valor_nao_programado', 14, 2)->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medicao_contratual_itens');
    }
};
