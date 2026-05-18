<?php

namespace App\Support;

use App\Models\SsmaTstRegistro;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final class SsmaTstRegistroIndicadores
{
    public function __construct(
        private readonly Builder $baseQuery,
    ) {}

    /**
     * @return array{
     *     total: int,
     *     mes_atual: int,
     *     colaboradores_distintos: int,
     *     com_atividade: int,
     *     sem_atividade: int,
     *     media_dia: float|null,
     *     dias_periodo: int,
     * }
     */
    public function cartoes(): array
    {
        $total = (clone $this->baseQuery)->count();

        $inicioMes = now()->startOfMonth()->toDateString();
        $fimMes = now()->endOfMonth()->toDateString();
        $mesAtual = (clone $this->baseQuery)
            ->whereDate('data', '>=', $inicioMes)
            ->whereDate('data', '<=', $fimMes)
            ->count();

        $comAtividade = (clone $this->baseQuery)->whereNotNull('ssma_tst_atividade_id')->count();
        $semAtividade = $total - $comAtividade;

        $colaboradoresDistintos = (int) (clone $this->baseQuery)
            ->distinct()
            ->count('colaborador_id');

        $minData = (clone $this->baseQuery)->min('data');
        $maxData = (clone $this->baseQuery)->max('data');
        $diasPeriodo = 0;
        $mediaDia = null;

        if ($minData && $maxData) {
            $diasPeriodo = max(1, Carbon::parse($minData)->diffInDays(Carbon::parse($maxData)) + 1);
            $mediaDia = round($total / $diasPeriodo, 1);
        }

        return [
            'total' => $total,
            'mes_atual' => $mesAtual,
            'colaboradores_distintos' => $colaboradoresDistintos,
            'com_atividade' => $comAtividade,
            'sem_atividade' => $semAtividade,
            'media_dia' => $mediaDia,
            'dias_periodo' => $diasPeriodo,
        ];
    }

    /**
     * Janeiro a dezembro do ano informado (respeita filtros da query, exceto data).
     *
     * @return list<array{rotulo: string, competencia: string, total: int}>
     */
    public function serieMensal(?int $ano = null): array
    {
        $ano ??= (int) now()->format('Y');
        $inicio = Carbon::create($ano, 1, 1)->startOfMonth();
        $fim = Carbon::create($ano, 12, 31)->endOfMonth();

        $driver = DB::connection()->getDriverName();
        $exprCompetencia = $driver === 'sqlite'
            ? "strftime('%Y-%m', data)"
            : "DATE_FORMAT(data, '%Y-%m')";

        $rows = (clone $this->baseQuery)
            ->whereDate('data', '>=', $inicio->toDateString())
            ->whereDate('data', '<=', $fim->toDateString())
            ->selectRaw("{$exprCompetencia} as competencia, COUNT(*) as total")
            ->groupBy('competencia')
            ->orderBy('competencia')
            ->pluck('total', 'competencia');

        $serie = [];
        $cursor = $inicio->copy();
        while ($cursor <= $fim) {
            $key = $cursor->format('Y-m');
            $serie[] = [
                'rotulo' => $cursor->translatedFormat('M/y'),
                'competencia' => $key,
                'total' => (int) ($rows[$key] ?? 0),
            ];
            $cursor->addMonth();
        }

        return $serie;
    }

    /**
     * @return list<array{nome: string, total: int}>
     */
    public function porAtividade(int $limite = 8): array
    {
        $rows = (clone $this->baseQuery)
            ->leftJoin('ssma_tst_atividades as a', 'a.id', '=', 'ssma_tst_registros.ssma_tst_atividade_id')
            ->selectRaw('COALESCE(a.nome, ?) as nome, COUNT(*) as total', ['Sem atividade'])
            ->groupBy('ssma_tst_registros.ssma_tst_atividade_id', 'a.nome')
            ->orderByDesc('total')
            ->limit($limite)
            ->get();

        return $rows->map(fn ($r) => [
            'nome' => (string) $r->nome,
            'total' => (int) $r->total,
        ])->all();
    }

    /**
     * @return list<array{nome: string, total: int}>
     */
    public function topColaboradores(int $limite = 8): array
    {
        return (clone $this->baseQuery)
            ->join('colaboradores as c', 'c.id', '=', 'ssma_tst_registros.colaborador_id')
            ->selectRaw('c.nome as nome, COUNT(*) as total')
            ->groupBy('c.id', 'c.nome')
            ->orderByDesc('total')
            ->limit($limite)
            ->get()
            ->map(fn ($r) => ['nome' => (string) $r->nome, 'total' => (int) $r->total])
            ->all();
    }

    public static function queryFiltrada(
        ?string $busca,
        ?string $dataDe,
        ?string $dataAte,
        ?int $atividadeId,
        ?int $colaboradorId,
    ): Builder {
        return SsmaTstRegistro::query()->filtrar($busca, $dataDe, $dataAte, $atividadeId, $colaboradorId);
    }
}
