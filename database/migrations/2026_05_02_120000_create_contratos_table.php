<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contratos', function (Blueprint $table) {
            $table->id();
            $table->string('numero')->unique();
            $table->string('nome');
            $table->string('cliente')->nullable();
            $table->string('contratada')->nullable();
            $table->text('objeto')->nullable();
            $table->string('tipo', 80)->nullable();
            $table->string('centro_custo', 120)->nullable();
            $table->string('local_execucao')->nullable();
            $table->string('gestor')->nullable();
            $table->string('fiscal')->nullable();
            $table->date('data_inicio')->nullable();
            $table->date('data_fim')->nullable();
            $table->decimal('valor', 14, 2)->nullable();
            $table->string('status', 40)->default('ativo')->index();
            $table->text('descricao')->nullable();
            $table->text('observacoes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contratos');
    }
};
