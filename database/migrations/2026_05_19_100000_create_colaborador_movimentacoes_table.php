<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('colaborador_movimentacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('colaborador_id')->constrained('colaboradores')->cascadeOnDelete();
            $table->string('tipo', 40);
            $table->date('data_inicio');
            $table->date('data_fim')->nullable();
            $table->string('status_anterior', 20)->nullable();
            $table->string('status_novo', 20)->nullable();
            $table->string('centro_custo_anterior', 80)->nullable();
            $table->string('centro_custo_novo', 80)->nullable();
            $table->string('tipo_contrato_anterior', 80)->nullable();
            $table->string('tipo_contrato_novo', 80)->nullable();
            $table->string('local_trabalho_anterior', 255)->nullable();
            $table->string('local_trabalho_novo', 255)->nullable();
            $table->string('departamento_anterior', 255)->nullable();
            $table->string('departamento_novo', 255)->nullable();
            $table->string('cargo_anterior', 255)->nullable();
            $table->string('cargo_novo', 255)->nullable();
            $table->decimal('salario_anterior', 12, 2)->nullable();
            $table->decimal('salario_novo', 12, 2)->nullable();
            $table->string('tipo_rescisao', 80)->nullable();
            $table->string('motivo_codigo', 40)->nullable();
            $table->string('motivo_texto', 500)->nullable();
            $table->string('especie_beneficio_inss', 80)->nullable();
            $table->string('cid', 20)->nullable();
            $table->unsignedSmallInteger('dias_ferias')->nullable();
            $table->boolean('abono_pecuniario')->default(false);
            $table->foreignId('registrado_por_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('observacoes')->nullable();
            $table->timestamps();

            $table->index(['colaborador_id', 'tipo', 'data_inicio']);
            $table->index(['tipo', 'data_inicio']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('colaborador_movimentacoes');
    }
};
