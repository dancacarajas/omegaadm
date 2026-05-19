<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('frequencia_feriados', function (Blueprint $table) {
            $table->id();
            $table->date('data');
            $table->string('nome');
            $table->boolean('recorrente')->default(false);
            $table->boolean('ativo')->default(true)->index();
            $table->text('observacoes')->nullable();
            $table->timestamps();

            $table->index(['data', 'ativo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('frequencia_feriados');
    }
};
