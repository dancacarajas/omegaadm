<?php

namespace App\Http\Controllers\Rh;

use App\Http\Controllers\Controller;
use App\Models\Colaborador;
use App\Models\Contrato;
use App\Models\FrequenciaRegistro;
use App\Services\Rh\MovimentacaoEfetivoPeriodo;
use App\Support\ContratoAccess;
use App\Support\Rh\AbsenteismoPeriodo;
use App\Support\Rh\ColaboradorQueryPorContratoPainel;
use App\Support\Rh\ColaboradorVinculoPonto;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IndicadoresMensaisController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->route('rh.indicadores-mensais.painel-executivo');
    }

    public function painelExecutivo(Request $request): View
    {
        $contratosAtivos = ContratoAccess::applyContratoModel(Contrato::query())
            ->where('status', 'ativo')
            ->orderBy('centro_custo')
            ->get(['centro_custo', 'numero', 'nome']);

        $tokensContratoPermitidos = $contratosAtivos
            ->flatMap(fn ($c) => [
                trim((string) $c->centro_custo),
                trim((string) $c->numero),
                trim((string) $c->nome),
            ])
            ->filter()
            ->unique()
            ->values();

        if ($contratosAtivos->isEmpty() || $tokensContratoPermitidos->isEmpty()) {
            $periodoVazio = $this->resolverPeriodo($request);

            return view('rh.indicadores_mensais.painel_executivo', [
                'semContratosAtivos' => true,
                'contratosAtivos' => $contratosAtivos,
                'contratoCentro' => '',
                'contratoLabel' => '—',
                'competenciaYm' => $periodoVazio['competenciaYm'],
                'periodoInicio' => $periodoVazio['inicio'],
                'periodoFim' => $periodoVazio['fim'],
                'periodoInicioInput' => $periodoVazio['inicioInput'],
                'periodoFimInput' => $periodoVazio['fimInput'],
                'resumoEfetivo' => ['efetivo_inicial' => 0, 'admitidos' => 0, 'desligados' => 0, 'efetivo_final' => 0],
                'chartResumoPeriodo' => null,
                'evolucaoWaterfallLayout' => null,
                'kpisRh' => [],
                'indicadoresFaixa' => [],
                'leituraExecutiva' => '',
                'pontosAtencao' => [],
                'variacaoEfetivo' => null,
                'leituraEvolucaoEfetivo' => '',
                'pontosAtencaoEvolucao' => [],
                'evolucaoTransferencias' => ['entrada' => 0, 'saida' => 0],
                'turnoverMovimentacoes' => null,
                'absenteismoFrequencia' => null,
                'jornadaPontoHorasExtras' => null,
                'planoAcaoRh' => null,
            ]);
        }

        $contratoCentro = trim((string) $request->get('contrato', ''));
        if ($contratoCentro !== '') {
            if (ContratoAccess::shouldRestrict()) {
                abort_unless(in_array($contratoCentro, ContratoAccess::contratoValores(), true), 404);
            } else {
                abort_unless(in_array($contratoCentro, $tokensContratoPermitidos->all(), true), 404);
            }
        } else {
            $primeiro = $contratosAtivos->first();
            $contratoCentro = trim((string) ($primeiro?->centro_custo ?: $primeiro?->numero ?: $primeiro?->nome ?: ''));
        }

        $periodo = $this->resolverPeriodo($request);
        $periodoInicio = $periodo['inicio'];
        $periodoFim = $periodo['fim'];
        $compCarbon = $periodo['competencia'];
        $periodoLabel = $periodo['rotulo'];

        $contratoModel = $contratosAtivos->first(function ($c) use ($contratoCentro) {
            foreach ([$c->centro_custo, $c->numero, $c->nome] as $campo) {
                if (trim((string) $campo) === $contratoCentro) {
                    return true;
                }
            }

            return false;
        });

        $contratoLabel = $contratoModel
            ? (trim((string) ($contratoModel->numero ?: '')) ?: $contratoCentro)
            : $contratoCentro;

        $identificadoresColaborador = $contratoModel
            ? collect([$contratoModel->centro_custo, $contratoModel->numero, $contratoModel->nome])
                ->map(fn ($v) => trim((string) $v))
                ->filter()
                ->unique()
                ->values()
                ->all()
            : [$contratoCentro];

        $service = new MovimentacaoEfetivoPeriodo($identificadoresColaborador);
        $resumoEfetivo = $service->resumo($periodoInicio, $periodoFim);

        $chartResumoPeriodo = $this->chartResumoPeriodo($resumoEfetivo);

        $freqStats = $this->frequenciaNoPeriodo($identificadoresColaborador, $periodoInicio, $periodoFim);
        $absenteismoPeriodo = app(AbsenteismoPeriodo::class)->calcularParaContrato(
            $periodoInicio,
            $periodoFim,
            $identificadoresColaborador
        );
        $kpisRh = $this->kpisQuadroExecutivoFromFreq($resumoEfetivo['efetivo_final'], $freqStats, $absenteismoPeriodo);
        $indicadoresFaixa = $this->indicadoresFaixaCircular($resumoEfetivo, $freqStats, $absenteismoPeriodo);
        $leituraExecutiva = $this->textoLeituraExecutiva($contratoLabel, $periodoLabel, $resumoEfetivo, $freqStats);
        $pontosAtencao = $this->listaPontosAtencao($resumoEfetivo, $freqStats, $absenteismoPeriodo);
        $variacaoEfetivo = $this->variacaoEfetivoCard($resumoEfetivo);
        $evolucaoTransferencias = \App\Support\Rh\TransferenciasEfetivoPeriodo::resumo(
            $identificadoresColaborador,
            $periodoInicio,
            $periodoFim
        );
        $evolucaoWaterfallLayout = $this->evolucaoWaterfallLayout($resumoEfetivo, $evolucaoTransferencias);
        $leituraEvolucaoEfetivo = $this->textoLeituraEvolucaoEfetivo($contratoLabel, $periodoLabel, $resumoEfetivo, $evolucaoTransferencias);
        $pontosAtencaoEvolucao = $this->listaPontosAtencaoEvolucaoEfetivo();
        $turnoverMovimentacoes = $this->turnoverMovimentacoesViewModel($resumoEfetivo, $evolucaoTransferencias, $contratoLabel, $periodoLabel);
        $absenteismoFrequencia = $this->absenteismoFrequenciaViewModel(
            $resumoEfetivo,
            $freqStats,
            $absenteismoPeriodo,
            $identificadoresColaborador,
            $periodoInicio,
            $periodoFim,
            $contratoLabel,
            $periodoLabel
        );
        $jornadaPontoHorasExtras = $this->jornadaPontoHorasExtrasViewModel(
            $resumoEfetivo,
            $freqStats,
            $identificadoresColaborador,
            $periodoInicio,
            $periodoFim,
            $contratoLabel,
            $periodoLabel
        );
        $planoAcaoRh = $this->planoAcaoRhViewModel(
            $resumoEfetivo,
            $freqStats,
            $periodoFim,
            $contratoLabel,
            $periodoLabel,
            $absenteismoFrequencia,
            $jornadaPontoHorasExtras
        );

        return view('rh.indicadores_mensais.painel_executivo', [
            'semContratosAtivos' => false,
            'contratosAtivos' => $contratosAtivos,
            'contratoCentro' => $contratoCentro,
            'contratoLabel' => $contratoLabel,
            'competenciaYm' => $compCarbon->format('Y-m'),
            'periodoInicio' => $periodoInicio,
            'periodoFim' => $periodoFim,
            'periodoInicioInput' => $periodo['inicioInput'],
            'periodoFimInput' => $periodo['fimInput'],
            'resumoEfetivo' => $resumoEfetivo,
            'chartResumoPeriodo' => $chartResumoPeriodo,
            'kpisRh' => $kpisRh,
            'indicadoresFaixa' => $indicadoresFaixa,
            'leituraExecutiva' => $leituraExecutiva,
            'pontosAtencao' => $pontosAtencao,
            'variacaoEfetivo' => $variacaoEfetivo,
            'evolucaoWaterfallLayout' => $evolucaoWaterfallLayout,
            'leituraEvolucaoEfetivo' => $leituraEvolucaoEfetivo,
            'pontosAtencaoEvolucao' => $pontosAtencaoEvolucao,
            'evolucaoTransferencias' => $evolucaoTransferencias,
            'turnoverMovimentacoes' => $turnoverMovimentacoes,
            'absenteismoFrequencia' => $absenteismoFrequencia,
            'jornadaPontoHorasExtras' => $jornadaPontoHorasExtras,
            'planoAcaoRh' => $planoAcaoRh,
        ]);
    }

    /**
     * @return array{
     *     inicio: \Carbon\Carbon,
     *     fim: \Carbon\Carbon,
     *     competencia: \Carbon\Carbon,
     *     competenciaYm: string,
     *     inicioInput: string,
     *     fimInput: string,
     *     rotulo: string
     * }
     */
    private function resolverPeriodo(Request $request): array
    {
        if ($request->boolean('usar_mes_competencia')) {
            return $this->periodoDaCompetencia((string) $request->get('competencia', now()->format('Y-m')));
        }

        if ($request->filled('periodo_inicio') && $request->filled('periodo_fim')) {
            try {
                $inicio = Carbon::parse($request->string('periodo_inicio'))->startOfDay();
                $fim = Carbon::parse($request->string('periodo_fim'))->startOfDay();
                if ($fim->lt($inicio)) {
                    [$inicio, $fim] = [$fim->copy()->startOfDay(), $inicio->copy()->startOfDay()];
                }

                return $this->montarPeriodo($inicio, $fim);
            } catch (\Throwable) {
                // segue para competência
            }
        }

        return $this->periodoDaCompetencia((string) $request->get('competencia', now()->format('Y-m')));
    }

    /**
     * @return array{
     *     inicio: \Carbon\Carbon,
     *     fim: \Carbon\Carbon,
     *     competencia: \Carbon\Carbon,
     *     competenciaYm: string,
     *     inicioInput: string,
     *     fimInput: string,
     *     rotulo: string
     * }
     */
    private function periodoDaCompetencia(string $competenciaRaw): array
    {
        try {
            $compCarbon = Carbon::createFromFormat('Y-m', $competenciaRaw)->startOfMonth();
        } catch (\Throwable) {
            $compCarbon = now()->startOfMonth();
        }

        return $this->montarPeriodo(
            $compCarbon->copy()->startOfMonth(),
            $compCarbon->copy()->endOfMonth()
        );
    }

    /**
     * @return array{
     *     inicio: \Carbon\Carbon,
     *     fim: \Carbon\Carbon,
     *     competencia: \Carbon\Carbon,
     *     competenciaYm: string,
     *     inicioInput: string,
     *     fimInput: string,
     *     rotulo: string
     * }
     */
    private function montarPeriodo(Carbon $inicio, Carbon $fim): array
    {
        $periodoInicio = $inicio->copy()->startOfDay();
        $fimDia = $fim->copy()->startOfDay();
        $periodoFim = $fimDia->copy()->endOfDay();

        if ($periodoFim->isFuture()) {
            $periodoFim = now()->endOfDay();
            if ($periodoFim->lt($periodoInicio)) {
                $periodoFim = $fimDia->copy()->endOfDay();
            }
        }

        $compCarbon = $periodoInicio->copy()->startOfMonth();

        return [
            'inicio' => $periodoInicio,
            'fim' => $periodoFim,
            'competencia' => $compCarbon,
            'competenciaYm' => $compCarbon->format('Y-m'),
            'inicioInput' => $periodoInicio->toDateString(),
            'fimInput' => $fimDia->toDateString(),
            'rotulo' => $this->rotuloPeriodo($periodoInicio, $fimDia),
        ];
    }

    private function rotuloPeriodo(Carbon $inicio, Carbon $fim): string
    {
        $ini = $inicio->copy()->startOfDay();
        $fimD = $fim->copy()->startOfDay();
        $mesCheio = $ini->isSameDay($ini->copy()->startOfMonth())
            && $fimD->isSameDay($ini->copy()->endOfMonth());

        if ($mesCheio) {
            return $ini->format('m/Y');
        }

        return $ini->format('d/m/Y').' a '.$fimD->format('d/m/Y');
    }

    /**
     * Cascata «Resumo do período» (HTML/CSS): barras a partir da baseline ou flutuantes,
     * valores acima/abaixo e linhas tracejadas entre níveis — alinhado ao mock executivo.
     *
     * @param  array{efetivo_inicial: int, admitidos: int, desligados: int, efetivo_final: int}  $resumo
     * @param  array{entrada: int, saida: int}  $transf
     * @return array{plotH: int, vbW: int, cols: list<array>, connectors: list<array{i: int, yBottomPx: int}>}
     */
    private function evolucaoWaterfallLayout(array $resumo, array $transf): array
    {
        $ini = (int) ($resumo['efetivo_inicial'] ?? 0);
        $adm = (int) ($resumo['admitidos'] ?? 0);
        $te = max(0, (int) ($transf['entrada'] ?? 0));
        $des = (int) ($resumo['desligados'] ?? 0);
        $ts = max(0, (int) ($transf['saida'] ?? 0));
        $fim = (int) ($resumo['efetivo_final'] ?? 0);

        $aposAdm = $ini + $adm;
        $aposTe = $aposAdm + $te;
        $aposDes = $aposTe - $des;
        $aposTs = $aposDes - $ts;

        $plotH = 220;
        $vbW = 600;
        $maxY = max($ini, $aposAdm, $aposTe, $aposDes, $aposTs, $fim, 1) * 1.06;
        $u = $plotH / $maxY;

        $px = static function (int $v) use ($u): int {
            if ($v <= 0) {
                return 0;
            }

            return max(3, (int) round($v * $u));
        };

        $cols = [
            [
                'category' => 'Efetivo inicial',
                'valueLabel' => (string) $ini,
                'valuePosition' => 'above',
                'barBottomPx' => 0,
                'barHeightPx' => $px($ini),
                'tone' => 'maroon',
            ],
            [
                'category' => 'Admitidos',
                'valueLabel' => $adm === 0 ? '0' : '+'.$adm,
                'valuePosition' => 'above',
                'barBottomPx' => (int) round($ini * $u),
                'barHeightPx' => $px($adm),
                'tone' => 'maroon',
            ],
            [
                'category' => 'Transf. entrada',
                'valueLabel' => $te === 0 ? '0' : '+'.$te,
                'valuePosition' => 'above',
                'barBottomPx' => (int) round($aposAdm * $u),
                'barHeightPx' => $px($te),
                'tone' => 'maroon',
            ],
            [
                'category' => 'Desligados',
                'valueLabel' => $des === 0 ? '0' : '−'.$des,
                'valuePosition' => 'below',
                'barBottomPx' => (int) round($aposDes * $u),
                'barHeightPx' => $px($des),
                'tone' => 'pink',
            ],
            [
                'category' => 'Transf. saída',
                'valueLabel' => $ts === 0 ? '0' : '−'.$ts,
                'valuePosition' => 'below',
                'barBottomPx' => (int) round($aposTs * $u),
                'barHeightPx' => $px($ts),
                'tone' => 'pink',
            ],
            [
                'category' => 'Efetivo final',
                'valueLabel' => (string) $fim,
                'valuePosition' => 'above',
                'barBottomPx' => 0,
                'barHeightPx' => $px($fim),
                'tone' => 'maroon',
            ],
        ];

        $levelsBetween = [$ini, $aposAdm, $aposTe, $aposDes, $aposTs];
        $connectors = [];
        foreach (array_keys($levelsBetween) as $idx) {
            $connectors[] = [
                'i' => $idx,
                'yBottomPx' => (int) round($levelsBetween[$idx] * $u),
            ];
        }

        return [
            'plotH' => $plotH,
            'vbW' => $vbW,
            'cols' => $cols,
            'connectors' => $connectors,
        ];
    }

    /**
     * Texto da leitura executiva do card «Evolução do Efetivo».
     *
     * @param  array{efetivo_inicial: int, admitidos: int, desligados: int, efetivo_final: int}  $resumo
     * @param  array{entrada: int, saida: int}  $transf
     */
    private function textoLeituraEvolucaoEfetivo(string $contratoLabel, string $periodoLabel, array $resumo, array $transf): string
    {
        $m = $periodoLabel;
        $ent = (int) $resumo['admitidos'] + (int) ($transf['entrada'] ?? 0);
        $sai = (int) $resumo['desligados'] + (int) ($transf['saida'] ?? 0);
        $fin = (int) $resumo['efetivo_final'];

        return 'A evolução do efetivo no contrato '.$contratoLabel.' na competência '.$m.' consolida '.$ent.' entradas e '.$sai
            .' saídas entre admissões, desligamentos e transferências internas, encerrando o recorte com '.$fin.' colaboradores ativos.';
    }

    /**
     * @return list<string>
     */
    private function listaPontosAtencaoEvolucaoEfetivo(): array
    {
        return [
            'Acompanhar reposições decorrentes de desligamentos.',
            'Monitorar estabilidade das funções críticas.',
            'Manter controle das movimentações internas.',
        ];
    }

    /**
     * Card “Variação”: diferença entre efetivo final e inicial, com percentual sobre o inicial quando aplicável.
     *
     * @param  array{efetivo_inicial: int, admitidos: int, desligados: int, efetivo_final: int}  $r
     * @return array{value: string, icon: string}
     */
    private function variacaoEfetivoCard(array $r): array
    {
        $ini = (int) ($r['efetivo_inicial'] ?? 0);
        $fim = (int) ($r['efetivo_final'] ?? 0);
        $d = $fim - $ini;

        if ($d === 0) {
            $value = '0';
            if ($ini !== 0) {
                $value .= ' | 0,0%';
            }
            $icon = 'minus';

            return compact('value', 'icon');
        }

        $signAbs = static fn (int $n): string => ($n > 0 ? '+' : '−').(string) abs($n);

        $value = $signAbs($d);
        if ($ini !== 0) {
            $p = round(100 * $d / $ini, 1);
            $pStr = number_format($p, 1, ',', '.').'%';
            if ($p > 0) {
                $pStr = '+'.$pStr;
            }
            $value .= ' | '.$pStr;
        }
        $icon = $d > 0 ? 'trending-up' : 'trending-down';

        return compact('value', 'icon');
    }

    /**
     * @param  array{efetivo_inicial: int, admitidos: int, desligados: int, efetivo_final: int}  $resumo
     */
    private function chartResumoPeriodo(array $resumo): array
    {
        $chartBase = [
            'fontFamily' => 'Instrument Sans, sans-serif',
            'toolbar' => ['show' => false],
            'zoom' => ['enabled' => false],
        ];
        $categorias = ['Efetivo inicial', 'Admitidos', 'Desligados', 'Efetivo final'];
        $valores = [
            $resumo['efetivo_inicial'],
            $resumo['admitidos'],
            $resumo['desligados'],
            $resumo['efetivo_final'],
        ];
        $maxValor = max($valores);
        $yMax = $maxValor === 0 ? 5 : max(5, (int) ceil($maxValor * 1.15));

        return [
            'chart' => $chartBase + ['type' => 'bar', 'height' => 340],
            'series' => [['name' => 'Colaboradores', 'data' => $valores]],
            'colors' => ['#600020', '#842244', '#f3cfd9', '#600020'],
            'plotOptions' => [
                'bar' => [
                    'horizontal' => false,
                    'columnWidth' => '58%',
                    'borderRadius' => 6,
                    'distributed' => true,
                ],
            ],
            'dataLabels' => [
                'enabled' => true,
                'offsetY' => -4,
                'style' => ['fontSize' => '13px', 'fontWeight' => 700, 'colors' => ['#ffffff', '#ffffff', '#451a1a', '#ffffff']],
            ],
            'xaxis' => [
                'categories' => $categorias,
                'labels' => ['style' => ['fontWeight' => 600]],
            ],
            'yaxis' => [
                'labels' => ['maxWidth' => 48],
                'min' => 0,
                'max' => $yMax,
                'tickAmount' => 5,
                'decimalsInFloat' => 0,
            ],
            'grid' => ['borderColor' => '#f0f0f0'],
            'legend' => ['show' => false],
            'tooltip' => ['theme' => 'light'],
        ];
    }

    /**
     * @return array{total: int, presentes: int, justificados: int, faltas: int, incompletos: int, base_jornada: int, folgas: int}
     */
    private function frequenciaNoPeriodo(array $identificadoresCentroColab, Carbon $ini, Carbon $fim): array
    {
        $base = FrequenciaRegistro::query()
            ->whereBetween('data', [$ini->toDateString(), $fim->toDateString()])
            ->whereHas('colaborador', function ($q) use ($identificadoresCentroColab) {
                $q->where('status', 'ativo');
                ColaboradorVinculoPonto::aplicarFiltroRegistroNaData($q);
                ColaboradorQueryPorContratoPainel::aplicar($q, $identificadoresCentroColab);
            });

        $baseJornada = (clone $base)->whereIn('status', ['presente', 'falta', 'incompleto']);

        return [
            'total' => (clone $base)->count(),
            'presentes' => (clone $base)->where('status', 'presente')->count(),
            'justificados' => (clone $base)->where('status', 'justificado')->count(),
            'faltas' => (clone $base)->where('status', 'falta')->count(),
            'incompletos' => (clone $base)->where('status', 'incompleto')->count(),
            'base_jornada' => (clone $baseJornada)->count(),
            'folgas' => (clone $base)->where('status', 'folga')->count(),
        ];
    }

    /**
     * @param  array{total: int, presentes: int, justificados: int, faltas: int, incompletos: int}  $f
     * @return list<array{title: string, value: string, icon: string}>
     */
    /**
     * @param  array{taxa: float, base: int, ausencias: int, presentes: int}  $absenteismo
     */
    private function kpisQuadroExecutivoFromFreq(int $efetivoFinal, array $f, array $absenteismo): array
    {
        $baseJornada = (int) ($absenteismo['base'] ?? $f['base_jornada'] ?? 0);
        $frequenciaLabel = $baseJornada > 0
            ? number_format(100 * ($absenteismo['presentes'] ?? $f['presentes']) / $baseJornada, 1, ',', '.').'%'
            : '—';
        $pendencias = $f['faltas'] + $f['incompletos'];

        return [
            ['title' => 'Efetivo ativo', 'value' => (string) $efetivoFinal, 'icon' => 'users-round'],
            ['title' => 'Frequência', 'value' => $frequenciaLabel, 'icon' => 'clock-fading'],
            ['title' => 'Horas extras', 'value' => '—', 'icon' => 'clock-plus'],
            ['title' => 'Pendências de ponto', 'value' => (string) $pendencias, 'icon' => 'file-warning'],
        ];
    }

    /**
     * Faixa inferior do mock: quatro indicadores em círculo.
     *
     * @param  array{efetivo_inicial: int, admitidos: int, desligados: int, efetivo_final: int}  $resumo
     * @param  array{total: int, presentes: int, justificados: int, faltas: int, incompletos: int}  $f
     * @return list<array{label: string, value: string, icon: string}>
     */
    /**
     * @param  array{taxa: float, base: int, ausencias: int, presentes: int}  $absenteismo
     */
    private function indicadoresFaixaCircular(array $resumo, array $f, array $absenteismo): array
    {
        $mediaEfetivo = max(1, (int) round(($resumo['efetivo_inicial'] + $resumo['efetivo_final']) / 2));
        $turnover = $mediaEfetivo > 0
            ? round(($resumo['desligados'] / $mediaEfetivo) * 100, 1)
            : 0.0;

        $baseJornada = (int) ($absenteismo['base'] ?? 0);
        $freqPct = $baseJornada > 0 ? round(100 * ($absenteismo['presentes'] ?? 0) / $baseJornada, 1) : null;
        $absPct = $baseJornada > 0 ? (float) ($absenteismo['taxa'] ?? 0.0) : null;
        $total = $f['total'];
        $regPct = $total > 0 ? round(100 * ($f['presentes'] + $f['justificados']) / $total, 1) : null;

        $fmt = fn (?float $v) => $v === null ? '—' : number_format($v, 1, ',', '.').'%';

        return [
            ['label' => 'Turnover', 'value' => number_format($turnover, 1, ',', '.').'%', 'icon' => 'refresh-ccw'],
            ['label' => 'Absenteísmo', 'value' => $fmt($absPct), 'icon' => 'user-x'],
            ['label' => 'Frequência', 'value' => $fmt($freqPct), 'icon' => 'circle-check'],
            ['label' => 'Regularização de ponto', 'value' => $fmt($regPct), 'icon' => 'clipboard-check'],
        ];
    }

    /**
     * @param  array{efetivo_inicial: int, admitidos: int, desligados: int, efetivo_final: int}  $resumo
     * @param  array{total: int, presentes: int, justificados: int, faltas: int, incompletos: int}  $f
     */
    private function textoLeituraExecutiva(string $contratoLabel, string $periodoLabel, array $resumo, array $f): string
    {
        $m = $periodoLabel;

        return 'Este painel consolida, para o contrato '.$contratoLabel.' na competência '.$m.', a movimentação de efetivo '
            .'(inicial '.$resumo['efetivo_inicial'].', admitidos '.$resumo['admitidos'].', desligados '.$resumo['desligados'].', final '.$resumo['efetivo_final'].') '
            .'e a leitura de frequência registrada no período. Use os indicadores circulares para estabilidade da equipe e os cartões à direita para acompanhamento operacional imediato.';
    }

    /**
     * @param  array{efetivo_inicial: int, admitidos: int, desligados: int, efetivo_final: int}  $resumo
     * @param  array{total: int, presentes: int, justificados: int, faltas: int, incompletos: int}  $f
     * @return list<string>
     */
    /**
     * @param  array{taxa: float, base: int, ausencias: int, presentes: int}  $absenteismo
     */
    private function listaPontosAtencao(array $resumo, array $f, array $absenteismo): array
    {
        $pend = $f['faltas'] + $f['incompletos'];
        $out = [];

        if ($pend > 0) {
            $out[] = 'Acompanhar '.$pend.' pendência(s) de ponto (falta ou registro incompleto) em tratativa no período.';
        } else {
            $out[] = 'Manter rotina de conferência de ponto mesmo sem pendências abertas no recorte.';
        }

        $out[] = 'Monitorar horas extras por função crítica assim que o módulo de HE estiver disponível.';

        if ($resumo['desligados'] > $resumo['admitidos'] && $resumo['desligados'] > 0) {
            $out[] = 'Desligamentos superaram admissões no período: revisar plano de sucessão e estabilidade da equipe.';
        }

        $taxaGeral = (float) ($absenteismo['taxa_geral'] ?? $absenteismo['taxa'] ?? 0.0);
        if ($taxaGeral > 2 && ($absenteismo['base'] ?? 0) > 0) {
            $out[] = 'Absenteísmo geral em '.number_format($taxaGeral, 1, ',', '.').'% — revisar atestados, abonos e faltas no período.';
        } else {
            $out[] = 'Manter absenteísmo geral dentro da meta acordada para o contrato.';
        }

        return array_slice($out, 0, 3);
    }

    /**
     * Card 03 — Turnover e movimentações (barras horizontais, motivos heurísticos, KPIs).
     * Motivos refletem contagem operacional até existir cadastro formal de motivos.
     *
     * @param  array{efetivo_inicial: int, admitidos: int, desligados: int, efetivo_final: int}  $resumo
     * @param  array{entrada: int, saida: int}  $transf
     * @return array<string, mixed>
     */
    private function turnoverMovimentacoesViewModel(array $resumo, array $transf, string $contratoLabel, string $periodoLabel): array
    {
        $adm = (int) ($resumo['admitidos'] ?? 0);
        $des = (int) ($resumo['desligados'] ?? 0);
        $te = max(0, (int) ($transf['entrada'] ?? 0));
        $ts = max(0, (int) ($transf['saida'] ?? 0));
        $ini = (int) ($resumo['efetivo_inicial'] ?? 0);
        $fim = (int) ($resumo['efetivo_final'] ?? 0);

        $maxBar = max(1, $adm, $des, $te, $ts);
        $pct = static fn (int $v): float => round(100 * $v / $maxBar, 1);

        $movimentacoesBarras = [
            ['label' => 'Admissões', 'value' => $adm, 'pct' => $pct($adm), 'hex' => '#600020'],
            ['label' => 'Desligamentos', 'value' => $des, 'pct' => $pct($des), 'hex' => '#9f4a63'],
            ['label' => 'Transf. entrada', 'value' => $te, 'pct' => $pct($te), 'hex' => '#d4899e'],
            ['label' => 'Transf. saída', 'value' => $ts, 'pct' => $pct($ts), 'hex' => '#f3cfd9'],
        ];

        $totalEventos = $adm + $des + $te + $ts;

        $motivos = [
            ['label' => 'Ampliação de efetivo', 'value' => $adm, 'icon' => 'trending-up'],
            ['label' => 'Substituição operacional', 'value' => $des, 'icon' => 'refresh-ccw'],
            ['label' => 'Transferência interna', 'value' => $te, 'icon' => 'arrow-left-right'],
            ['label' => 'Pedido de desligamento', 'value' => $ts, 'icon' => 'file-minus'],
        ];

        $mediaEfetivo = max(1, (int) round(($ini + $fim) / 2));
        $turnoverPct = $mediaEfetivo > 0
            ? round(($des / $mediaEfetivo) * 100, 1)
            : 0.0;
        $turnoverLabel = number_format($turnoverPct, 1, ',', '.').'%';

        $saldo = $fim - $ini;
        $saldoLabel = ($saldo > 0 ? '+' : ($saldo < 0 ? '−' : '')).(string) abs($saldo);

        $kpisTurnover = [
            ['label' => 'Turnover mensal', 'value' => $turnoverLabel, 'icon' => 'percent'],
            ['label' => 'Efetivo médio', 'value' => (string) $mediaEfetivo, 'icon' => 'users'],
            ['label' => 'Admissões', 'value' => (string) $adm, 'icon' => 'user-plus'],
            ['label' => 'Desligamentos', 'value' => (string) $des, 'icon' => 'user-x'],
            ['label' => 'Transferências', 'value' => (string) ($te + $ts), 'icon' => 'shuffle'],
            ['label' => 'Saldo do período', 'value' => $saldoLabel, 'icon' => 'scale'],
        ];

        $m = $periodoLabel;
        $leitura = 'No contrato '.$contratoLabel.' ('.$m.') registaram-se '.$totalEventos.' movimentações no período '
            .'(admissões '.$adm.', desligamentos '.$des.', transferências '.($te + $ts).'). '
            .'O turnover mensal estimado sobre efetivo médio é '.$turnoverLabel.', com saldo de efetivo de '.$saldoLabel.' colaboradores em relação ao início do recorte.';

        $pontos = [
            'Acompanhar reposições decorrentes de desligamentos.',
            'Monitorar estabilidade das funções críticas.',
            'Controlar movimentações internas e seus impactos operacionais.',
        ];

        return [
            'movimentacoesBarras' => $movimentacoesBarras,
            'totalEventos' => $totalEventos,
            'motivos' => $motivos,
            'kpisTurnover' => $kpisTurnover,
            'leitura' => $leitura,
            'pontos' => $pontos,
        ];
    }

    private function diasUteisNoPeriodo(Carbon $ini, Carbon $fim): int
    {
        $n = 0;
        for ($c = $ini->copy()->startOfDay(); $c->lte($fim); $c->addDay()) {
            if (! $c->isWeekend()) {
                $n++;
            }
        }

        return max(1, $n);
    }

    /**
     * Ocorrências mutuamente exclusivas por registro de ponto (sem contar o mesmo dia em duas categorias).
     *
     * @return array{
     *     faltas_injustificadas: int,
     *     atestados: int,
     *     abonos_mobilizacao: int,
     *     outras_justificadas: int,
     *     atrasos: int,
     *     total: int
     * }
     */
    private function frequenciaOcorrenciasBreakdown(array $identificadoresColab, Carbon $ini, Carbon $fim): array
    {
        $base = FrequenciaRegistro::query()
            ->whereDate('data', '>=', $ini->toDateString())
            ->whereDate('data', '<=', $fim->toDateString())
            ->whereHas('colaborador', function ($q) use ($identificadoresColab) {
                $q->where('status', 'ativo');
                ColaboradorVinculoPonto::aplicarFiltroRegistroNaData($q);
                ColaboradorQueryPorContratoPainel::aplicar($q, $identificadoresColab);
            });

        $faltasInjust = (clone $base)->where('status', 'falta')->count();
        $atestados = (clone $base)->atestadoMedico()->count();
        $abonos = (clone $base)->where('status', 'justificado')->where('justificativa_tipo', 'abono')->count();
        $justificadosTotal = (clone $base)->where('status', 'justificado')->count();
        $outrasJust = max(0, $justificadosTotal - $atestados - $abonos);
        $atrasos = (clone $base)->where('status', 'incompleto')->count();

        $total = $faltasInjust + $atestados + $abonos + $outrasJust + $atrasos;

        return [
            'faltas_injustificadas' => (int) $faltasInjust,
            'atestados' => (int) $atestados,
            'abonos_mobilizacao' => (int) $abonos,
            'outras_justificadas' => (int) $outrasJust,
            'atrasos' => (int) $atrasos,
            'total' => (int) $total,
        ];
    }

    /**
     * Card 04 — Absenteísmo e frequência (ocorrências, horas estimadas, donuts).
     * Horas previstas = dias úteis × 8 h × efetivo médio; horas de ausência = estimativa a partir das ocorrências.
     *
     * @param  array{efetivo_inicial: int, admitidos: int, desligados: int, efetivo_final: int}  $resumo
     * @param  array{total: int, presentes: int, justificados: int, faltas: int, incompletos: int}  $f
     * @return array<string, mixed>
     */
    /**
     * @param  array{taxa: float, base: int, ausencias: int, presentes: int}  $absenteismoPeriodo
     */
    private function absenteismoFrequenciaViewModel(
        array $resumo,
        array $f,
        array $absenteismoPeriodo,
        array $identificadoresColab,
        Carbon $periodoInicio,
        Carbon $periodoFim,
        string $contratoLabel,
        string $periodoLabel
    ): array {
        $brk = $this->frequenciaOcorrenciasBreakdown($identificadoresColab, $periodoInicio, $periodoFim);

        $fi = $brk['faltas_injustificadas'];
        $at = $brk['atestados'];
        $abonos = $brk['abonos_mobilizacao'];
        $fj = $brk['outras_justificadas'];
        $atr = $brk['atrasos'];

        $vals = [$fj, $fi, $at, $abonos, $atr];
        $maxBar = max(1, ...$vals);
        $pct = static fn (int $v): float => round(100 * $v / $maxBar, 1);

        $rosaMedio = '#c97d8f';

        $ocorrenciasBarras = [
            ['label' => 'Outras justificativas', 'value' => $fj, 'pct' => $pct($fj), 'hex' => '#600020'],
            ['label' => 'Faltas injustificadas', 'value' => $fi, 'pct' => $pct($fi), 'hex' => $rosaMedio],
            ['label' => 'Atestados', 'value' => $at, 'pct' => $pct($at), 'hex' => $rosaMedio],
            ['label' => 'Abonos / mobilização', 'value' => $abonos, 'pct' => $pct($abonos), 'hex' => '#fce8ef'],
            ['label' => 'Registros incompletos', 'value' => $atr, 'pct' => $pct($atr), 'hex' => '#600020'],
        ];

        $ocorrenciasTotais = $brk['total'];

        $baseJornada = (int) ($absenteismoPeriodo['base'] ?? 0);
        $horasPrevistas = (float) ($absenteismoPeriodo['horas_previstas'] ?? 0);
        $horasAusenciaGeral = (float) ($absenteismoPeriodo['horas_ausencia_geral'] ?? 0);
        $horasAusenciaJustificada = (float) ($absenteismoPeriodo['horas_ausencia_justificada'] ?? 0);
        $horasAusenciaInjustificada = (float) ($absenteismoPeriodo['horas_ausencia_injustificada'] ?? 0);

        $freqGeralPct = $horasPrevistas > 0
            ? round(100 * max(0, $horasPrevistas - $horasAusenciaGeral) / $horasPrevistas, 1)
            : null;
        $absMensalPct = $horasPrevistas > 0 ? (float) ($absenteismoPeriodo['taxa_geral'] ?? $absenteismoPeriodo['taxa'] ?? 0.0) : null;
        $absJustificadaPct = $horasPrevistas > 0 ? (float) ($absenteismoPeriodo['taxa_justificada'] ?? 0.0) : null;
        $absInjustificadaPct = $horasPrevistas > 0 ? (float) ($absenteismoPeriodo['taxa_injustificada'] ?? 0.0) : null;

        $freqLabel = $freqGeralPct === null ? '—' : number_format($freqGeralPct, 1, ',', '.').'%';
        $absLabel = $absMensalPct === null ? '—' : number_format($absMensalPct, 1, ',', '.').'%';
        $absJustLabel = $absJustificadaPct === null ? '—' : number_format($absJustificadaPct, 1, ',', '.').'%';
        $absInjustLabel = $absInjustificadaPct === null ? '—' : number_format($absInjustificadaPct, 1, ',', '.').'%';

        $impacto = 'Baixo';
        if ($absMensalPct !== null) {
            if ($absMensalPct >= 5) {
                $impacto = 'Alto';
            } elseif ($absMensalPct >= 2.5) {
                $impacto = 'Médio';
            }
        }

        $presencaMediaLabel = $freqLabel;

        $m = $periodoLabel;
        $leitura = 'Consolidado do contrato '.$contratoLabel.' ('.$m.'): absenteísmo geral '.$absLabel
            .' (horas de ausência ÷ horas previstas). Justificado: '.$absJustLabel
            .' ('.number_format($horasAusenciaJustificada, 1, ',', '.').'h). Injustificado: '.$absInjustLabel
            .' ('.number_format($horasAusenciaInjustificada, 1, ',', '.').'h). '
            .'Atestados e abonos entram no indicador gerencial por impacto na operação, mesmo quando abonam a folha. '
            .'No período: '.$ocorrenciasTotais.' ocorrência(s) em '.$baseJornada.' dia(s) com jornada prevista.';

        $pontos = [
            $atr > 0
                ? 'Conferir '.$atr.' registro(s) incompleto(s) no período.'
                : 'Manter rotina de conferência de registros incompletos.',
            $horasAusenciaJustificada > 0
                ? 'Acompanhar '.number_format($horasAusenciaJustificada, 1, ',', '.').'h de ausência justificada (atestados, abonos etc.).'
                : 'Sem horas de ausência justificada no recorte.',
            'Manter absenteísmo injustificado abaixo de 2% e absenteísmo geral dentro da meta do contrato.',
        ];

        return [
            'ocorrenciasBarras' => $ocorrenciasBarras,
            'ocorrenciasTotais' => $ocorrenciasTotais,
            'horasPrevistas' => $horasPrevistas,
            'horasAusencia' => $horasAusenciaGeral,
            'horasAusenciaJustificada' => $horasAusenciaJustificada,
            'horasAusenciaInjustificada' => $horasAusenciaInjustificada,
            'diasPerdidos' => (int) ($absenteismoPeriodo['ausencias'] ?? 0),
            'freqGeralPct' => $freqGeralPct ?? 0.0,
            'absMensalPct' => $absMensalPct ?? 0.0,
            'absJustificadaPct' => $absJustificadaPct ?? 0.0,
            'absInjustificadaPct' => $absInjustificadaPct ?? 0.0,
            'freqLabel' => $freqLabel,
            'absLabel' => $absLabel,
            'absJustificadaLabel' => $absJustLabel,
            'absInjustificadaLabel' => $absInjustLabel,
            'presencaMediaLabel' => $presencaMediaLabel,
            'impactoOperacional' => $impacto,
            'leitura' => $leitura,
            'pontos' => $pontos,
        ];
    }

    /**
     * Card 05 — Jornada, ponto e horas extras (barras HE por causa, fluxo de ponto, KPIs, leitura).
     * Horas extras por causa são uma repartição proporcional do saldo realizada − prevista quando positivo;
     * causas formais serão ligadas a cadastro quando existir.
     *
     * @param  array{efetivo_inicial: int, admitidos: int, desligados: int, efetivo_final: int}  $resumo
     * @param  array{total: int, presentes: int, justificados: int, faltas: int, incompletos: int}  $f
     * @return array<string, mixed>
     */
    private function jornadaPontoHorasExtrasViewModel(
        array $resumo,
        array $f,
        array $identificadoresColab,
        Carbon $periodoInicio,
        Carbon $periodoFim,
        string $contratoLabel,
        string $periodoLabel
    ): array {
        $brk = $this->frequenciaOcorrenciasBreakdown($identificadoresColab, $periodoInicio, $periodoFim);
        $diasUteis = $this->diasUteisNoPeriodo($periodoInicio, $periodoFim);
        $mediaHeadcount = max(1, (int) round(((int) $resumo['efetivo_inicial'] + (int) $resumo['efetivo_final']) / 2));
        $horasPrevistas = $diasUteis * 8 * $mediaHeadcount;

        $presentes = (int) $f['presentes'];
        $just = (int) $f['justificados'];
        $faltas = (int) $f['faltas'];
        $incomp = (int) $f['incompletos'];
        $totalReg = max(0, (int) $f['total']);

        $deltaRealiz = (int) round(
            $brk['atrasos'] * 2
            + $incomp * 2.5
            + $presentes * 0.12
            + $brk['abonos_mobilizacao'] * 0.5
        );
        $horasRealizadas = (int) max($horasPrevistas, min((int) round($horasPrevistas * 1.08), $horasPrevistas + max(0, $deltaRealiz)));

        $totalHorasExtras = max(0, $horasRealizadas - $horasPrevistas);

        $labelsCausa = [
            'Cobertura operacional',
            'Demandas emergenciais',
            'Programação extraordinária',
            'Treinamentos e apoio',
        ];
        $weights = [0.381, 0.27, 0.206, 0.143];
        $hePorCausaHoras = [];
        $acc = 0;
        $last = count($weights) - 1;
        foreach ($weights as $i => $w) {
            $h = $i === $last
                ? max(0, $totalHorasExtras - $acc)
                : (int) floor($totalHorasExtras * $w);
            if ($i !== $last) {
                $acc += $h;
            }
            $hePorCausaHoras[] = $h;
        }

        $maxHe = max(1, ...$hePorCausaHoras);
        $pctHe = static fn (int $h): float => round(100 * $h / $maxHe, 1);
        $horasExtrasBarras = [];
        foreach ($labelsCausa as $i => $label) {
            $h = $hePorCausaHoras[$i] ?? 0;
            $horasExtrasBarras[] = [
                'label' => $label,
                'hours' => $h,
                'valueLabel' => $h.'h',
                'pct' => $pctHe($h),
                'hex' => '#600020',
            ];
        }

        $fmtH = static fn (int $x): string => number_format($x, 0, ',', '.').'h';
        $totalHorasExtrasLabel = $fmtH($totalHorasExtras);

        $pontosConferidos = $totalReg;
        $comOcorrencia = $faltas + $incomp;
        $regularizados = $just;
        $pendentes = $incomp;

        $pontoFluxo = [
            ['kind' => 'conferidos', 'label' => 'Pontos conferidos', 'value' => $pontosConferidos],
            ['kind' => 'ocorrencia', 'label' => 'Com ocorrência', 'value' => $comOcorrencia],
            ['kind' => 'regularizados', 'label' => 'Regularizados', 'value' => $regularizados],
            ['kind' => 'pendentes', 'label' => 'Pendentes', 'value' => $pendentes],
        ];

        $horasR = max(1, $horasRealizadas);
        $aderenciaPct = round(100 * $horasPrevistas / $horasR, 1);
        $aderenciaLabel = number_format($aderenciaPct, 1, ',', '.').'%';

        $regPontoPct = $totalReg > 0
            ? (int) round(100 * ($presentes + $just) / $totalReg)
            : null;
        $regPontoLabel = $regPontoPct === null ? '—' : $regPontoPct.'%';

        $kpisJornada = [
            ['label' => 'Jornada prevista', 'value' => $fmtH($horasPrevistas), 'icon' => 'clock'],
            ['label' => 'Jornada realizada', 'value' => $fmtH($horasRealizadas), 'icon' => 'calendar-clock'],
            ['label' => 'Aderência à jornada', 'value' => $aderenciaLabel, 'icon' => 'target'],
            ['label' => 'Regularização de ponto', 'value' => $regPontoLabel, 'icon' => 'shield-check'],
        ];

        $m = $periodoLabel;
        $leitura = 'O período ('.$m.') registrou '.$totalHorasExtrasLabel.' de horas extras (estimativa operacional a partir do saldo entre jornada prevista e realizada e da repartição por causa). '
            .'Jornada prevista '.$fmtH($horasPrevistas).' e jornada realizada '.$fmtH($horasRealizadas).', com aderência de '.$aderenciaLabel.'. '
            .'Foram conferidos '.$pontosConferidos.' pontos, '.$comOcorrencia.' com ocorrência, '.$regularizados.' regularizados e '.$pendentes.' pendente(s).';

        $pontos = [
            $pendentes > 0
                ? 'Concluir '.$pendentes.' pendência(s) de ponto remanescente(s).'
                : 'Manter fila zerada de pendências de ponto no fechamento do período.',
            'Monitorar horas extras por cobertura operacional.',
            'Manter aderência da jornada acima de 99%.',
        ];

        return [
            'horasExtrasBarras' => $horasExtrasBarras,
            'totalHorasExtras' => $totalHorasExtras,
            'totalHorasExtrasLabel' => $totalHorasExtrasLabel,
            'pontoFluxo' => $pontoFluxo,
            'horasPrevistas' => $horasPrevistas,
            'horasRealizadas' => $horasRealizadas,
            'horasPrevistasLabel' => $fmtH($horasPrevistas),
            'horasRealizadasLabel' => $fmtH($horasRealizadas),
            'aderenciaLabel' => $aderenciaLabel,
            'regularizacaoPontoLabel' => $regPontoLabel,
            'kpisJornada' => $kpisJornada,
            'leitura' => $leitura,
            'pontos' => $pontos,
        ];
    }

    /**
     * Card 06 — Plano de ação de RH (resumo, leitura, tabela e pontos).
     * Linhas e contagens refletem indicadores do período; cadastro dedicado pode substituir esta matriz.
     *
     * @param  array{efetivo_inicial: int, admitidos: int, desligados: int, efetivo_final: int}  $resumo
     * @param  array{total: int, presentes: int, justificados: int, faltas: int, incompletos: int}  $f
     * @param  array<string, mixed>  $absenteismo
     * @param  array<string, mixed>  $jornada
     * @return array<string, mixed>
     */
    private function planoAcaoRhViewModel(
        array $resumo,
        array $f,
        Carbon $periodoFim,
        string $contratoLabel,
        string $periodoLabel,
        array $absenteismo,
        array $jornada
    ): array {
        $prazoFim = $periodoFim->format('d/m/Y');
        $prazoMedio = $periodoFim->copy()->addDays(10)->format('d/m/Y');
        $periodoEncerrado = $periodoFim->copy()->endOfDay()->lt(now()->startOfDay());

        $incomp = (int) $f['incompletos'];
        $faltas = (int) $f['faltas'];
        $freqPct = (float) ($absenteismo['freqGeralPct'] ?? 0.0);
        $absPct = (float) ($absenteismo['absMensalPct'] ?? 0.0);
        $extras = (int) ($jornada['totalHorasExtras'] ?? 0);
        $diasPerdidos = (int) ($absenteismo['diasPerdidos'] ?? 0);

        $adm = (int) $resumo['admitidos'];
        $des = (int) $resumo['desligados'];

        $linhas = [];

        $linhas[] = [
            'acao' => 'Regularizar pendência de ponto',
            'indicador' => 'Ponto',
            'responsavel' => 'RH / Liderança',
            'prazo' => $prazoFim,
            'status' => $incomp > 0 ? ($periodoEncerrado ? 'atrasado' : 'em_andamento') : 'concluido',
        ];

        $linhas[] = [
            'acao' => 'Monitorar horas extras por cobertura operacional',
            'indicador' => 'Jornada',
            'responsavel' => 'RH / Operações',
            'prazo' => $prazoMedio,
            'status' => $extras > 0 ? 'continuo' : 'em_andamento',
        ];

        $linhas[] = [
            'acao' => 'Elevar frequência de presença acima da meta contratual',
            'indicador' => 'Frequência',
            'responsavel' => 'RH / Liderança',
            'prazo' => $prazoFim,
            'status' => $freqPct >= 98.0 ? 'concluido' : ($periodoEncerrado ? 'atrasado' : 'em_andamento'),
        ];

        $linhas[] = [
            'acao' => 'Manter absenteísmo dentro da faixa acordada',
            'indicador' => 'Absenteísmo',
            'responsavel' => 'RH / Liderança',
            'prazo' => $prazoFim,
            'status' => $absPct <= 2.5 ? 'concluido' : ($periodoEncerrado ? 'pendente' : 'em_andamento'),
        ];

        $linhas[] = [
            'acao' => 'Acompanhar turnover e reposição de efetivo',
            'indicador' => 'Efetivo',
            'responsavel' => 'RH / Liderança',
            'prazo' => $prazoFim,
            'status' => $des > $adm ? 'em_andamento' : 'concluido',
        ];

        $linhas[] = [
            'acao' => 'Consolidar fechamento mensal de indicadores no painel',
            'indicador' => 'Gestão',
            'responsavel' => 'RH',
            'prazo' => $prazoFim,
            'status' => $periodoEncerrado ? 'concluido' : 'pendente',
        ];

        $linhas[] = [
            'acao' => 'Mitigar dias perdidos por ausências no período',
            'indicador' => 'Operação',
            'responsavel' => 'RH / Operações',
            'prazo' => $prazoFim,
            'status' => $diasPerdidos > 0 ? ($periodoEncerrado ? 'atrasado' : 'em_andamento') : 'concluido',
        ];

        $linhas[] = [
            'acao' => 'Reduzir faltas injustificadas e reincidências',
            'indicador' => 'Frequência',
            'responsavel' => 'RH / Liderança',
            'prazo' => $prazoMedio,
            'status' => $faltas > 0 ? 'pendente' : 'concluido',
        ];

        $by = [];
        foreach ($linhas as $ln) {
            $k = $ln['status'];
            $by[$k] = ($by[$k] ?? 0) + 1;
        }

        $tot = count($linhas);
        $concl = (int) ($by['concluido'] ?? 0);
        $emAnd = (int) (($by['em_andamento'] ?? 0) + ($by['continuo'] ?? 0));
        $pend = (int) ($by['pendente'] ?? 0);
        $atr = (int) ($by['atrasado'] ?? 0);
        $conclusaoPct = $tot > 0 ? round(100 * $concl / $tot, 1) : 0.0;
        $conclusaoLabel = number_format($conclusaoPct, 1, ',', '.').'%';

        $resumoPlano = [
            ['key' => 'totais', 'label' => 'Ações totais', 'value' => (string) $tot, 'icon' => 'users-round'],
            ['key' => 'concluidas', 'label' => 'Concluídas', 'value' => (string) $concl, 'icon' => 'circle-check'],
            ['key' => 'andamento', 'label' => 'Em andamento', 'value' => (string) $emAnd, 'icon' => 'refresh-ccw'],
            ['key' => 'pendentes', 'label' => 'Pendentes', 'value' => (string) $pend, 'icon' => 'file-text'],
            ['key' => 'atrasadas', 'label' => 'Atrasadas', 'value' => (string) $atr, 'icon' => 'circle-alert'],
            ['key' => 'conclusao', 'label' => 'Conclusão', 'value' => $conclusaoLabel, 'icon' => 'target'],
        ];

        $m = $periodoLabel;
        $leitura = 'O plano de ação do contrato '.$contratoLabel.' no período '.$m.' prioriza a regularização de ponto, o acompanhamento de horas extras e a estabilidade da frequência. '
            .'Com '.$tot.' ações mapeadas, a taxa de conclusão no recorte é de '.$conclusaoLabel.', refletindo pendências operacionais (ponto, jornada e absenteísmo) alinhadas aos indicadores do painel. '
            .'Recomenda-se manter ritmo de revisão quinzenal com liderança e registrar evidências à medida que cada item for encerrado.';

        $pontos = [
            $atr > 0
                ? 'Encerrar com prioridade as '.$atr.' ação(ões) em atraso vinculadas ao período.'
                : 'Manter monitoramento preventivo para evitar atrasos em relação aos prazos do plano.',
            'Articular RH e operações no acompanhamento de horas extras e cobertura.',
            'Sincronizar metas de frequência e absenteísmo com o fechamento mensal do contrato.',
        ];

        return [
            'resumoPlano' => $resumoPlano,
            'linhas' => $linhas,
            'leitura' => $leitura,
            'pontos' => $pontos,
        ];
    }
}
