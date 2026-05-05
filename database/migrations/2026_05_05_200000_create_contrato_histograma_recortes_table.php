<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contrato_histograma_recortes', function (Blueprint $table) {
            $table->id();
            $table->string('contrato')->index();
            $table->date('competencia')->index();
            $table->date('data_limite_etapa_2')->nullable();
            $table->timestamps();

            $table->unique(['contrato', 'competencia']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contrato_histograma_recortes');
    }
};
