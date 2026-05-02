<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patrimonios', function (Blueprint $table) {
            $table->id();
            $table->string('tag_patrimonial')->unique();
            $table->string('nome');
            $table->string('categoria')->nullable();
            $table->string('tipo')->nullable();
            $table->string('marca')->nullable();
            $table->string('modelo')->nullable();
            $table->string('numero_serie')->nullable()->index();
            $table->string('contrato')->nullable()->index();
            $table->string('centro_custo')->nullable();
            $table->string('fornecedor')->nullable();
            $table->date('data_aquisicao')->nullable();
            $table->date('data_entrada')->nullable();
            $table->decimal('valor', 12, 2)->nullable();
            $table->string('responsavel')->nullable();
            $table->string('setor')->nullable();
            $table->string('localizacao')->nullable();
            $table->string('status', 40)->default('ativo')->index();
            $table->string('condicao', 40)->default('bom');
            $table->date('ultima_conferencia')->nullable();
            $table->date('proxima_conferencia')->nullable();
            $table->text('observacoes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patrimonios');
    }
};
