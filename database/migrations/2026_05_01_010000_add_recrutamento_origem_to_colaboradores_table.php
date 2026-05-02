<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('colaboradores', function (Blueprint $table) {
            $table->string('telefone', 40)->nullable()->after('nome');
            $table->foreignId('recrutamento_vaga_id')->nullable()->after('telefone')->constrained('recrutamento_vagas')->nullOnDelete();
            $table->unsignedInteger('recrutamento_posicao')->nullable()->after('recrutamento_vaga_id');
            $table->unique(['recrutamento_vaga_id', 'recrutamento_posicao'], 'colaboradores_recrutamento_posicao_unique');
        });
    }

    public function down(): void
    {
        Schema::table('colaboradores', function (Blueprint $table) {
            $table->dropUnique('colaboradores_recrutamento_posicao_unique');
            $table->dropConstrainedForeignId('recrutamento_vaga_id');
            $table->dropColumn(['telefone', 'recrutamento_posicao']);
        });
    }
};
