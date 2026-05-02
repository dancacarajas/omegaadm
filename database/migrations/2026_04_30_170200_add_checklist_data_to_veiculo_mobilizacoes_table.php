<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('veiculo_mobilizacoes', function (Blueprint $table) {
            $table->json('checklist_data')->nullable()->after('link_evidencia');
        });
    }

    public function down(): void
    {
        Schema::table('veiculo_mobilizacoes', function (Blueprint $table) {
            $table->dropColumn('checklist_data');
        });
    }
};
