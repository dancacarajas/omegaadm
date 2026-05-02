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
        Schema::create('colaboradores', function (Blueprint $table) {
            $table->id();
            $table->string('matricula')->nullable()->unique();
            $table->string('nome');
            $table->string('foto_path')->nullable();
            $table->string('filiacao_pai')->nullable();
            $table->string('filiacao_mae')->nullable();
            $table->string('cpf', 20)->nullable()->index();
            $table->string('rg', 30)->nullable();
            $table->string('carteira_profissional', 40)->nullable();
            $table->string('serie_ctps', 20)->nullable();
            $table->string('pis', 30)->nullable();
            $table->string('titulo_eleitor', 40)->nullable();
            $table->string('zona_eleitoral', 20)->nullable();
            $table->string('secao_eleitoral', 20)->nullable();
            $table->string('carteira_identidade', 40)->nullable();
            $table->date('emissao_identidade')->nullable();
            $table->string('orgao_emissor', 40)->nullable();
            $table->date('data_ctps')->nullable();
            $table->date('vencimento_ctps')->nullable();
            $table->date('data_nascimento')->nullable();
            $table->string('estado_civil', 40)->nullable();
            $table->string('conjuge')->nullable();
            $table->string('local_nascimento')->nullable();
            $table->string('sexo', 30)->nullable();
            $table->string('grau_instrucao')->nullable();
            $table->string('uf_nascimento', 2)->nullable();
            $table->string('cor', 40)->nullable();
            $table->string('nacionalidade', 80)->nullable();
            $table->string('endereco')->nullable();
            $table->string('numero', 20)->nullable();
            $table->string('bairro')->nullable();
            $table->string('cidade')->nullable();
            $table->string('estado', 2)->nullable();
            $table->string('cep', 20)->nullable();
            $table->string('tipo_contrato', 80)->nullable();
            $table->string('departamento')->nullable();
            $table->string('cargo')->nullable();
            $table->string('cbo', 30)->nullable();
            $table->string('centro_custo', 80)->nullable();
            $table->string('jornada_semanal', 40)->nullable();
            $table->string('horario')->nullable();
            $table->date('data_admissao')->nullable();
            $table->date('data_opcao_fgts')->nullable();
            $table->date('data_demissao')->nullable();
            $table->string('forma_pagamento', 80)->nullable();
            $table->decimal('salario_inicial', 12, 2)->nullable();
            $table->string('local_trabalho')->nullable();
            $table->string('almoco', 80)->nullable();
            $table->string('status', 40)->default('ativo');
            $table->text('dependentes')->nullable();
            $table->string('contato_emergencia_nome')->nullable();
            $table->string('contato_emergencia_telefone', 40)->nullable();
            $table->string('contato_emergencia_parentesco', 80)->nullable();
            $table->text('observacoes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('colaboradores');
    }
};
