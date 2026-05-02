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
        Schema::create('colaborador_beneficios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('colaborador_id')->constrained('colaboradores')->cascadeOnDelete();
            $table->foreignId('beneficio_id')->constrained('beneficios')->cascadeOnDelete();
            $table->boolean('tem_direito')->default(true);
            $table->boolean('cartao_entregue')->default(false);
            $table->boolean('beneficio_ativo')->default(false);
            $table->date('data_direito')->nullable();
            $table->date('data_entrega_cartao')->nullable();
            $table->string('numero_cartao')->nullable();
            $table->text('observacoes')->nullable();
            $table->timestamps();

            $table->unique(['colaborador_id', 'beneficio_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('colaborador_beneficios');
    }
};
