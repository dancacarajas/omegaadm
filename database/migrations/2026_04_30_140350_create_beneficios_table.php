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
        Schema::create('beneficios', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('tipo', 80)->nullable();
            $table->string('fornecedor')->nullable();
            $table->string('codigo', 80)->nullable()->unique();
            $table->decimal('valor', 12, 2)->nullable();
            $table->string('periodicidade', 80)->nullable();
            $table->text('elegibilidade')->nullable();
            $table->string('status', 40)->default('ativo');
            $table->text('descricao')->nullable();
            $table->text('observacoes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('beneficios');
    }
};
