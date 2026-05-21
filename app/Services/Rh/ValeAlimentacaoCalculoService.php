<?php

namespace App\Services\Rh;

use App\Models\Beneficio;
use App\Models\Colaborador;
use App\Models\ColaboradorBeneficio;
use App\Models\HorarioEscalaDia;
use App\Support\FrequenciaCalculo;
use App\Support\Rh\CartaoPontoService;
use App\Support\Rh\ColaboradorVinculoPonto;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

/**
 * Vale alimentação: valor do cadastro do benefício, proporcional à admissão/demissão no mês
 * e desconto por assiduidade (faltas injustificadas do mês anterior na apuração de ponto).
 */
class ValeAlimentacaoCalculoService
{
    public function __construct(
        private readonly CartaoPontoService $cartaoPonto
    ) {}

    public function usaCalculoAssiduidade(Beneficio $beneficio): bool
    {
        $nome = mb_strtolower((string) $beneficio->nome);
        $tipo = mb_strtolower((string) ($beneficio->tipo ?? ''));
        $codigo = mb_strtolower((string) ($beneficio->codigo ?? ''));

        return str_contains($nome, 'aliment')
            || str_contains($nome, 'vale')
            || str_contains($tipo, 'aliment')
            || in_array($codigo, ['alelo001', 'vale-alimentacao', 'va'], true);
    }

    /**
     * @return array<string, mixed>
     */
    public function calcularParaVinculo(
        ColaboradorBeneficio $vinculo,
        Beneficio $beneficio,
        ?Carbon $mesPagamento = null
    ): array {
        $mesPagamento ??= Carbon::now()->startOfMonth();
        $mesPagamento = $mesPagamento->copy()->startOfMonth();

        $valorBase = (float) ($beneficio->valor ?? 0);
        $vazio = [
            'aplica' => false,
            'mes_pagamento' => $mesPagamento->format('m/Y'),
            'mes_apuracao_faltas' => $mesPagamento->copy()->subMonth()->format('m/Y'),
            'valor_base' => $valorBase,
            'valor_final' => $valorBase,
        ];

        if (! $this->usaCalculoAssiduidade($beneficio) || $valorBase <= 0) {
            return $vazio;
        }

        if (! $vinculo->tem_direito) {
            return array_merge($vazio, [
                'aplica' => true,
                'valor_final' => 0.0,
                'detalhe' => 'Sem direito ao benefício neste vínculo.',
            ]);
        }

        $colaborador = $vinculo->colaborador;
        if ($colaborador === null) {
            return $vazio;
        }

        $colaborador->loadMissing(['horarioEscala.dias', 'horarioEscala.excecoes']);

        $mesApuracaoFaltas = $mesPagamento->copy()->subMonth()->startOfMonth();
        $faltasInjustificadas = $this->contarFaltasInjustificadasIntegral($colaborador, $mesApuracaoFaltas);

        $proporcao = $this->fatorProporcionalMes($colaborador, $mesPagamento);
        $valorProporcional = round($valorBase * $proporcao['fator'], 2);

        $percentualDesconto = $this->percentualDescontoAssiduidade($faltasInjustificadas);
        $valorDescontado = round($valorProporcional * $percentualDesconto, 2);
        $valorFinal = round(max(0, $valorProporcional - $valorDescontado), 2);

        return [
            'aplica' => true,
            'mes_pagamento' => $mesPagamento->format('m/Y'),
            'mes_apuracao_faltas' => $mesApuracaoFaltas->format('m/Y'),
            'valor_base' => $valorBase,
            'faltas_injustificadas' => $faltasInjustificadas,
            'percentual_desconto' => (int) round($percentualDesconto * 100),
            'valor_descontado' => $valorDescontado,
            'fator_proporcional' => $proporcao['fator'],
            'dias_uteis_mes' => $proporcao['dias_uteis_mes'],
            'dias_com_direito' => $proporcao['dias_com_direito'],
            'valor_proporcional' => $valorProporcional,
            'valor_final' => $valorFinal,
            'detalhe' => $this->montarDetalhe($faltasInjustificadas, $percentualDesconto, $proporcao),
        ];
    }

    /**
     * @param  Collection<int, ColaboradorBeneficio>  $vinculos
     * @return array<int, array<string, mixed>>
     */
    public function calcularParaVinculos(Collection $vinculos, Beneficio $beneficio, ?Carbon $mesPagamento = null): array
    {
        $map = [];
        foreach ($vinculos as $vinculo) {
            $map[$vinculo->id] = $this->calcularParaVinculo($vinculo, $beneficio, $mesPagamento);
        }

        return $map;
    }

