<?php

namespace App\Http\Controllers;

use App\Models\Colaborador;
use App\Models\Contrato;
use App\Models\SsmaPlanoAcao;
use App\Models\SsmaRegistroMensal;
use App\Services\Rh\MovimentacaoEfetivoPeriodo;
use App\Support\ContratoAccess;
use App\Support\Rh\ColaboradorQueryPorContratoPainel;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SsmaIndicadoresMensaisController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->route('sesmt.indicadores-mensais.painel-executivo');
    }

    public function painelExecutivo(Request $request): View
    {
        $this->authorizeView();

        $ctx = $this->resolverContextoIndicadoresMensais($request);

        if ($ctx['semContratosAtivos']) {
            return view('sesmt.indicadores_mensais.painel_executivo', [
                'semContratosAtivos' => true,
                'contratosAtivos' => $ctx['contratosAtivos'],
                'contratoCentro' => '',
                'contratoLabel' => '—',
                'competenciaYm' => $ctx['compCarbon']->format('Y-m'),
                'periodoInicio' => $ctx['compCarbon']->copy()->startOfMonth(),
                'periodoFim' => now()->endOfDay(),
                'resumoEfetivo' => ['efetivo_inicial' => 0, 'admitidos' => 0, 'desligados' => 0, 'efetivo_final' => 0],
                'diasUteisPeriodo' => 1,
                'horasTrabalhadasLabel' => '—',
                'cartoesPainel' => [],
                'visaoProativos' => 0,
                'visaoReativos' => 0,
                'visaoConformidade' => 0,
                'desempenhoGeralLabel' => '—',
                'leituraExecutiva' => '',
                'pontosAtencao' => [],
                'cardReativos' => null,
                'cardProativos' => null,
                'cardTreinamentos' => null,
                'cardInspecoesConformidade' => null,
                'cardDesviosTratativas' => null,
                'cardBoasPraticasKaizen' => null,
                'cardPlanoAcaoSesmt' => null,
            ]);
        }

        $service = new MovimentacaoEfetivoPeriodo($ctx['identificadoresColaborador']);
        $resumoEfetivo = $service->resumo($ctx['periodoInicio'], $ctx['periodoFim']);

        $diasUteis = $this->diasUteisNoPeriodo($ctx['periodoInicio'], $ctx['periodoFim']);
        $mediaHeadcount = max(1, (int) round(((int) $resumoEfetivo['efetivo_inicial'] + (int) $resumoEfetivo['efetivo_final']) / 2));
        $horasTrabalhadas = $diasUteis * 8 * $mediaHeadcount;
        $horasTrabalhadasLabel = number_format($horasTrabalhadas, 0, ',', '.').'h';

        $efetivoExposto = (int) ColaboradorQueryPorContratoPainel::aplicar(Colaborador::query(), $ctx['identificadoresColaborador'])
            ->where('status', 'ativo')
            ->count();

        $painel = $this->montarDadosPainelSesmt(
            $ctx['identificadoresColaborador'],
            $ctx['compCarbon'],
            $ctx['periodoInicio'],
            $ctx['periodoFim'],
            $horasTrabalhadasLabel,
            $efetivoExposto,
            $ctx['contratoLabel']
        );

        return view('sesmt.indicadores_mensais.painel_executivo', array_merge([
            'semContratosAtivos' => false,
            'contratosAtivos' => $ctx['contratosAtivos'],
            'contratoCentro' => $ctx['contratoCentro'],
            'contratoLabel' => $ctx['contratoLabel'],
            'competenciaYm' => $ctx['compCarbon']->format('Y-m'),
            'periodoInicio' => $ctx['periodoInicio'],
            'periodoFim' => $ctx['periodoFim'],
            'resumoEfetivo' => $resumoEfetivo,
            'diasUteisPeriodo' => $diasUteis,
            'horasTrabalhadasLabel' => $horasTrabalhadasLabel,
        ], $painel));
    }

    /**
     * @return array{
     *     semContratosAtivos: bool,
     *     contratosAtivos: \Illuminate\Database\Eloquent\Collection<int, Contrato>,
     *     contratoCentro: string,
     *     contratoLabel: string,
     *     compCarbon: Carbon,
     *     periodoInicio: Carbon,
     *     periodoFim: Carbon,
     *     identificadoresColaborador: list<string>
     * }
     */
    private function resolverContextoIndicadoresMensais(Request $request): array
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

        if ($contratosAtivos->isEmpty() || $tokensContratoPermitidos->isEmpty()) {
            return [
                'semContratosAtivos' => true,
                'contratosAtivos' => $contratosAtivos,
                'contratoCentro' => '',
                'contratoLabel' => '—',
                'compCarbon' => $compCarbon,
                'competenciaYm' => $compCarbon->format('Y-m'),
                'periodoInicio' => $periodoInicio,
                'periodoFim' => $periodoFim,
                'identificadoresColaborador' => [],
            ];
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

        return [
            'semContratosAtivos' => false,
            'contratosAtivos' => $contratosAtivos,
            'contratoCentro' => $contratoCentro,
            'contratoLabel' => $contratoLabel,
            'compCarbon' => $compCarbon,
            'competenciaYm' => $compCarbon->format('Y-m'),
            'periodoInicio' => $periodoInicio,
            'periodoFim' => $periodoFim,
            'identificadoresColaborador' => $identificadoresColaborador,
        ];
    }

    /**
     * @param  list<string>  $identificadoresContrato
     * @return array<string, mixed>
     */
    private function montarDadosPainelSesmt(
        array $identificadoresContrato,
        Carbon $compCarbon,
        Carbon $periodoInicio,
        Carbon $periodoFim,
        string $horasTrabalhadasLabel,
        int $efetivoExposto,
        string $contratoLabel
    ): array {
        $registros = $this->registrosMensaisDoContratoNaCompetencia($identificadoresContrato, $compCarbon);
        $agg = $this->agregarEtapasRegistrosMensais($registros);

        $planos = SsmaPlanoAcao::query()
            ->whereBetween('created_at', [$periodoInicio->copy()->startOfDay(), $periodoFim->copy()->endOfDay()]);

        $porOrigem = (clone $planos)->selectRaw('origem, COUNT(*) as c')->groupBy('origem')->pluck('c', 'origem');

        $countOrigem = fn (string $o): int => (int) ($porOrigem[$o] ?? 0);

        $planosPreventMelhoria = (clone $planos)->whereIn('tipo', ['preventiva', 'melhoria'])->count();
        $planosConcluidas = (clone $planos)->whereIn('status', ['concluida', 'validada'])->count();

        $quasePlano = $countOrigem('quase_acidente');
        $quaseLinhas = $agg['quase_acidente_pro'];
        $quaseTotal = $quasePlano + $quaseLinhas;

        $desvios = $countOrigem('desvio');
        $inspecoesPlano = $countOrigem('inspecao');
        $auditoriasPlano = $countOrigem('auditoria');

        $linhasAcidente = $agg['registro_acidente_linhas'];
        $comAfastTexto = $this->contarLinhasComIndicioAfastamento($linhasAcidente);
        $acidComAfastamento = (int) $agg['restricao_trabalho'] + (int) $agg['tratamento_medico'] + $comAfastTexto;
        $totalLinhasAcidente = count($linhasAcidente);
        $acidSemAfastamento = max(0, $totalLinhasAcidente - $comAfastTexto);

        $inspecoesRealizadas = $inspecoesPlano + $agg['inspecoes_canteiro'];
        $treinamentos = $agg['treinamentos'];
        $primeirosSocorros = $agg['primeiros_socorros'];

        $visProativos = $planosPreventMelhoria + $treinamentos + $agg['inspecoes_canteiro'] + $agg['auditorias'] + $agg['campanhas'] + ($agg['kaizen'] > 0 ? 1 : 0);
        $visReativos = $quaseTotal + $desvios + $primeirosSocorros + $totalLinhasAcidente + $countOrigem('acidente');
        $visConformidade = $planosConcluidas + $auditoriasPlano + $inspecoesPlano;

        $temAcidente = $acidComAfastamento > 0 || $acidSemAfastamento > 0 || $countOrigem('acidente') > 0;
        $desempenhoGeralLabel = (! $temAcidente && $quaseTotal <= 2) ? 'BOM DESEMPENHO' : 'ATENÇÃO NECESSÁRIA';

        $compRotulo = $compCarbon->format('m/Y');
        $leituraExecutiva = 'Este painel consolida indicadores de SESMT do contrato '.$contratoLabel.' na competência '.$compRotulo.', permitindo acompanhar o desempenho em segurança, ações preventivas e conformidade. As horas trabalhadas são estimadas por dias úteis do recorte × 8 h × efetivo médio (inicial e final do período), alinhado à capacidade de jornada da equipe vinculada ao contrato.';

        $pontosAtencao = array_values(array_filter([
            $desvios > 0 ? 'Monitorar tratativa dos desvios identificados.' : null,
            'Manter rotina de inspeções e treinamentos obrigatórios.',
            $temAcidente ? 'Analisar ocorrências registradas e o retorno seguro ao trabalho.' : 'Sustentar desempenho sem acidentes no período.',
        ]));

        $cartoesPainel = [
            ['icon' => 'clock', 'value' => $horasTrabalhadasLabel, 'label' => 'Horas trabalhadas'],
            ['icon' => 'users', 'value' => (string) $efetivoExposto, 'label' => 'Efetivo exposto'],
            ['icon' => 'shield-plus', 'value' => (string) $acidComAfastamento, 'label' => 'Acid. c/ afastamento'],
            ['icon' => 'bandage', 'value' => (string) $acidSemAfastamento, 'label' => 'Acid. s/ afastamento'],
            ['icon' => 'cross', 'value' => (string) $primeirosSocorros, 'label' => 'Primeiros socorros'],
            ['icon' => 'triangle-alert', 'value' => (string) $quaseTotal, 'label' => 'Quase acidentes'],
            ['icon' => 'flag', 'value' => (string) $desvios, 'label' => 'Desvios identificados'],
            ['icon' => 'clipboard-list', 'value' => (string) $inspecoesRealizadas, 'label' => 'Inspeções realizadas'],
            ['icon' => 'graduation-cap', 'value' => (string) $treinamentos, 'label' => 'Treinamentos realizados'],
            ['icon' => 'clipboard-check', 'value' => (string) $planosConcluidas, 'label' => 'Ações concluídas'],
        ];

        return [
            'cartoesPainel' => $cartoesPainel,
            'visaoProativos' => $visProativos,
            'visaoReativos' => $visReativos,
            'visaoConformidade' => $visConformidade,
            'desempenhoGeralLabel' => $desempenhoGeralLabel,
            'leituraExecutiva' => $leituraExecutiva,
            'pontosAtencao' => $pontosAtencao,
            'cardReativos' => $this->montarCardIndicadoresReativosSesmt($agg, $compCarbon),
            'cardProativos' => $this->montarCardIndicadoresProativosSesmt($agg, $periodoInicio, $periodoFim),
            'cardTreinamentos' => $this->montarCardTreinamentosIntegracoesCampanhasSesmt($registros, $agg, $efetivoExposto),
            'cardInspecoesConformidade' => $this->montarCardInspecoesAuditoriasConformidadeSesmt($registros, $periodoInicio, $periodoFim),
            'cardDesviosTratativas' => $this->montarCardDesviosNotificacoesTratativasSesmt($registros, $agg, $periodoInicio, $periodoFim),
            'cardBoasPraticasKaizen' => $this->montarCardBoasPraticasKaizenMelhoriasSesmt($registros, $agg, $periodoInicio, $periodoFim),
            'cardPlanoAcaoSesmt' => $this->montarCardPlanoAcaoSesmt($periodoInicio, $periodoFim),
        ];
    }

    /**
     * Dados do segundo card («Indicadores Reativos de Segurança») no mesmo painel.
     *
     * @param  array<string, mixed>  $agg
     * @return array<string, mixed>
     */
    private function montarCardIndicadoresReativosSesmt(array $agg, Carbon $compCarbon): array
    {
        $linhasAcidente = $agg['registro_acidente_linhas'];
        $comAfastTexto = $this->contarLinhasComIndicioAfastamento($linhasAcidente);
        $acidComAfastamento = (int) $agg['restricao_trabalho'] + (int) $agg['tratamento_medico'] + $comAfastTexto;
        $totalLinhasAcidente = count($linhasAcidente);
        $acidSemAfastamento = max(0, $totalLinhasAcidente - $comAfastTexto);

        $nPessoal = 0;
        $nMaterial = 0;
        $nAmbiental = 0;
        foreach ($linhasAcidente as $row) {
            if (! empty($row['pessoal'])) {
                $nPessoal++;
            }
            if (! empty($row['material'])) {
                $nMaterial++;
            }
            if (! empty($row['ambiental'])) {
                $nAmbiental++;
            }
        }

        $primeiros = (int) $agg['primeiros_socorros'];
        $tratamento = (int) $agg['tratamento_medico'];
        $restricao = (int) $agg['restricao_trabalho'];
        $regraOuro = (int) ($agg['regra_ouro'] ?? 0);
        $telemetria = (int) ($agg['telemetria'] ?? 0);

        $barRaw = [
            ['label' => 'Primeiros socorros', 'icon' => 'package-plus', 'value' => $primeiros],
            ['label' => 'Tratamento médico', 'icon' => 'stethoscope', 'value' => $tratamento],
            ['label' => 'Restrição de trabalho', 'icon' => 'user-minus', 'value' => $restricao],
            ['label' => 'Acidente material', 'icon' => 'box', 'value' => $nMaterial],
            ['label' => 'Acidente pessoal', 'icon' => 'user', 'value' => $nPessoal],
            ['label' => 'Acidente ambiental', 'icon' => 'leaf', 'value' => $nAmbiental],
        ];

        $maxVal = max(1, ...array_map(fn ($r) => $r['value'], $barRaw));
        $escalaMax = max(1.5, ceil($maxVal * 1.2 * 2) / 2);
        if ($escalaMax > 1.5 && $escalaMax <= 2.5) {
            $escalaMax = 2.5;
        }
        $step = $escalaMax <= 2 ? 0.5 : (ceil($escalaMax / 4 * 2) / 2);
        $escalaTicks = [];
        for ($t = 0.0; $t <= $escalaMax + 0.001; $t += $step) {
            $escalaTicks[] = round($t, 2);
        }

        $barChartOcorrencias = [];
        foreach ($barRaw as $row) {
            $pct = $escalaMax > 0 ? min(100.0, ($row['value'] / $escalaMax) * 100.0) : 0.0;
            $barChartOcorrencias[] = [
                'label' => $row['label'],
                'icon' => $row['icon'],
                'value' => $row['value'],
                'pct' => $pct,
            ];
        }

        $totalOcorrenciasReativas = $primeiros + $tratamento + $restricao + $nPessoal + $nMaterial + $nAmbiental;
        $outrosReativos = $primeiros + $tratamento + $restricao + $regraOuro + $telemetria;
        $impactoGeral = $this->rotuloImpactoGeralCardReativos($nPessoal, $nMaterial, $nAmbiental, $acidComAfastamento, $outrosReativos);

        $cartoesMini = [
            ['icon' => 'shield-plus', 'label' => 'Acid. c/ afastamento', 'value' => (string) $acidComAfastamento],
            ['icon' => 'bandage', 'label' => 'Acid. s/ afastamento', 'value' => (string) $acidSemAfastamento],
            ['icon' => 'package-plus', 'label' => 'Primeiros socorros', 'value' => (string) $primeiros],
            ['icon' => 'stethoscope', 'label' => 'Tratamento médico', 'value' => (string) $tratamento],
            ['icon' => 'user-minus', 'label' => 'Restrição de trabalho', 'value' => (string) $restricao],
            ['icon' => 'star', 'label' => 'Regra de ouro', 'value' => (string) $regraOuro],
            ['icon' => 'wifi', 'label' => 'Telemetria crítica', 'value' => (string) $telemetria],
            ['icon' => 'shield', 'label' => 'Impacto geral', 'value' => $impactoGeral],
        ];

        $leituraReativos = $this->textoLeituraExecutivaReativos(
            $nMaterial,
            $acidComAfastamento,
            $tratamento,
            $primeiros,
            $restricao,
            $impactoGeral
        );
        $pontosReativos = $this->pontosAtencaoReativosPainel($nMaterial, $nPessoal, $primeiros + $tratamento + $restricao);

        return [
            'barChartOcorrencias' => $barChartOcorrencias,
            'escalaMax' => $escalaMax,
            'escalaTicks' => $escalaTicks,
            'resumoTipoAcidente' => [
                'pessoal' => $nPessoal,
                'material' => $nMaterial,
                'ambiental' => $nAmbiental,
                'total' => $totalOcorrenciasReativas,
            ],
            'cartoesMini' => $cartoesMini,
            'leituraReativos' => $leituraReativos,
            'pontosReativos' => $pontosReativos,
        ];
    }

    private function rotuloImpactoGeralCardReativos(int $pessoal, int $material, int $ambiental, int $acidComAfast, int $outrosReativos): string
    {
        if ($pessoal > 0 || $acidComAfast > 0) {
            return 'Alto';
        }
        if ($material + $ambiental >= 2 || $outrosReativos >= 5) {
            return 'Médio';
        }

        return 'Baixo';
    }

    private function textoLeituraExecutivaReativos(
        int $material,
        int $acidComAfast,
        int $tratamento,
        int $primeiros,
        int $restricao,
        string $impacto
    ): string {
        $impactoLc = mb_strtolower($impacto);

        if ($material > 0 && $acidComAfast === 0 && $tratamento === 0 && $primeiros === 0 && $restricao === 0) {
            return 'No período, os indicadores reativos permaneceram sob controle, com registro de '.$material.' ocorrência'
                .($material === 1 ? '' : 's').' material e ausência de eventos com afastamento, atendimento médico, primeiros socorros ou restrição de trabalho. O cenário indica '
                .$impactoLc.' impacto operacional e manutenção do desempenho seguro do contrato.';
        }

        return 'No período, os indicadores reativos encontram-se com impacto classificado como '.$impactoLc
            .'. Foram registradas '.$material.' ocorrência(s) material, '.$primeiros.' de primeiros socorros, '
            .$tratamento.' de tratamento médico e '.$restricao.' de restrição de trabalho; afastamentos associados: '
            .$acidComAfast.'.';
    }

    /**
     * @return list<string>
     */
    private function pontosAtencaoReativosPainel(int $material, int $pessoal, int $cuidadosMedicos): array
    {
        $pontos = [];
        if ($material > 0) {
            $pontos[] = 'Acompanhar tratativa da ocorrência material registrada.';
        }
        $pontos[] = 'Manter controle preventivo para evitar reincidências.';
        if ($pessoal === 0) {
            $pontos[] = 'Sustentar indicador zero para eventos pessoais.';
        }
        if ($cuidadosMedicos > 0) {
            $pontos[] = 'Revisar registros de tratamento médico e restrição até encerramento seguro.';
        }

        return $pontos;
    }

    /**
     * Dados do terceiro card («Indicadores Proativos de Segurança») no mesmo painel.
     *
     * @param  array<string, mixed>  $agg
     * @return array<string, mixed>
     */
    private function montarCardIndicadoresProativosSesmt(
        array $agg,
        Carbon $periodoInicio,
        Carbon $periodoFim
    ): array {
        $planos = SsmaPlanoAcao::query()
            ->whereBetween('created_at', [$periodoInicio->copy()->startOfDay(), $periodoFim->copy()->endOfDay()]);

        $porOrigem = (clone $planos)->selectRaw('origem, COUNT(*) as c')->groupBy('origem')->pluck('c', 'origem');
        $countOrigem = fn (string $o): int => (int) ($porOrigem[$o] ?? 0);

        $desvios = $countOrigem('desvio');
        $inspecoesPlano = $countOrigem('inspecao');

        $dds = (int) $agg['treinamentos'];
        $insp = (int) $agg['inspecoes_canteiro'] + $inspecoesPlano;
        $notif = (int) ($agg['pro_termo_notificacao_vale'] ?? 0) + (int) ($agg['pro_notificacao_omega'] ?? 0);
        $interd = (int) ($agg['pro_termo_interdicao_vale'] ?? 0) + (int) ($agg['pro_interdicao_omega'] ?? 0);
        $quase = (int) $agg['quase_acidente_pro'];
        $abord = (int) $agg['campanhas'] + (($agg['kaizen'] ?? 0) > 0 ? 1 : 0);

        $barRaw = [
            ['label' => 'DDS realizados', 'icon' => 'users-round', 'value' => $dds],
            ['label' => 'Inspeções preventivas', 'icon' => 'clipboard-list', 'value' => $insp],
            ['label' => 'Desvios identificados', 'icon' => 'triangle-alert', 'value' => $desvios],
            ['label' => 'Notificações preventivas', 'icon' => 'bell', 'value' => $notif],
            ['label' => 'Interdições preventivas', 'icon' => 'ban', 'value' => $interd],
            ['label' => 'Quase acidentes', 'icon' => 'zap', 'value' => $quase],
            ['label' => 'Abordagens comportamentais', 'icon' => 'message-circle', 'value' => $abord],
        ];

        $maxVal = max(0, ...array_map(fn ($r) => $r['value'], $barRaw));
        $escalaMax = max(6.0, (float) $maxVal);
        if ($escalaMax > 12) {
            $escalaMax = ceil($escalaMax / 2) * 2;
        }
        $step = $escalaMax <= 10 ? 1.0 : ceil($escalaMax / 6);
        $escalaTicks = [];
        for ($t = 0.0; $t <= $escalaMax + 0.001; $t += $step) {
            $escalaTicks[] = round($t, 2);
        }

        $barChartAcoes = [];
        foreach ($barRaw as $row) {
            $pct = $escalaMax > 0 ? min(100.0, ($row['value'] / $escalaMax) * 100.0) : 0.0;
            $barChartAcoes[] = [
                'label' => $row['label'],
                'icon' => $row['icon'],
                'value' => $row['value'],
                'pct' => $pct,
            ];
        }

        $totalPro = array_sum(array_map(fn ($r) => $r['value'], $barRaw));

        $planosConcluidas = (clone $planos)->whereIn('status', ['concluida', 'validada'])->count();
        $planosEmAndamento = (clone $planos)->whereNotIn('status', ['concluida', 'validada', 'cancelada'])->count();
        $denPlanos = $planosConcluidas + $planosEmAndamento;
        $indice = $denPlanos > 0
            ? round(100 * $planosConcluidas / $denPlanos, 1)
            : ($planosConcluidas > 0 ? 100.0 : 0.0);

        $cartoesResumo = [
            ['icon' => 'shield-plus', 'label' => 'Total de ações proativas', 'value' => (string) $totalPro],
            ['icon' => 'clipboard-check', 'label' => 'Tratativas concluídas', 'value' => (string) $planosConcluidas],
            ['icon' => 'refresh-cw', 'label' => 'Ações em andamento', 'value' => (string) $planosEmAndamento],
            ['icon' => 'pie-chart', 'label' => 'Índice de conclusão', 'value' => number_format($indice, 1, ',', '.').'%'],
        ];

        $faixaRapida = [
            ['icon' => 'zap', 'label' => 'Quase acidentes', 'value' => $quase],
            ['icon' => 'triangle-alert', 'label' => 'Desvios identificados', 'value' => $desvios],
            ['icon' => 'users-round', 'label' => 'DDS realizados', 'value' => $dds],
            ['icon' => 'clipboard-check', 'label' => 'Tratativas concluídas', 'value' => $planosConcluidas],
        ];

        $leituraProativos = $this->textoLeituraExecutivaProativos(
            $totalPro,
            $planosConcluidas,
            $planosEmAndamento,
            $indice,
            $dds,
            $insp
        );
        $pontosProativos = $this->pontosAtencaoProativosPainel($desvios, $notif, $interd, $quase, $planosEmAndamento);

        return [
            'barChartAcoes' => $barChartAcoes,
            'escalaMax' => $escalaMax,
            'escalaTicks' => $escalaTicks,
            'cartoesResumo' => $cartoesResumo,
            'faixaRapida' => $faixaRapida,
            'leituraProativos' => $leituraProativos,
            'pontosProativos' => $pontosProativos,
        ];
    }

    private function textoLeituraExecutivaProativos(
        int $totalPro,
        int $planosConcluidas,
        int $planosEmAndamento,
        float $indice,
        int $dds,
        int $insp
    ): string {
        $comp = number_format($indice, 1, ',', '.');

        if ($totalPro === 0 && $planosConcluidas === 0 && $planosEmAndamento === 0) {
            return 'No período não houve consolidação numérica de ações proativas nos registros mensais nem planos de ação no recorte analisado. Recomenda-se manter o preenchimento das etapas preventivas (treinos, inspeções e DDS) e o acompanhamento de planos para evidenciar o esforço de segurança.';
        }

        return 'Foram evidenciadas '.$totalPro.' ações proativas no período, somando treinos (proxy de DDS), inspeções, desvios com plano, notificações e interdições preventivas, quase acidentes e abordagens (campanhas e Kaizen). '
            .'Há '.$planosConcluidas.' tratativa(s) concluída(s) e '.$planosEmAndamento.' em andamento nos planos de ação, com índice de conclusão de '.$comp.'%. '
            .'Destacam-se '.$dds.' registro(s) em treinos/DDS e '.$insp.' inspeção(ões) preventiva(s) contabilizada(s).';
    }

    /**
     * @return list<string>
     */
    private function pontosAtencaoProativosPainel(int $desvios, int $notif, int $interd, int $quase, int $planosEmAndamento): array
    {
        $pontos = [];
        if ($desvios > 0) {
            $pontos[] = 'Garantir encerramento das tratativas dos desvios identificados no período.';
        }
        if ($interd + $notif > 0) {
            $pontos[] = 'Revisar interdições e notificações preventivas registradas (Vale/Omega) até normalização segura.';
        }
        if ($quase > 2) {
            $pontos[] = 'Volume elevado de quase acidentes: reforçar DDS e barreiras críticas.';
        }
        $pontos[] = 'Manter cadência de inspeções e treinamentos alinhada ao risco da obra.';
        if ($planosEmAndamento > 0) {
            $pontos[] = 'Acompanhar '.$planosEmAndamento.' plano(s) de ação ainda em aberto até conclusão ou validação.';
        }

        return array_values(array_unique($pontos));
    }

    /**
     * Quarto card: treinamentos mensais, integrações (heurística no texto) e campanhas de segurança.
     *
     * @param  Collection<int, SsmaRegistroMensal>  $registros
     * @param  array<string, mixed>  $agg
     * @return array<string, mixed>
     */
    private function montarCardTreinamentosIntegracoesCampanhasSesmt(Collection $registros, array $agg, int $efetivoExposto): array
    {
        $linhasTreino = $this->coletarLinhasTreinamentosMensais($registros);
        $nTreinos = count($linhasTreino);
        $campanhasItens = $this->coletarCampanhasMensais($registros);
        $nCamp = count($campanhasItens);

        $nRac = 0;
        $nNr = 0;
        $nPro = 0;
        $nInt = 0;
        foreach ($linhasTreino as $lin) {
            if (! empty($lin['rac'])) {
                $nRac++;
            }
            if (! empty($lin['nr'])) {
                $nNr++;
            }
            if ($lin['pro_outros'] !== '') {
                $nPro++;
            }
            if ($this->treinamentoLinhaEhIntegracao($lin)) {
                $nInt++;
            }
        }

        $barRaw = [
            ['label' => 'RAC', 'icon' => 'gauge', 'value' => $nRac],
            ['label' => 'NR', 'icon' => 'hard-hat', 'value' => $nNr],
            ['label' => 'PRO Vale', 'icon' => 'shield', 'value' => $nPro],
            ['label' => 'Integrações', 'icon' => 'id-card', 'value' => $nInt],
            ['label' => 'Campanhas', 'icon' => 'megaphone', 'value' => $nCamp],
        ];

        $maxVal = max(0, ...array_map(fn ($r) => $r['value'], $barRaw));
        $escalaMax = max(6.0, (float) $maxVal);
        if ($escalaMax > 12) {
            $escalaMax = ceil($escalaMax / 2) * 2;
        }
        $step = $escalaMax <= 10 ? 1.0 : ceil($escalaMax / 6);
        $escalaTicks = [];
        for ($t = 0.0; $t <= $escalaMax + 0.001; $t += $step) {
            $escalaTicks[] = round($t, 2);
        }

        $barChartCapacitacoes = [];
        foreach ($barRaw as $row) {
            $pct = $escalaMax > 0 ? min(100.0, ($row['value'] / $escalaMax) * 100.0) : 0.0;
            $barChartCapacitacoes[] = [
                'label' => $row['label'],
                'icon' => $row['icon'],
                'value' => $row['value'],
                'pct' => $pct,
            ];
        }

        $refObrigatorio = 7;
        $aderenciaPct = $nTreinos === 0
            ? 0.0
            : min(100.0, round(100 * min($nTreinos, $refObrigatorio) / $refObrigatorio, 1));
        $horasTreino = $nTreinos > 0 ? (int) max(1, round($nTreinos * (24 / 7))) : 0;
        $participantes = ($nTreinos + $nCamp) > 0 ? $efetivoExposto : 0;

        $cartoesResumo = [
            ['icon' => 'graduation-cap', 'label' => 'Treinamentos realizados', 'value' => (string) $nTreinos],
            ['icon' => 'users', 'label' => 'Participantes treinados', 'value' => (string) $participantes],
            ['icon' => 'clock', 'label' => 'Horas de treinamento', 'value' => $horasTreino > 0 ? $horasTreino.'h' : '0h'],
            ['icon' => 'badge-check', 'label' => 'Aderência obrigatória', 'value' => number_format($aderenciaPct, 1, ',', '.').'%'],
        ];

        $linhasOrdenadas = $linhasTreino;
        usort($linhasOrdenadas, function (array $a, array $b): int {
            $da = (string) ($a['data'] ?? '');
            $db = (string) ($b['data'] ?? '');

            return strcmp($da, $db);
        });

        $tabelaTreinos = [];
        foreach (array_slice($linhasOrdenadas, 0, 13) as $lin) {
            $tabelaTreinos[] = [
                'data' => $this->formatarDataTreinoTabela($lin['data'] ?? null),
                'categoria' => $this->rotuloCategoriaTreinamento($lin),
                'titulo' => $lin['titulo_descricao'] !== '' ? $lin['titulo_descricao'] : '—',
                'instrutor' => $lin['instrutor'] !== '' ? $lin['instrutor'] : '—',
            ];
        }

        $primeiraCampanha = $campanhasItens[0] ?? null;
        $tituloCampanha = is_array($primeiraCampanha)
            ? trim((string) ($primeiraCampanha['titulo'] ?? ''))
            : '';
        $descCampanha = is_array($primeiraCampanha)
            ? trim((string) ($primeiraCampanha['descricao'] ?? ''))
            : '';

        $campanhaTituloExibir = $nCamp === 0
            ? 'Nenhuma campanha registrada'
            : ($tituloCampanha !== '' ? $tituloCampanha : 'Campanha de segurança');
        $campanhaDescExibir = $nCamp === 0
            ? 'Não há campanha de segurança preenchida neste recorte. Ao realizar ações como Maio Amarelo ou DDS participativos, registre título, data e descrição na etapa correspondente do registro mensal.'
            : ($descCampanha !== '' ? $descCampanha : 'Registre a descrição na etapa «Campanha de segurança» do registro mensal para detalhar a ação de conscientização.');

        $leituraTreinos = $this->textoLeituraExecutivaTreinamentosCampanhas(
            $nTreinos,
            $horasTreino,
            $aderenciaPct,
            $tituloCampanha,
            $nCamp
        );
        $pontosTreinos = $this->pontosAtencaoTreinamentosCampanhas($nTreinos, $aderenciaPct, $nCamp);

        return [
            'barChartCapacitacoes' => $barChartCapacitacoes,
            'escalaMax' => $escalaMax,
            'escalaTicks' => $escalaTicks,
            'cartoesResumo' => $cartoesResumo,
            'tabelaTreinos' => $tabelaTreinos,
            'campanhaTitulo' => $campanhaTituloExibir,
            'campanhaDescricao' => $campanhaDescExibir,
            'campanhasRealizadas' => $nCamp,
            'publicoCampanhaColaboradores' => $participantes,
            'leituraTreinos' => $leituraTreinos,
            'pontosTreinos' => $pontosTreinos,
        ];
    }

    /**
     * Quinto card: inspeções de canteiro, auditorias mensais e planos de ação de conformidade.
     *
     * @param  Collection<int, SsmaRegistroMensal>  $registros
     * @return array<string, mixed>
     */
    private function montarCardInspecoesAuditoriasConformidadeSesmt(
        Collection $registros,
        Carbon $periodoInicio,
        Carbon $periodoFim
    ): array {
        $planos = SsmaPlanoAcao::query()
            ->whereBetween('created_at', [$periodoInicio->copy()->startOfDay(), $periodoFim->copy()->endOfDay()]);

        $porOrigem = (clone $planos)->selectRaw('origem, COUNT(*) as c')->groupBy('origem')->pluck('c', 'origem');
        $countOrigem = fn (string $o): int => (int) ($porOrigem[$o] ?? 0);
        $inspecoesPlano = $countOrigem('inspecao');
        $auditoriasPlano = $countOrigem('auditoria');

        $nInspSim = 0;
        $nInspNao = 0;
        $nInspQ = 0;
        $nAudSim = 0;
        $nAudNao = 0;
        $nAudQ = 0;
        $notas = [];
        foreach ($registros as $r) {
            $e = $r->etapas;
            if (! is_array($e)) {
                continue;
            }
            $insp = $e['inspecao_mensal_canteiro'] ?? [];
            if (is_array($insp) && (($insp['passou_inspecao'] ?? null) !== null || ! empty($insp['data_inspecao']) || ! empty($insp['descricao']))) {
                $nInspQ++;
                $p = $insp['passou_inspecao'] ?? null;
                if ($p === 'sim') {
                    $nInspSim++;
                } elseif ($p === 'nao') {
                    $nInspNao++;
                }
                $nv = $this->extrairNotaPercentualDeCampo(is_string($insp['nota'] ?? null) ? $insp['nota'] : null);
                if ($nv !== null) {
                    $notas[] = $nv;
                }
            }
            $aud = $e['auditoria_mensal'] ?? [];
            if (is_array($aud) && (($aud['passou_auditoria'] ?? null) !== null || ! empty($aud['data_auditoria']) || ! empty($aud['descricao']))) {
                $nAudQ++;
                $pa = $aud['passou_auditoria'] ?? null;
                if ($pa === 'sim') {
                    $nAudSim++;
                } elseif ($pa === 'nao') {
                    $nAudNao++;
                }
                $nv = $this->extrairNotaPercentualDeCampo(is_string($aud['nota'] ?? null) ? $aud['nota'] : null);
                if ($nv !== null) {
                    $notas[] = $nv;
                }
            }
        }

        $planosConcluidas = (clone $planos)->whereIn('status', ['concluida', 'validada'])->count();
        $planosConclConform = (clone $planos)->whereIn('status', ['concluida', 'validada'])
            ->whereIn('origem', ['inspecao', 'auditoria', 'desvio'])
            ->count();
        $planosEmAndamento = (clone $planos)->whereNotIn('status', ['concluida', 'validada', 'cancelada'])->count();
        $planosVencidos = (clone $planos)->where('status', 'vencida')->count();

        $itensConformes = $nInspSim + $nAudSim + $planosConclConform;
        $emTratativaBar = $planosEmAndamento;
        $naoConformesBar = $nInspNao + $nAudNao + $planosVencidos;

        $barRaw = [
            ['label' => 'Itens conformes', 'icon' => 'shield-check', 'value' => $itensConformes],
            ['label' => 'Em tratativa', 'icon' => 'clock', 'value' => $emTratativaBar],
            ['label' => 'Não conformes', 'icon' => 'circle-x', 'value' => $naoConformesBar],
        ];

        $maxVal = max(0, ...array_map(fn ($r) => $r['value'], $barRaw));
        $escalaMax = max(30.0, (float) $maxVal);
        if ($escalaMax > 40) {
            $escalaMax = ceil($escalaMax / 10) * 10;
        }
        $step = $escalaMax <= 30 ? 5.0 : ceil($escalaMax / 6);
        $escalaTicks = [];
        for ($t = 0.0; $t <= $escalaMax + 0.001; $t += $step) {
            $escalaTicks[] = round($t, 2);
        }

        $barConformidade = [];
        foreach ($barRaw as $row) {
            $pct = $escalaMax > 0 ? min(100.0, ($row['value'] / $escalaMax) * 100.0) : 0.0;
            $barConformidade[] = [
                'label' => $row['label'],
                'icon' => $row['icon'],
                'value' => $row['value'],
                'pct' => $pct,
            ];
        }

        $inspecoesRealizadas = $inspecoesPlano + $nInspQ;
        $auditoriasRealizadas = $auditoriasPlano + $nAudQ;

        $mediaNota = $notas !== [] ? round(array_sum($notas) / count($notas), 0) : null;
        $notaMediaLabel = $mediaNota !== null
            ? ((int) $mediaNota).'/100'
            : (($nInspQ + $nAudQ) > 0 ? '100/100' : '—');

        $paresDecisao = $nInspSim + $nInspNao + $nAudSim + $nAudNao;
        if ($paresDecisao > 0) {
            $conformidadeGeralPct = round(100 * ($nInspSim + $nAudSim) / $paresDecisao, 1);
        } elseif ($mediaNota !== null) {
            $conformidadeGeralPct = (float) $mediaNota;
        } else {
            $conformidadeGeralPct = ($naoConformesBar === 0 && $emTratativaBar === 0) ? 100.0 : 96.0;
        }

        $cartoesResumo = [
            ['icon' => 'clipboard-list', 'label' => 'Inspeções realizadas', 'value' => (string) $inspecoesRealizadas],
            ['icon' => 'search', 'label' => 'Auditorias realizadas', 'value' => (string) $auditoriasRealizadas],
            ['icon' => 'star', 'label' => 'Nota média', 'value' => $notaMediaLabel],
            ['icon' => 'shield-check', 'label' => 'Conformidade geral', 'value' => number_format($conformidadeGeralPct, 1, ',', '.').'%'],
        ];

        $areasInspecionadas = $nInspQ + $inspecoesPlano;
        $pendenciasAbertas = $emTratativaBar + $nInspNao + $nAudNao;
        $pendenciasTratadas = $planosConclConform;

        $faixaResumo = [
            ['icon' => 'building', 'label' => 'Áreas inspecionadas', 'value' => $areasInspecionadas],
            ['icon' => 'triangle-alert', 'label' => 'Pendências abertas', 'value' => $pendenciasAbertas],
            ['icon' => 'clipboard-check', 'label' => 'Pendências tratadas', 'value' => $pendenciasTratadas],
        ];

        $ultimo = $registros->first();
        $audBox = $this->montarDetalheAuditoriaMensalPainel($ultimo?->etapas);
        $inspBox = $this->montarDetalheInspecaoCanteiroPainel($ultimo?->etapas);

        $leitura = $this->textoLeituraExecutivaInspecoesConformidade(
            $inspecoesRealizadas,
            $auditoriasRealizadas,
            $conformidadeGeralPct,
            $emTratativaBar,
            $naoConformesBar
        );
        $pontos = $this->pontosAtencaoInspecoesConformidade($naoConformesBar, $emTratativaBar, $conformidadeGeralPct);

        return [
            'barConformidade' => $barConformidade,
            'escalaMax' => $escalaMax,
            'escalaTicks' => $escalaTicks,
            'faixaResumo' => $faixaResumo,
            'cartoesResumo' => $cartoesResumo,
            'auditoriaBox' => $audBox,
            'inspecaoBox' => $inspBox,
            'leituraConformidade' => $leitura,
            'pontosConformidade' => $pontos,
        ];
    }

    private function extrairNotaPercentualDeCampo(?string $nota): ?float
    {
        if ($nota === null || trim($nota) === '') {
            return null;
        }
        $nota = trim($nota);
        if (preg_match('/(\d+)\s*\/\s*100/', $nota, $m) === 1) {
            return min(100.0, max(0.0, (float) $m[1]));
        }
        if (preg_match('/^(\d{1,3})(?:\s|$|,)/', $nota, $m) === 1) {
            return min(100.0, max(0.0, (float) $m[1]));
        }

        return null;
    }

    /**
     * @return array<string, string>
     */
    private function montarDetalheAuditoriaMensalPainel(?array $etapas): array
    {
        $aud = is_array($etapas) ? ($etapas['auditoria_mensal'] ?? []) : [];
        if (! is_array($aud)) {
            $aud = [];
        }
        $p = $aud['passou_auditoria'] ?? null;
        $preenchida = ($p !== null) || ! empty($aud['data_auditoria']) || ! empty($aud['descricao']);
        if (! $preenchida) {
            return [
                'auditor' => '—',
                'local' => '—',
                'data' => '—',
                'status' => '—',
                'resultado' => 'Sem auditoria registrada na competência.',
            ];
        }
        $status = match ($p) {
            'sim' => 'Realizada',
            'nao' => 'Com pendências',
            default => 'Não informado',
        };
        $res = trim((string) ($aud['descricao'] ?? ''));
        if ($res === '' && $p === 'sim') {
            $res = 'Sem desvios críticos registrados no relatório.';
        } elseif ($res === '') {
            $res = '—';
        }

        return [
            'auditor' => trim((string) ($aud['auditor'] ?? '')) !== '' ? trim((string) $aud['auditor']) : '—',
            'local' => trim((string) ($aud['local'] ?? '')) !== '' ? trim((string) $aud['local']) : '—',
            'data' => $this->formatarDataBrasilCurta($aud['data_auditoria'] ?? null),
            'status' => $status,
            'resultado' => $res,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function montarDetalheInspecaoCanteiroPainel(?array $etapas): array
    {
        $insp = is_array($etapas) ? ($etapas['inspecao_mensal_canteiro'] ?? []) : [];
        if (! is_array($insp)) {
            $insp = [];
        }
        $p = $insp['passou_inspecao'] ?? null;
        $preenchida = ($p !== null) || ! empty($insp['data_inspecao']) || ! empty($insp['descricao']);
        if (! $preenchida) {
            return [
                'inspetor' => '—',
                'local' => '—',
                'data' => '—',
                'nota' => '—',
                'referencia' => 'Sem inspeção de canteiro registrada na competência.',
            ];
        }
        $nota = trim((string) ($insp['nota'] ?? ''));
        if ($nota === '') {
            $nota = $p === 'sim' ? '100/100' : '—';
        }
        $ref = trim((string) ($insp['descricao'] ?? ''));
        if ($ref === '' && $p === 'sim') {
            $ref = 'Padrão 5S Vale';
        } elseif ($ref === '') {
            $ref = '—';
        }

        return [
            'inspetor' => trim((string) ($insp['inspetor'] ?? '')) !== '' ? trim((string) $insp['inspetor']) : '—',
            'local' => trim((string) ($insp['local'] ?? '')) !== '' ? trim((string) $insp['local']) : '—',
            'data' => $this->formatarDataBrasilCurta($insp['data_inspecao'] ?? null),
            'nota' => $nota,
            'referencia' => $ref,
        ];
    }

    private function formatarDataBrasilCurta(mixed $data): string
    {
        if ($data === null || $data === '') {
            return '—';
        }
        try {
            return Carbon::parse((string) $data)->format('d/m/Y');
        } catch (\Throwable) {
            return '—';
        }
    }

    private function textoLeituraExecutivaInspecoesConformidade(
        int $inspecoesRealizadas,
        int $auditoriasRealizadas,
        float $conformidadePct,
        int $emTratativa,
        int $naoConformes
    ): string {
        $c = number_format($conformidadePct, 1, ',', '.');

        if ($inspecoesRealizadas === 0 && $auditoriasRealizadas === 0 && $emTratativa === 0 && $naoConformes === 0) {
            return 'Não há inspeções de canteiro, auditorias mensais ou planos de conformidade evidenciados no recorte. Preencha as etapas correspondentes no registro mensal e mantenha os planos de ação de inspeção e auditoria atualizados.';
        }

        return 'O período consolida '.$inspecoesRealizadas.' inspeção(ões) realizada(s) (canteiro e planos) e '.$auditoriasRealizadas.' auditoria(s), com conformidade geral estimada em '.$c.'%. '
            .'Há '.$emTratativa.' pendência(s) em tratativa nos planos de ação e '.$naoConformes.' indicador(es) de não conformidade (inspeções/auditorias com resultado negativo ou planos vencidos). '
            .'Recomenda-se manter o acompanhamento até encerramento das tratativas e evidências de verificação.';
    }

    /**
     * @return list<string>
     */
    private function pontosAtencaoInspecoesConformidade(int $naoConformes, int $emTratativa, float $conformidadePct): array
    {
        $pontos = [];
        if ($naoConformes > 0) {
            $pontos[] = 'Concluir '.$naoConformes.' item(ns) não conforme(s) identificado(s).';
        }
        if ($emTratativa > 0) {
            $pontos[] = 'Encerrar '.$emTratativa.' pendência(s) em tratativa nos planos de ação.';
        }
        $pontos[] = 'Manter desempenho de conformidade acima de 95%.';
        if ($conformidadePct < 95.0 && $naoConformes === 0 && $emTratativa === 0) {
            $pontos[] = 'Reforçar evidências de inspeção e auditoria para sustentar o percentual de conformidade.';
        }

        return array_values(array_unique($pontos));
    }

    /**
     * Sexto card: desvios (planos de ação), notificações e interdições proativas.
     *
     * @param  Collection<int, SsmaRegistroMensal>  $registros
     * @param  array<string, mixed>  $agg
     * @return array<string, mixed>
     */
    private function montarCardDesviosNotificacoesTratativasSesmt(
        Collection $registros,
        array $agg,
        Carbon $periodoInicio,
        Carbon $periodoFim
    ): array {
        $planos = SsmaPlanoAcao::query()
            ->whereBetween('created_at', [$periodoInicio->copy()->startOfDay(), $periodoFim->copy()->endOfDay()]);

        /** @var \Illuminate\Support\Collection<int, SsmaPlanoAcao> $listaDesvio */
        $listaDesvio = (clone $planos)->where('origem', 'desvio')->orderByDesc('id')->get();

        $identificados = $listaDesvio->count();
        $concluidos = $listaDesvio->whereIn('status', ['concluida', 'validada'])->count();
        $emTratativa = $listaDesvio->whereNotIn('status', ['concluida', 'validada', 'cancelada'])->count();

        $canais = ['inspecao' => 0, 'campo' => 0, 'vale' => 0, 'interno' => 0];
        foreach ($listaDesvio as $plano) {
            $ch = $this->classificarCanalDesvioPlano($plano);
            $canais[$ch]++;
        }
        $somaCanais = $canais['inspecao'] + $canais['campo'] + $canais['vale'] + $canais['interno'];
        if ($identificados > $somaCanais) {
            $canais['interno'] += $identificados - $somaCanais;
        }

        $resolucaoPct = $identificados > 0
            ? (int) round(100 * $concluidos / $identificados)
            : 0;

        $diasConcl = [];
        foreach ($listaDesvio as $plano) {
            if (! in_array($plano->status, ['concluida', 'validada'], true)) {
                continue;
            }
            if ($plano->data_conclusao === null || $plano->created_at === null) {
                continue;
            }
            try {
                $ini = Carbon::parse($plano->created_at)->startOfDay();
                $fim = Carbon::parse($plano->data_conclusao)->startOfDay();
                $diasConcl[] = max(1, $ini->diffInDays($fim));
            } catch (\Throwable) {
                continue;
            }
        }
        $prazoMedioDias = $diasConcl !== [] ? (int) round(array_sum($diasConcl) / count($diasConcl)) : 0;

        $notifInternas = (int) ($agg['pro_notificacao_omega'] ?? 0);
        $notifVale = (int) ($agg['pro_termo_notificacao_vale'] ?? 0);
        $interdInternas = (int) ($agg['pro_interdicao_omega'] ?? 0);

        $gridMini = [
            ['icon' => 'triangle-alert', 'label' => 'Desvios identificados', 'value' => (string) $identificados],
            ['icon' => 'clipboard-check', 'label' => 'Desvios concluídos', 'value' => (string) $concluidos],
            ['icon' => 'refresh-cw', 'label' => 'Em tratativa', 'value' => (string) $emTratativa],
            ['icon' => 'bell', 'label' => 'Notificações internas', 'value' => (string) $notifInternas],
            ['icon' => 'mail', 'label' => 'Notificações Vale', 'value' => (string) $notifVale],
            ['icon' => 'ban', 'label' => 'Interdições internas', 'value' => (string) $interdInternas],
        ];

        $faixaMedia = [
            [
                'icon' => 'clock',
                'label' => 'Prazo médio de tratativa',
                'value' => $prazoMedioDias > 0 ? $prazoMedioDias.' dia'.($prazoMedioDias === 1 ? '' : 's') : '—',
            ],
            [
                'icon' => 'target',
                'label' => 'Resolução',
                'value' => $identificados > 0 ? $resolucaoPct.'%' : '—',
            ],
        ];

        $fluxoDesvios = [
            ['key' => 'id', 'label' => 'Identificados', 'icon' => 'search', 'value' => $identificados],
            ['key' => 'tr', 'label' => 'Em tratativa', 'icon' => 'refresh-cw', 'value' => $emTratativa],
            ['key' => 'ok', 'label' => 'Concluídos', 'icon' => 'check', 'value' => $concluidos],
        ];

        $origemRegistros = [
            ['label' => 'Inspeção', 'icon' => 'clipboard-list', 'value' => $canais['inspecao']],
            ['label' => 'Campo', 'icon' => 'hard-hat', 'value' => $canais['campo']],
            ['label' => 'Vale', 'icon' => 'truck', 'value' => $canais['vale']],
            ['label' => 'Interno', 'icon' => 'users', 'value' => $canais['interno']],
        ];

        $tabelaPlano = [];
        foreach ($listaDesvio->take(10) as $plano) {
            $canal = $this->classificarCanalDesvioPlano($plano);
            $origemTab = match ($canal) {
                'inspecao' => 'Inspeção',
                'campo' => 'Campo',
                'vale' => 'Vale',
                default => 'Interno',
            };
            $texto = trim((string) ($plano->descricao_desvio ?? ''));
            if ($texto === '') {
                $texto = trim((string) ($plano->acao_necessaria ?? ''));
            }
            $concl = in_array($plano->status, ['concluida', 'validada'], true);
            $prazoTxt = '—';
            if (! $concl && $plano->prazo !== null) {
                try {
                    $ini = Carbon::parse($plano->created_at)->startOfDay();
                    $pr = Carbon::parse($plano->prazo)->startOfDay();
                    $nd = max(1, $ini->diffInDays($pr));
                    $prazoTxt = $nd.' dia'.($nd === 1 ? '' : 's');
                } catch (\Throwable) {
                    $prazoTxt = '—';
                }
            }
            $tabelaPlano[] = [
                'origem' => $origemTab,
                'tipo' => 'Desvio',
                'descricao' => Str::limit($texto !== '' ? $texto : '—', 96),
                'status' => $concl ? 'Concluído' : 'Em tratativa',
                'statusVariant' => $concl ? 'ok' : 'warn',
                'prazo' => $prazoTxt,
            ];
        }

        $tabelaProativas = $this->coletarLinhasNotificacoesInterdicoesParaTabela($registros);
        $tabelaPrincipais = array_values(array_slice(array_merge($tabelaPlano, $tabelaProativas), 0, 10));

        $leitura = $this->textoLeituraExecutivaDesviosTratativas(
            $identificados,
            $concluidos,
            $emTratativa,
            $resolucaoPct,
            $notifInternas + $notifVale,
            $interdInternas
        );
        $pontos = $this->pontosAtencaoDesviosTratativas($emTratativa, $identificados, $resolucaoPct);

        return [
            'fluxoDesvios' => $fluxoDesvios,
            'origemRegistros' => $origemRegistros,
            'percentualResolucao' => $identificados > 0 ? $resolucaoPct : null,
            'percentualResolucaoFmt' => $identificados > 0 ? $resolucaoPct.'%' : '—',
            'gridMini' => $gridMini,
            'faixaMedia' => $faixaMedia,
            'tabelaPrincipais' => $tabelaPrincipais,
            'leituraDesvios' => $leitura,
            'pontosDesvios' => $pontos,
        ];
    }

    /**
     * Sétimo card: boas práticas, Kaizen e melhorias (registro mensal + planos de melhoria).
     *
     * @param  Collection<int, SsmaRegistroMensal>  $registros
     * @param  array<string, mixed>  $agg
     * @return array<string, mixed>
     */
    private function montarCardBoasPraticasKaizenMelhoriasSesmt(
        Collection $registros,
        array $agg,
        Carbon $periodoInicio,
        Carbon $periodoFim
    ): array {
        $planos = SsmaPlanoAcao::query()
            ->whereBetween('created_at', [$periodoInicio->copy()->startOfDay(), $periodoFim->copy()->endOfDay()]);

        $planosMelhoria = (clone $planos)->where('tipo', 'melhoria')->orderByDesc('id')->get();
        $melhoriasConclPlano = $planosMelhoria->whereIn('status', ['concluida', 'validada'])->count();

        $nKaizenRegs = 0;
        $colabIds = 0;
        foreach ($registros as $r) {
            $kz = data_get($r->etapas, 'boas_praticas_kaizen');
            if (! is_array($kz) || ! $this->kaizenEtapaPreenchida($kz)) {
                continue;
            }
            $nKaizenRegs++;
            $col = $kz['colaboradores'] ?? [];
            if (is_array($col)) {
                $colabIds += count($col);
            }
            $resp = trim((string) ($kz['responsaveis'] ?? ''));
            if ($resp !== '') {
                $colabIds += max(1, substr_count($resp, ',') + 1);
            }
        }

        $boasPraticasReg = (int) $agg['treinamentos'] + (int) $agg['campanhas'] + $nKaizenRegs;
        $kaizensImpl = $nKaizenRegs + $planosMelhoria->count();
        $melhoriasConcl = $melhoriasConclPlano + $nKaizenRegs;
        $responsaveisEnv = max($colabIds, $nKaizenRegs > 0 ? 1 : 0);

        $planosDesvioConcl = (clone $planos)->where('origem', 'desvio')->whereIn('status', ['concluida', 'validada'])->count();
        $riscosReduzidos = (int) $agg['quase_acidente_pro'] + $planosDesvioConcl;
        $ganhosGerados = max($responsaveisEnv, $boasPraticasReg);

        $contagemTipo = $this->contarMelhoriasPorTipoBarrasVerticais($registros, $planosMelhoria);
        $maxBar = max(1, ...array_map(fn ($r) => $r['value'], $contagemTipo));
        $chartBarrasVert = [];
        foreach ($contagemTipo as $row) {
            $chartBarrasVert[] = [
                'label' => $row['label'],
                'value' => $row['value'],
                'pct' => $maxBar > 0 ? (int) round(100 * $row['value'] / $maxBar) : 0,
            ];
        }

        $faixaSobGrafico = [
            ['icon' => 'award', 'label' => 'Boas práticas', 'value' => $boasPraticasReg],
            ['icon' => 'refresh-cw', 'label' => 'Kaizens implementados', 'value' => $kaizensImpl],
            ['icon' => 'check', 'label' => 'Melhorias concluídas', 'value' => $melhoriasConcl],
            ['icon' => 'shield', 'label' => 'Riscos reduzidos', 'value' => $riscosReduzidos],
        ];

        $gridResumo = [
            ['icon' => 'clipboard-list', 'label' => 'Boas práticas registradas', 'value' => (string) $boasPraticasReg],
            ['icon' => 'refresh-cw', 'label' => 'Kaizens implementados', 'value' => (string) $kaizensImpl],
            ['icon' => 'check', 'label' => 'Melhorias concluídas', 'value' => (string) $melhoriasConcl],
            ['icon' => 'users', 'label' => 'Responsáveis envolvidos', 'value' => (string) $responsaveisEnv],
        ];

        $faixaLarga = [
            ['icon' => 'shield', 'label' => 'Riscos reduzidos / eliminados', 'value' => (string) $riscosReduzidos],
            ['icon' => 'target', 'label' => 'Ganhos gerados', 'value' => (string) $ganhosGerados],
        ];

        $kaizenDestaque = $this->montarPayloadKaizenDestaquePainel($registros);

        $destaques = [
            ['icon' => 'shield', 'titulo' => 'Segurança', 'desc' => 'Redução de riscos e reincidências quando há Kaizen, treinamentos e campanhas alinhados.'],
            ['icon' => 'user', 'titulo' => 'Ergonomia', 'desc' => 'Melhoria nas condições de trabalho com organização do layout e postos.'],
            ['icon' => 'chart-column-increasing', 'titulo' => 'Produtividade', 'desc' => 'Processos mais ágeis e eficientes após padronização e 5S.'],
            ['icon' => 'triangle-alert', 'titulo' => 'Risco eliminado', 'desc' => 'Tratativas concluídas de desvios e quase acidentes reduzem exposição operacional.'],
        ];

        $leitura = $this->textoLeituraExecutivaBoasPraticasKaizen(
            $boasPraticasReg,
            $kaizensImpl,
            $melhoriasConcl,
            $kaizenDestaque['titulo'] ?? ''
        );
        $pontos = $this->pontosAtencaoBoasPraticasKaizen($nKaizenRegs, $melhoriasConclPlano, $planosMelhoria->count());

        return [
            'chartBarrasVert' => $chartBarrasVert,
            'faixaSobGrafico' => $faixaSobGrafico,
            'gridResumo' => $gridResumo,
            'faixaLarga' => $faixaLarga,
            'kaizenDestaque' => $kaizenDestaque,
            'destaques' => $destaques,
            'leituraBoasPraticas' => $leitura,
            'pontosBoasPraticas' => $pontos,
        ];
    }

    private function kaizenEtapaPreenchida(?array $kz): bool
    {
        if (! is_array($kz)) {
            return false;
        }
        $tit = trim((string) ($kz['titulo'] ?? ''));
        $gan = trim((string) ($kz['ganhos_processo'] ?? ''));
        $resp = trim((string) ($kz['responsaveis'] ?? ''));
        $col = $kz['colaboradores'] ?? [];
        $hasCol = is_array($col) && $col !== [];
        $f1 = ! empty($kz['foto_antes_path']);
        $f2 = ! empty($kz['foto_depois_path']);

        return $tit !== '' || $gan !== '' || $resp !== '' || $hasCol || $f1 || $f2;
    }

    /**
     * @param  Collection<int, SsmaPlanoAcao>  $planosMelhoria
     * @return list<array{label: string, value: int}>
     */
    private function contarMelhoriasPorTipoBarrasVerticais(Collection $registros, Collection $planosMelhoria): array
    {
        $order = ['Segurança', 'Ergonomia', 'Processo', 'Meio ambiente', 'Produtividade'];
        $cats = array_fill_keys($order, 0);

        $patterns = [
            'Segurança' => '/segur|acidente|epi|risco|queda|inc[eê]ndio|trava|bloqueio/i',
            'Ergonomia' => '/ergonom|lombar|postura|esfor[cç]o|peso|moviment/i',
            'Meio ambiente' => '/ambient|res[ií]duo|lixo|[eé]gua|impacto ambient|sustentabil/i',
            'Produtividade' => '/produtiv|tempo|redu[cç]|efici[eê]n|otimi|velocidade/i',
            'Processo' => '/processo|5s|padron|almoxarif|layout|sequ[eê]ncia|fluxo|organiza/i',
        ];

        foreach ($registros as $r) {
            $kz = data_get($r->etapas, 'boas_praticas_kaizen');
            if (! is_array($kz) || ! $this->kaizenEtapaPreenchida($kz)) {
                continue;
            }
            $blob = mb_strtolower(trim((string) ($kz['titulo'] ?? '').' '.(string) ($kz['ganhos_processo'] ?? '')));
            if ($blob === '') {
                continue;
            }
            $this->incrementarUmaCategoriaMelhoria($cats, $patterns, $blob);
        }

        foreach ($planosMelhoria as $plano) {
            $blob = mb_strtolower(trim((string) ($plano->descricao_desvio ?? '').' '.(string) ($plano->acao_necessaria ?? '')));
            if ($blob === '') {
                continue;
            }
            $this->incrementarUmaCategoriaMelhoria($cats, $patterns, $blob);
        }

        $sum = array_sum($cats);
        if ($sum === 0) {
            foreach ($registros as $r) {
                if ($this->kaizenEtapaPreenchida(data_get($r->etapas, 'boas_praticas_kaizen'))) {
                    $cats['Processo'] = 1;
                    break;
                }
            }
        }

        $out = [];
        foreach ($order as $label) {
            $out[] = ['label' => $label, 'value' => (int) $cats[$label]];
        }

        return $out;
    }

    /**
     * @param  array<string, int>  $cats
     * @param  array<string, string>  $patterns
     */
    private function incrementarUmaCategoriaMelhoria(array &$cats, array $patterns, string $blob): void
    {
        foreach (['Segurança', 'Meio ambiente', 'Ergonomia', 'Produtividade', 'Processo'] as $key) {
            if (preg_match($patterns[$key], $blob) === 1) {
                $cats[$key]++;

                return;
            }
        }
        $cats['Processo']++;
    }

    /**
     * @param  Collection<int, SsmaRegistroMensal>  $registros
     * @return array<string, mixed>
     */
    private function montarPayloadKaizenDestaquePainel(Collection $registros): array
    {
        $vazio = [
            'vazio' => true,
            'titulo' => '—',
            'responsavel' => '—',
            'participantes' => '—',
            'data' => '—',
            'problema' => '—',
            'solucao' => '—',
            'ganho' => '—',
            'urlAntes' => null,
            'urlDepois' => null,
        ];

        $ultimoComKaizen = null;
        foreach ($registros->sortByDesc('id') as $r) {
            $kz = data_get($r->etapas, 'boas_praticas_kaizen');
            if ($this->kaizenEtapaPreenchida(is_array($kz) ? $kz : null)) {
                $ultimoComKaizen = ['reg' => $r, 'kz' => $kz];
                break;
            }
        }

        if ($ultimoComKaizen === null) {
            return $vazio;
        }

        /** @var SsmaRegistroMensal $reg */
        $reg = $ultimoComKaizen['reg'];
        /** @var array<string, mixed> $kz */
        $kz = $ultimoComKaizen['kz'];

        $tit = trim((string) ($kz['titulo'] ?? ''));
        $resp = trim((string) ($kz['responsaveis'] ?? ''));
        $gan = trim((string) ($kz['ganhos_processo'] ?? ''));
        $partes = $this->extrairProblemaSolucaoGanhoKaizen($gan);

        $nomes = [];
        $col = $kz['colaboradores'] ?? [];
        if (is_array($col)) {
            foreach ($col as $c) {
                if (is_array($c) && ! empty($c['nome'])) {
                    $nomes[] = (string) $c['nome'];
                }
            }
        }
        $participantes = $nomes !== [] ? implode(' / ', array_slice($nomes, 0, 8)) : '—';

        $dataImpl = '—';
        try {
            if ($reg->competencia !== null) {
                $dataImpl = Carbon::parse($reg->competencia)->endOfMonth()->format('d/m/Y');
            }
        } catch (\Throwable) {
            $dataImpl = '—';
        }

        $urlAntes = null;
        $urlDepois = null;
        if (! empty($kz['foto_antes_path']) && is_string($kz['foto_antes_path'])) {
            $urlAntes = Storage::disk('public')->url($kz['foto_antes_path']);
        }
        if (! empty($kz['foto_depois_path']) && is_string($kz['foto_depois_path'])) {
            $urlDepois = Storage::disk('public')->url($kz['foto_depois_path']);
        }

        return [
            'vazio' => false,
            'titulo' => $tit !== '' ? $tit : 'Projeto Kaizen',
            'responsavel' => $resp !== '' ? $resp : '—',
            'participantes' => $participantes,
            'data' => $dataImpl,
            'problema' => $partes['problema'],
            'solucao' => $partes['solucao'],
            'ganho' => $partes['ganho'],
            'urlAntes' => $urlAntes,
            'urlDepois' => $urlDepois,
        ];
    }

    /**
     * @return array{problema: string, solucao: string, ganho: string}
     */
    private function extrairProblemaSolucaoGanhoKaizen(string $ganhos): array
    {
        $ganhos = trim($ganhos);
        if ($ganhos === '') {
            return [
                'problema' => '—',
                'solucao' => '—',
                'ganho' => '—',
            ];
        }

        $partes = preg_split('/\n{2,}|\.\s+(?=[A-ZÁÉÍÓÚÂÊÔÇ])/u', $ganhos);
        $p0 = isset($partes[0]) ? trim((string) $partes[0]) : '';
        $p1 = isset($partes[1]) ? trim((string) $partes[1]) : '';
        $p2 = isset($partes[2]) ? trim((string) $partes[2]) : '';

        $problema = $p0 !== '' ? Str::limit($p0, 220) : Str::limit($ganhos, 120);
        $solucao = $p1 !== '' ? Str::limit($p1, 220) : ($p0 !== '' ? 'Detalhes complementares no ganho obtido.' : Str::limit($ganhos, 120));
        $ganho = $p2 !== '' ? Str::limit($p2, 260) : Str::limit($ganhos, 280);

        return [
            'problema' => $problema,
            'solucao' => $solucao,
            'ganho' => $ganho,
        ];
    }

    private function textoLeituraExecutivaBoasPraticasKaizen(
        int $boasPraticas,
        int $kaizens,
        int $melhoriasConcl,
        string $tituloKaizen
    ): string {
        if ($boasPraticas === 0 && $kaizens === 0 && $melhoriasConcl === 0) {
            return 'Não há evidências consolidadas de boas práticas ou Kaizen na competência. Preencha a etapa «Boas práticas - Kaizen» com título, responsáveis, ganhos e fotos antes/depois, e registre treinamentos e campanhas para fortalecer o indicador.';
        }

        $trecho = $tituloKaizen !== '' && $tituloKaizen !== '—'
            ? ' O destaque do período inclui o projeto «'.$tituloKaizen.'», evidenciando melhoria aplicada no campo.'
            : '';

        return 'O período soma '.$boasPraticas.' registro(s) de boas práticas (treinos, campanhas e Kaizen), '.$kaizens.' implementação(ões) associada(s) a Kaizen e planos de melhoria, e '.$melhoriasConcl.' melhoria(s) concluída(s) ou validada(s).'
            .$trecho
            .' Mantenha o registro fotográfico e os ganhos de processo para sustentar auditorias e reconhecimento da equipe.';
    }

    /**
     * @return list<string>
     */
    private function pontosAtencaoBoasPraticasKaizen(int $nKaizenRegs, int $melhoriasConclPlano, int $planosMelhoriaTotal): array
    {
        $pontos = [];
        if ($nKaizenRegs === 0) {
            $pontos[] = 'Registrar ao menos um ciclo Kaizen com antes/depois e ganhos de processo na competência.';
        }
        if ($planosMelhoriaTotal > $melhoriasConclPlano) {
            $pontos[] = 'Concluir ou validar planos de melhoria ainda em aberto no período.';
        }
        $pontos[] = 'Amarrar melhorias a indicadores de segurança e produtividade mensuráveis.';
        $pontos[] = 'Divulgar boas práticas vencedoras para replicação em outras frentes.';

        return array_values(array_unique($pontos));
    }

    /**
     * Card «Plano de ação de SESMT»: planos criados no recorte do painel (created_at).
     *
     * @return array<string, mixed>
     */
    private function montarCardPlanoAcaoSesmt(Carbon $periodoInicio, Carbon $periodoFim): array
    {
        $lista = SsmaPlanoAcao::query()
            ->whereBetween('created_at', [
                $periodoInicio->copy()->startOfDay(),
                $periodoFim->copy()->endOfDay(),
            ])
            ->orderByRaw('CASE WHEN prazo IS NULL THEN 1 ELSE 0 END')
            ->orderBy('prazo')
            ->get();

        $ativa = $lista->where('status', '!=', 'cancelada');
        $total = $ativa->count();
        $concluidas = $ativa->whereIn('status', ['concluida', 'validada'])->count();
        $emAndamento = $ativa->filter(fn (SsmaPlanoAcao $p) => in_array($p->status, ['em_andamento', 'aguardando_evidencia'], true)
            && ! $p->estaAtrasada())->count();
        $pendentes = $ativa->filter(fn (SsmaPlanoAcao $p) => $p->status === 'aberta' && ! $p->estaAtrasada())->count();
        $atrasadas = $ativa->filter(fn (SsmaPlanoAcao $p) => $p->estaAtrasada())->count();
        $criticas = $ativa->filter(fn (SsmaPlanoAcao $p) => in_array($p->prioridade, ['alta', 'critica'], true)
            || in_array($p->nivel_risco, ['alto', 'critico'], true))->count();
        $criticasAtrasadas = $ativa->filter(fn (SsmaPlanoAcao $p) => $p->estaAtrasada()
            && (in_array($p->prioridade, ['alta', 'critica'], true) || in_array($p->nivel_risco, ['alto', 'critico'], true)))->count();
        $preventivas = $ativa->where('tipo', 'preventiva')->count();
        $corretivas = $ativa->where('tipo', 'corretiva')->count();

        $denom = max(1, $total);
        $pct = $total > 0 ? (int) round(100 * $concluidas / $denom) : 0;
        $rSvg = 22.0;
        $circ = 2 * M_PI * $rSvg;
        $circDash = $circ * ($pct / 100.0);

        $linhasTabela = $ativa->values()->sortBy(fn (SsmaPlanoAcao $p) => $p->prazo?->timestamp ?? PHP_INT_MAX)->take(15)
            ->map(fn (SsmaPlanoAcao $p) => $this->mapearLinhaTabelaPlanoAcaoSesmt($p))
            ->all();

        return [
            'numeroDestaque' => (string) max($total, 0),
            'metricas' => [
                ['icon' => 'clipboard-list', 'label' => 'TOTAL DE AÇÕES', 'value' => (string) $total, 'highlight' => false, 'sub' => null],
                ['icon' => 'circle-check', 'label' => 'AÇÕES CONCLUÍDAS', 'value' => (string) $concluidas, 'highlight' => false, 'sub' => null],
                [
                    'icon' => 'pie-chart',
                    'label' => 'PERCENTUAL DE CONCLUSÃO',
                    'value' => $total > 0 ? $pct.'%' : '—',
                    'highlight' => true,
                    'sub' => $total > 0 ? $concluidas.' de '.$total : null,
                    'pct' => $pct,
                    'circDash' => $circDash,
                    'circLen' => $circ,
                ],
                ['icon' => 'refresh-cw', 'label' => 'AÇÕES EM ANDAMENTO', 'value' => (string) $emAndamento, 'highlight' => false, 'sub' => null],
                ['icon' => 'clock', 'label' => 'AÇÕES PENDENTES', 'value' => (string) $pendentes, 'highlight' => false, 'sub' => null],
                ['icon' => 'triangle-alert', 'label' => 'AÇÕES ATRASADAS', 'value' => (string) $atrasadas, 'highlight' => false, 'sub' => null],
                ['icon' => 'bell', 'label' => 'AÇÕES CRÍTICAS', 'value' => (string) $criticas, 'highlight' => false, 'sub' => null],
                ['icon' => 'shield-check', 'label' => 'AÇÕES PREVENTIVAS', 'value' => (string) $preventivas, 'highlight' => false, 'sub' => null],
                ['icon' => 'wrench', 'label' => 'AÇÕES CORRETIVAS', 'value' => (string) $corretivas, 'highlight' => false, 'sub' => null],
            ],
            'linhasTabela' => $linhasTabela,
            'leituraPlanoAcao' => $this->leituraExecutivaPlanoAcaoSesmt($total, $concluidas, $pct, $emAndamento, $pendentes, $atrasadas, $criticasAtrasadas),
            'pontosPlanoAcao' => $this->pontosAtencaoPlanoAcaoSesmt($criticasAtrasadas, $pendentes, $atrasadas, $total),
        ];
    }

    /**
     * @return array{
     *     acao: string,
     *     origem: string,
     *     origemIcon: string,
     *     responsavel: string,
     *     prazo: string,
     *     status: string,
     *     statusVariant: string,
     *     categoria: string,
     *     prioridade: string,
     *     prioridadeIcon: string,
     *     prioridadeTone: string,
     *     progresso: int
     * }
     */
    private function mapearLinhaTabelaPlanoAcaoSesmt(SsmaPlanoAcao $p): array
    {
        $acao = trim((string) ($p->acao_necessaria ?: $p->descricao_desvio));
        if ($acao === '') {
            $acao = '—';
        }
        $acao = Str::limit($acao, 96, '…');

        if ($p->estaAtrasada() && ! in_array($p->status, ['concluida', 'validada'], true)) {
            $status = 'Atrasada';
            $variant = 'danger';
        } elseif (in_array($p->status, ['concluida', 'validada'], true)) {
            $status = 'Concluído';
            $variant = 'success';
        } elseif (in_array($p->status, ['em_andamento', 'aguardando_evidencia'], true)) {
            $status = 'Em andamento';
            $variant = 'warn';
        } elseif ($p->status === 'aberta') {
            $status = 'Pendente';
            $variant = 'danger';
        } elseif ($p->status === 'vencida') {
            $status = 'Pendente';
            $variant = 'danger';
        } else {
            $status = $p->rotuloStatus();
            $variant = 'muted';
        }

        [$pIcon, $pTone] = match ($p->prioridade ?? 'baixa') {
            'alta', 'critica' => ['trending-up', 'red'],
            'media' => ['minus', 'orange'],
            default => ['trending-down', 'emerald'],
        };

        $progresso = match ($p->status) {
            'concluida', 'validada' => 100,
            'aguardando_evidencia' => 75,
            'em_andamento' => 50,
            'vencida' => 25,
            'aberta' => 0,
            default => 10,
        };

        $resp = trim((string) $p->responsavel);

        return [
            'acao' => $acao,
            'origem' => $p->rotuloOrigem(),
            'origemIcon' => $this->iconeLucideOrigemPlanoAcaoSesmt($p),
            'responsavel' => $resp !== '' ? $resp : '—',
            'prazo' => $p->prazo ? $p->prazo->format('d/m/Y') : '—',
            'status' => $status,
            'statusVariant' => $variant,
            'categoria' => $p->rotuloTipo(),
            'prioridade' => $p->rotuloPrioridade(),
            'prioridadeIcon' => $pIcon,
            'prioridadeTone' => $pTone,
            'progresso' => $progresso,
        ];
    }

    private function iconeLucideOrigemPlanoAcaoSesmt(SsmaPlanoAcao $p): string
    {
        $det = mb_strtolower((string) ($p->origem_detalhe ?? ''));
        if (str_contains($det, 'trein') || str_contains($det, 'capacita')) {
            return 'graduation-cap';
        }

        return match ($p->origem) {
            'desvio' => 'triangle-alert',
            'inspecao' => 'clipboard-list',
            'auditoria' => 'search',
            'acidente' => 'cross',
            'quase_acidente' => 'zap',
            'campanha' => 'megaphone',
            default => 'circle-dot',
        };
    }

    private function leituraExecutivaPlanoAcaoSesmt(
        int $total,
        int $concluidas,
        int $pct,
        int $emAndamento,
        int $pendentes,
        int $atrasadas,
        int $criticasAtrasadas
    ): string {
        if ($total === 0) {
            return 'Não há planos de ação registrados no período do painel. Inclua ações com origem, responsável, prazo e tipo (preventiva ou corretiva) para consolidar o acompanhamento de SSMA.';
        }

        $trechoCrit = $criticasAtrasadas > 0
            ? ', sendo '.$criticasAtrasadas.' ação(ões) crítica(s) atrasada(s)'
            : ($atrasadas > 0 ? ', com '.$atrasadas.' ação(ões) fora do prazo' : '');

        return 'No período, das '.$total.' ações previstas, '.$concluidas.' foram concluídas ('.$pct.'%). '
            .$emAndamento.' ações estão em andamento e '.$pendentes.' pendente(s)'.$trechoCrit.'. '
            .'As ações estão sendo acompanhadas conforme responsabilidade e prazo definidos, garantindo tratativas efetivas para melhorias contínuas em SSMA.';
    }

    /**
     * @return list<string>
     */
    private function pontosAtencaoPlanoAcaoSesmt(int $criticasAtrasadas, int $pendentes, int $atrasadas, int $total): array
    {
        if ($total === 0) {
            return ['Cadastrar planos de ação vinculados às inspeções, desvios e campanhas do período.'];
        }

        $pontos = [];
        if ($criticasAtrasadas > 0) {
            $pontos[] = 'Acompanhar as '.$criticasAtrasadas.' ação(ões) crítica(s) atrasada(s) com prioridade.';
        } elseif ($atrasadas > 0) {
            $pontos[] = 'Priorizar a conclusão das '.$atrasadas.' ação(ões) atrasada(s) ou registrar justificativa formal.';
        }
        if ($pendentes > 0) {
            $pontos[] = 'Concluir ações pendentes dentro do prazo estabelecido.';
        }
        $pontos[] = 'Manter atualização do status das ações semanalmente.';
        $pontos[] = 'Garantir evidências das ações concluídas.';

        return array_values(array_unique($pontos));
    }

    private function classificarCanalDesvioPlano(SsmaPlanoAcao $plano): string
    {
        $s = mb_strtolower(trim(
            (string) ($plano->origem_detalhe ?? '')
                .' '.(string) ($plano->descricao_desvio ?? '')
                .' '.(string) ($plano->acao_necessaria ?? '')
        ));
        if ($s === '') {
            return 'interno';
        }
        if (str_contains($s, 'vale') || str_contains($s, 'termo') && str_contains($s, 'vale')) {
            return 'vale';
        }
        if (str_contains($s, 'inspe') || str_contains($s, 'auditor')) {
            return 'inspecao';
        }
        if (str_contains($s, 'campo') || str_contains($s, 'canteiro') || str_contains($s, 'obra')) {
            return 'campo';
        }

        return 'interno';
    }

    /**
     * @return list<array{origem: string, tipo: string, descricao: string, status: string, statusVariant: string, prazo: string}>
     */
    private function coletarLinhasNotificacoesInterdicoesParaTabela(Collection $registros): array
    {
        $map = [
            'termo_notificacao_vale' => ['origem' => 'Vale', 'tipo' => 'Notificação'],
            'notificacao_interna_omega' => ['origem' => 'Interno', 'tipo' => 'Notificação'],
            'termo_interdicao_vale' => ['origem' => 'Vale', 'tipo' => 'Interdição'],
            'interdicao_interna_omega' => ['origem' => 'Interno', 'tipo' => 'Interdição'],
        ];
        $out = [];
        foreach ($registros as $r) {
            $e = $r->etapas;
            if (! is_array($e)) {
                continue;
            }
            $blocos = data_get($e, 'registro_acoes_proativas.blocos', []);
            if (! is_array($blocos)) {
                continue;
            }
            foreach ($map as $blocoKey => $meta) {
                $linhas = data_get($blocos, $blocoKey.'.linhas', []);
                if (! is_array($linhas)) {
                    continue;
                }
                foreach ($linhas as $linha) {
                    if (! is_array($linha)) {
                        continue;
                    }
                    $d = trim((string) ($linha['descricao'] ?? ''));
                    $loc = trim((string) ($linha['local'] ?? ''));
                    if ($d === '' && $loc === '') {
                        continue;
                    }
                    $txt = $d !== '' ? $d : $loc;
                    $out[] = [
                        'origem' => $meta['origem'],
                        'tipo' => $meta['tipo'],
                        'descricao' => Str::limit($txt, 96),
                        'status' => 'Em tratativa',
                        'statusVariant' => 'warn',
                        'prazo' => '—',
                    ];
                }
            }
        }

        return $out;
    }

    private function textoLeituraExecutivaDesviosTratativas(
        int $identificados,
        int $concluidos,
        int $emTratativa,
        int $resolucaoPct,
        int $notifs,
        int $interd
    ): string {
        if ($identificados === 0 && $notifs === 0 && $interd === 0) {
            return 'Não há planos de ação com origem em desvio no período nem registros proativos de notificação/interdição na competência. Ao identificar desvios, abra planos de ação e utilize as etapas Vale/Omega para evidenciar notificações e interdições.';
        }

        return 'O período registrou '.$identificados.' desvio(s) monitorado(s) em planos de ação, com '.$concluidos.' concluído(s) e '.$emTratativa.' em tratativa. '
            .'O percentual de resolução no recorte é de '.$resolucaoPct.'%. '
            .'Foram contabilizadas '.$notifs.' notificação(ões) (internas e Vale) e '.$interd.' interdição(ões) interna(s) nos registros mensais proativos. '
            .'Mantenha o acompanhamento até encerramento ou validação de cada tratativa.';
    }

    /**
     * @return list<string>
     */
    private function pontosAtencaoDesviosTratativas(int $emTratativa, int $identificados, int $resolucaoPct): array
    {
        $pontos = [];
        if ($emTratativa > 0) {
            $pontos[] = 'Concluir '.$emTratativa.' tratativa(s) ainda em andamento.';
        }
        if ($identificados > 0 && $resolucaoPct < 80) {
            $pontos[] = 'Elevar o percentual de resolução de desvios acima de 80% com ações objetivas e prazos realistas.';
        }
        $pontos[] = 'Garantir registro e evidências das notificações e interdições na competência.';
        $pontos[] = 'Priorizar desvios com maior criticidade e risco operacional.';

        return array_values(array_unique($pontos));
    }

    /**
     * @return list<array{rac: bool, nr: bool, pro_outros: string, data: mixed, instrutor: string, titulo_descricao: string}>
     */
    private function coletarLinhasTreinamentosMensais(Collection $registros): array
    {
        $out = [];
        foreach ($registros as $r) {
            $e = $r->etapas;
            if (! is_array($e)) {
                continue;
            }
            $linhas = data_get($e, 'treinamentos_mensais.linhas', []);
            if (! is_array($linhas)) {
                continue;
            }
            foreach ($linhas as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $rac = (bool) ($row['rac'] ?? false);
                $nr = (bool) ($row['nr'] ?? false);
                $pro = trim((string) ($row['pro_outros'] ?? ''));
                $data = $row['data'] ?? null;
                $instrutor = trim((string) ($row['instrutor'] ?? ''));
                $titulo = trim((string) ($row['titulo_descricao'] ?? ''));
                if (! $rac && ! $nr && $pro === '' && $data === null && $instrutor === '' && $titulo === '') {
                    continue;
                }
                $out[] = [
                    'rac' => $rac,
                    'nr' => $nr,
                    'pro_outros' => $pro,
                    'data' => $data,
                    'instrutor' => $instrutor,
                    'titulo_descricao' => $titulo,
                ];
            }
        }

        return $out;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function coletarCampanhasMensais(Collection $registros): array
    {
        $out = [];
        foreach ($registros as $r) {
            $e = $r->etapas;
            if (! is_array($e)) {
                continue;
            }
            $camp = $e['campanha_seguranca'] ?? [];
            if (! is_array($camp)) {
                continue;
            }
            $itens = $camp['campanhas'] ?? $camp['itens'] ?? [];
            if (! is_array($itens)) {
                continue;
            }
            foreach ($itens as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $titulo = trim((string) ($row['titulo'] ?? ''));
                $desc = trim((string) ($row['descricao'] ?? ''));
                $local = trim((string) ($row['local'] ?? ''));
                if ($titulo === '' && $desc === '' && $local === '' && empty($row['data_reuniao'])) {
                    continue;
                }
                $out[] = $row;
            }
        }

        return $out;
    }

    /**
     * @param  array{rac: bool, nr: bool, pro_outros: string, titulo_descricao: string, ...}  $linha
     */
    private function treinamentoLinhaEhIntegracao(array $linha): bool
    {
        $blob = mb_strtolower(
            ($linha['titulo_descricao'] ?? '').' '.($linha['pro_outros'] ?? '')
        );

        if (trim($blob) === '') {
            return false;
        }

        return preg_match('/integra(cao|ção)?|onboarding|integração/i', $blob) === 1;
    }

    private function formatarDataTreinoTabela(mixed $data): string
    {
        if ($data === null || $data === '') {
            return '—';
        }
        try {
            return Carbon::parse((string) $data)->format('d/m');
        } catch (\Throwable) {
            return '—';
        }
    }

    /**
     * @param  array{rac: bool, nr: bool, pro_outros: string, titulo_descricao: string}  $linha
     */
    private function rotuloCategoriaTreinamento(array $linha): string
    {
        $tit = (string) ($linha['titulo_descricao'] ?? '');
        if (! empty($linha['rac'])) {
            if (preg_match('/RAC\s*(\d+)/i', $tit, $m) === 1) {
                return 'RAC '.$m[1];
            }
            if (preg_match('/\b(\d{2})\b/', $tit, $m) === 1) {
                return 'RAC '.$m[1];
            }

            return 'RAC';
        }
        if (! empty($linha['nr'])) {
            if (preg_match('/NR\s*([\d.\s]+)/i', $tit, $m) === 1) {
                return 'NR '.trim($m[1]);
            }

            return 'NR';
        }
        $pro = $linha['pro_outros'] ?? '';
        if ($pro !== '') {
            return str_contains(mb_strtolower($pro), 'vale') ? 'PRO Vale' : $pro;
        }

        return '—';
    }

    private function textoLeituraExecutivaTreinamentosCampanhas(
        int $nTreinos,
        int $horasTreino,
        float $aderenciaPct,
        string $tituloCampanha,
        int $nCamp
    ): string {
        $ad = number_format($aderenciaPct, 1, ',', '.');
        if ($nTreinos === 0 && $nCamp === 0) {
            return 'Não há linhas de treinamento nem campanhas registradas no registro mensal da competência. Utilize a etapa «Treinamentos mensais» e «Campanha de segurança» para evidenciar capacitação e conscientização da equipe.';
        }

        $trechoCamp = $nCamp > 0 && $tituloCampanha !== ''
            ? ' A campanha em destaque no período: «'.$tituloCampanha.'». '
            : ($nCamp > 0 ? ' Há '.$nCamp.' campanha(s) de segurança registrada(s). ' : ' ');

        return 'Foram registrados '.$nTreinos.' treinamento(s) no mês, totalizando cerca de '.$horasTreino.' hora(s) estimada(s) de capacitação (média de referência por linha). '
            .'A aderência ao pacote mínimo de referência (7 linhas de cadastro mensal) aparece como '.$ad.'%.'
            .$trechoCamp
            .'Recomenda-se manter evidências e listas de presença alinhadas ao programa de integração e reciclagens obrigatórias.';
    }

    /**
     * @return list<string>
     */
    private function pontosAtencaoTreinamentosCampanhas(int $nTreinos, float $aderenciaPct, int $nCamp): array
    {
        $pontos = [
            'Manter rotina de reciclagens obrigatórias.',
            'Acompanhar validade dos treinamentos críticos.',
            'Sustentar participação integral nas ações de campanha.',
        ];
        if ($nTreinos > 0 && $aderenciaPct < 100.0) {
            $pontos[] = 'Completar linhas de treinamento até atingir o referencial mensal de cadastro, conforme planejamento de SESMT.';
        }
        if ($nCamp === 0) {
            $pontos[] = 'Registrar campanhas de conscientização quando houver ações de segurança participativas no mês.';
        }

        return array_values(array_unique($pontos));
    }

    /**
     * @param  list<string>  $identificadoresContrato
     */
    private function registrosMensaisDoContratoNaCompetencia(array $identificadoresContrato, Carbon $compCarbon): Collection
    {
        $tokens = collect($identificadoresContrato)
            ->map(fn ($v) => trim((string) $v))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($tokens === []) {
            return collect();
        }

        return SsmaRegistroMensal::query()
            ->whereYear('competencia', $compCarbon->year)
            ->whereMonth('competencia', $compCarbon->month)
            ->where(function (Builder $w) use ($tokens) {
                // Há no máximo um registro por competência no fluxo atual; linhas provisionadas
                // ou antigas podem vir sem «contrato». Sem este ramo, o painel ignora Kaizen/treinos
                // ao filtrar só por tokens do contrato selecionado.
                $w->where(function (Builder $semContrato) {
                    $semContrato->whereNull('contrato')
                        ->orWhereRaw("TRIM(COALESCE(contrato, '')) = ''");
                });
                foreach ($tokens as $t) {
                    $w->orWhere(function (Builder $x) use ($t) {
                        $x->whereRaw('TRIM(COALESCE(contrato, \'\')) = ?', [$t])
                            ->orWhere('contrato', 'like', '%'.$t.'%');
                    });
                }
            })
            ->orderByDesc('id')
            ->get();
    }

    /**
     * @param  Collection<int, SsmaRegistroMensal>  $registros
     * @return array{
     *     treinamentos: int,
     *     primeiros_socorros: int,
     *     quase_acidente_pro: int,
     *     inspecoes_canteiro: int,
     *     auditorias: int,
     *     campanhas: int,
     *     kaizen: int,
     *     restricao_trabalho: int,
     *     tratamento_medico: int,
     *     regra_ouro: int,
     *     telemetria: int,
     *     pro_termo_interdicao_vale: int,
     *     pro_termo_notificacao_vale: int,
     *     pro_interdicao_omega: int,
     *     pro_notificacao_omega: int,
     *     registro_acidente_linhas: list<array<string, mixed>>
     * }
     */
    private function agregarEtapasRegistrosMensais(Collection $registros): array
    {
        $out = [
            'treinamentos' => 0,
            'primeiros_socorros' => 0,
            'quase_acidente_pro' => 0,
            'inspecoes_canteiro' => 0,
            'auditorias' => 0,
            'campanhas' => 0,
            'kaizen' => 0,
            'restricao_trabalho' => 0,
            'tratamento_medico' => 0,
            'regra_ouro' => 0,
            'telemetria' => 0,
            'pro_termo_interdicao_vale' => 0,
            'pro_termo_notificacao_vale' => 0,
            'pro_interdicao_omega' => 0,
            'pro_notificacao_omega' => 0,
            'registro_acidente_linhas' => [],
        ];

        foreach ($registros as $r) {
            $e = $r->etapas;
            if (! is_array($e)) {
                continue;
            }

            $out['treinamentos'] += $this->contarLinhasEmPaths($e, ['treinamentos_mensais.linhas']);
            $out['primeiros_socorros'] += $this->contarLinhasEmPaths($e, [
                'acoes_reativas.blocos.primeiros_socorros.linhas',
                'acoes_reativas.primeiros_socorros.linhas',
            ]);
            $out['quase_acidente_pro'] += $this->contarLinhasEmPaths($e, [
                'registro_acoes_proativas.blocos.quase_acidente.linhas',
                'registro_acoes_proativas.quase_acidente.linhas',
            ]);
            $out['pro_termo_interdicao_vale'] += $this->contarLinhasEmPaths($e, [
                'registro_acoes_proativas.blocos.termo_interdicao_vale.linhas',
            ]);
            $out['pro_termo_notificacao_vale'] += $this->contarLinhasEmPaths($e, [
                'registro_acoes_proativas.blocos.termo_notificacao_vale.linhas',
            ]);
            $out['pro_interdicao_omega'] += $this->contarLinhasEmPaths($e, [
                'registro_acoes_proativas.blocos.interdicao_interna_omega.linhas',
            ]);
            $out['pro_notificacao_omega'] += $this->contarLinhasEmPaths($e, [
                'registro_acoes_proativas.blocos.notificacao_interna_omega.linhas',
            ]);
            $out['restricao_trabalho'] += $this->contarLinhasEmPaths($e, [
                'acoes_reativas.blocos.restricao_trabalho.linhas',
                'acoes_reativas.restricao_trabalho.linhas',
            ]);
            $out['tratamento_medico'] += $this->contarLinhasEmPaths($e, [
                'acoes_reativas.blocos.tratamento_medico.linhas',
                'acoes_reativas.tratamento_medico.linhas',
            ]);
            $out['regra_ouro'] += $this->contarLinhasEmPaths($e, [
                'acoes_reativas.blocos.regra_ouro.linhas',
                'acoes_reativas.regra_ouro.linhas',
            ]);
            $out['telemetria'] += $this->contarLinhasEmPaths($e, [
                'acoes_reativas.blocos.telemetria.linhas',
                'acoes_reativas.telemetria.linhas',
            ]);

            $insp = $e['inspecao_mensal_canteiro'] ?? [];
            if (is_array($insp) && (($insp['passou_inspecao'] ?? null) !== null || ! empty($insp['data_inspecao']) || ! empty($insp['descricao']))) {
                $out['inspecoes_canteiro']++;
            }

            $aud = $e['auditoria_mensal'] ?? [];
            if (is_array($aud) && (($aud['passou_auditoria'] ?? null) !== null || ! empty($aud['data_auditoria']) || ! empty($aud['descricao']))) {
                $out['auditorias']++;
            }

            $camp = $e['campanha_seguranca'] ?? [];
            if (is_array($camp)) {
                $itens = $camp['campanhas'] ?? $camp['itens'] ?? [];
                $out['campanhas'] += is_array($itens) ? count($itens) : 0;
            }

            $kz = $e['boas_praticas_kaizen'] ?? [];
            if (is_array($kz) && ! empty($kz['realizado'])) {
                $out['kaizen']++;
            }

            $acLin = data_get($e, 'registro_acidente.linhas', []);
            if (is_array($acLin)) {
                foreach ($acLin as $row) {
                    if (is_array($row)) {
                        $out['registro_acidente_linhas'][] = $row;
                    }
                }
            }
        }

        return $out;
    }

    /**
     * @param  list<string>  $paths
     */
    private function contarLinhasEmPaths(array $etapas, array $paths): int
    {
        $total = 0;
        foreach ($paths as $p) {
            $v = data_get($etapas, $p);
            if (is_array($v)) {
                $total += count($v);
            }
        }

        return $total;
    }

    /**
     * @param  list<array<string, mixed>>  $linhas
     */
    private function contarLinhasComIndicioAfastamento(array $linhas): int
    {
        $n = 0;
        foreach ($linhas as $linha) {
            $d = mb_strtolower((string) ($linha['descricao'] ?? ''));
            if ($d !== '' && preg_match('/afast|afastamento|\bcat\b|auxílio|auxilio|atestado|dias?\s+de\s+trabalho/i', $d) === 1) {
                $n++;
            }
        }

        return $n;
    }

    private function authorizeView(): void
    {
        abort_unless(
            auth()->user()?->podeSecaoSesmt('indicadores_mensais'),
            403,
            'Seu perfil não tem acesso a esta área do SSMA.'
        );
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
}
