<?php

namespace App\Support\Rh;

use Illuminate\Database\Eloquent\Builder;

/**
 * Compara centro de custo de movimentações com tokens do contrato (TRIM e 286 = 0286).
 */
final class CentroCustoContratoMatcher
{
    /**
     * @param  list<string>  $tokens
     */
    public static function aplicar(Builder $query, string $coluna, array $tokens): Builder
    {
        $lista = collect($tokens)
            ->map(fn ($v) => trim((string) $v))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($lista === []) {
            return $query->whereRaw('0 = 1');
        }

        $castNum = $query->getConnection()->getDriverName() === 'sqlite' ? 'INTEGER' : 'UNSIGNED';

        return $query->where(function (Builder $outer) use ($coluna, $lista, $castNum) {
            foreach ($lista as $t) {
                $outer->orWhere(function (Builder $g) use ($coluna, $t, $castNum) {
                    $g->whereRaw("TRIM(COALESCE({$coluna}, '')) = ?", [$t]);

                    if (preg_match('/^\d+$/', $t) === 1) {
                        $n = (int) $t;
                        $g->orWhereRaw(
                            "NULLIF(TRIM({$coluna}), '') IS NOT NULL AND CAST(TRIM({$coluna}) AS {$castNum}) = ?",
                            [$n]
                        );

                        if (strlen($t) >= 2) {
                            $g->orWhere($coluna, 'like', '%'.$t.'%');
                        }
                    }
                });
            }
        });
    }
}
