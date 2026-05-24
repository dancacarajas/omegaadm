<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (DB::table('perfis')->where('nome', 'Administrador')->get(['id', 'permissoes']) as $perfil) {
            $permissoes = json_decode($perfil->permissoes ?? '{}', true);
            if (! is_array($permissoes)) {
                $permissoes = [];
            }
            if (! isset($permissoes['almoxarifado']) || ! is_array($permissoes['almoxarifado'])) {
                $permissoes['almoxarifado'] = [
                    'visualizar' => true,
                    'criar' => true,
                    'editar' => true,
                    'excluir' => true,
                ];
            }
            $permissoes['almoxarifado']['papel'] = 'gestao';
            DB::table('perfis')->where('id', $perfil->id)->update(['permissoes' => json_encode($permissoes)]);
        }
    }

    public function down(): void
    {
        // noop
    }
};
