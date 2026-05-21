<?php

namespace App\Services\Rh;

use App\Models\Beneficio;
use App\Models\BeneficioExtratoRegra;
use App\Models\Colaborador;
use App\Models\ColaboradorBeneficio;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class BeneficioExtratoCalculoService
{
    public function __construct(
        private readonly ValeAlimentacaoCalculoService $valeAlimentacao,
        private readonly CafeDaManhaCalculoService $cafeDaManha
    ) {}

    /**
     * @return Collection<int, BeneficioExtratoRegra>
     */
    public function regrasAtivas(): Collection
    {
        return BeneficioExtratoRegra::query()
            ->where('ativo', true)
            ->with('beneficio')
            ->orderBy('id')
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    public function montarExtratoColaborador(
        Colaborador $colaborador,
        Carbon $periodoInicio,
        Carbon $periodoFim,
        ?Collection $regras = null
    ): array {
        $regras ??= $this->regrasAtivas();
        $mesPagamento = $periodoFim->copy()->startOfMonth();

        $linhas = [];
        $total = 0.0;
        $totalDescontos = 0.0;

        foreach ($regras as $regra) {
            $beneficio = $regra->beneficio;
            if ($beneficio === null) {
                continue;
            }

            $vinculo = ColaboradorBeneficio::query()
                ->where('colaborador_id', $colaborador->id)
                ->where('beneficio_id', $beneficio->id)
                ->with('colaborador')
                ->first();

            $calculo = $this->calcularLinha(
                $regra,
                $beneficio,
                $vinculo,
                $mesPagamento,
                $periodoInicio,
                $periodoFim
            );
            $valorFinal = (float) ($calculo['valor_final'] ?? 0);
            $total += $valorFinal;
            $totalDescontos += (float) ($calculo['valor_descontado'] ?? 0);

            $linhas[] = [
                'regra' => $regra,
                'beneficio' => $beneficio,
                'vinculo' => $vinculo,
                'calculo' => $calculo,
            ];
        }

        return [
            'colaborador' => $colaborador,
            'periodo_inicio' => $periodoInicio,
            'periodo_fim' => $periodoFim,
            'mes_pagamento' => $mesPagamento,
            'linhas' => $linhas,
            'total' => round($total, 2),
            'total_descontos' => round($totalDescontos, 2),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function calcularLinha(
        BeneficioExtratoRegra $regra,
        Beneficio $beneficio,
        ?ColaboradorBeneficio $vinculo,
        Carbon $mesPagamento,
        Carbon $periodoInicio,
        Carbon $periodoFim
    ): array {
        if ($vinculo === null) {
            return [
                'aplica' => true,
                'tipo_regra' => $regra->tipo_regra,
                'valor_base' => (float) ($beneficio->valor ?? 0),
                'valor_final' => 0.0,
                'detalhe' => 'Colaborador não vinculado a este benefício.',
            ];
        }

        return match ($regra->tipo_regra) {
            BeneficioExtratoRegra::TIPO_ASSIDUIDADE => $this->calcularAssiduidade(
                $vinculo,
                $beneficio,
                $regra,
                $mesPagamento,
                $periodoInicio,
                $periodoFim
            ),
            BeneficioExtratoRegra::TIPO_CAFE_MANHA => $this->calcularCafeDaManha(
                $vinculo,
                $beneficio,
                $regra,
                $periodoInicio,
                $periodoFim
            ),
            default => $this->calcularValorFixo($vinculo, $beneficio),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function calcularAssiduidade(
        ColaboradorBeneficio $vinculo,
        Beneficio $beneficio,
        BeneficioExtratoRegra $regra,
        Carbon $mesPagamento,
        Carbon $periodoInicio,
        Carbon $periodoFim
    ): array {
        $resultado = $this->valeAlimentacao->calcularParaVinculo(
            $vinculo,
            $beneficio,
            $mesPagamento,
            $periodoInicio,
            $periodoFim,
            forcarRegraAssiduidade: true,
            config: $regra->configValeAlimentacao()
        );

        $resultado['tipo_regra'] = BeneficioExtratoRegra::TIPO_ASSIDUIDADE;

        $recarga = $resultado['recarga_natal'] ?? [];
        if (($recarga['aplica'] ?? false) && ($recarga['valor'] ?? 0) > 0) {
            $resultado['valor_recarga_natal'] = (float) $recarga['valor'];
            $resultado['valor_final'] = round((float) $resultado['valor_final'] + (float) $recarga['valor'], 2);
        }

        return $resultado;
    }

    /**
     * @return array<string, mixed>
     */
    private function calcularCafeDaManha(
        ColaboradorBeneficio $vinculo,
        Beneficio $beneficio,
        BeneficioExtratoRegra $regra,
        Carbon $periodoInicio,
        Carbon $periodoFim
    ): array {
        return $this->cafeDaManha->calcularParaVinculo(
            $vinculo,
            $beneficio,
            $periodoInicio,
            $periodoFim,
            $regra->configCafeDaManha()
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function calcularValorFixo(ColaboradorBeneficio $vinculo, Beneficio $beneficio): array
    {
        $valorBase = (float) ($beneficio->valor ?? 0);

        if (! $vinculo->tem_direito) {
            return [
                'aplica' => true,
                'tipo_regra' => BeneficioExtratoRegra::TIPO_VALOR_FIXO,
                'valor_base' => $valorBase,
                'valor_final' => 0.0,
                'detalhe' => 'Sem direito ao benefício neste vínculo.',
            ];
        }

        return [
            'aplica' => true,
            'tipo_regra' => BeneficioExtratoRegra::TIPO_VALOR_FIXO,
            'valor_base' => $valorBase,
            'valor_final' => $valorBase,
            'detalhe' => 'Valor integral do cadastro do benefício.',
        ];
    }
}
