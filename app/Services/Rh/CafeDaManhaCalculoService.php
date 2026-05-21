<?php

namespace App\Services\Rh;

use App\Models\Beneficio;
use App\Models\BeneficioExtratoRegra;
use App\Models\Colaborador;
use App\Models\ColaboradorBeneficio;
use App\Support\EscalaPontoRegras;
use App\Support\FeriadoPontoService;
use App\Support\Rh\CafeDaManhaRegraConfig;
use App\Support\Rh\CartaoPontoService;
use App\Support\Rh\ColaboradorVinculoPonto;
use Carbon\Carbon;

/**
 * Café da manhã (ACT): R$ 7,95 por dia com horas na apuração; teto R$ 175 sem falta/atestado/justificativa em dia útil.
 * Convocação em sábado, domingo, feriado ou repouso com horas trabalhadas também gera o valor diário.
 */
class CafeDaManhaCalculoService
{
    public function __construct(
        private readonly CartaoPontoService $cartaoPonto
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function calcularParaVinculo(
        ColaboradorBeneficio $vinculo,
        Beneficio $beneficio,
        Carbon $periodoInicio,
        Carbon $periodoFim,
        ?CafeDaManhaRegraConfig $config = null
    ): array {
        $config ??= CafeDaManhaRegraConfig::resolver(null);
        $valorMensal = (float) ($beneficio->valor ?? 0) > 0
            ? (float) $beneficio->valor
            : $config->valorMensalCheio();
        $valorDiario = $config->valorDiario();

        $vazio = [
            'aplica' => false,
            'tipo_regra' => BeneficioExtratoRegra::TIPO_CAFE_MANHA,
            'valor_base' => $valorMensal,
            'valor_diario' => $valorDiario,
            'valor_final' => 0.0,
            'dias_apuracao' => [],
        ];

        if (! $vinculo->tem_direito) {
            return array_merge($vazio, [
                'aplica' => true,
                'detalhe' => 'Sem direito ao benefício neste vínculo.',
            ]);
        }

        $colaborador = $vinculo->colaborador;
        if ($colaborador === null) {
            return $vazio;
        }

        [$inicio, $fim] = $this->intersectarPeriodos(
            $periodoInicio,
            $periodoFim,
            Carbon::parse($config->periodoVigenciaInicio())->startOfDay(),
            Carbon::parse($config->periodoVigenciaFim())->endOfDay()
        );

        if ($inicio === null || $fim === null) {
            return array_merge($vazio, [
                'aplica' => true,
                'periodo_apuracao' => $this->formatarPeriodo($periodoInicio, $periodoFim),
                'detalhe' => 'Período fora da vigência do ACT configurado para café da manhã.',
            ]);
        }

        $resumo = $this->resumirDiasTrabalhados($colaborador, $inicio, $fim, $config);
        $valorProporcional = round($resumo['valor_total_dias'], 2);
        $semPenalidadeDiaUtil = $resumo['dias_justificado_sem_trabalho'] === 0
            && $resumo['dias_sem_trabalho'] === 0;

        if ($config->tetoMensalAtivo() && $semPenalidadeDiaUtil && $resumo['dias_trabalhados'] > 0) {
            $valorFinal = round($valorMensal, 2);
            $valorCheioAplicado = true;
        } elseif ($config->tetoMensalAtivo()) {
            $valorFinal = round(min($valorMensal, $valorProporcional), 2);
            $valorCheioAplicado = false;
        } else {
            $valorFinal = $valorProporcional;
            $valorCheioAplicado = false;
        }

        $diasSemPagamentoUteis = $resumo['dias_justificado_sem_trabalho'] + $resumo['dias_sem_trabalho'];
        $valorDescontado = round($diasSemPagamentoUteis * $valorDiario, 2);
        $valorBrutoApuracao = $valorDescontado > 0
            ? round($valorFinal + $valorDescontado, 2)
            : $valorFinal;

        return [
            'aplica' => true,
            'tipo_regra' => BeneficioExtratoRegra::TIPO_CAFE_MANHA,
            'periodo_apuracao' => $this->formatarPeriodo($inicio, $fim),
            'valor_base' => $valorMensal,
            'valor_diario' => $valorDiario,
            'dias_trabalhados' => $resumo['dias_trabalhados'],
            'dias_com_justificativa_sem_trabalho' => $resumo['dias_justificado_sem_trabalho'],
            'dias_sem_trabalho' => $resumo['dias_sem_trabalho'],
            'valor_proporcional' => $valorProporcional,
            'valor_bruto_apuracao' => $valorBrutoApuracao,
            'valor_descontado' => $valorDescontado,
            'valor_final' => $valorFinal,
            'valor_cheio_aplicado' => $valorCheioAplicado,
            'dias_apuracao' => $resumo['dias'],
            'detalhe' => $this->montarDetalhe($resumo, $valorFinal, $valorMensal, $valorCheioAplicado, $config),
        ];
    }

    /**
     * @return array{
     *   dias_trabalhados: int,
     *   dias_justificado_sem_trabalho: int,
     *   dias_sem_trabalho: int,
     *   valor_total_dias: float,
     *   dias: list<array<string, mixed>>
     * }
     */
    public function resumirDiasTrabalhados(
        Colaborador $colaborador,
        Carbon $periodoInicio,
        Carbon $periodoFim,
        CafeDaManhaRegraConfig $config
    ): array {
        $inicio = $periodoInicio->copy()->startOfDay();
        $fim = $periodoFim->copy()->startOfDay();
        $minMinutos = $config->minutosMinimosDiaTrabalhado();

        $cartoes = $this->cartaoPonto->montarCartoes(
            collect([$colaborador]),
            $inicio->toDateString(),
            $fim->toDateString()
        );

        $linhas = $cartoes[0]['linhas'] ?? [];
        $diasTrabalhados = 0;
        $diasJustificadoSemTrabalho = 0;
        $diasSemTrabalho = 0;
        $valorTotal = 0.0;
        $diasDetalhe = [];

        foreach ($linhas as $linha) {
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

            if (empty($linha['registro_id'])) {
                continue;
            }

            $minutos = (int) ($linha['minutos_trabalhado'] ?? 0);
            $status = (string) ($linha['status'] ?? '');
            $ehRotulo = (bool) ($linha['eh_rotulo'] ?? false);
            $entrada1 = (string) ($linha['entrada_1'] ?? '');
            $convocacao = $this->diaConvocacaoOuRepouso($colaborador, $dia, $linha);

            if (! $ehRotulo && $minutos >= $minMinutos) {
                $valorDia = $config->valorDiarioParaDia($convocacao);
                $diasTrabalhados++;
                $valorTotal += $valorDia;
                $rotuloConvocacao = $convocacao ? ' (convocação em repouso/feriado)' : '';
                $diasDetalhe[] = $this->montarItemDia(
                    $dia,
                    'trabalhado',
                    'Pago — '.$this->formatarMinutos($minutos).' trabalhadas na apuração'.$rotuloConvocacao,
                    $valorDia,
                    $minutos
                );

                continue;
            }

            if (! $this->diaUtilParaPenalidadeCafe($colaborador, $dia, $linha)) {
                continue;
            }

            if ($ehRotulo || $status === 'justificado' || ($linha['atestado'] ?? '') === '1') {
                $diasJustificadoSemTrabalho++;
                $diasDetalhe[] = $this->montarItemDia(
                    $dia,
                    'justificado_sem_horas',
                    'Sem valor diário — '.($entrada1 !== '' && $entrada1 !== 'Falta' ? $entrada1 : 'justificado/atestado sem horas na apuração'),
                    0.0,
                    $minutos
                );

                continue;
            }

            $diasSemTrabalho++;
            $diasDetalhe[] = $this->montarItemDia(
                $dia,
                'sem_horas',
                'Sem valor diário — falta ou sem minutos trabalhados na apuração',
                0.0,
                $minutos
            );
        }

        usort($diasDetalhe, fn (array $a, array $b): int => strcmp($a['data'], $b['data']));

        return [
            'dias_trabalhados' => $diasTrabalhados,
            'dias_justificado_sem_trabalho' => $diasJustificadoSemTrabalho,
            'dias_sem_trabalho' => $diasSemTrabalho,
            'valor_total_dias' => round($valorTotal, 2),
            'dias' => $diasDetalhe,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function montarItemDia(
        Carbon $dia,
        string $tipo,
        string $descricao,
        float $valorDia,
        int $minutos
    ): array {
        $diasSemana = [1 => 'SEG', 2 => 'TER', 3 => 'QUA', 4 => 'QUI', 5 => 'SEX', 6 => 'SAB', 7 => 'DOM'];

        return [
            'data' => $dia->toDateString(),
            'data_fmt' => $dia->format('d/m/Y'),
            'dia_semana' => $diasSemana[(int) $dia->isoWeekday()] ?? '',
            'tipo' => $tipo,
            'tipo_label' => match ($tipo) {
                'trabalhado' => 'Dia pago',
                'justificado_sem_horas' => 'Justificado sem horas',
                default => 'Sem pagamento',
            },
            'descricao' => $descricao,
            'valor' => $valorDia,
            'minutos_trabalhado' => $minutos,
            'impacto' => $valorDia > 0 ? 'credito' : 'desconto',
        ];
    }

    private function formatarMinutos(int $minutos): string
    {
        if ($minutos <= 0) {
            return '0h';
        }

        $h = intdiv($minutos, 60);
        $m = $minutos % 60;

        return sprintf('%d:%02d', $h, $m);
    }

    /**
     * @return array{0: ?Carbon, 1: ?Carbon}
     */
    private function intersectarPeriodos(
        Carbon $pedidoInicio,
        Carbon $pedidoFim,
        Carbon $vigenciaInicio,
        Carbon $vigenciaFim
    ): array {
        $inicio = $pedidoInicio->copy()->startOfDay();
        $fim = $pedidoFim->copy()->startOfDay();

        if ($fim->lt($vigenciaInicio->startOfDay()) || $inicio->gt($vigenciaFim->startOfDay())) {
            return [null, null];
        }

        if ($inicio->lt($vigenciaInicio)) {
            $inicio = $vigenciaInicio->copy()->startOfDay();
        }
        if ($fim->gt($vigenciaFim)) {
            $fim = $vigenciaFim->copy()->startOfDay();
        }

        return [$inicio, $fim];
    }

    private function formatarPeriodo(Carbon $inicio, Carbon $fim): string
    {
        if ($inicio->isSameDay($fim)) {
            return $inicio->format('d/m/Y');
        }

        return $inicio->format('d/m/Y').' a '.$fim->format('d/m/Y');
    }

    /**
     * @param  array{dias_trabalhados: int, dias_justificado_sem_trabalho: int, dias_sem_trabalho: int, valor_total_dias: float}  $resumo
     */
    private function montarDetalhe(
        array $resumo,
        float $valorFinal,
        float $valorMensal,
        bool $valorCheioAplicado,
        CafeDaManhaRegraConfig $config
    ): string {
        $partes = [];

        if ($valorCheioAplicado) {
            $partes[] = sprintf(
                'Sem falta/atestado/justificativa em dias úteis — valor integral R$ %s',
                number_format($valorMensal, 2, ',', '.')
            );
            if ($resumo['dias_trabalhados'] > 0) {
                $partes[] = sprintf(
                    '(%d dia(s) com horas na apuração, proporcional diário R$ %s)',
                    $resumo['dias_trabalhados'],
                    number_format($config->valorDiario(), 2, ',', '.')
                );
            }
        } else {
            $partes[] = sprintf(
                '%d dia(s) com horas na apuração × R$ %s',
                $resumo['dias_trabalhados'],
                number_format($config->valorDiario(), 2, ',', '.')
            );
        }

        if ($resumo['dias_justificado_sem_trabalho'] > 0) {
            $partes[] = sprintf(
                '%d dia(s) útil(eis) justificado(s)/atestado sem horas → reduz benefício',
                $resumo['dias_justificado_sem_trabalho']
            );
        }

        if ($resumo['dias_sem_trabalho'] > 0) {
            $partes[] = sprintf(
                '%d dia(s) útil(eis) com falta/sem horas → reduz benefício',
                $resumo['dias_sem_trabalho']
            );
        }

        if (! $valorCheioAplicado && $config->tetoMensalAtivo() && $valorFinal >= $valorMensal - 0.01) {
            $partes[] = 'Teto mensal R$ '.number_format($valorMensal, 2, ',', '.').' aplicado.';
        }

        $partes[] = 'Convocação em sábado, domingo, feriado ou repouso com horas trabalhadas também recebe o valor diário.';

        return implode(' ', $partes);
    }

    /**
     * Sábado, domingo, feriado cadastrado ou folga na escala (sem ser dia útil de penalidade).
     *
     * @param  array<string, mixed>  $linha
     */
    private function diaConvocacaoOuRepouso(Colaborador $colaborador, Carbon $dia, array $linha): bool
    {
        if ($dia->isWeekend()) {
            return true;
        }

        if (app(FeriadoPontoService::class)->diaAbonadoPorFeriado($dia)) {
            return true;
        }

        if (app(EscalaPontoRegras::class)->diaAbonadoPorFolgaEscala($colaborador, $dia)) {
            return true;
        }

        if ((bool) ($linha['eh_rotulo'] ?? false)) {
            $rotulo = mb_strtolower(trim((string) ($linha['entrada_1'] ?? '')));
            if (
                str_contains($rotulo, 'feriado')
                || $rotulo === 'folga'
                || str_starts_with($rotulo, 'feriado:')
            ) {
                return true;
            }
        }

        return in_array((string) ($linha['status'] ?? ''), ['folga', 'feriado'], true);
    }

    /**
     * Penalidade (falta/justificativa sem horas) só em dia útil com jornada esperada.
     *
     * @param  array<string, mixed>  $linha
     */
    private function diaUtilParaPenalidadeCafe(Colaborador $colaborador, Carbon $dia, array $linha): bool
    {
        return ! $this->diaConvocacaoOuRepouso($colaborador, $dia, $linha);
    }
}
