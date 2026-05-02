<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('users')->exists()) {
            return;
        }

        $perfilId = DB::table('perfis')->where('nome', 'Administrador')->value('id');

        DB::table('users')->insert([
            'perfil_id' => $perfilId,
            'todos_contratos' => true,
            'name' => 'Administrador',
            'email' => 'admin@omega286.local',
            'telefone' => null,
            'cargo' => 'Administrador do sistema',
            'password' => Hash::make('123456'),
            'status' => 'ativo',
            'email_verified_at' => now(),
            'remember_token' => null,
            'ultimo_acesso_em' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('users')->where('email', 'admin@omega286.local')->delete();
    }
};
