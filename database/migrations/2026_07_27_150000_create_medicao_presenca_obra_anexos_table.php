<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medicao_presenca_obra_anexos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registro_id')
                ->constrained('medicao_presenca_obra_registros')
                ->cascadeOnDelete();
            $table->string('nome_original');
            $table->string('caminho');
            $table->string('mime', 120)->nullable();
            $table->unsignedBigInteger('tamanho')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medicao_presenca_obra_anexos');
    }
};
