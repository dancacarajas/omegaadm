<?php

namespace App\Services\Rh;

use App\Models\Colaborador;
use App\Support\Rh\ColaboradorQueryPorContratoPainel;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

/**
 * Consolida movimentação de efetivo no período a partir do cadastro de colaboradores:
 * admissões e demissões datadas alimentam o painel executivo mensal.
 */
final class MovimentacaoEfetivoPeriodo
{
    /**
     * @param  list<string>  $identificadoresCentroCusto  Valores possíveis de {@see Colaborador::$centro_custo}
     *                                                    (ex.: centro de custo, número ou nome do contrato no cadastro).
     */
    public function __construct(
        private array $identificadoresCentroCusto,
    ) {}

    /**
     * @return array{efetivo_inicial: int, admitidos: int, desligados: int, efetivo_final: int}
     */
    public function resumo(Carbon $periodoInicio, Carbon $periodoFim): array
    {
        $ini = $periodoInicio->copy()->startOfDay();
        $fim = $periodoFim->copy()->endOfDay();
        $diaAntesDoInicio = $ini->copy()->subDay();

        return [
            'efetivo_inicial' => $this->contarEfetivoNaData($diaAntesDoInicio),
            'admitidos' => $this->contarAdmissoesNoPeriodo($ini, $fim),
            'desligados' => $this->contarDemissoesNoPeriodo($ini, $fim),
            'efetivo_final' => $this->contarEfetivoNaData($fim),
        ];
    }

    private function base(): Builder
    {
        $q = Colaborador::query();

        if ($this->identificadoresCentroCusto !== []) {
            ColaboradorQueryPorContratoPainel::aplicar($q, $this->identificadoresCentroCusto);
        }

        return $q;
    }

    /**
     * Efetivo na data D: admitido até D e não demitido até D.
     * Sem data de admissão: considera ativos/afastados cadastrados até D (alinhado ao efetivo operacional).
     */
    private function contarEfetivoNaData(Carbon $data): int
    {
        $d = $data->toDateString();

        $q = $this->base()
            ->where(function ($w) use ($d) {
                $w->where(function ($a) use ($d) {
                    $a->whereNotNull('data_admissao')
                        ->whereDate('data_admissao', '<=', $d);
                })->orWhere(function ($a) use ($d) {
                    $a->whereNull('data_admissao')
                        ->whereIn('status', ['ativo', 'afastado'])
                        ->whereDate('created_at', '<=', $d);
                });
            })
            ->where(function ($w) use ($d) {
                $w->whereNull('data_demissao')
                    ->orWhereDate('data_demissao', '>', $d);
            });

        return $this->contarChavesDistintas($q);
    }

    private function contarAdmissoesNoPeriodo(Carbon $ini, Carbon $fim): int
    {
        $q = $this->base()
            ->whereNotNull('data_admissao')
            ->whereDate('data_admissao', '>=', $ini->toDateString())
            ->whereDate('data_admissao', '<=', $fim->toDateString());

        return $this->contarChavesDistintas($q);
    }

    private function contarDemissoesNoPeriodo(Carbon $ini, Carbon $fim): int
    {
        $q = $this->base()
            ->whereNotNull('data_demissao')
            ->whereDate('data_demissao', '>=', $ini->toDateString())
            ->whereDate('data_demissao', '<=', $fim->toDateString());

        return $this->contarChavesDistintas($q);
    }

    /**
     * Evita inflar totais com cadastros duplicados (mesmo CPF): usa CPF quando informado, senão o id.
     */
    private function contarChavesDistintas(Builder $query): int
    {
        $driver = $query->getConnection()->getDriverName();
        $expr = match ($driver) {
            'sqlite', 'pgsql' => "CASE WHEN NULLIF(TRIM(COALESCE(cpf, '')), '') IS NOT NULL THEN TRIM(COALESCE(cpf, '')) ELSE 'id:' || CAST(id AS TEXT) END",
            default => "CASE WHEN NULLIF(TRIM(COALESCE(cpf, '')), '') IS NOT NULL THEN TRIM(COALESCE(cpf, '')) ELSE CONCAT('id:', id) END",
        };

        $aggregate = $query->clone()
            ->toBase()
            ->selectRaw("COUNT(DISTINCT {$expr}) as __painel_efetivo_cnt")
            ->value('__painel_efetivo_cnt');

        return (int) $aggregate;
    }
}
