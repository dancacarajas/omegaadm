<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $modulo = [
            'visualizar' => true,
            'criar' => true,
            'editar' => true,
            'excluir' => true,
        ];

        foreach (DB::table('perfis')->get(['id', 'nome', 'permissoes']) as $perfil) {
            $permissoes = json_decode($perfil->permissoes, true);
            if (! is_array($permissoes)) {
                $permissoes = [];
            }

            if (isset($permissoes['configuracoes'])) {
                continue;
            }

            if ($perfil->nome === 'Administrador' || ! empty($permissoes['acessos']['editar'])) {
                $permissoes['configuracoes'] = $modulo;
                DB::table('perfis')->where('id', $perfil->id)->update([
                    'permissoes' => json_encode($permissoes),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        foreach (DB::table('perfis')->get(['id', 'permissoes']) as $perfil) {
            $permissoes = json_decode($perfil->permissoes, true);
            if (! is_array($permissoes) || ! isset($permissoes['configuracoes'])) {
                continue;
            }

            unset($permissoes['configuracoes']);
            DB::table('perfis')->where('id', $perfil->id)->update([
                'permissoes' => json_encode($permissoes),
                'updated_at' => now(),
            ]);
        }
    }
};
