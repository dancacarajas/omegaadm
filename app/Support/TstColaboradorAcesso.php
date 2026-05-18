<?php

namespace App\Support;

use App\Models\Colaborador;
use App\Models\User;

class TstColaboradorAcesso
{
    /** Perfis que veem todas as atividades ativas no app /registro-tst. */
    public const PERFIS_TODAS_ATIVIDADES_APP = [
        'SSMA',
        'Administrador',
    ];

    /**
     * Colaborador com usuário ativo vinculado (colaborador_id ou mesmo nome) e perfil SSMA/Administrador.
     */
    public static function veTodasAtividadesNoApp(Colaborador $colaborador): bool
    {
        $user = self::usuarioSistemaDoColaborador($colaborador);
        if ($user === null) {
            return false;
        }

        if (! $user->perfil_id) {
            return false;
        }

        $perfil = $user->relationLoaded('perfil') ? $user->perfil : $user->perfil()->first();
        if ($perfil === null || ! $perfil->ativo) {
            return false;
        }

        return in_array($perfil->nome, self::PERFIS_TODAS_ATIVIDADES_APP, true);
    }

    public static function usuarioSistemaDoColaborador(Colaborador $colaborador): ?User
    {
        $porVinculo = User::query()
            ->where('status', 'ativo')
            ->where('colaborador_id', $colaborador->id)
            ->with('perfil')
            ->first();

        if ($porVinculo !== null) {
            return $porVinculo;
        }

        $nome = mb_strtolower(trim((string) $colaborador->nome));
        if ($nome === '') {
            return null;
        }

        return User::query()
            ->where('status', 'ativo')
            ->whereRaw('LOWER(name) = ?', [$nome])
            ->with('perfil')
            ->first();
    }
}
