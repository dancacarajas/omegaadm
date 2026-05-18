<?php

namespace App\Support\Rh;

use App\Models\Colaborador;
use App\Models\FrequenciaRegistro;
use App\Models\HorarioEscalaDia;
use App\Support\EscalaPontoRegras;
use App\Support\FrequenciaCalculo;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

class CartaoPontoService
{
    private const DIAS_SEMANA = [
        1 => 'SEG',
        2 => 'TER',
        3 => 'QUA',
        4 => 'QUI',
        5 => 'SEX',
        6 => 'SAB',
        7 => 'DOM',
    ];

    /**
     * @param  Collection<int, Colaborador>  $colaboradores
     * @return list<array<string, mixed>>
     */
    public function montarCartoes(Collection $colaboradores, string $dataInicio, string $dataFim): array
    {
        $inicio = Carbon::parse($dataInicio)->startOfDay();
        $fim = Carbon::parse($dataFim)->startOfDay();
        if ($fim->lt($inicio)) {
            [$inicio, $fim] = [$fim, $inicio];
        }

        $ids = $colaboradores->pluck('id')->all();
        $registrosPorColaborador = FrequenciaRegistro::query()
            ->whereIn('colaborador_id', $ids)
            ->whereDate('data', '>=', $inicio->toDateString())
            ->whereDate('data', '<=', $fim->toDateString())
            ->get()
            ->groupBy('colaborador_id');

        $regras = app(EscalaPontoRegras::class);
        $cartoes = [];

        foreach ($colaboradores as $colaborador) {
            $colaborador->loadMissing(['horarioEscala.dias']);
            $registros = $registrosPorColaborador->get($colaborador->id, collect())
                ->keyBy(fn (FrequenciaRegistro $r) => $r->data instanceof \DateTimeInterface
                    ? $r->data->format('Y-m-d')
                    : Carbon::parse($r->data)->format('Y-m-d'));

            $linhas = [];
            $totais = [
                'normais' => 0,
                'trabalhado' => 0,
                'noturno' => 0,
                'previstas' => 0,
                'dia_falta' => 0,
                'horas_falta' => 0,
                'horas_atraso' => 0,
                'falta_atraso' => 0,
                'extras' => 0,
            ];

            foreach (CarbonPeriod::create($inicio, $fim) as $dia) {
                $ymd = $dia->format('Y-m-d');
                /** @var FrequenciaRegistro|null $registro */
                $registro = $registros->get($ymd);
                $linha = $this->montarLinhaDia($colaborador, $dia, $registro, $regras);
                $linhas[] = $linha;

                $totais['normais'] += $linha['minutos_normais'];
                $totais['trabalhado'] += $linha['minutos_trabalhado'];
                $totais['noturno'] += $linha['minutos_noturno'];
                $totais['previstas'] += $linha['minutos_previstas'];
                $totais['dia_falta'] += (int) ($linha['minutos_dia_falta'] ?? $linha['dia_falta'] ?? 0);
                $totais['horas_falta'] += $linha['minutos_falta'];
                $totais['horas_atraso'] += $linha['minutos_atraso'];
                $totais['falta_atraso'] += $linha['minutos_falta_atraso'];
                $totais['extras'] += $linha['minutos_extras'];
            }

            $cartoes[] = [
                'colaborador' => $colaborador,
                'horario_semana' => $this->horarioSemana($colaborador),
                'linhas' => $linhas,
                'totais' => [
                    'normais' => FrequenciaCalculo::formatarMinutosRelogio($totais['normais']),
                    'trabalhado' => FrequenciaCalculo::formatarMinutosRelogio($totais['trabalhado']),
                    'noturno' => FrequenciaCalculo::formatarMinutosRelogio($totais['noturno']),
                    'previstas' => FrequenciaCalculo::formatarMinutosRelogio($totais['previstas']),
                    'dia_falta' => $totais['dia_falta'] > 0 ? (string) $totais['dia_falta'] : '',
                    'horas_falta' => FrequenciaCalculo::formatarMinutosRelogio($totais['horas_falta']),
                    'horas_atraso' => FrequenciaCalculo::formatarMinutosRelogio($totais['horas_atraso']),
                    'falta_atraso' => FrequenciaCalculo::formatarMinutosRelogio($totais['falta_atraso']),
                    'atestado' => '',
                    'extras' => FrequenciaCalculo::formatarMinutosRelogio($totais['extras']),
                ],
            ];
        }

        return $cartoes;
    }

