<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('colaborador_beneficios')) {
            return;
        }

        Schema::table('colaborador_beneficios', function (Blueprint $table) {
            if (! Schema::hasColumn('colaborador_beneficios', 'formulario_adesao_assinado_path')) {
                $table->string('formulario_adesao_assinado_path', 500)->nullable()->after('data_formulario_recebido');
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('colaborador_beneficios', 'formulario_adesao_assinado_path')) {
            Schema::table('colaborador_beneficios', function (Blueprint $table) {
                $table->dropColumn('formulario_adesao_assinado_path');
            });
        }
    }
};
