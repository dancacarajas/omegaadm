<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sistema_emails_enviados', function (Blueprint $table) {
            $table->id();
            $table->string('categoria', 32);
            $table->string('tipo', 64);
            $table->string('nome', 255)->nullable();
            $table->string('assunto', 500);
            $table->string('mailer', 48)->nullable();
            $table->string('from_address')->nullable();
            $table->string('from_name')->nullable();
            $table->string('destinatario');
            $table->unsignedTinyInteger('anexos_qtd')->default(0);
            $table->string('referencia_tipo', 64)->nullable();
            $table->unsignedBigInteger('referencia_id')->nullable();
            $table->foreignId('enviado_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 16)->default('enviado');
            $table->timestamp('enviado_em');
            $table->timestamps();

            $table->index(['categoria', 'tipo']);
            $table->index('destinatario');
            $table->index('enviado_em');
            $table->index(['referencia_tipo', 'referencia_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sistema_emails_enviados');
    }
};
