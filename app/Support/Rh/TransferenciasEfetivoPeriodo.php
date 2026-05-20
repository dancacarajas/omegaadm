<?php

namespace App\Support\Rh;

use App\Models\ColaboradorMovimentacao;
use Carbon\Carbon;

final class TransferenciasEfetivoPeriodo
{
    /**
     * @param  list<string>  $identificadoresContrato
     * @return array{entrada: int, saida: int}
     */
    public static function resumo(array $identificadoresContrato, Carbon $periodoInicio, Carbon $periodoFim): array
    {
        $tokens = collect($identificadoresContrato)
            ->map(fn ($v) => trim((string) $v))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($tokens === []) {
            return ['entrada' => 0, 'saida' => 0];
        }

        $ini = $periodoInicio->toDateString();
        $fim = $periodoFim->toDateString();

        $base = ColaboradorMovimentacao::query()
            ->where('tipo', ColaboradorMovimentacaoTipos::TRANSFERENCIA_CONTRATO)
            ->whereDate('data_inicio', '>=', $ini)
            ->whereDate('data_inicio', '<=', $fim);

        return [
            'entrada' => (clone $base)->whereIn('centro_custo_novo', $tokens)->count(),
            'saida' => (clone $base)->whereIn('centro_custo_anterior', $tokens)->count(),
        ];
    }
}
