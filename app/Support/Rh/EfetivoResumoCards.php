<?php

namespace App\Support\Rh;

use App\Models\Colaborador;
use App\Models\Contrato;
use App\Services\Rh\MovimentacaoEfetivoPeriodo;
use App\Support\ContratoAccess;
use Carbon\Carbon;

/**
 * Totais de efetivo para cards da tela RH → Efetivo.
 *
 * Efetivo operacional = cadastros com status ativo (21 − 1 desligado − 1 afastado = 19).
 */
final class EfetivoResumoCards
{
    /**
     * @return array{
     *     tem_contrato_ref: bool,
     *     contrato_label: ?string,
     *     efetivo_operacional: int,
     *     efetivo_contrato: ?int,
     *     ativos_contrato: ?int,
     *     afastados_contrato: ?int,
     *     desligados_contrato: ?int,
     *     cadastros_total: int,
     *     ativos: int,
     *     afastados: int,
     *     desligados: int,
     *     mobilizacao_concluida: int
     * }
     */
    public static function paraTelaEfetivo(?Carbon $dataReferencia = null): array
    {
        $dataReferencia ??= today();

        $cadastrosTotal = Colaborador::query()->count();
        $ativos = Colaborador::query()->where('status', 'ativo')->count();
        $afastados = Colaborador::query()->where('status', 'afastado')->count();
        $desligados = Colaborador::query()->where('status', 'desligado')->count();

        $global = [
            'cadastros_total' => $cadastrosTotal,
            'ativos' => $ativos,
            'afastados' => $afastados,
            'desligados' => $desligados,
            'mobilizacao_concluida' => Colaborador::query()->where('mobilizacao_status', 'mobilizacao_concluida')->count(),
            'efetivo_operacional' => $ativos,
        ];

        $contrato = self::contratoReferencia();
        if ($contrato === null) {
            return array_merge($global, [
                'tem_contrato_ref' => false,
                'contrato_label' => null,
                'efetivo_contrato' => null,
                'ativos_contrato' => null,
                'afastados_contrato' => null,
                'desligados_contrato' => null,
            ]);
        }

        $tokens = collect([$contrato->centro_custo, $contrato->numero, $contrato->nome])
            ->map(fn ($v) => trim((string) $v))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $qContrato = Colaborador::query();
        ColaboradorQueryPorContratoPainel::aplicar($qContrato, $tokens);

        $mov = new MovimentacaoEfetivoPeriodo($tokens);
        $efetivoNaData = (int) $mov->resumo($dataReferencia, $dataReferencia)['efetivo_final'];

        $label = trim((string) ($contrato->numero ?: ''));
        if ($label === '') {
            $label = trim((string) ($contrato->centro_custo ?: 'Contrato'));
        }

        return array_merge($global, [
            'tem_contrato_ref' => true,
            'contrato_label' => $label,
            'efetivo_contrato' => $efetivoNaData,
            'ativos_contrato' => (clone $qContrato)->where('status', 'ativo')->count(),
            'afastados_contrato' => (clone $qContrato)->where('status', 'afastado')->count(),
            'desligados_contrato' => (clone $qContrato)->where('status', 'desligado')->count(),
        ]);
    }

    private static function contratoReferencia(): ?Contrato
    {
        return ContratoAccess::applyContratoModel(Contrato::query())
            ->where('status', 'ativo')
            ->orderBy('centro_custo')
            ->first();
    }
}
