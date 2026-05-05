<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('perfis')
            ->select(['id', 'permissoes'])
            ->orderBy('id')
            ->chunkById(200, function ($perfis) {
                foreach ($perfis as $perfil) {
                    $permissoes = json_decode($perfil->permissoes ?? '{}', true);
                    if (! is_array($permissoes)) {
                        $permissoes = [];
                    }

                    if (isset($permissoes['medicao']) && is_array($permissoes['medicao'])) {
                        continue;
                    }

                    $base = $permissoes['dashboard'] ?? null;
                    $permissoes['medicao'] = is_array($base)
                        ? [
                            'visualizar' => (bool) ($base['visualizar'] ?? true),
                            'criar' => (bool) ($base['criar'] ?? true),
                            'editar' => (bool) ($base['editar'] ?? true),
                            'excluir' => (bool) ($base['excluir'] ?? true),
                        ]
                        : [
                            'visualizar' => true,
                            'criar' => true,
                            'editar' => true,
                            'excluir' => true,
                        ];

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
                    if (! is_array($permissoes) || ! array_key_exists('medicao', $permissoes)) {
                        continue;
                    }

                    unset($permissoes['medicao']);

                    DB::table('perfis')
                        ->where('id', $perfil->id)
                        ->update(['permissoes' => json_encode($permissoes)]);
                }
            });
    }
};
