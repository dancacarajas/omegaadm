<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ContratoAccess
{
    public static function user(): ?User
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user;
    }

    public static function shouldRestrict(?User $user = null): bool
    {
        $user ??= self::user();

        if (! $user) {
            return false;
        }

        return ! (bool) $user->todos_contratos;
    }

    public static function contratoIds(?User $user = null): array
    {
        $user ??= self::user();

        if (! $user || ! self::shouldRestrict($user)) {
            return [];
        }

        return $user->contratos()->pluck('contratos.id')->map(fn ($id) => (int) $id)->all();
    }

    public static function contratoValores(?User $user = null): array
    {
        $user ??= self::user();

        if (! $user || ! self::shouldRestrict($user)) {
            return [];
        }

        return $user->contratos()
            ->get(['numero', 'nome', 'centro_custo'])
            ->flatMap(fn ($contrato) => [$contrato->numero, $contrato->nome, $contrato->centro_custo])
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public static function applyContratoModel(Builder $query, ?User $user = null): Builder
    {
        if (! self::shouldRestrict($user)) {
            return $query;
        }

        $ids = self::contratoIds($user);

        return empty($ids) ? $query->whereRaw('1 = 0') : $query->whereIn('id', $ids);
    }

    public static function applyContratoString(Builder $query, string $column = 'contrato', ?User $user = null): Builder
    {
        if (! self::shouldRestrict($user)) {
            return $query;
        }

        $valores = self::contratoValores($user);

        return empty($valores) ? $query->whereRaw('1 = 0') : $query->whereIn($column, $valores);
    }

    public static function authorizeContratoId(int $contratoId, ?User $user = null): void
    {
        if (! self::shouldRestrict($user)) {
            return;
        }

        $ids = self::contratoIds($user);
        abort_unless(in_array($contratoId, $ids, true), 403, 'Seu usuário não tem acesso a este contrato.');
    }
}
