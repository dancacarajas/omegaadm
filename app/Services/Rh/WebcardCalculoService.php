<?php

namespace App\Services\Rh;

use App\Models\Beneficio;
use App\Models\BeneficioExtratoRegra;
use App\Models\ColaboradorBeneficio;
use App\Models\ColaboradorBeneficioWebcardSolicitacao;
use App\Support\Rh\WebcardRegraConfig;
use Carbon\Carbon;

class WebcardCalculoService
{
    /**
     * @return array<string, mixed>
     */
    public function calcularParaVinculo(
        ColaboradorBeneficio $vinculo,
        Beneficio $beneficio,
        Carbon $mesPagamento,
        ?WebcardRegraConfig $config = null
    ): array {
        $config ??= WebcardRegraConfig::resolver(null);
        $vinculo->loadMissing('colaborador');
        $salarioReferencia = filled($vinculo->colaborador?->salario_inicial)
            ? (float) $vinculo->colaborador->salario_inicial
            : null;
        $limiteSolicitacao = $config->limitePorSolicitacaoParaSalario($salarioReferencia);
        $limiteMensal = $config->limiteMensal();
        $percentualLimite = $config->percentualLimitePorSolicitacao();
        $diaRenovacao = $config->diaRenovacaoSaldo();

        $vazio = [
            'aplica' => false,
            'tipo_regra' => BeneficioExtratoRegra::TIPO_WEBCARD,
            'valor_base' => 0.0,
            'valor_final' => 0.0,
            'valor_descontado' => 0.0,
            'solicitacoes' => [],
        ];

        if (! $vinculo->tem_direito) {
            return array_merge($vazio, [
                'aplica' => true,
                'detalhe' => 'Sem direito ao benefício neste vínculo.',
            ]);
        }

        if (! $vinculo->cartao_entregue || ! $vinculo->beneficio_ativo) {
            return array_merge($vazio, [
                'aplica' => true,
                'detalhe' => 'Cartão não entregue ou benefício inativo no vínculo.',
            ]);
        }

        $inicioMes = $mesPagamento->copy()->startOfMonth();
        $fimMes = $mesPagamento->copy()->endOfMonth();

        $solicitacoes = ColaboradorBeneficioWebcardSolicitacao::query()
            ->where('colaborador_beneficio_id', $vinculo->id)
            ->whereBetween('data_solicitacao', [$inicioMes->toDateString(), $fimMes->toDateString()])
            ->orderBy('data_solicitacao')
            ->orderBy('id')
            ->get();

        $linhas = [];
        $totalBruto = 0.0;
        $alertas = [];

        foreach ($solicitacoes as $sol) {
            $valor = round((float) $sol->valor, 2);
            if ($limiteSolicitacao > 0 && $valor > $limiteSolicitacao + 0.001) {
                $alertas[] = 'Solicitação em '.$sol->data_solicitacao->format('d/m/Y').' excede o limite por uso ('.number_format($percentualLimite, 0, ',', '.').'% do salário: R$ '.number_format($limiteSolicitacao, 2, ',', '.').').';
            }
            $totalBruto += $valor;
            $linhas[] = [
                'id' => $sol->id,
                'data' => $sol->data_solicitacao->format('d/m/Y'),
                'valor' => $valor,
                'observacao' => $sol->observacao,
            ];
        }

        $totalBruto = round($totalBruto, 2);
        $excedeuTeto = $totalBruto > $limiteMensal + 0.001;
        $valorDescontado = $excedeuTeto ? round($limiteMensal, 2) : $totalBruto;

        if ($excedeuTeto) {
            $alertas[] = 'Total do mês excede o limite mensal de R$ '.number_format($limiteMensal, 2, ',', '.').'; o desconto na folha considera o teto.';
        }

        $proximaRenovacao = $this->proximaDataRenovacao($mesPagamento, $diaRenovacao);

        $detalhe = count($linhas) === 0
            ? 'Nenhuma solicitação de adiantamento no mês '.$mesPagamento->format('m/Y').'.'
            : count($linhas).' solicitação(ões) no mês — desconto na folha de '.$mesPagamento->format('m/Y').'.';

        return [
            'aplica' => true,
            'tipo_regra' => BeneficioExtratoRegra::TIPO_WEBCARD,
            'valor_base' => $limiteMensal,
            'valor_final' => 0.0,
            'valor_descontado' => $valorDescontado,
            'valor_bruto_solicitado' => $totalBruto,
            'percentual_limite_por_solicitacao' => $percentualLimite,
            'salario_referencia' => $salarioReferencia,
            'limite_por_solicitacao' => $limiteSolicitacao,
            'limite_mensal' => $limiteMensal,
            'dia_renovacao_saldo' => $diaRenovacao,
            'proxima_renovacao' => $proximaRenovacao->format('d/m/Y'),
            'mes_referencia' => $mesPagamento->format('m/Y'),
            'solicitacoes' => $linhas,
            'quantidade_solicitacoes' => count($linhas),
            'alertas' => $alertas,
            'detalhe' => $detalhe,
        ];
    }

    public function proximaDataRenovacao(Carbon $referencia, int $diaRenovacao): Carbon
    {
        $dia = min(28, max(1, $diaRenovacao));
        $candidata = $referencia->copy()->day($dia)->startOfDay();
        if ($referencia->greaterThanOrEqualTo($candidata)) {
            return $candidata->copy()->addMonth()->day($dia);
        }

        return $candidata;
    }
}
