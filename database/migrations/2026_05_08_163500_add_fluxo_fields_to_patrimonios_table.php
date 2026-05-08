<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patrimonios', function (Blueprint $table) {
            $table->json('fluxo_state')->nullable()->after('observacoes');
            $table->string('fluxo_step', 60)->nullable()->after('fluxo_state');
        });
    }

    public function down(): void
    {
        Schema::table('patrimonios', function (Blueprint $table) {
            $table->dropColumn(['fluxo_state', 'fluxo_step']);
        });
    }
};

