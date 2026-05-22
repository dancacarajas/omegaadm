<?php

namespace App\Support\Rh;

use App\Models\Colaborador;
use App\Models\ColaboradorMovimentacao;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Regra de vale alimentação: afastamento por acidente de trabalho (espécie INSS)
 * → sem desconto por falta, limitado aos primeiros N meses de afastamento.
 */
final class AfastamentoAcidenteTrabalho
{
    /** Espécies de movimentação que disparam a isenção de desconto por assiduidade. */
    public static function especiesElegiveisValeAlimentacao(): array
    {
        return ['acidente_trabalho'];
    }

    public static function especieElegivel(?string $especie): bool
    {
        return in_array($especie, self::especiesElegiveisValeAlimentacao(), true);
    }

    /**
     * Movimentações de acidente de trabalho que cobrem o mês de pagamento.
     *
     * @return Collection<int, ColaboradorMovimentacao>
     */
    public static function movimentacoesNoMes(Colaborador $colaborador, Carbon $mesPagamento): Collection
    {
        $inicioMes = $mesPagamento->copy()->startOfMonth();
        $fimMes = $mesPagamento->copy()->endOfMonth();

        return ColaboradorMovimentacao::query()
            ->efetiva()
            ->where('colaborador_id', $colaborador->id)
            ->where('tipo', ColaboradorMovimentacaoTipos::AFASTAMENTO_INSS)
            ->whereIn('especie_beneficio_inss', self::especiesElegiveisValeAlimentacao())
            ->where('data_inicio', '<=', $fimMes->toDateString())
            ->where(function ($query) use ($inicioMes) {
                $query->whereNull('data_fim')
                    ->orWhere('data_fim', '>=', $inicioMes->toDateString());
            })
            ->orderBy('data_inicio')
            ->get();
    }

    /**
     * Número do mês de afastamento (1 = mês do início) em relação ao mês de pagamento.
     */
    public static function mesesDecorridosDesdeInicio(Carbon $dataInicioAfastamento, Carbon $mesPagamento): int
    {
        $inicio = $dataInicioAfastamento->copy()->startOfMonth();
        $referencia = $mesPagamento->copy()->startOfMonth();

        return (int) $inicio->diffInMonths($referencia) + 1;
    }

    /**
     * @return array{isento: bool, mes_afastamento: int|null, limite_meses: int, movimentacao_id: int|null}
     */
    public static function situacaoValeAlimentacaoNoMes(
        Colaborador $colaborador,
        Carbon $mesPagamento,
        int $limiteMesesIntegral = 3
    ): array {
        $limite = max(1, $limiteMesesIntegral);
        $movimentacoes = self::movimentacoesNoMes($colaborador, $mesPagamento);

        if ($movimentacoes->isEmpty()) {
            return [
                'isento' => false,
                'mes_afastamento' => null,
                'limite_meses' => $limite,
                'movimentacao_id' => null,
            ];
        }

        foreach ($movimentacoes as $mov) {
            if ($mov->data_inicio === null) {
                continue;
            }

            $mesAfastamento = self::mesesDecorridosDesdeInicio($mov->data_inicio, $mesPagamento);
            if ($mesAfastamento >= 1 && $mesAfastamento <= $limite) {
                return [
                    'isento' => true,
                    'mes_afastamento' => $mesAfastamento,
                    'limite_meses' => $limite,
                    'movimentacao_id' => $mov->id,
                ];
            }
        }

        $primeira = $movimentacoes->first();
        $mesAfastamento = $primeira && $primeira->data_inicio
            ? self::mesesDecorridosDesdeInicio($primeira->data_inicio, $mesPagamento)
            : null;

        return [
            'isento' => false,
            'mes_afastamento' => $mesAfastamento,
            'limite_meses' => $limite,
            'movimentacao_id' => $primeira?->id,
        ];
    }
}
