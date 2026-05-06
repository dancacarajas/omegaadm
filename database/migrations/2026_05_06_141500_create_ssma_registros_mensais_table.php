<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ssma_registros_mensais', function (Blueprint $table) {
            $table->id();
            $table->date('competencia');
            $table->string('titulo', 255)->nullable();
            $table->string('responsavel', 255)->nullable();
            $table->string('status', 30)->default('rascunho');
            $table->json('etapas')->nullable();
            $table->timestamps();

            $table->index('competencia');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ssma_registros_mensais');
    }
};
