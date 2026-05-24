<?php

namespace App\Support\Almoxarifado;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

final class AlmoxarifadoAcesso
{
    public const PAPEL_ALMOXARIFE = 'almoxarife';

    public const PAPEL_COMPRAS = 'compras';

    public const PAPEL_GESTAO = 'gestao';

    /** @return array<string, string> */
    public static function papeisDefinicao(): array
    {
        return [
            self::PAPEL_ALMOXARIFE => 'Almoxarife',
            self::PAPEL_COMPRAS => 'Compras',
            self::PAPEL_GESTAO => 'Gestão / Coordenação',
        ];
    }

    public static function user(): ?User
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user;
    }

    public static function papel(?User $user = null): string
    {
        $user ??= self::user();
        if (! $user) {
            return self::PAPEL_ALMOXARIFE;
        }

        $user->loadMissing('perfil');
        $perfil = $user->perfil;
        if (! $perfil || ! $perfil->ativo) {
            return self::PAPEL_GESTAO;
        }

        if ($perfil->acessoTotalAoSistema()) {
            return self::PAPEL_GESTAO;
        }

        $papel = data_get($perfil->permissoes, 'almoxarifado.papel');
        if (is_string($papel) && array_key_exists($papel, self::papeisDefinicao())) {
            return $papel;
        }

        if ($user->podeAcaoNoModulo('almoxarifado', 'criar') || $user->podeAcaoNoModulo('almoxarifado', 'excluir')) {
            return self::PAPEL_GESTAO;
        }

        if ($user->podeAcaoNoModulo('almoxarifado', 'editar')) {
            return self::PAPEL_COMPRAS;
        }

        return self::PAPEL_ALMOXARIFE;
    }

    public static function podeVisualizar(?User $user = null): bool
    {
        $user ??= self::user();

        return $user && $user->temQualquerPermissaoNoModulo('almoxarifado');
    }

    public static function isGestao(?User $user = null): bool
    {
        return self::papel($user) === self::PAPEL_GESTAO;
    }

    public static function isCompras(?User $user = null): bool
    {
        $p = self::papel($user);

        return $p === self::PAPEL_COMPRAS || $p === self::PAPEL_GESTAO;
    }

    public static function isAlmoxarife(?User $user = null): bool
    {
        return self::podeVisualizar($user);
    }

    public static function podeCriarMaterial(?User $user = null): bool
    {
        $user ??= self::user();

        return $user && (self::isGestao($user) || $user->podeAcaoNoModulo('almoxarifado', 'criar'));
    }

    public static function podeEditarMaterialBasico(?User $user = null): bool
    {
        return self::isGestao($user) || self::isAlmoxarife($user);
    }

    public static function podeAlterarQuantidadeNecessaria(?User $user = null): bool
    {
        return self::isGestao($user);
    }

    public static function podeAtualizarSigo(?User $user = null): bool
    {
        return self::isGestao($user) || self::papel($user) === self::PAPEL_ALMOXARIFE;
    }

    public static function podeAtualizarCompras(?User $user = null): bool
    {
        return self::isCompras($user);
    }

    public static function podeRegistrarRecebimento(?User $user = null): bool
    {
        return self::isGestao($user) || self::papel($user) === self::PAPEL_ALMOXARIFE;
    }

    public static function podeCancelarItem(?User $user = null): bool
    {
        return self::isGestao($user);
    }

    public static function podeReabrirItem(?User $user = null): bool
    {
        return self::isGestao($user);
    }

    public static function podeAnexar(?User $user = null): bool
    {
        return self::podeVisualizar($user);
    }

    public static function podeExportar(?User $user = null): bool
    {
        return self::podeVisualizar($user);
    }

    public static function podeExtrairInsumosSigo(?User $user = null): bool
    {
        return self::isGestao($user);
    }

    public static function abortUnless(bool $allowed, string $message = 'Sem permissão para esta ação no Almoxarifado.'): void
    {
        abort_unless($allowed, 403, $message);
    }
}
