<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ssma_tst_registro_fotos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ssma_tst_registro_id')
                ->constrained('ssma_tst_registros')
                ->cascadeOnDelete();
            $table->string('arquivo_path', 500);
            $table->string('arquivo_nome', 255)->nullable();
            $table->string('arquivo_mime', 120)->nullable();
            $table->unsignedTinyInteger('ordem')->default(0);
            $table->timestamps();

            $table->index(['ssma_tst_registro_id', 'ordem']);
        });

        if (Schema::hasColumn('ssma_tst_registros', 'arquivo_path')) {
            $registros = DB::table('ssma_tst_registros')
                ->whereNotNull('arquivo_path')
                ->where('arquivo_path', '!=', '')
                ->get(['id', 'arquivo_path', 'arquivo_nome', 'arquivo_mime']);

            foreach ($registros as $row) {
                DB::table('ssma_tst_registro_fotos')->insert([
                    'ssma_tst_registro_id' => $row->id,
                    'arquivo_path' => $row->arquivo_path,
                    'arquivo_nome' => $row->arquivo_nome,
                    'arquivo_mime' => $row->arquivo_mime,
                    'ordem' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ssma_tst_registro_fotos');
    }
};