    /**
     * @return list<array{label: string, entrada_1: string, saida_1: string, entrada_2: string, saida_2: string}>
     */
    private function horarioSemana(Colaborador $colaborador): array
    {
        $escala = $colaborador->horarioEscala;
        if (! $escala) {
            return collect(self::DIAS_SEMANA)
                ->map(fn (string $label) => [
                    'label' => $label,
                    'entrada_1' => '',
                    'saida_1' => '',
                    'entrada_2' => '',
                    'saida_2' => '',
                ])
                ->values()
                ->all();
        }

        $dias = $escala->dias->keyBy('dia_semana');
        $grade = [];

        foreach (self::DIAS_SEMANA as $num => $label) {
            /** @var HorarioEscalaDia|null $dia */
            $dia = $dias->get($num);
            $grade[] = [
                'label' => $label,
                'entrada_1' => $this->fmtHoraGrade($dia?->entrada_1),
                'saida_1' => $this->fmtHoraGrade($dia?->saida_1),
                'entrada_2' => $this->fmtHoraGrade($dia?->entrada_2),
                'saida_2' => $this->fmtHoraGrade($dia?->saida_2),
            ];
        }

        return $grade;
    }

    /**
     * @return array<string, mixed>
     */
    private function montarLinhaDia(
        Colaborador $colaborador,
        Carbon $dia,
        ?FrequenciaRegistro $registro,
        EscalaPontoRegras $regras
    ): array {
        $diaEscala = $colaborador->horarioEscalaDiaNaData($dia);
        $temJornada = $this->diaTemJornada($diaEscala);
        $rotuloEspecial = $this->rotuloEspecialDia($colaborador, $registro, $temJornada);

        if ($rotuloEspecial !== null) {
            return $this->linhaRotulo($dia, $rotuloEspecial);
        }

        if (! $registro) {
            return $this->linhaVazia($dia, $temJornada);
        }

        $sufixo = $this->sufixoOrigem($registro->origem);
        $resumo = FrequenciaCalculo::resumo($registro);
        $minutosTrabalhado = (int) $resumo['trabalhadas'];
        $minutosPrevistas = (int) $resumo['jornada_esperada_minutos'];
        $minutosExtras = (int) $resumo['extras'];
        $minutosFalta = $resumo['falta'] !== null ? (int) $resumo['falta'] : 0;
        $minutosNormais = min($minutosTrabalhado, max(0, $minutosPrevistas));
        $minutosAtraso = $this->minutosAtraso($registro, $diaEscala, $dia->format('Y-m-d'));

        $status = (string) ($registro->status ?? 'falta');
        $diaFalta = $status === 'falta' ? 1 : 0;
        $atestado = $status === 'justificado' && $registro->justificativa_tipo === 'atestado' ? '1' : '';

        return [
            'dia' => $dia->format('d/m/Y').' - '.self::DIAS_SEMANA[(int) $dia->isoWeekday()],
            'entrada_1' => $this->fmtBatida($registro->entrada_1, $sufixo),
            'saida_1' => $this->fmtBatida($registro->saida_1, $sufixo),
            'entrada_2' => $this->fmtBatida($registro->entrada_2, $sufixo),
            'saida_2' => $this->fmtBatida($registro->saida_2, $sufixo),
            'total_normais' => $this->fmtCelulaHoras($minutosNormais),
            'total_trabalhado' => $this->fmtCelulaHoras($minutosTrabalhado),
            'adicional_noturno' => $this->fmtCelulaHoras(0) ?: '00:00',
            'horas_previstas' => $this->fmtCelulaHoras($minutosPrevistas),
            'dia_falta' => $diaFalta > 0 ? '1' : '',
            'horas_falta' => $this->fmtCelulaHoras($minutosFalta),
            'horas_atraso' => $this->fmtCelulaHoras($minutosAtraso),
            'falta_atraso' => $this->fmtCelulaHoras($minutosFalta + $minutosAtraso),
            'atestado' => $atestado,
            'extras_total' => $this->fmtCelulaHoras($minutosExtras),
            'minutos_normais' => $minutosNormais,
            'minutos_trabalhado' => $minutosTrabalhado,
            'minutos_noturno' => 0,
            'minutos_previstas' => $minutosPrevistas,
            'minutos_dia_falta' => $diaFalta,
            'minutos_falta' => $minutosFalta,
            'minutos_atraso' => $minutosAtraso,
            'minutos_falta_atraso' => $minutosFalta + $minutosAtraso,
            'minutos_extras' => $minutosExtras,
        ];
    }

