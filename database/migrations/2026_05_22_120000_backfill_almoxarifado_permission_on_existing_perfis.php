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

        DB::table('perfis')
            ->select(['id', 'nome', 'permissoes'])
            ->orderBy('id')
            ->chunkById(200, function ($perfis) use ($modulo) {
                foreach ($perfis as $perfil) {
                    $permissoes = json_decode($perfil->permissoes ?? '{}', true);
                    if (! is_array($permissoes)) {
                        $permissoes = [];
                    }

                    if (isset($permissoes['almoxarifado']) && is_array($permissoes['almoxarifado'])) {
                        continue;
                    }

                    if ($perfil->nome === 'Administrador') {
                        $permissoes['almoxarifado'] = $modulo;
                    } elseif (! empty($permissoes['patrimonial']['visualizar']) || ! empty($permissoes['patrimonial']['editar'])) {
                        $base = $permissoes['patrimonial'];
                        $permissoes['almoxarifado'] = [
                            'visualizar' => (bool) ($base['visualizar'] ?? false),
                            'criar' => (bool) ($base['criar'] ?? false),
                            'editar' => (bool) ($base['editar'] ?? false),
                            'excluir' => (bool) ($base['excluir'] ?? false),
                        ];
                    } else {
                        continue;
                    }

                    DB::table('perfis')
                        ->where('id', $perfil->id)
                        ->update(['permissoes' => json_encode($permissoes)]);
                }
            });
    }

    public function down(): void
    {
        DB::table('perfis')
            ->select(['id', 'permissoes'])
            ->orderBy('id')
            ->chunkById(200, function ($perfis) {
                foreach ($perfis as $perfil) {
                    $permissoes = json_decode($perfil->permissoes ?? '{}', true);
                    if (! is_array($permissoes) || ! array_key_exists('almoxarifado', $permissoes)) {
                        continue;
                    }

                    unset($permissoes['almoxarifado']);

                    DB::table('perfis')
                        ->where('id', $perfil->id)
                        ->update(['permissoes' => json_encode($permissoes)]);
                }
            });
    }
};
