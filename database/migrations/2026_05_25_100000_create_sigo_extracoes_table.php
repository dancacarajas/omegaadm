<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sigo_extracoes', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('sigo_usuario', 120);
            $table->text('sigo_senha_criptografada')->nullable();
            $table->string('status', 32)->default('pendente');
            $table->unsignedInteger('paginas_lidas')->default(0);
            $table->unsignedInteger('registros_brutos')->default(0);
            $table->unsignedInteger('registros_unicos')->default(0);
            $table->string('diretorio_relativo')->nullable();
            $table->text('erro_tecnico')->nullable();
            $table->text('erro_usuario')->nullable();
            $table->timestamp('iniciado_em')->nullable();
            $table->timestamp('finalizado_em')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sigo_extracoes');
    }
};
