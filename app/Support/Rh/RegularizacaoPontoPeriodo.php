<?php

namespace App\Support\Rh;

use App\Models\FrequenciaRegistro;
use App\Support\EscalaPontoRegras;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

/**
 * Regularização de ponto = % de dias de trabalho previstos com tratamento concluído:
 * batidas completas (presente), justificativa/abono (justificado) ou falta lançada (falta).
 * Incompleto não conta como tratado.
 *
 * Dias sem registro recebem falta automática (origem grade), como na apuração, antes do cálculo.
 */
final class RegularizacaoPontoPeriodo
{
    private const STATUS_TRATADO = ['presente', 'justificado', 'falta'];

    /**
     * @param  list<string>|null  $identificadoresContrato
     * @return array{
     *     dias_exigem_tratamento: int,
     *     dias_tratados: int,
     *     dias_pendentes: int,
     *     incompletos: int,
     *     sem_registro: int,
     *     faltas_geradas: int,
     *     percentual: ?float,
     *     percentual_label: string,
     *     colaboradores_no_escopo: int
     * }
     */
    public static function calcular(
        Carbon $periodoInicio,
        Carbon $periodoFim,
        ?array $identificadoresContrato = null,
        bool $gerarFaltasAutomaticas = true
    ): array {
        $faltasGeradas = 0;
        if ($gerarFaltasAutomaticas) {
            $faltasGeradas = GarantirFrequenciaRegistrosPeriodo::gerarFaltasEmDiasSemRegistro(
                $periodoInicio,
                $periodoFim,
                $identificadoresContrato,
                true
            );
        }

        [$ini, $fim] = self::normalizarIntervalo($periodoInicio, $periodoFim);

        $colaboradores = GarantirFrequenciaRegistrosPeriodo::colaboradoresEscopo($identificadoresContrato);
        if ($colaboradores->isEmpty()) {
            return self::vazio();
        }

        $ids = $colaboradores->pluck('id')->all();
        $registrosPorChave = FrequenciaRegistro::query()
            ->whereDate('data', '>=', $ini->toDateString())
            ->whereDate('data', '<=', $fim->toDateString())
            ->whereIn('colaborador_id', $ids)
            ->get(['colaborador_id', 'data', 'status'])
            ->keyBy(fn (FrequenciaRegistro $r) => $r->colaborador_id.'|'.$r->data->format('Y-m-d'));

        $regras = app(EscalaPontoRegras::class);
        $exige = 0;
        $tratados = 0;
        $incompletos = 0;
        $semRegistro = 0;

        foreach ($colaboradores as $colab) {
            foreach (CarbonPeriod::create($ini, $fim) as $dia) {
                $dataYmd = $dia->toDateString();

                if (! ColaboradorVinculoPonto::contaPontoNaData($colab, $dataYmd)) {
                    continue;
                }

                if (! $regras->deveTrabalharNoDia($colab, $dataYmd)) {
                    continue;
                }

                $exige++;
                $reg = $registrosPorChave->get($colab->id.'|'.$dataYmd);

                if ($reg === null) {
                    $semRegistro++;

                    continue;
                }

                $status = (string) ($reg->status ?? '');
                if (in_array($status, self::STATUS_TRATADO, true)) {
                    $tratados++;
                } elseif ($status === 'incompleto') {
                    $incompletos++;
                }
            }
        }

        $pendentes = max(0, $exige - $tratados);
        $pct = $exige > 0 ? round(100 * $tratados / $exige, 1) : null;

        return [
            'dias_exigem_tratamento' => $exige,
            'dias_tratados' => $tratados,
            'dias_pendentes' => $pendentes,
            'incompletos' => $incompletos,
            'sem_registro' => $semRegistro,
            'faltas_geradas' => $faltasGeradas,
            'percentual' => $pct,
            'percentual_label' => $pct === null ? '—' : number_format($pct, 1, ',', '.').'%',
            'colaboradores_no_escopo' => $colaboradores->count(),
        ];
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private static function normalizarIntervalo(Carbon $periodoInicio, Carbon $periodoFim): array
    {
        $ini = $periodoInicio->copy()->startOfDay();
        $fim = $periodoFim->copy()->startOfDay();
        $hoje = today()->startOfDay();
        if ($fim->gt($hoje)) {
            $fim = $hoje->copy();
        }
        if ($fim->lt($ini)) {
            $fim = $ini->copy();
        }

        return [$ini, $fim];
    }

    /**
     * @return array{
     *     dias_exigem_tratamento: int,
     *     dias_tratados: int,
     *     dias_pendentes: int,
     *     incompletos: int,
     *     sem_registro: int,
     *     faltas_geradas: int,
     *     percentual: ?float,
     *     percentual_label: string,
     *     colaboradores_no_escopo: int
     * }
     */
    private static function vazio(): array
    {
        return [
            'dias_exigem_tratamento' => 0,
            'dias_tratados' => 0,
            'dias_pendentes' => 0,
            'incompletos' => 0,
            'sem_registro' => 0,
            'faltas_geradas' => 0,
            'percentual' => null,
            'percentual_label' => '—',
            'colaboradores_no_escopo' => 0,
        ];
    }
}
