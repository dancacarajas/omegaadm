<?php

namespace App\Services\Rh;

use App\Models\Beneficio;
use App\Models\Colaborador;
use App\Models\ColaboradorBeneficio;
use App\Models\FrequenciaRegistro;
use App\Models\HorarioEscalaDia;
use App\Support\Rh\AfastamentoAcidenteTrabalho;
use App\Support\Rh\ValeAlimentacaoRegraConfig;
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
        return \App\Models\BeneficioExtratoRegra::pareceValeAlimentacao($beneficio);
    }

    /**
     * @return array<string, mixed>
     */
    public function calcularParaVinculo(
        ColaboradorBeneficio $vinculo,
        Beneficio $beneficio,
        ?Carbon $mesPagamento = null,
        ?Carbon $periodoInicioApuracao = null,
        ?Carbon $periodoFimApuracao = null,
        bool $forcarRegraAssiduidade = false,
        ?ValeAlimentacaoRegraConfig $config = null
    ): array {
        $config ??= ValeAlimentacaoRegraConfig::resolver(null);
        $mesPagamento ??= Carbon::now()->startOfMonth();
        $mesPagamento = $mesPagamento->copy()->startOfMonth();

        [$inicioApuracao, $fimApuracao] = $this->normalizarPeriodoApuracao(
            $mesPagamento,
            $periodoInicioApuracao,
            $periodoFimApuracao
        );

        $valorBase = (float) ($beneficio->valor ?? 0);
        $vazio = [
            'aplica' => false,
            'mes_pagamento' => $mesPagamento->format('m/Y'),
            'periodo_apuracao' => $this->formatarPeriodoApuracao($inicioApuracao, $fimApuracao),
            'valor_base' => $valorBase,
            'valor_final' => $valorBase,
        ];

        $aplicaRegra = $forcarRegraAssiduidade || $this->usaCalculoAssiduidade($beneficio);
        if (! $aplicaRegra || $valorBase <= 0) {
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

        $faltasInjustificadas = $this->contarFaltasInjustificadasNoPeriodo($colaborador, $inicioApuracao, $fimApuracao);

        $proporcao = $config->proporcionalAdmissaoDemissao()
            ? $this->fatorProporcionalMes($colaborador, $mesPagamento)
            : ['fator' => 1.0, 'dias_uteis_mes' => 0, 'dias_com_direito' => 0, 'faltas_injustificadas' => 0];
        $valorProporcional = round($valorBase * $proporcao['fator'], 2);

        $percentualDesconto = $config->percentualDescontoPorFaltas($faltasInjustificadas);
        $situacaoAcidente = $this->situacaoAcidenteTrabalho($colaborador, $fimApuracao, $config);
        $isentoAcidente = $situacaoAcidente['isento'];
        if ($isentoAcidente) {
            $percentualDesconto = 0.0;
        }

        $valorDescontadoAssiduidade = round($valorProporcional * $percentualDesconto, 2);
        $valorDescontadoProporcional = round(max(0, $valorBase - $valorProporcional), 2);
        $valorDescontado = round($valorDescontadoProporcional + $valorDescontadoAssiduidade, 2);
        $valorFinal = round(max(0, $valorProporcional - $valorDescontadoAssiduidade), 2);

        $recargaNatal = $this->calcularRecargaNatal(
            $vinculo,
            $colaborador,
            $config,
            $faltasInjustificadas,
            $inicioApuracao,
            $fimApuracao
        );

        return [
            'aplica' => true,
            'mes_pagamento' => $mesPagamento->format('m/Y'),
            'periodo_apuracao' => $this->formatarPeriodoApuracao($inicioApuracao, $fimApuracao),
            'valor_base' => $valorBase,
            'faltas_injustificadas' => $faltasInjustificadas,
            'percentual_desconto' => (int) round($percentualDesconto * 100),
            'valor_descontado' => $valorDescontado,
            'valor_descontado_assiduidade' => $valorDescontadoAssiduidade,
            'valor_descontado_proporcional' => $valorDescontadoProporcional,
            'fator_proporcional' => $proporcao['fator'],
            'dias_uteis_mes' => $proporcao['dias_uteis_mes'],
            'dias_com_direito' => $proporcao['dias_com_direito'],
            'valor_proporcional' => $valorProporcional,
            'valor_final' => $valorFinal,
            'isento_acidente_trabalho' => $isentoAcidente,
            'acidente_trabalho_mes_afastamento' => $situacaoAcidente['mes_afastamento'],
            'acidente_trabalho_limite_meses' => $situacaoAcidente['limite_meses'],
            'recarga_natal' => $recargaNatal,
            'dias_apuracao' => $this->listarDiasApuracaoParaExtrato(
                $colaborador,
                $inicioApuracao,
                $fimApuracao,
                $mesPagamento,
                $proporcao,
                $config->proporcionalAdmissaoDemissao()
            ),
            'detalhe' => $this->montarDetalhe($faltasInjustificadas, $percentualDesconto, $proporcao, $config, $situacaoAcidente, $recargaNatal),
        ];
    }

    /**
     * @param  Collection<int, ColaboradorBeneficio>  $vinculos
     * @return array<int, array<string, mixed>>
     */
    public function calcularParaVinculos(
        Collection $vinculos,
        Beneficio $beneficio,
        ?Carbon $mesPagamento = null,
        ?Carbon $periodoInicioApuracao = null,
        ?Carbon $periodoFimApuracao = null,
        bool $forcarRegraAssiduidade = false,
        ?ValeAlimentacaoRegraConfig $config = null
    ): array {
        $map = [];
        foreach ($vinculos as $vinculo) {
            $map[$vinculo->id] = $this->calcularParaVinculo(
                $vinculo,
                $beneficio,
                $mesPagamento,
                $periodoInicioApuracao,
                $periodoFimApuracao,
                $forcarRegraAssiduidade,
                $config
            );
        }

        return $map;
    }

    public function contarFaltasInjustificadasNoPeriodo(
        Colaborador $colaborador,
        Carbon $periodoInicio,
        Carbon $periodoFim
    ): int {
        return count($this->listarFaltasInjustificadasNoPeriodo($colaborador, $periodoInicio, $periodoFim));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listarFaltasInjustificadasNoPeriodo(
        Colaborador $colaborador,
        Carbon $periodoInicio,
        Carbon $periodoFim
    ): array {
        $inicio = $periodoInicio->copy()->startOfDay();
        $fim = $periodoFim->copy()->startOfDay();

        $cartoes = $this->cartaoPonto->montarCartoes(
            collect([$colaborador]),
            $inicio->toDateString(),
            $fim->toDateString()
        );

        $linhas = $cartoes[0]['linhas'] ?? [];
        $dias = [];

        foreach ($linhas as $linha) {
            if (! $this->linhaEhFaltaInjustificadaIntegral($linha)) {
                continue;
            }

            $ymd = (string) ($linha['data_ymd'] ?? '');
            if ($ymd === '') {
                continue;
            }

            $dia = Carbon::parse($ymd)->startOfDay();
            if ($dia->lt($inicio) || $dia->gt($fim)) {
                continue;
            }

            if (! ColaboradorVinculoPonto::contaPontoNaData($colaborador, $dia)) {
                continue;
            }

            $entrada1 = (string) ($linha['entrada_1'] ?? 'Falta');
            $dias[] = $this->montarItemDiaApuracao(
                $dia,
                'falta_injustificada',
                'Falta injustificada',
                ($entrada1 !== '' ? $entrada1 : 'Falta integral no cartão de ponto').' — conta na assiduidade do vale',
                'desconto'
            );
        }

        usort($dias, fn (array $a, array $b): int => strcmp($a['data'], $b['data']));

        return $dias;
    }

    /**
     * @param  array{faltas_injustificadas?: int, dias_uteis_mes: int, dias_com_direito: int, fator: float}  $proporcao
     * @return list<array<string, mixed>>
     */
    public function listarDiasApuracaoParaExtrato(
        Colaborador $colaborador,
        Carbon $periodoInicio,
        Carbon $periodoFim,
        Carbon $mesPagamento,
        array $proporcao,
        bool $incluirProporcionalMes = true
    ): array {
        $dias = $this->listarFaltasInjustificadasNoPeriodo($colaborador, $periodoInicio, $periodoFim);

        if (
            $incluirProporcionalMes
            && ($proporcao['dias_com_direito'] ?? 0) < ($proporcao['dias_uteis_mes'] ?? 0)
        ) {
            $dias = array_merge($dias, $this->listarDiasSemDireitoMesPagamento($colaborador, $mesPagamento));
        }

        usort($dias, fn (array $a, array $b): int => strcmp($a['data'], $b['data']));

        return $dias;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function listarDiasSemDireitoMesPagamento(Colaborador $colaborador, Carbon $mes): array
    {
        $colaborador->loadMissing(['horarioEscala.dias', 'horarioEscala.excecoes']);

        $inicio = $mes->copy()->startOfMonth();
        $fim = $mes->copy()->endOfMonth();
        $dias = [];

        foreach (CarbonPeriod::create($inicio, $fim) as $dia) {
            $diaEscala = $colaborador->horarioEscalaDiaNaData($dia);
            if (! $this->diaTemJornadaEscala($diaEscala)) {
                continue;
            }

            if (ColaboradorVinculoPonto::contaPontoNaData($colaborador, $dia)) {
                continue;
            }

            $dias[] = $this->montarItemDiaApuracao(
                $dia->copy()->startOfDay(),
                'sem_direito_mes',
                'Sem direito no mês',
                'Dia útil da escala fora do vínculo no mês '.$mes->format('m/Y').' — reduz valor proporcional',
                'desconto'
            );
        }

        return $dias;
    }

    /**
     * @return array<string, mixed>
     */
    private function montarItemDiaApuracao(
        Carbon $dia,
        string $tipo,
        string $tipoLabel,
        string $descricao,
        string $impacto
    ): array {
        $diasSemana = [1 => 'SEG', 2 => 'TER', 3 => 'QUA', 4 => 'QUI', 5 => 'SEX', 6 => 'SAB', 7 => 'DOM'];

        return [
            'data' => $dia->toDateString(),
            'data_fmt' => $dia->format('d/m/Y'),
            'dia_semana' => $diasSemana[(int) $dia->isoWeekday()] ?? '',
            'tipo' => $tipo,
            'tipo_label' => $tipoLabel,
            'descricao' => $descricao,
            'valor' => 0.0,
            'minutos_trabalhado' => null,
            'impacto' => $impacto,
        ];
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function normalizarPeriodoApuracao(
        Carbon $mesPagamento,
        ?Carbon $periodoInicioApuracao,
        ?Carbon $periodoFimApuracao
    ): array {
        $fimPadrao = $mesPagamento->copy()->endOfMonth()->startOfDay();
        $inicioPadrao = $mesPagamento->copy()->startOfMonth()->startOfDay();

        $inicio = ($periodoInicioApuracao ?? $inicioPadrao)->copy()->startOfDay();
        $fim = ($periodoFimApuracao ?? $fimPadrao)->copy()->startOfDay();

        if ($fim->lt($inicio)) {
            [$inicio, $fim] = [$fim, $inicio];
        }

        return [$inicio, $fim];
    }

    private function formatarPeriodoApuracao(Carbon $inicio, Carbon $fim): string
    {
        if ($inicio->isSameDay($fim)) {
            return $inicio->format('d/m/Y');
        }

        return $inicio->format('d/m/Y').' a '.$fim->format('d/m/Y');
    }

    public function percentualDescontoAssiduidade(int $faltasInjustificadas, ?ValeAlimentacaoRegraConfig $config = null): float
    {
        $config ??= ValeAlimentacaoRegraConfig::resolver(null);

        return $config->percentualDescontoPorFaltas($faltasInjustificadas);
    }

    public function isentoDescontoPorAcidenteTrabalho(
        Colaborador $colaborador,
        Carbon $mesPagamento,
        ValeAlimentacaoRegraConfig $config
    ): bool {
        return $this->situacaoAcidenteTrabalho($colaborador, $mesPagamento, $config)['isento'];
    }

    /**
     * @return array{isento: bool, mes_afastamento: int|null, limite_meses: int, movimentacao_id: int|null}
     */
    public function situacaoAcidenteTrabalho(
        Colaborador $colaborador,
        Carbon $mesPagamento,
        ValeAlimentacaoRegraConfig $config
    ): array {
        $regra = $config->afastamentoAcidente();
        if (! ($regra['ativo'] ?? false)) {
            return [
                'isento' => false,
                'mes_afastamento' => null,
                'limite_meses' => (int) ($regra['meses_limite_integral'] ?? 3),
                'movimentacao_id' => null,
            ];
        }

        $limiteMeses = (int) ($regra['meses_limite_integral'] ?? 3);

        return AfastamentoAcidenteTrabalho::situacaoValeAlimentacaoNoMes(
            $colaborador,
            $mesPagamento->copy()->startOfMonth(),
            $limiteMeses
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function calcularRecargaNatal(
        ColaboradorBeneficio $vinculo,
        Colaborador $colaborador,
        ValeAlimentacaoRegraConfig $config,
        int $faltasInjustificadasPeriodo,
        Carbon $periodoInicio,
        Carbon $periodoFim
    ): array {
        $natal = $config->recargaNatal();
        $vazio = [
            'aplica' => false,
            'valor' => 0.0,
            'detalhe' => 'Recarga de Natal desativada na configuração.',
        ];

        if (! ($natal['ativo'] ?? false)) {
            return $vazio;
        }

        $cargo = mb_strtolower((string) ($colaborador->cargo ?? ''));
        foreach ($config->cargosExcluidosRecargaNatal() as $termo) {
            if ($termo !== '' && str_contains($cargo, $termo)) {
                return [
                    'aplica' => true,
                    'valor' => 0.0,
                    'detalhe' => 'Cargo de gestão/coordenação — não elegível à recarga extra de Natal.',
                ];
            }
        }

        if (($natal['exige_sindicalizado'] ?? false) && ! $this->colaboradorElegivelSindicalizado($vinculo, $colaborador)) {
            return [
                'aplica' => true,
                'valor' => 0.0,
                'detalhe' => 'Recarga de Natal: exige vínculo sindicalizado/contribuinte (marque nas observações do vínculo).',
            ];
        }

        $iniAtest = Carbon::parse($natal['periodo_atestados_inicio'])->startOfDay();
        $fimAtest = Carbon::parse($natal['periodo_atestados_fim'])->endOfDay();
        $intersecta = $periodoFim->gte($iniAtest) && $periodoInicio->lte($fimAtest);

        if (! $intersecta && ! $periodoFim->between($iniAtest, $fimAtest)) {
            return [
                'aplica' => false,
                'valor' => 0.0,
                'detalhe' => 'Período fora da vigência da recarga de Natal configurada.',
            ];
        }

        $qtdAtestados = FrequenciaRegistro::query()
            ->where('colaborador_id', $colaborador->id)
            ->whereBetween('data', [$iniAtest->toDateString(), $fimAtest->toDateString()])
            ->atestadoMedico()
            ->count();

        $pctValor = $config->percentualValorRecargaPorAtestados($qtdAtestados);
        $valorIntegral = (float) ($natal['valor_integral'] ?? 0);
        $valor = round($valorIntegral * $pctValor, 2);

        if ($faltasInjustificadasPeriodo >= 1) {
            $perda = ((float) ($natal['perda_uma_falta_injustificada_percentual'] ?? 100)) / 100;
            $valor = round($valor * (1 - $perda), 2);
        }

        return [
            'aplica' => true,
            'valor' => $valor,
            'valor_integral' => $valorIntegral,
            'atestados_periodo' => $qtdAtestados,
            'percentual_faixa' => (int) round($pctValor * 100),
            'detalhe' => sprintf(
                'Recarga Natal: %d atestado(s) no período configurado → %d%% do valor base.',
                $qtdAtestados,
                (int) round($pctValor * 100)
            ).($faltasInjustificadasPeriodo >= 1 ? ' Perda por falta injustificada no período de apuração.' : ''),
        ];
    }

    private function colaboradorElegivelSindicalizado(ColaboradorBeneficio $vinculo, Colaborador $colaborador): bool
    {
        $texto = mb_strtolower(implode(' ', array_filter([
            (string) $vinculo->observacoes,
            (string) $colaborador->observacoes,
        ])));

        foreach (['sindical', 'simetal', 'associado', 'contribuinte'] as $termo) {
            if (str_contains($texto, $termo)) {
                return true;
            }
        }

        return false;
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
    /**
     * @param  array<string, mixed>  $recargaNatal
     */
    private function montarDetalhe(
        int $faltas,
        float $percentualDesconto,
        array $proporcao,
        ValeAlimentacaoRegraConfig $config,
        array $situacaoAcidente,
        array $recargaNatal
    ): string {
        $partes = [];

        if ($proporcao['dias_com_direito'] < $proporcao['dias_uteis_mes']) {
            $partes[] = sprintf(
                'Proporcional admissão/demissão: %d de %d dias úteis.',
                $proporcao['dias_com_direito'],
                $proporcao['dias_uteis_mes']
            );
        }

        if ($situacaoAcidente['isento']) {
            $mes = (int) ($situacaoAcidente['mes_afastamento'] ?? 0);
            $limite = (int) ($situacaoAcidente['limite_meses'] ?? 3);
            $partes[] = sprintf(
                'Afastamento por acidente de trabalho (mês %d de %d) → vale alimentação sem desconto por falta neste pagamento.',
                $mes,
                $limite
            );
        } elseif ($situacaoAcidente['mes_afastamento'] !== null) {
            $partes[] = sprintf(
                'Afastamento por acidente de trabalho: mês %d — ultrapassou o limite de %d mês(es) com vale integral; aplica-se desconto por assiduidade.',
                $situacaoAcidente['mes_afastamento'],
                $situacaoAcidente['limite_meses']
            );
            $partes[] = $config->textoFaixaDesconto($faltas);
        } else {
            $partes[] = $config->textoFaixaDesconto($faltas);
        }

        if ($recargaNatal['aplica'] ?? false) {
            $partes[] = $recargaNatal['detalhe'] ?? '';
        }

        return implode(' ', array_filter($partes));
    }
}
