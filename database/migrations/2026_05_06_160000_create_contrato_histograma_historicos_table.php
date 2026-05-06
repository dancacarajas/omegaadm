<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contrato_histograma_historicos', function (Blueprint $table) {
            $table->id();
            $table->string('contrato', 255);
            $table->date('competencia');
            $table->date('snapshot_date');
            $table->unsignedInteger('total_functions')->default(0);
            $table->decimal('completed', 12, 2)->default(0);
            $table->decimal('pending', 12, 2)->default(0);
            $table->decimal('progress', 5, 2)->default(0);
            $table->timestamps();

            $table->unique(['contrato', 'competencia', 'snapshot_date'], 'hist_historico_unique');
            $table->index(['contrato', 'competencia'], 'hist_historico_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contrato_histograma_historicos');
    }
};

