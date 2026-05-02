<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('veiculo_solicitacoes', function (Blueprint $table) {
            $table->string('placa', 20)->nullable()->after('checklist_data');
            $table->string('renavam')->nullable()->after('placa');
            $table->string('tipo')->nullable()->after('renavam');
            $table->string('marca')->nullable()->after('tipo');
            $table->string('modelo')->nullable()->after('marca');
            $table->string('ano_fabricacao', 4)->nullable()->after('modelo');
            $table->string('ano_modelo', 4)->nullable()->after('ano_fabricacao');
            $table->string('cor')->nullable()->after('ano_modelo');
            $table->string('proprietario')->nullable()->after('cor');
            $table->string('fornecedor')->nullable()->after('proprietario');
            $table->string('crlv_path')->nullable()->after('fornecedor');
            $table->json('documentos_adicionais')->nullable()->after('crlv_path');
        });
    }

    public function down(): void
    {
        Schema::table('veiculo_solicitacoes', function (Blueprint $table) {
            $table->dropColumn([
                'placa',
                'renavam',
                'tipo',
                'marca',
                'modelo',
                'ano_fabricacao',
                'ano_modelo',
                'cor',
                'proprietario',
                'fornecedor',
                'crlv_path',
                'documentos_adicionais',
            ]);
        });
    }
};
