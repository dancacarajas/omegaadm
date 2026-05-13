<?php

namespace App\Http\Controllers\Rh;

use App\Http\Controllers\Controller;
use App\Models\Colaborador;
use App\Models\Contrato;
use App\Models\FrequenciaRegistro;
use App\Services\Rh\MovimentacaoEfetivoPeriodo;
use App\Support\ContratoAccess;
use App\Support\Rh\ColaboradorQueryPorContratoPainel;
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
            return view('rh.indicadores_mensais.painel_executivo', [
                'semContratosAtivos' => true,
                'contratosAtivos' => $contratosAtivos,
                'contratoCentro' => '',
                'contratoLabel' => '—',
                'competenciaYm' => now()->format('Y-m'),
                'periodoInicio' => now()->startOfMonth(),
                'periodoFim' => now()->endOfDay(),
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

        $competenciaRaw = (string) $request->get('competencia', now()->format('Y-m'));
        try {
            $compCarbon = Carbon::createFromFormat('Y-m', $competenciaRaw)->startOfMonth();
        } catch (\Throwable) {
            $compCarbon = now()->startOfMonth();
        }

        $periodoInicio = $compCarbon->copy()->startOfMonth();
        $periodoFim = $compCarbon->copy()->endOfMonth();
        if ($periodoFim->isFuture()) {
            $periodoFim = now()->endOfDay();
            if ($periodoFim->lt($periodoInicio)) {
                $periodoFim = $compCarbon->copy()->endOfMonth();
            }
        }

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
        $kpisRh = $this->kpisQuadroExecutivoFromFreq($resumoEfetivo['efetivo_final'], $freqStats);
        $indicadoresFaixa = $this->indicadoresFaixaCircular($resumoEfetivo, $freqStats);
        $leituraExecutiva = $this->textoLeituraExecutiva($contratoLabel, $compCarbon, $resumoEfetivo, $freqStats);
        $pontosAtencao = $this->listaPontosAtencao($resumoEfetivo, $freqStats);
        $variacaoEfetivo = $this->variacaoEfetivoCard($resumoEfetivo);
        $evolucaoTransferencias = ['entrada' => 0, 'saida' => 0];
        $evolucaoWaterfallLayout = $this->evolucaoWaterfallLayout($resumoEfetivo, $evolucaoTransferencias);
        $leituraEvolucaoEfetivo = $this->textoLeituraEvolucaoEfetivo($contratoLabel, $compCarbon, $resumoEfetivo, $evolucaoTransferencias);
        $pontosAtencaoEvolucao = $this->listaPontosAtencaoEvolucaoEfetivo();
        $turnoverMovimentacoes = $this->turnoverMovimentacoesViewModel($resumoEfetivo, $evolucaoTransferencias, $contratoLabel, $compCarbon);
        $absenteismoFrequencia = $this->absenteismoFrequenciaViewModel(
            $resumoEfetivo,
            $freqStats,
            $identificadoresColaborador,
            $periodoInicio,
            $periodoFim,
            $contratoLabel,
            $compCarbon
        );
        $jornadaPontoHorasExtras = $this->jornadaPontoHorasExtrasViewModel(
            $resumoEfetivo,
            $freqStats,
            $identificadoresColaborador,
            $periodoInicio,
            $periodoFim,
            $contratoLabel,
            $compCarbon
        );
        $planoAcaoRh = $this->planoAcaoRhViewModel(
            $resumoEfetivo,
            $freqStats,
            $periodoFim,
            $contratoLabel,
            $compCarbon,
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
    private function textoLeituraEvolucaoEfetivo(string $contratoLabel, Carbon $competencia, array $resumo, array $transf): string
    {
        $m = $competencia->format('m/Y');
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
     * @return array{total: int, presentes: int, justificados: int, faltas: int, incompletos: int}
     */
    private function frequenciaNoPeriodo(array $identificadoresCentroColab, Carbon $ini, Carbon $fim): array
    {
        $ids = ColaboradorQueryPorContratoPainel::aplicar(Colaborador::query(), $identificadoresCentroColab)
            ->pluck('id');

        if ($ids->isEmpty()) {
            return ['total' => 0, 'presentes' => 0, 'justificados' => 0, 'faltas' => 0, 'incompletos' => 0];
        }

        $base = FrequenciaRegistro::query()
            ->whereIn('colaborador_id', $ids)
            ->whereBetween('data', [$ini->toDateString(), $fim->toDateString()]);

        return [
            'total' => (clone $base)->count(),
            'presentes' => (clone $base)->where('status', 'presente')->count(),
            'justificados' => (clone $base)->where('status', 'justificado')->count(),
            'faltas' => (clone $base)->where('status', 'falta')->count(),
            'incompletos' => (clone $base)->where('status', 'incompleto')->count(),
        ];
    }

    /**
     * @param  array{total: int, presentes: int, justificados: int, faltas: int, incompletos: int}  $f
     * @return list<array{title: string, value: string, icon: string}>
     */
    private function kpisQuadroExecutivoFromFreq(int $efetivoFinal, array $f): array
    {
        $total = $f['total'];
        $frequenciaLabel = $total > 0
            ? number_format(100 * $f['presentes'] / $total, 1, ',', '.').'%'
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
    private function indicadoresFaixaCircular(array $resumo, array $f): array
    {
        $mediaEfetivo = max(1, (int) round(($resumo['efetivo_inicial'] + $resumo['efetivo_final']) / 2));
        $turnover = $mediaEfetivo > 0
            ? round(($resumo['desligados'] / $mediaEfetivo) * 100, 1)
            : 0.0;

        $total = $f['total'];
        $freqPct = $total > 0 ? round(100 * $f['presentes'] / $total, 1) : null;
        $absPct = $total > 0 ? round(100 * ($f['faltas'] + $f['justificados']) / $total, 1) : null;
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
    private function textoLeituraExecutiva(string $contratoLabel, Carbon $competencia, array $resumo, array $f): string
    {
        $m = $competencia->format('m/Y');

        return 'Este painel consolida, para o contrato '.$contratoLabel.' na competência '.$m.', a movimentação de efetivo '
            .'(inicial '.$resumo['efetivo_inicial'].', admitidos '.$resumo['admitidos'].', desligados '.$resumo['desligados'].', final '.$resumo['efetivo_final'].') '
            .'e a leitura de frequência registrada no período. Use os indicadores circulares para estabilidade da equipe e os cartões à direita para acompanhamento operacional imediato.';
    }

    /**
     * @param  array{efetivo_inicial: int, admitidos: int, desligados: int, efetivo_final: int}  $resumo
     * @param  array{total: int, presentes: int, justificados: int, faltas: int, incompletos: int}  $f
     * @return list<string>
     */
    private function listaPontosAtencao(array $resumo, array $f): array
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

        $total = $f['total'];
        $freqPct = $total > 0 ? 100 * $f['presentes'] / $total : 100.0;
        if ($freqPct < 98 && $total > 0) {
            $out[] = 'Elevar frequência acima de 98% (atualmente '.number_format($freqPct, 1, ',', '.').'% sobre registros de ponto).';
        } else {
            $out[] = 'Manter frequência acima de 98% nos registros de ponto do contrato.';
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
    private function turnoverMovimentacoesViewModel(array $resumo, array $transf, string $contratoLabel, Carbon $comp): array
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

        $m = $comp->format('m/Y');
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
     * @return array{faltas_justificadas: int, faltas_injustificadas: int, atestados: int, atrasos: int, saidas_antecipadas: int}
     */
    private function frequenciaOcorrenciasBreakdown(array $identificadoresColab, Carbon $ini, Carbon $fim): array
    {
        $ids = ColaboradorQueryPorContratoPainel::aplicar(Colaborador::query(), $identificadoresColab)->pluck('id');
        if ($ids->isEmpty()) {
            return [
                'faltas_justificadas' => 0,
                'faltas_injustificadas' => 0,
                'atestados' => 0,
                'atrasos' => 0,
                'saidas_antecipadas' => 0,
            ];
        }

        $base = FrequenciaRegistro::query()
            ->whereIn('colaborador_id', $ids)
            ->whereBetween('data', [$ini->toDateString(), $fim->toDateString()]);

        $atestados = (clone $base)->where('status', 'justificado')->where('justificativa_tipo', 'atestado')->count();
        $faltasJust = (clone $base)->where('status', 'justificado')
            ->where(function ($q) {
                $q->where('justificativa_tipo', '!=', 'atestado')->orWhereNull('justificativa_tipo');
            })
            ->count();

        $faltasInjust = (clone $base)->where('status', 'falta')->count();
        $atrasos = (clone $base)->where('status', 'incompleto')->count();
        $saidasAnt = (clone $base)->where('justificativa_tipo', 'abono')->count();

        return [
            'faltas_justificadas' => (int) $faltasJust,
            'faltas_injustificadas' => (int) $faltasInjust,
            'atestados' => (int) $atestados,
            'atrasos' => (int) $atrasos,
            'saidas_antecipadas' => (int) $saidasAnt,
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
    private function absenteismoFrequenciaViewModel(
        array $resumo,
        array $f,
        array $identificadoresColab,
        Carbon $periodoInicio,
        Carbon $periodoFim,
        string $contratoLabel,
        Carbon $comp
    ): array {
        $brk = $this->frequenciaOcorrenciasBreakdown($identificadoresColab, $periodoInicio, $periodoFim);

        $fj = $brk['faltas_justificadas'];
        $fi = $brk['faltas_injustificadas'];
        $at = $brk['atestados'];
        $atr = $brk['atrasos'];
        $sa = $brk['saidas_antecipadas'];

        $vals = [$fj, $fi, $at, $atr, $sa];
        $maxBar = max(1, ...$vals);
        $pct = static fn (int $v): float => round(100 * $v / $maxBar, 1);

        $rosaMedio = '#c97d8f';

        $ocorrenciasBarras = [
            ['label' => 'Faltas justificadas', 'value' => $fj, 'pct' => $pct($fj), 'hex' => '#600020'],
            ['label' => 'Faltas injustificadas', 'value' => $fi, 'pct' => $pct($fi), 'hex' => $rosaMedio],
            ['label' => 'Atestados', 'value' => $at, 'pct' => $pct($at), 'hex' => $rosaMedio],
            ['label' => 'Atrasos', 'value' => $atr, 'pct' => $pct($atr), 'hex' => '#600020'],
            ['label' => 'Saídas antecipadas', 'value' => $sa, 'pct' => $pct($sa), 'hex' => '#fce8ef'],
        ];

        $ocorrenciasTotais = array_sum($vals);

        $diasUteis = $this->diasUteisNoPeriodo($periodoInicio, $periodoFim);
        $mediaHeadcount = max(1, (int) round(((int) $resumo['efetivo_inicial'] + (int) $resumo['efetivo_final']) / 2));
        $horasPrevistas = $diasUteis * 8 * $mediaHeadcount;

        $horasAusencia = min(
            $horasPrevistas,
            $fi * 8 + $atr * 4 + $at * 8 + $fj * 4 + $sa * 2
        );

        $diasPerdidos = (int) max(0, round($horasAusencia / 8));

        $total = $f['total'];
        $freqGeralPct = $total > 0 ? round(100 * $f['presentes'] / $total, 1) : null;
        $absMensalPct = $total > 0 ? min(100.0, round(100 * ($f['faltas'] + $f['justificados'] + $f['incompletos']) / $total, 1)) : null;

        $freqLabel = $freqGeralPct === null ? '—' : number_format($freqGeralPct, 1, ',', '.').'%';
        $absLabel = $absMensalPct === null ? '—' : number_format($absMensalPct, 1, ',', '.').'%';

        $impacto = 'Baixo';
        if ($absMensalPct !== null) {
            if ($absMensalPct >= 5) {
                $impacto = 'Alto';
            } elseif ($absMensalPct >= 2.5) {
                $impacto = 'Médio';
            }
        }

        $presencaMediaLabel = $freqLabel;

        $m = $comp->format('m/Y');
        $leitura = 'Consolidado do contrato '.$contratoLabel.' ('.$m.'): frequência geral '.$freqLabel
            .', absenteísmo mensal '.$absLabel.' sobre registos de ponto no período. '
            .'Foram contabilizadas '.$ocorrenciasTotais.' ocorrências distintas (justificadas, injustificadas, atestados, atrasos e abonos).';

        $pontos = [
            'Monitorar recorrência de atrasos.',
            'Acompanhar faltas justificadas por equipe.',
            'Manter absenteísmo abaixo de 2%.',
        ];

        return [
            'ocorrenciasBarras' => $ocorrenciasBarras,
            'ocorrenciasTotais' => $ocorrenciasTotais,
            'horasPrevistas' => $horasPrevistas,
            'horasAusencia' => $horasAusencia,
            'diasPerdidos' => $diasPerdidos,
            'freqGeralPct' => $freqGeralPct ?? 0.0,
            'absMensalPct' => $absMensalPct ?? 0.0,
            'freqLabel' => $freqLabel,
            'absLabel' => $absLabel,
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
        Carbon $comp
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
            + $brk['saidas_antecipadas'] * 0.5
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

        $m = $comp->format('m/Y');
        $leitura = 'O período registrou '.$totalHorasExtrasLabel.' de horas extras (estimativa operacional a partir do saldo entre jornada prevista e realizada e da repartição por causa). '
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
        Carbon $comp,
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

        $m = $comp->format('m/Y');
        $leitura = 'O plano de ação do contrato '.$contratoLabel.' na competência '.$m.' prioriza a regularização de ponto, o acompanhamento de horas extras e a estabilidade da frequência. '
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