    public function percentualDescontoAssiduidade(int $faltasInjustificadas): float
    {
        return match (true) {
            $faltasInjustificadas <= 0 => 0.0,
            $faltasInjustificadas === 1 => 0.20,
            $faltasInjustificadas === 2 => 0.50,
            default => 1.0,
        };
    }

    public function contarFaltasInjustificadasIntegral(Colaborador $colaborador, Carbon $mes): int
    {
        $resumo = $this->analisarMes($colaborador, $mes);

        return $resumo['faltas_injustificadas'];
    }

    /**
     * @return array{faltas_injustificadas: int, dias_uteis_mes: int, dias_com_direito: int, fator: float}
     */
    public function fatorProporcionalMes(Colaborador $colaborador, Carbon $mes): array
    {
        $resumo = $this->analisarMes($colaborador, $mes);

        $diasUteis = $resumo['dias_uteis_mes'];
        $diasComDireito = $resumo['dias_com_direito'];
        $fator = $diasUteis > 0 ? min(1.0, $diasComDireito / $diasUteis) : 1.0;

        return [
            'faltas_injustificadas' => $resumo['faltas_injustificadas'],
            'dias_uteis_mes' => $diasUteis,
            'dias_com_direito' => $diasComDireito,
            'fator' => round($fator, 4),
        ];
    }

    /**
     * @return array{faltas_injustificadas: int, dias_uteis_mes: int, dias_com_direito: int}
     */
    private function analisarMes(Colaborador $colaborador, Carbon $mes): array
    {
        $inicio = $mes->copy()->startOfMonth();
        $fim = $mes->copy()->endOfMonth();

        $diasUteisMes = 0;
        $diasComDireito = 0;

        foreach (CarbonPeriod::create($inicio, $fim) as $dia) {
            $diaEscala = $colaborador->horarioEscalaDiaNaData($dia);
            if (! $this->diaTemJornadaEscala($diaEscala)) {
                continue;
            }

            $diasUteisMes++;

            if (ColaboradorVinculoPonto::contaPontoNaData($colaborador, $dia)) {
                $diasComDireito++;
            }
        }

        $cartoes = $this->cartaoPonto->montarCartoes(collect([$colaborador]), $inicio->toDateString(), $fim->toDateString());
        $linhas = $cartoes[0]['linhas'] ?? [];
        $faltas = 0;

        foreach ($linhas as $linha) {
            $ymd = (string) ($linha['data_ymd'] ?? '');
            if ($ymd === '' || ! $this->linhaEhFaltaInjustificadaIntegral($linha)) {
                continue;
            }

            if (ColaboradorVinculoPonto::contaPontoNaData($colaborador, Carbon::parse($ymd))) {
                $faltas++;
            }
        }

        return [
            'faltas_injustificadas' => $faltas,
            'dias_uteis_mes' => $diasUteisMes,
            'dias_com_direito' => $diasComDireito,
        ];
    }

    private function diaTemJornadaEscala(?HorarioEscalaDia $dia): bool
    {
        if ($dia === null) {
            return false;
        }

        foreach (['entrada_1', 'saida_1', 'entrada_2', 'saida_2'] as $campo) {
            if (! FrequenciaCalculo::horarioArmazenadoVazio($dia->getAttribute($campo))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Falta injustificada = dia integral de falta na apuração (status falta / sem justificativa abonada).
     *
     * @param  array<string, mixed>  $linha
     */
    private function linhaEhFaltaInjustificadaIntegral(array $linha): bool
    {
        $status = (string) ($linha['status'] ?? '');

        if ($status === 'justificado' || $status === 'folga') {
            return false;
        }

        return (int) ($linha['minutos_dia_falta'] ?? 0) > 0;
    }

    /**
     * @param  array{dias_uteis_mes: int, dias_com_direito: int, fator: float}  $proporcao
     */
    private function montarDetalhe(int $faltas, float $percentualDesconto, array $proporcao): string
    {
        $partes = [];

        if ($proporcao['dias_com_direito'] < $proporcao['dias_uteis_mes']) {
            $partes[] = sprintf(
                'Proporcional admissão/demissão: %d de %d dias úteis.',
                $proporcao['dias_com_direito'],
                $proporcao['dias_uteis_mes']
            );
        }

        if ($faltas <= 0) {
            $partes[] = 'Sem falta injustificada no mês de apuração → valor integral (após proporcional).';
        } else {
            $partes[] = match ($faltas) {
                1 => '1 falta injustificada no mês anterior → desconto 20%.',
                2 => '2 faltas injustificadas no mês anterior → desconto 50%.',
                default => "{$faltas} faltas injustificadas no mês anterior → desconto 100%.",
            };
        }

        return implode(' ', $partes);
    }
}
