<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ssma_tst_registros', function (Blueprint $table) {
            $table->string('origem', 32)->default('sistema')->after('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('ssma_tst_registros', function (Blueprint $table) {
            $table->dropColumn('origem');
        });
    }
};
