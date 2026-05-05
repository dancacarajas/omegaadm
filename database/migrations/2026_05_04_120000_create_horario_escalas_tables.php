<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('horario_escalas', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('tipo', 30)->default('semanal');
            $table->string('status', 20)->default('ativo')->index();
            $table->timestamps();
        });

        Schema::create('horario_escala_dias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('horario_escala_id')->constrained('horario_escalas')->cascadeOnDelete();
            $table->unsignedTinyInteger('dia_semana');
            $table->time('entrada_1')->nullable();
            $table->time('saida_1')->nullable();
            $table->time('entrada_2')->nullable();
            $table->time('saida_2')->nullable();
            $table->boolean('almoco_livre')->default(false);
            $table->boolean('compensado')->default(false);
            $table->boolean('neutro')->default(false);
            $table->boolean('noturno')->default(false);
            $table->timestamps();

            $table->unique(['horario_escala_id', 'dia_semana']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('horario_escala_dias');
        Schema::dropIfExists('horario_escalas');
    }
};
