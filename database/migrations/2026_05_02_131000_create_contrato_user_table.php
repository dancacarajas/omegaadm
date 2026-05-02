<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contrato_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contrato_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'contrato_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('todos_contratos')->default(false)->after('perfil_id');
        });

        DB::table('users')->update(['todos_contratos' => true]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('todos_contratos');
        });

        Schema::dropIfExists('contrato_user');
    }
};
