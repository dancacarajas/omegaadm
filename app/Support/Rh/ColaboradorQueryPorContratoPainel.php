<?php

namespace App\Support\Rh;

use App\Models\Colaborador;
use App\Models\RecrutamentoVaga;
use Illuminate\Database\Eloquent\Builder;

/**
 * Filtro de colaboradores por contrato no painel de indicadores mensais.
 *
 * Considera: {@see Colaborador::$centro_custo}, {@see Colaborador::$tipo_contrato},
 * vínculo {@see Colaborador::$recrutamento_vaga_id} quando a vaga tem o mesmo {@see RecrutamentoVaga::$contrato},
 * menção numérica em {@see Colaborador::$local_trabalho} (ex.: obra 286)
 * e escala de horários cujo nome referencia o contrato (ex.: "CT 286" com centro de custo vazio).
 * Usa TRIM e equivalência numérica (286 = 0286).
 */
final class ColaboradorQueryPorContratoPainel
{
    /**
     * @param  list<string>  $identificadores  Tokens do contrato (centro de custo, número, nome).
     */
    public static function aplicar(Builder $query, array $identificadores): Builder
    {
        $tokens = collect($identificadores)
            ->map(fn ($v) => trim((string) $v))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($tokens === []) {
            return $query;
        }

        $castNum = $query->getConnection()->getDriverName() === 'sqlite' ? 'INTEGER' : 'UNSIGNED';

        return $query->where(function (Builder $outer) use ($tokens, $castNum) {
            foreach ($tokens as $t) {
                $outer->orWhere(function (Builder $g) use ($t, $castNum) {
                    self::aplicarTokenEmColunasColaborador($g, $t, $castNum);
                });
            }

            $outer->orWhereHas('recrutamentoVaga', function (Builder $vq) use ($tokens, $castNum) {
                $vq->where(function (Builder $w) use ($tokens, $castNum) {
                    foreach ($tokens as $t) {
                        $w->orWhere(function (Builder $x) use ($t, $castNum) {
                            self::aplicarTokenEmColunaContratoVaga($x, $t, $castNum);
                        });
                    }
                });
            });

            $outer->orWhereHas('horarioEscala', function (Builder $eq) use ($tokens) {
                $eq->where(function (Builder $w) use ($tokens) {
                    foreach ($tokens as $t) {
                        $w->orWhere(function (Builder $x) use ($t) {
                            self::aplicarTokenEmNomeEscala($x, $t);
                        });
                    }
                });
            });
        });
    }

    private static function aplicarTokenEmNomeEscala(Builder $x, string $t): void
    {
        $x->whereRaw('TRIM(COALESCE(nome, \'\')) = ?', [$t]);

        if (strlen($t) >= 2) {
            $x->orWhere('nome', 'like', '%'.$t.'%');
        }
    }

    private static function aplicarTokenEmColunasColaborador(Builder $g, string $t, string $castNum): void
    {
        $g->whereRaw('TRIM(COALESCE(centro_custo, \'\')) = ?', [$t])
            ->orWhereRaw('TRIM(COALESCE(tipo_contrato, \'\')) = ?', [$t]);

        if (preg_match('/^\d+$/', $t) === 1) {
            $n = (int) $t;
            $g->orWhereRaw(
                "NULLIF(TRIM(centro_custo), '') IS NOT NULL AND CAST(TRIM(centro_custo) AS {$castNum}) = ?",
                [$n]
            )->orWhereRaw(
                "NULLIF(TRIM(tipo_contrato), '') IS NOT NULL AND CAST(TRIM(tipo_contrato) AS {$castNum}) = ?",
                [$n]
            );

            if (strlen($t) >= 2) {
                $like = '%'.$t.'%';
                $g->orWhere('local_trabalho', 'like', $like)
                    ->orWhere('centro_custo', 'like', $like)
                    ->orWhere('tipo_contrato', 'like', $like);
            }
        }
    }

    private static function aplicarTokenEmColunaContratoVaga(Builder $x, string $t, string $castNum): void
    {
        $x->whereRaw('TRIM(COALESCE(contrato, \'\')) = ?', [$t]);

        if (preg_match('/^\d+$/', $t) === 1) {
            $n = (int) $t;
            $x->orWhereRaw(
                "NULLIF(TRIM(contrato), '') IS NOT NULL AND CAST(TRIM(contrato) AS {$castNum}) = ?",
                [$n]
            );

            if (strlen($t) >= 2) {
                $x->orWhere('contrato', 'like', '%'.$t.'%');
            }
        }
    }
}
