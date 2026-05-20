<?php

namespace App\Http\Controllers\Rh;

use App\Http\Controllers\Controller;
use App\Models\Colaborador;
use App\Models\Contrato;
use App\Models\FrequenciaRegistro;
use App\Services\Rh\MovimentacaoEfetivoPeriodo;
use App\Support\ContratoAccess;
use App\Support\FrequenciaCalculo;
use App\Support\Rh\AbsenteismoPeriodo;
use App\Support\Rh\ColaboradorQueryPorContratoPainel;
use App\Support\Rh\ColaboradorVinculoPonto;
use App\Support\Rh\MovimentacoesPainelExecutivoPeriodo;
use App\Support\Rh\JornadaPontoPeriodoAgregador;
use App\Support\Rh\RegularizacaoPontoPeriodo;
use App\Support\Rh\TurnoverIndicadoresPeriodo;
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

        $movimentacoesPainel = MovimentacoesPainelExecutivoPeriodo::resumo(
            $identificadoresColaborador,
            $periodoInicio,
            $periodoFim
        );
        $resumoEfetivo['admitidos'] = $movimentacoesPainel['admitidos'];
        $resumoEfetivo['desligados'] = $movimentacoesPainel['desligados'];

        $chartResumoPeriodo = $this->chartResumoPeriodo($resumoEfetivo, $movimentacoesPainel);

        $freqStats = $this->frequenciaNoPeriodo($identificadoresColaborador, $periodoInicio, $periodoFim);
        $absenteismoPeriodo = app(AbsenteismoPeriodo::class)->calcularParaContrato(
            $periodoInicio,
            $periodoFim,
            $identificadoresColaborador
        );
        $regularizacaoPonto = RegularizacaoPontoPeriodo::calcular(
            $periodoInicio,
            $periodoFim,
            $identificadoresColaborador
        );
        $jornadaAgg = JornadaPontoPeriodoAgregador::agregar(
            $periodoInicio,
            $periodoFim,
            $identificadoresColaborador
        );
        $kpisRh = $this->kpisQuadroExecutivoFromFreq(
            $resumoEfetivo['efetivo_final'],
            $freqStats,
            $absenteismoPeriodo,
            $regularizacaoPonto,
            $jornadaAgg['extras_minutos']
        );
        $indicadoresFaixa = $this->indicadoresFaixaCircular($resumoEfetivo, $freqStats, $absenteismoPeriodo, $regularizacaoPonto);
        $leituraExecutiva = $this->textoLeituraExecutiva($contratoLabel, $periodoLabel, $resumoEfetivo, $freqStats, $movimentacoesPainel);
        $pontosAtencao = $this->listaPontosAtencao($resumoEfetivo, $freqStats, $absenteismoPeriodo, $movimentacoesPainel, $regularizacaoPonto);
        $variacaoEfetivo = $this->variacaoEfetivoCard($resumoEfetivo);
        $evolucaoTransferencias = $movimentacoesPainel['transferencias'];
        $evolucaoWaterfallLayout = $this->evolucaoWaterfallLayout($resumoEfetivo, $evolucaoTransferencias);
        $leituraEvolucaoEfetivo = $this->textoLeituraEvolucaoEfetivo(
            $contratoLabel,
            $periodoLabel,
            $resumoEfetivo,
            $evolucaoTransferencias,
            $movimentacoesPainel
        );
        $pontosAtencaoEvolucao = $this->listaPontosAtencaoEvolucaoEfetivo($resumoEfetivo, $movimentacoesPainel);
        $evolucaoMetricasExtras = $this->evolucaoMetricasExtrasViewModel($movimentacoesPainel);
        $turnoverMovimentacoes = $this->turnoverMovimentacoesViewModel(
            $resumoEfetivo,
            $movimentacoesPainel,
            $contratoLabel,
            $periodoLabel
        );
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
            $contratoLabel,
            $periodoLabel,
            $regularizacaoPonto,
            $jornadaAgg
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
            'movimentacoesPainel' => $movimentacoesPainel,
            'evolucaoMetricasExtras' => $evolucaoMetricasExtras,
            'turnoverMovimentacoes' => $turnoverMovimentacoes,
            'resumoMovimentacoesCard' => $this->resumoMovimentacoesCardViewModel($movimentacoesPainel),
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
     * @param  array<string, mixed>  $movPainel
     */
    private function textoLeituraEvolucaoEfetivo(
        string $contratoLabel,
        string $periodoLabel,
        array $resumo,
        array $transf,
        array $movPainel
    ): string {
        $m = $periodoLabel;
        $adm = (int) ($resumo['admitidos'] ?? 0);
        $te = max(0, (int) ($transf['entrada'] ?? 0));
        $des = (int) ($resumo['desligados'] ?? 0);
        $ts = max(0, (int) ($transf['saida'] ?? 0));
        $ent = $adm + $te;
        $sai = $des + $ts;
        $fin = (int) $resumo['efetivo_final'];

        $txt = 'A evolução do efetivo no contrato '.$contratoLabel.' na competência '.$m.' consolida '.$ent
            .' entradas ('.$adm.' admissões'.($te > 0 ? ', '.$te.' transferência(s) de entrada' : '').') e '.$sai
            .' saídas ('.$des.' desligamento(s)'.($ts > 0 ? ', '.$ts.' transferência(s) de saída' : '').'), '
            .'encerrando o recorte com '.$fin.' colaboradores ativos.';

        $extras = [];
        if (($movPainel['promocoes'] ?? 0) > 0) {
            $extras[] = (int) $movPainel['promocoes'].' promoção(ões)';
        }
        if (($movPainel['mudanca_funcao'] ?? 0) > 0) {
            $extras[] = (int) $movPainel['mudanca_funcao'].' mudança(s) de função';
        }
        if (($movPainel['ferias'] ?? 0) > 0) {
            $extras[] = (int) $movPainel['ferias'].' férias';
        }
        if (($movPainel['afastamento_inss'] ?? 0) > 0) {
            $extras[] = (int) $movPainel['afastamento_inss'].' afastamento(s) INSS';
        }
        if ($extras !== []) {
            $txt .= ' No histórico de movimentações constam também '.implode(', ', $extras).'.';
        }

        return $txt;
    }

    /**
     * Métricas inferiores do card Evolução (além das quatro principais).
     *
     * @param  array<string, mixed>  $movPainel
     * @return list<array{label: string, value: int, icon: string}>
     */
    private function evolucaoMetricasExtrasViewModel(array $movPainel): array
    {
        $itens = [
            ['label' => 'Promoções', 'value' => (int) ($movPainel['promocoes'] ?? 0), 'icon' => 'trending-up'],
            ['label' => 'Mudança de função', 'value' => (int) ($movPainel['mudanca_funcao'] ?? 0), 'icon' => 'briefcase'],
            ['label' => 'Férias', 'value' => (int) ($movPainel['ferias'] ?? 0), 'icon' => 'palmtree'],
            ['label' => 'Afastamento INSS', 'value' => (int) ($movPainel['afastamento_inss'] ?? 0), 'icon' => 'heart-pulse'],
        ];

        return array_values(array_filter($itens, static fn (array $i): bool => $i['value'] > 0));
    }

    /**
     * @param  array{efetivo_inicial: int, admitidos: int, desligados: int, efetivo_final: int}  $resumo
     * @param  array<string, mixed>  $movPainel
     * @return list<string>
     */
    private function listaPontosAtencaoEvolucaoEfetivo(array $resumo, array $movPainel): array
    {
        $out = [];
        $des = (int) ($resumo['desligados'] ?? 0);
        $adm = (int) ($resumo['admitidos'] ?? 0);
        $transf = max(0, (int) ($movPainel['transferencia_entrada'] ?? 0))
            + max(0, (int) ($movPainel['transferencia_saida'] ?? 0));

        if ($des > 0) {
            $out[] = 'Acompanhar reposição de '.$des.' desligamento(s) no período.';
        } else {
            $out[] = 'Sem desligamentos no recorte; manter plano de sucessão atualizado.';
        }

        if ($transf > 0) {
            $out[] = 'Validar '.$transf.' transferência(s) interna(s) e o impacto no centro de custo do contrato.';
        } elseif ($des > $adm && $des > 0) {
            $out[] = 'Saídas superaram entradas: revisar estabilidade das funções críticas.';
        } else {
            $out[] = 'Monitorar estabilidade das funções críticas e registrar movimentações em RH → Movimentações.';
        }

        $inss = (int) ($movPainel['afastamento_inss'] ?? 0);
        if ($inss > 0) {
            $out[] = 'Acompanhar '.$inss.' afastamento(s) INSS registrado(s) no período.';
        } else {
            $out[] = 'Manter controle das movimentações internas (promoção, função, férias) no histórico de efetivo.';
        }

        return array_slice($out, 0, 3);
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
     * Card 01 — barras de movimentação (efetivo + histórico de movimentações no período).
     *
     * @param  array{efetivo_inicial: int, admitidos: int, desligados: int, efetivo_final: int}  $resumo
     * @param  array<string, mixed>  $movPainel
     */
    private function chartResumoPeriodo(array $resumo, array $movPainel): array
    {
        $chartBase = [
            'fontFamily' => 'Instrument Sans, sans-serif',
            'toolbar' => ['show' => false],
            'zoom' => ['enabled' => false],
        ];
        $categorias = [
            'Efetivo inicial',
            'Admitidos',
            'Transf. entrada',
            'Desligados',
            'Transf. saída',
            'Efetivo final',
        ];
        $valores = [
            (int) ($resumo['efetivo_inicial'] ?? 0),
            (int) ($resumo['admitidos'] ?? 0),
            max(0, (int) ($movPainel['transferencia_entrada'] ?? 0)),
            (int) ($resumo['desligados'] ?? 0),
            max(0, (int) ($movPainel['transferencia_saida'] ?? 0)),
            (int) ($resumo['efetivo_final'] ?? 0),
        ];
        $maxValor = max($valores);
        $yMax = $maxValor === 0 ? 5 : max(5, (int) ceil($maxValor * 1.15));

        return [
            'chart' => $chartBase + ['type' => 'bar', 'height' => 340],
            'series' => [['name' => 'Colaboradores', 'data' => $valores]],
            'colors' => ['#600020', '#842244', '#9f4a63', '#f3cfd9', '#e8b4c4', '#600020'],
            'plotOptions' => [
                'bar' => [
                    'horizontal' => false,
                    'columnWidth' => '42%',
                    'borderRadius' => 6,
                    'distributed' => true,
                ],
            ],
            'dataLabels' => [
                'enabled' => true,
                'offsetY' => -4,
                'style' => [
                    'fontSize' => '12px',
                    'fontWeight' => 700,
                    'colors' => ['#ffffff', '#ffffff', '#ffffff', '#451a1a', '#451a1a', '#ffffff'],
                ],
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
    /**
     * @param  array<string, mixed>  $regularizacaoPonto
     */
    private function kpisQuadroExecutivoFromFreq(int $efetivoFinal, array $f, array $absenteismo, array $regularizacaoPonto, int $extrasMinutos = 0): array
    {
        $baseJornada = (int) ($absenteismo['base'] ?? $f['base_jornada'] ?? 0);
        $frequenciaLabel = $baseJornada > 0
            ? number_format(100 * ($absenteismo['presentes'] ?? $f['presentes']) / $baseJornada, 1, ',', '.').'%'
            : '—';
        $pendencias = (int) ($regularizacaoPonto['dias_pendentes'] ?? ($f['faltas'] + $f['incompletos']));
        $horasExtrasLabel = $extrasMinutos > 0
            ? JornadaPontoPeriodoAgregador::fmtHoras(JornadaPontoPeriodoAgregador::minutosParaHoras($extrasMinutos))
            : '—';

        return [
            ['title' => 'Efetivo ativo', 'value' => (string) $efetivoFinal, 'icon' => 'users-round'],
            ['title' => 'Frequência', 'value' => $frequenciaLabel, 'icon' => 'clock-fading'],
            ['title' => 'Horas extras', 'value' => $horasExtrasLabel, 'icon' => 'clock-plus'],
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
    /**
     * @param  array<string, mixed>  $regularizacaoPonto
     */
    private function indicadoresFaixaCircular(array $resumo, array $f, array $absenteismo, array $regularizacaoPonto): array
    {
        $turnoverCalc = TurnoverIndicadoresPeriodo::calcular($resumo);

        $baseJornada = (int) ($absenteismo['base'] ?? 0);
        $freqPct = $baseJornada > 0 ? round(100 * ($absenteismo['presentes'] ?? 0) / $baseJornada, 1) : null;
        $absPct = $baseJornada > 0 ? (float) ($absenteismo['taxa'] ?? 0.0) : null;
        $fmt = fn (?float $v) => $v === null ? '—' : number_format($v, 1, ',', '.').'%';

        return [
            ['label' => 'Turnover', 'value' => $turnoverCalc['turnover_geral_label'], 'icon' => 'refresh-ccw'],
            ['label' => 'Absenteísmo', 'value' => $fmt($absPct), 'icon' => 'user-x'],
            ['label' => 'Frequência', 'value' => $fmt($freqPct), 'icon' => 'circle-check'],
            ['label' => 'Regularização de ponto', 'value' => $regularizacaoPonto['percentual_label'], 'icon' => 'clipboard-check'],
        ];
    }

    /**
     * @param  array{efetivo_inicial: int, admitidos: int, desligados: int, efetivo_final: int}  $resumo
     * @param  array{total: int, presentes: int, justificados: int, faltas: int, incompletos: int}  $f
     * @param  array<string, mixed>  $movPainel
     */
    private function textoLeituraExecutiva(string $contratoLabel, string $periodoLabel, array $resumo, array $f, array $movPainel): string
    {
        $m = $periodoLabel;
        $te = max(0, (int) ($movPainel['transferencia_entrada'] ?? 0));
        $ts = max(0, (int) ($movPainel['transferencia_saida'] ?? 0));

        $base = 'Este painel consolida, para o contrato '.$contratoLabel.' na competência '.$m.', a movimentação de efetivo '
            .'(inicial '.$resumo['efetivo_inicial'].', admitidos '.$resumo['admitidos'].', desligados '.$resumo['desligados'].', final '.$resumo['efetivo_final'].')';

        if ($te > 0 || $ts > 0) {
            $base .= ' e transferências internas ('.$te.' entrada(s), '.$ts.' saída(s)) registradas em Movimentações de efetivo';
        }

        $extras = [];
        if (($movPainel['promocoes'] ?? 0) > 0) {
            $extras[] = (int) $movPainel['promocoes'].' promoção(ões)';
        }
        if (($movPainel['mudanca_funcao'] ?? 0) > 0) {
            $extras[] = (int) $movPainel['mudanca_funcao'].' mudança(s) de função';
        }
        if (($movPainel['ferias'] ?? 0) > 0) {
            $extras[] = (int) $movPainel['ferias'].' férias';
        }
        if (($movPainel['afastamento_inss'] ?? 0) > 0) {
            $extras[] = (int) $movPainel['afastamento_inss'].' afastamento(s) INSS';
        }
        if ($extras !== []) {
            $base .= ', além de '.implode(', ', $extras).' no histórico';
        }

        return $base
            .'. A leitura de frequência cobre o mesmo intervalo. Use os indicadores circulares para estabilidade da equipe e os cartões à direita para acompanhamento operacional imediato.';
    }

    /**
     * Chips do card MOVIMENTAÇÃO (eventos além das barras principais).
     *
     * @param  array<string, mixed>  $movPainel
     * @return list<array{label: string, value: int}>
     */
    private function resumoMovimentacoesCardViewModel(array $movPainel): array
    {
        $itens = [
            ['label' => 'Transf. entrada', 'value' => max(0, (int) ($movPainel['transferencia_entrada'] ?? 0))],
            ['label' => 'Transf. saída', 'value' => max(0, (int) ($movPainel['transferencia_saida'] ?? 0))],
            ['label' => 'Promoções', 'value' => (int) ($movPainel['promocoes'] ?? 0)],
            ['label' => 'Mudança de função', 'value' => (int) ($movPainel['mudanca_funcao'] ?? 0)],
            ['label' => 'Férias', 'value' => (int) ($movPainel['ferias'] ?? 0)],
            ['label' => 'Afastamento INSS', 'value' => (int) ($movPainel['afastamento_inss'] ?? 0)],
        ];

        return array_values(array_filter($itens, static fn (array $i): bool => $i['value'] > 0));
    }

    /**
     * @param  array{efetivo_inicial: int, admitidos: int, desligados: int, efetivo_final: int}  $resumo
     * @param  array{total: int, presentes: int, justificados: int, faltas: int, incompletos: int}  $f
     * @return list<string>
     */
    /**
     * @param  array{taxa: float, base: int, ausencias: int, presentes: int}  $absenteismo
     */
    /**
     * @param  array<string, mixed>  $movPainel
     */
    /**
     * @param  array<string, mixed>  $regularizacaoPonto
     */
    private function listaPontosAtencao(array $resumo, array $f, array $absenteismo, array $movPainel = [], array $regularizacaoPonto = []): array
    {
        $pend = (int) ($regularizacaoPonto['dias_pendentes'] ?? ($f['faltas'] + $f['incompletos']));
        $out = [];

        if ($pend > 0) {
            $sem = (int) ($regularizacaoPonto['sem_registro'] ?? 0);
            $inc = (int) ($regularizacaoPonto['incompletos'] ?? 0);
            $out[] = 'Regularizar '.$pend.' dia(s) de jornada prevista pendente(s)'
                .($inc > 0 ? ' — '.$inc.' incompleto(s)' : '')
                .($sem > 0 ? ($inc > 0 ? '; ' : ' — ').$sem.' sem registro após grade automática' : '').'.';
        } else {
            $out[] = 'Todos os dias de jornada prevista estão tratados no período (ponto completo, justificativa ou falta).';
        }

        $transf = max(0, (int) ($movPainel['transferencia_entrada'] ?? 0))
            + max(0, (int) ($movPainel['transferencia_saida'] ?? 0));
        if ($transf > 0) {
            $out[] = 'Houve '.$transf.' transferência(s) interna(s) no período: validar impacto operacional e cadastro de centro de custo.';
        } else {
            $out[] = 'Registrar transferências e demais movimentações em RH → Movimentações para refletir no painel.';
        }

        if ($resumo['desligados'] > $resumo['admitidos'] && $resumo['desligados'] > 0) {
            $out[] = 'Desligamentos superaram admissões no período: revisar plano de sucessão e estabilidade da equipe.';
        }

        if (count($out) < 3) {
            $taxaGeral = (float) ($absenteismo['taxa_geral'] ?? $absenteismo['taxa'] ?? 0.0);
            if ($taxaGeral > 2 && ($absenteismo['base'] ?? 0) > 0) {
                $out[] = 'Absenteísmo geral em '.number_format($taxaGeral, 1, ',', '.').'% — revisar atestados, abonos e faltas no período.';
            } else {
                $out[] = 'Manter absenteísmo geral dentro da meta acordada para o contrato.';
            }
        }

        return array_slice($out, 0, 3);
    }

    /**
     * Card 03 — Turnover e movimentações (barras horizontais, motivos do histórico, KPIs).
     *
     * @param  array{efetivo_inicial: int, admitidos: int, desligados: int, efetivo_final: int}  $resumo
     * @param  array<string, mixed>  $movPainel  Saída de {@see MovimentacoesPainelExecutivoPeriodo::resumo()}
     * @return array<string, mixed>
     */
    private function turnoverMovimentacoesViewModel(array $resumo, array $movPainel, string $contratoLabel, string $periodoLabel): array
    {
        $adm = (int) ($resumo['admitidos'] ?? 0);
        $des = (int) ($resumo['desligados'] ?? 0);
        $te = max(0, (int) ($movPainel['transferencia_entrada'] ?? 0));
        $ts = max(0, (int) ($movPainel['transferencia_saida'] ?? 0));
        $prom = (int) ($movPainel['promocoes'] ?? 0);
        $func = (int) ($movPainel['mudanca_funcao'] ?? 0);
        $ferias = (int) ($movPainel['ferias'] ?? 0);
        $inss = (int) ($movPainel['afastamento_inss'] ?? 0);
        $ini = (int) ($resumo['efetivo_inicial'] ?? 0);
        $fim = (int) ($resumo['efetivo_final'] ?? 0);

        $maxBar = max(1, $adm, $des, $te, $ts, $prom, $func, $ferias, $inss);
        $pct = static fn (int $v): float => round(100 * $v / $maxBar, 1);

        $movimentacoesBarras = [
            ['label' => 'Admissões', 'value' => $adm, 'pct' => $pct($adm), 'hex' => '#600020'],
            ['label' => 'Desligamentos', 'value' => $des, 'pct' => $pct($des), 'hex' => '#9f4a63'],
            ['label' => 'Transf. entrada', 'value' => $te, 'pct' => $pct($te), 'hex' => '#842244'],
            ['label' => 'Transf. saída', 'value' => $ts, 'pct' => $pct($ts), 'hex' => '#c97d8f'],
            ['label' => 'Promoções', 'value' => $prom, 'pct' => $pct($prom), 'hex' => '#d4899e'],
            ['label' => 'Mudança de função', 'value' => $func, 'pct' => $pct($func), 'hex' => '#e8b4c4'],
            ['label' => 'Férias', 'value' => $ferias, 'pct' => $pct($ferias), 'hex' => '#f3cfd9'],
            ['label' => 'Afastamento INSS', 'value' => $inss, 'pct' => $pct($inss), 'hex' => '#fce8ef'],
        ];
        $movimentacoesBarras = array_values(array_filter(
            $movimentacoesBarras,
            static fn (array $bar): bool => ($bar['value'] ?? 0) > 0
        ));
        if ($movimentacoesBarras === []) {
            $movimentacoesBarras[] = ['label' => 'Sem movimentações', 'value' => 0, 'pct' => 0.0, 'hex' => '#f3cfd9'];
        }

        $totalEventos = (int) ($movPainel['total_eventos'] ?? ($adm + $des + $te + $ts + $prom + $func + $ferias + $inss));

        $motivos = $movPainel['motivos'] ?? [];
        if ($motivos === []) {
            $motivos = [
                ['label' => 'Nenhuma movimentação registrada no período', 'value' => 0, 'icon' => 'minus'],
            ];
        }

        $turnoverCalc = TurnoverIndicadoresPeriodo::calcular($resumo, [
            'desligamentos_voluntarios' => (int) ($movPainel['desligamentos_voluntarios'] ?? 0),
        ]);

        $saldo = $fim - $ini;
        $saldoLabel = ($saldo > 0 ? '+' : ($saldo < 0 ? '−' : '')).(string) abs($saldo);

        $kpisTurnover = [
            ['label' => 'Turnover geral', 'value' => $turnoverCalc['turnover_geral_label'], 'icon' => 'percent'],
            ['label' => 'Turnover desligamento', 'value' => $turnoverCalc['turnover_desligamento_label'], 'icon' => 'user-x'],
            ['label' => 'Turnover voluntário', 'value' => $turnoverCalc['turnover_voluntario_label'], 'icon' => 'log-out'],
            ['label' => 'Turnover involuntário', 'value' => $turnoverCalc['turnover_involuntario_label'], 'icon' => 'shield-off'],
            ['label' => 'Efetivo médio', 'value' => $turnoverCalc['efetivo_medio_label'], 'icon' => 'users'],
            ['label' => 'Admissões', 'value' => (string) $adm, 'icon' => 'user-plus'],
            ['label' => 'Desligamentos', 'value' => (string) $des, 'icon' => 'user-minus'],
            ['label' => 'Transferências', 'value' => (string) ($te + $ts), 'icon' => 'shuffle'],
            ['label' => 'Saldo do período', 'value' => $saldoLabel, 'icon' => 'scale'],
        ];

        $m = $periodoLabel;
        $extras = [];
        if ($prom > 0) {
            $extras[] = $prom.' promoção(ões)';
        }
        if ($func > 0) {
            $extras[] = $func.' mudança(s) de função';
        }
        if ($ferias > 0) {
            $extras[] = $ferias.' férias';
        }
        if ($inss > 0) {
            $extras[] = $inss.' afastamento(s) INSS';
        }
        $extrasTxt = $extras !== [] ? ' Inclui '.implode(', ', $extras).'.' : '';

        $tg = $turnoverCalc['turnover_geral_label'];
        $td = $turnoverCalc['turnover_desligamento_label'];
        $tv = $turnoverCalc['turnover_voluntario_label'];
        $em = $turnoverCalc['efetivo_medio_label'];

        $leitura = 'No contrato '.$contratoLabel.' ('.$m.') registaram-se '.$totalEventos.' eventos no período '
            .'(admissões '.$adm.', desligamentos '.$des.', transferências '.($te + $ts).').'.$extrasTxt.' '
            .'Turnover geral '.$tg.' [(admissões + desligamentos) ÷ 2 ÷ efetivo médio '.$em.'], '
            .'turnover de desligamento '.$td.', turnover voluntário '.$tv.'. '
            .'Saldo de efetivo '.$saldoLabel.' em relação ao início do recorte.';

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
            'turnoverCalc' => $turnoverCalc,
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
     * Card 05 — Jornada, ponto e horas extras (apuração de batidas + escala, alinhado ao cartão de ponto).
     *
     * @param  array{efetivo_inicial: int, admitidos: int, desligados: int, efetivo_final: int}  $resumo
     * @param  array{total: int, presentes: int, justificados: int, faltas: int, incompletos: int}  $f
     * @return array<string, mixed>
     */
    /**
     * @param  array<string, mixed>  $regularizacaoPonto
     */
    /**
     * @param  array{previstas_minutos: int, trabalhadas_minutos: int, extras_minutos: int, entrada_antecipada_minutos: int, saida_posterior_minutos: int, dias_com_extra: int, colaboradores_com_extra: int}  $ag
     */
    private function jornadaPontoHorasExtrasViewModel(
        array $resumo,
        array $f,
        string $contratoLabel,
        string $periodoLabel,
        array $regularizacaoPonto,
        array $ag
    ): array {

        $horasPrevistas = JornadaPontoPeriodoAgregador::minutosParaHoras($ag['previstas_minutos']);
        $horasRealizadas = JornadaPontoPeriodoAgregador::minutosParaHoras($ag['trabalhadas_minutos']);
        $totalHorasExtras = JornadaPontoPeriodoAgregador::minutosParaHoras($ag['extras_minutos']);

        $horasExtrasBarras = JornadaPontoPeriodoAgregador::barrasHorasExtras(
            $ag['extras_minutos'],
            $ag['entrada_antecipada_minutos'],
            $ag['saida_posterior_minutos']
        );

        $totalHorasExtrasLabel = JornadaPontoPeriodoAgregador::fmtHoras($totalHorasExtras);
        $fmtH = static fn (float $h): string => JornadaPontoPeriodoAgregador::fmtHoras($h);

        $presentes = (int) $f['presentes'];
        $just = (int) $f['justificados'];
        $faltas = (int) $f['faltas'];
        $incomp = (int) $f['incompletos'];
        $totalReg = max(0, (int) $f['total']);

        $diasExige = (int) ($regularizacaoPonto['dias_exigem_tratamento'] ?? 0);
        $diasTratados = (int) ($regularizacaoPonto['dias_tratados'] ?? 0);
        $diasPendentes = (int) ($regularizacaoPonto['dias_pendentes'] ?? 0);
        $semRegistro = (int) ($regularizacaoPonto['sem_registro'] ?? 0);
        $incompPonto = (int) ($regularizacaoPonto['incompletos'] ?? 0);

        $pontoFluxo = [
            ['kind' => 'conferidos', 'label' => 'Dias com jornada prevista', 'value' => $diasExige],
            ['kind' => 'regularizados', 'label' => 'Tratados', 'value' => $diasTratados],
            ['kind' => 'ocorrencia', 'label' => 'Incompletos', 'value' => $incompPonto],
            ['kind' => 'pendentes', 'label' => 'Pendentes', 'value' => $diasPendentes],
        ];

        $aderenciaPct = $ag['previstas_minutos'] > 0
            ? round(100 * $ag['trabalhadas_minutos'] / $ag['previstas_minutos'], 1)
            : null;
        $aderenciaLabel = $aderenciaPct === null ? '—' : number_format($aderenciaPct, 1, ',', '.').'%';

        $regPontoLabel = $regularizacaoPonto['percentual_label'];

        $kpisJornada = [
            ['label' => 'Jornada prevista', 'value' => $fmtH($horasPrevistas), 'icon' => 'clock'],
            ['label' => 'Jornada realizada', 'value' => $fmtH($horasRealizadas), 'icon' => 'calendar-clock'],
            ['label' => 'Aderência à jornada', 'value' => $aderenciaLabel, 'icon' => 'target'],
            ['label' => 'Regularização de ponto', 'value' => $regPontoLabel, 'icon' => 'shield-check'],
        ];

        $m = $periodoLabel;
        $leitura = 'O período ('.$m.') registrou '.$totalHorasExtrasLabel.' de horas extras apuradas (batidas × escala, tolerância de '
            .FrequenciaCalculo::toleranciaMinutosFalta().' min). '
            .'Jornada prevista '.$fmtH($horasPrevistas).' e realizada '.$fmtH($horasRealizadas)
            .' (aderência realizada ÷ prevista: '.$aderenciaLabel.'). '
            .'Extras em '.$ag['dias_com_extra'].' dia(s), '.$ag['colaboradores_com_extra'].' colaborador(es). '
            .'Regularização de ponto: '.$diasTratados.' de '.$diasExige.' dia(s) de jornada prevista tratados ('.$regPontoLabel.'), '
            .(int) ($regularizacaoPonto['colaboradores_no_escopo'] ?? 0).' colaborador(es) no escopo.';

        $pontos = [
            $diasPendentes > 0
                ? 'Tratar '.$diasPendentes.' dia(s) pendente(s) de ponto'
                    .($incompPonto > 0 ? ' ('.$incompPonto.' incompleto)' : '')
                    .($semRegistro > 0 ? ($incompPonto > 0 ? ', ' : ' (').$semRegistro.' sem registro)' : '').'.'
                : 'Todos os dias de jornada prevista estão tratados no período.',
            $totalHorasExtras > 0
                ? 'Acompanhar '.$totalHorasExtrasLabel.' de horas extras apuradas no período.'
                : 'Sem horas extras apuradas no recorte.',
            $aderenciaPct !== null && $aderenciaPct < 95
                ? 'Aderência à jornada em '.$aderenciaLabel.' — revisar faltas, batidas incompletas e escala.'
                : 'Manter conferência de batidas e escala alinhada ao cartão de ponto.',
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
