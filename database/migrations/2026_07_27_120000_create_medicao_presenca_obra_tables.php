<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('colaboradores', function (Blueprint $table) {
            $table->boolean('presenca_obra_liberado')->default(false)->after('status');
        });

        Schema::create('medicao_presenca_obra_registros', function (Blueprint $table) {
            $table->id();
            $table->date('data')->index();
            $table->foreignId('colaborador_id')->constrained('colaboradores')->cascadeOnDelete();
            $table->string('status', 20); // presente | ausente
            $table->foreignId('confirmado_por_id')->constrained('colaboradores')->cascadeOnDelete();
            $table->string('centro_custo', 80)->nullable()->index();
            $table->string('observacao', 500)->nullable();
            $table->timestamp('confirmado_em')->nullable();
            $table->timestamps();

            $table->unique(['data', 'colaborador_id'], 'presenca_obra_data_colab_unique');
            $table->index(['data', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medicao_presenca_obra_registros');

        Schema::table('colaboradores', function (Blueprint $table) {
            $table->dropColumn('presenca_obra_liberado');
        });
    }
};