    private function rotuloEspecialDia(Colaborador $colaborador, ?FrequenciaRegistro $registro, bool $temJornada): ?string
    {
        if ($registro) {
            $texto = strtolower((string) ($registro->justificativa_texto ?? ''));
            if (str_contains($texto, 'mobilização') || str_contains($texto, 'mobilizacao') || str_contains($texto, 'sgc')) {
                return 'Mobilização SGC';
            }
            if (str_contains($texto, 'feriado')) {
                return 'Feriado';
            }
        }

        if (! $temJornada) {
            return 'Folga';
        }

        if (! $registro && $colaborador->mobilizacao_status && $colaborador->mobilizacao_status !== 'mobilizacao_concluida') {
            return 'Mobilização SGC';
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function linhaRotulo(Carbon $dia, string $rotulo): array
    {
        return [
            'dia' => $dia->format('d/m/Y').' - '.self::DIAS_SEMANA[(int) $dia->isoWeekday()],
            'entrada_1' => $rotulo,
            'saida_1' => '',
            'entrada_2' => '',
            'saida_2' => '',
            'total_normais' => '',
            'total_trabalhado' => '',
            'adicional_noturno' => '',
            'horas_previstas' => '',
            'dia_falta' => '',
            'horas_falta' => '',
            'horas_atraso' => '',
            'falta_atraso' => '',
            'atestado' => '',
            'extras_total' => '',
            'minutos_normais' => 0,
            'minutos_trabalhado' => 0,
            'minutos_noturno' => 0,
            'minutos_previstas' => 0,
            'dia_falta' => 0,
            'minutos_falta' => 0,
            'minutos_atraso' => 0,
            'minutos_falta_atraso' => 0,
            'minutos_extras' => 0,
            'minutos_dia_falta' => 0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function linhaVazia(Carbon $dia, bool $temJornada): array
    {
        return [
            'dia' => $dia->format('d/m/Y').' - '.self::DIAS_SEMANA[(int) $dia->isoWeekday()],
            'entrada_1' => '',
            'saida_1' => '',
            'entrada_2' => '',
            'saida_2' => '',
            'total_normais' => '',
            'total_trabalhado' => '',
            'adicional_noturno' => '',
            'horas_previstas' => $temJornada ? '' : '',
            'dia_falta' => $temJornada ? '1' : '',
            'horas_falta' => '',
            'horas_atraso' => '',
            'falta_atraso' => '',
            'atestado' => '',
            'extras_total' => '',
            'minutos_normais' => 0,
            'minutos_trabalhado' => 0,
            'minutos_noturno' => 0,
            'minutos_previstas' => 0,
            'minutos_dia_falta' => $temJornada ? 1 : 0,
            'minutos_falta' => 0,
            'minutos_atraso' => 0,
            'minutos_falta_atraso' => 0,
            'minutos_extras' => 0,
        ];
    }

    private function diaTemJornada(?HorarioEscalaDia $dia): bool
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

    private function fmtHoraGrade(mixed $valor): string
    {
        $norm = FrequenciaCalculo::normalizarHorarioBanco($valor);

        return $norm ? substr($norm, 0, 5) : '';
    }

    private function fmtCelulaHoras(int $minutos): string
    {
        return $minutos > 0 ? FrequenciaCalculo::formatarMinutosRelogio($minutos) : '';
    }

    private function fmtBatida(mixed $valor, string $sufixo): string
    {
        $norm = FrequenciaCalculo::normalizarHorarioBanco($valor);
        if ($norm === null) {
            return '';
        }

        return substr($norm, 0, 5).$sufixo;
    }

    private function sufixoOrigem(?string $origem): string
    {
        return match ($origem) {
            'afd' => '(C)',
            'app_colaborador' => '(M)',
            'grade' => '(P)',
            'manual' => '(I)',
            default => '(I)',
        };
    }

    private function minutosAtraso(FrequenciaRegistro $registro, ?HorarioEscalaDia $diaEscala, string $ymd): int
    {
        if ($diaEscala === null) {
            return 0;
        }

        $previsto = FrequenciaCalculo::normalizarHorarioBanco($diaEscala->entrada_1);
        $real = FrequenciaCalculo::normalizarHorarioBanco($registro->entrada_1);
        if ($previsto === null || $real === null) {
            return 0;
        }

        try {
            $a = Carbon::parse("{$ymd} {$previsto}");
            $b = Carbon::parse("{$ymd} {$real}");
        } catch (\Throwable) {
            return 0;
        }

        if ($b->lte($a)) {
            return 0;
        }

        return (int) $a->diffInMinutes($b);
    }
}
