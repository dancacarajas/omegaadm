<?php

namespace App\Support\Rh;

use App\Models\Colaborador;
use App\Models\FrequenciaRegistro;
use App\Models\HorarioEscalaDia;
use App\Support\EscalaPontoRegras;
use App\Support\FeriadoPontoService;
use App\Support\FrequenciaCalculo;
use App\Support\Rh\ColaboradorVinculoPonto;
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
            ->with('justificativaTipoCatalogo')
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
                $linha = $this->montarLinhaDia($colaborador, $dia, $registro, $regras, $ymd);
                $linhas[] = $linha;

                $totais['normais'] += $linha['minutos_normais'];
                $totais['trabalhado'] += $linha['minutos_trabalhado'];
                $totais['noturno'] += $linha['minutos_noturno'];
                $totais['previstas'] += $linha['minutos_previstas'];
                $totais['dia_falta'] += (int) ($linha['minutos_dia_falta'] ?? 0);
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
                    'trabalhado' => $totais['trabalhado'] > 0
                    ? FrequenciaCalculo::formatarMinutosRelogio($totais['trabalhado'])
                    : '',
                    'noturno' => FrequenciaCalculo::formatarMinutosRelogio($totais['noturno']),
                    'previstas' => FrequenciaCalculo::formatarMinutosRelogio($totais['previstas']),
                    'dia_falta' => $totais['dia_falta'] > 0 ? (string) $totais['dia_falta'] : '',
                    'horas_falta' => $totais['horas_falta'] > 0
                        ? FrequenciaCalculo::formatarMinutosRelogio($totais['horas_falta'])
                        : '',
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
        EscalaPontoRegras $regras,
        string $ymd
    ): array {
        if (! ColaboradorVinculoPonto::contaPontoNaData($colaborador, $dia)) {
            return $this->linhaForaVinculo($colaborador, $dia, $ymd);
        }

        $diaEscala = $colaborador->horarioEscalaDiaNaData($dia);
        $temJornada = $this->diaTemJornada($diaEscala);

        if ($registro !== null && $this->registroTemBatidas($registro)) {
            return $this->linhaComBatidas($colaborador, $dia, $registro, $diaEscala, $ymd);
        }

        $feriado = app(FeriadoPontoService::class)->feriadoNaData($dia);
        if ($feriado !== null) {
            return $this->linhaRotulo($dia, $feriado->rotuloPonto(), $registro, $ymd);
        }

        if ($registro !== null && ($registro->origem ?? '') === FeriadoPontoService::ORIGEM) {
            return $this->linhaRotulo($dia, $registro->justificativa_texto ?: 'Feriado', $registro, $ymd);
        }

        if ($registro !== null && $registro->status === 'justificado') {
            return $this->linhaRotulo($dia, $this->rotuloJustificativa($registro), $registro, $ymd);
        }

        if ($registro !== null && $registro->status === 'folga') {
            return $this->linhaRotulo($dia, 'Folga', $registro, $ymd);
        }

        if ($registro !== null && in_array($registro->status, ['presente', 'incompleto'], true)) {
            return $this->linhaComBatidas($colaborador, $dia, $registro, $diaEscala, $ymd);
        }

        $rotuloEscala = $this->rotuloQuandoSemRegistro($colaborador, $registro, $temJornada, $dia);
        if ($rotuloEscala !== null) {
            return $this->linhaRotulo($dia, $rotuloEscala, $registro, $ymd);
        }

        if (! $registro) {
            return $this->linhaVazia($dia, $temJornada, $ymd);
        }

        if ($registro->status === 'falta' && ! $this->registroTemBatidas($registro)) {
            return $this->linhaVazia($dia, $temJornada, $ymd, $registro);
        }

        return $this->linhaComBatidas($colaborador, $dia, $registro, $diaEscala, $ymd);
    }

    /**
     * @return array<string, mixed>
     */
    private function linhaComBatidas(
        Colaborador $colaborador,
        Carbon $dia,
        FrequenciaRegistro $registro,
        ?HorarioEscalaDia $diaEscala,
        string $ymd
    ): array {
        $registro->setRelation('colaborador', $colaborador);

        $sufixo = $this->sufixoOrigem($registro->origem);
        $batidaCompleta = $this->registroTemBatidaCompleta($registro);
        $resumo = FrequenciaCalculo::resumo($registro);
        $minutosTrabalhado = (int) $resumo['trabalhadas'];

        if ($batidaCompleta && $minutosTrabalhado === 0) {
            $resumo = FrequenciaCalculo::resumoComFallbackEscala($registro);
            $minutosTrabalhado = (int) $resumo['trabalhadas'];
        }

        $minutosPrevistas = (int) $resumo['jornada_esperada_minutos'];
        $minutosExtras = (int) $resumo['extras'];
        $minutosFalta = $resumo['falta'] !== null ? (int) $resumo['falta'] : 0;

        if (! $batidaCompleta && $minutosTrabalhado === 0 && in_array($registro->status, ['presente', 'incompleto'], true)) {
            $minutosFalta = 0;
        }

        $toleranciaFalta = FrequenciaCalculo::toleranciaFaltaEfetiva($registro, $minutosPrevistas);
        $minutosFalta = FrequenciaCalculo::faltaEfetivaMinutos($minutosFalta, $toleranciaFalta);

        $minutosNormais = min($minutosTrabalhado, max(0, $minutosPrevistas));
        $minutosAtraso = FrequenciaCalculo::minutosAtrasoRegistro($registro);
        if (FrequenciaCalculo::registroTemPontoCompleto($registro) && $minutosFalta === 0) {
            $minutosAtraso = 0;
        }

        $status = (string) ($registro->status ?? 'falta');
        $diaFalta = $status === 'falta' ? 1 : 0;
        $atestado = $status === 'justificado' && $registro->justificativa_tipo === 'atestado' ? '1' : '';

        $tipoVisual = match (true) {
            $status === 'falta' => 'falta',
            $minutosFalta > 0 => 'falta',
            $status === 'incompleto' => 'incompleto',
            default => 'normal',
        };

        return array_merge($this->metadadosApuracao($registro, false), [
            'data_ymd' => $ymd,
            'registro_id' => $registro->id,
            'status' => $status,
            'tipo_visual' => $tipoVisual,
            'apurado' => $status !== 'falta' && $minutosFalta === 0,
            'dia' => $dia->format('d/m').' '.self::DIAS_SEMANA[(int) $dia->isoWeekday()],
            'dia_completo' => $dia->format('d/m/Y').' - '.self::DIAS_SEMANA[(int) $dia->isoWeekday()],
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
        ]);
    }

    private function rotuloJustificativa(FrequenciaRegistro $registro): string
    {
        if ($registro->justificativaTipoCatalogo) {
            return $registro->justificativaTipoCatalogo->nome;
        }

        if ($registro->justificativa_tipo === 'atestado') {
            return 'Atestado Médico';
        }

        $texto = trim((string) ($registro->justificativa_texto ?? ''));

        return $texto !== '' ? $texto : 'Justificado';
    }

    /** Rótulo inferido pela escala quando não há batidas gravadas no dia. */
    private function rotuloQuandoSemRegistro(Colaborador $colaborador, ?FrequenciaRegistro $registro, bool $temJornada, Carbon $dia): ?string
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

        if (! $colaborador->horario_escala_id) {
            return null;
        }

        if (! $temJornada) {
            return 'Folga';
        }

        if (! $registro && $colaborador->mobilizacao_status && $colaborador->mobilizacao_status !== 'mobilizacao_concluida') {
            return 'Mobilização SGC';
        }

        return null;
    }

    private function registroTemBatidas(FrequenciaRegistro $registro): bool
    {
        foreach (['entrada_1', 'saida_1', 'entrada_2', 'saida_2'] as $campo) {
            if (! FrequenciaCalculo::horarioArmazenadoVazio($registro->getAttribute($campo))) {
                return true;
            }
        }

        return false;
    }

    /** Entrada e saída final preenchidas (batida completa do dia). */
    private function registroTemBatidaCompleta(FrequenciaRegistro $registro): bool
    {
        return ! FrequenciaCalculo::horarioArmazenadoVazio($registro->entrada_1)
            && ! FrequenciaCalculo::horarioArmazenadoVazio($registro->saida_2);
    }

    /**
     * Dia fora do vínculo (antes da admissão ou após demissão): não conta falta.
     *
     * @return array<string, mixed>
     */
    private function linhaForaVinculo(Colaborador $colaborador, Carbon $dia, string $ymd): array
    {
        $rotulo = $this->rotuloForaVinculo($colaborador, $dia);

        return array_merge($this->metadadosApuracao(null, true), [
            'fora_vinculo' => true,
            'data_ymd' => $ymd,
            'registro_id' => null,
            'status' => null,
            'tipo_visual' => 'fora_vinculo',
            'apurado' => true,
            'dia' => $dia->format('d/m').' '.self::DIAS_SEMANA[(int) $dia->isoWeekday()],
            'dia_completo' => $dia->format('d/m/Y').' - '.self::DIAS_SEMANA[(int) $dia->isoWeekday()],
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
            'minutos_dia_falta' => 0,
            'minutos_falta' => 0,
            'minutos_atraso' => 0,
            'minutos_falta_atraso' => 0,
            'minutos_extras' => 0,
        ]);
    }

    private function rotuloForaVinculo(Colaborador $colaborador, Carbon $dia): string
    {
        if ($colaborador->data_admissao && $dia->lt($colaborador->data_admissao->copy()->startOfDay())) {
            return 'Antes da admissão';
        }

        if ($colaborador->data_demissao && $dia->gt($colaborador->data_demissao->copy()->startOfDay())) {
            return 'Após demissão';
        }

        return '—';
    }

    /**
     * @return array<string, mixed>
     */
    private function linhaRotulo(Carbon $dia, string $rotulo, ?FrequenciaRegistro $registro, string $ymd): array
    {
        $tipoVisual = match (true) {
            strcasecmp($rotulo, 'Folga') === 0 => 'folga',
            str_contains(mb_strtolower($rotulo), 'feriado') => 'feriado',
            str_contains(mb_strtolower($rotulo), 'atestado') => 'justificado',
            default => 'justificado',
        };

        return array_merge($this->metadadosApuracao($registro, true), [
            'data_ymd' => $ymd,
            'registro_id' => $registro?->id,
            'status' => $registro?->status ?? ($tipoVisual === 'folga' ? 'folga' : 'justificado'),
            'tipo_visual' => $tipoVisual,
            'apurado' => true,
            'dia' => $dia->format('d/m').' '.self::DIAS_SEMANA[(int) $dia->isoWeekday()],
            'dia_completo' => $dia->format('d/m/Y').' - '.self::DIAS_SEMANA[(int) $dia->isoWeekday()],
            'entrada_1' => $rotulo,
            'saida_1' => $tipoVisual === 'folga' ? 'Folga' : ($tipoVisual !== 'normal' ? $rotulo : ''),
            'entrada_2' => $tipoVisual === 'folga' ? 'Folga' : ($tipoVisual !== 'normal' ? $rotulo : ''),
            'saida_2' => $tipoVisual === 'folga' ? 'Folga' : ($tipoVisual !== 'normal' ? $rotulo : ''),
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
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function linhaVazia(Carbon $dia, bool $temJornada, string $ymd, ?FrequenciaRegistro $registro = null): array
    {
        $ehFalta = $temJornada;

        return array_merge($this->metadadosApuracao($registro, false), [
            'data_ymd' => $ymd,
            'registro_id' => $registro?->id,
            'status' => $registro?->status ?? ($ehFalta ? 'falta' : null),
            'tipo_visual' => $ehFalta ? 'falta' : 'vazio',
            'apurado' => ! $ehFalta,
            'dia' => $dia->format('d/m').' '.self::DIAS_SEMANA[(int) $dia->isoWeekday()],
            'dia_completo' => $dia->format('d/m/Y').' - '.self::DIAS_SEMANA[(int) $dia->isoWeekday()],
            'entrada_1' => $ehFalta ? 'Falta' : '',
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
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function metadadosApuracao(?FrequenciaRegistro $registro, bool $ehRotulo): array
    {
        return [
            'eh_rotulo' => $ehRotulo,
            'marcacoes_raw' => [
                'entrada_1' => $this->horaInput($registro?->entrada_1),
                'saida_1' => $this->horaInput($registro?->saida_1),
                'entrada_2' => $this->horaInput($registro?->entrada_2),
                'saida_2' => $this->horaInput($registro?->saida_2),
            ],
            'justificativa_tipo' => $registro?->justificativa_tipo,
            'justificativa_texto' => $registro?->justificativa_texto,
            'justificativa_tipo_id' => $registro?->justificativa_tipo_id,
            'justificativa_catalogo' => $registro?->justificativaTipoCatalogo?->nome,
            'anexo_url' => $registro?->anexo_path ? asset('storage/'.$registro->anexo_path) : null,
        ];
    }

    private function horaInput(mixed $valor): string
    {
        $norm = FrequenciaCalculo::normalizarHorarioBanco($valor);

        return $norm ? substr($norm, 0, 5) : '';
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
            'afd' => ' (C)',
            'csv_ponto' => ' (C)',
            'app_colaborador' => ' (M)',
            'grade' => ' (P)',
            'manual' => ' (I)',
            default => ' (I)',
        };
    }

}
