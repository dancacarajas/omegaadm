<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ssma_registro_mensal_prazos', function (Blueprint $table) {
            $table->id();
            $table->date('competencia')->unique();
            $table->dateTime('data_limite');
            $table->boolean('exige_finalizado')->default(true);
            $table->string('observacao', 500)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ssma_registro_mensal_prazos');
    }
};
