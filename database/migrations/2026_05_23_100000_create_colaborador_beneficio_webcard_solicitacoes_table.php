<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('colaborador_beneficio_webcard_solicitacoes')) {
            return;
        }

        Schema::create('colaborador_beneficio_webcard_solicitacoes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('colaborador_beneficio_id');
            $table->date('data_solicitacao');
            $table->decimal('valor', 10, 2);
            $table->string('observacao', 500)->nullable();
            $table->unsignedBigInteger('registrado_por_id')->nullable();
            $table->timestamps();

            $table->foreign('colaborador_beneficio_id', 'webcard_sol_vinculo_fk')
                ->references('id')->on('colaborador_beneficios')->cascadeOnDelete();
            $table->foreign('registrado_por_id', 'webcard_sol_user_fk')
                ->references('id')->on('users')->nullOnDelete();
            $table->index(['colaborador_beneficio_id', 'data_solicitacao'], 'webcard_sol_vinculo_data_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('colaborador_beneficio_webcard_solicitacoes');
    }
};
