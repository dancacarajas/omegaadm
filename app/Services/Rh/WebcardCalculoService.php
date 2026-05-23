<?php

namespace App\Services\Rh;

use App\Models\Beneficio;
use App\Models\BeneficioExtratoRegra;
use App\Models\ColaboradorBeneficio;
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
        $percentual = $config->percentualLimitePorSolicitacao();
        $limiteMensal = $config->limiteMensal();
        $diaRenovacao = $config->diaRenovacaoSaldo();

        $vazio = [
            'aplica' => false,
            'tipo_regra' => BeneficioExtratoRegra::TIPO_WEBCARD,
            'valor_base' => 0.0,
            'valor_final' => 0.0,
            'valor_descontado' => 0.0,
        ];

        if (! $vinculo->tem_direito) {
            return array_merge($vazio, [
                'aplica' => true,
                'detalhe' => 'Sem direito ao benefício neste vínculo.',
            ]);
        }

        if ($salarioReferencia === null || $salarioReferencia <= 0) {
            return array_merge($vazio, [
                'aplica' => true,
                'detalhe' => 'Cadastre o salário do colaborador na ficha do efetivo para calcular o direito WebCard.',
            ]);
        }

        $valorPorPercentual = $config->limitePorSolicitacaoParaSalario($salarioReferencia);
        $valorDireito = round(min($valorPorPercentual, $limiteMensal), 2);
        $aplicouTeto = $valorPorPercentual > $limiteMensal + 0.001;

        $proximaRenovacao = $this->proximaDataRenovacao($mesPagamento, $diaRenovacao);

        $detalhe = sprintf(
            'Direito de R$ %s (%s%% do salário de R$ %s). Teto mensal R$ %s. Renovação do saldo em %s.',
            number_format($valorDireito, 2, ',', '.'),
            number_format($percentual, 0, ',', '.'),
            number_format($salarioReferencia, 2, ',', '.'),
            number_format($limiteMensal, 2, ',', '.'),
            $proximaRenovacao->format('d/m/Y')
        );

        if ($aplicouTeto) {
            $detalhe .= ' Valor limitado pelo teto mensal.';
        }

        if (! $vinculo->cartao_entregue || ! $vinculo->beneficio_ativo) {
            $detalhe .= ' Cartão não entregue ou benefício inativo no vínculo.';
        }

        return [
            'aplica' => true,
            'tipo_regra' => BeneficioExtratoRegra::TIPO_WEBCARD,
            'valor_base' => $valorDireito,
            'valor_final' => $valorDireito,
            'valor_descontado' => 0.0,
            'valor_direito_mensal' => $valorDireito,
            'percentual_limite_por_solicitacao' => $percentual,
            'salario_referencia' => $salarioReferencia,
            'limite_mensal' => $limiteMensal,
            'dia_renovacao_saldo' => $diaRenovacao,
            'proxima_renovacao' => $proximaRenovacao->format('d/m/Y'),
            'mes_referencia' => $mesPagamento->format('m/Y'),
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
