<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('beneficio_extrato_regras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('beneficio_id')->constrained('beneficios')->cascadeOnDelete();
            $table->string('tipo_regra', 40);
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->unique('beneficio_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beneficio_extrato_regras');
    }
};
