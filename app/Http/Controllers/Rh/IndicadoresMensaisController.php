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
                'kpisRh' => [],
                'indicadoresFaixa' => [],
                'leituraExecutiva' => '',
                'pontosAtencao' => [],
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
        ]);
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
}
