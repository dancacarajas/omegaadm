<?php

namespace App\Http\Controllers;

use App\Models\Beneficio;
use App\Models\Colaborador;
use App\Models\FrequenciaRegistro;
use App\Models\Patrimonio;
use App\Models\RdoRelatorio;
use App\Models\RecrutamentoVaga;
use App\Models\SesmtTarefa;
use App\Models\VeiculoSolicitacao;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $colaboradores = Colaborador::query()->get();
        $vagas = RecrutamentoVaga::query()->get();
        $veiculos = VeiculoSolicitacao::query()->get();
        $frequencias = FrequenciaRegistro::query()->with('colaborador')->get();
        $patrimonios = Patrimonio::query()->get();
        $rdos = RdoRelatorio::query()->get();
        $sesmt = SesmtTarefa::query()->get();
        $beneficios = Beneficio::query()->with('colaboradores')->get();

        $rh = $this->rhResumo($colaboradores, $beneficios);
        $sesmtResumo = $this->sesmtResumo($sesmt);
        $recrutamento = $this->recrutamentoResumo($vagas);
        $veiculosResumo = $this->veiculosResumo($veiculos);
        $frequencia = $this->frequenciaResumo($frequencias, $colaboradores);
        $patrimonial = $this->patrimonialResumo($patrimonios);
        $rdo = $this->rdoResumo($rdos);

        $modulos = [
            'RH' => $rh,
            'Recrutamento' => $recrutamento,
            'SESMT' => $sesmtResumo,
            'Veículos' => $veiculosResumo,
            'Frequência' => $frequencia,
            'Patrimonial' => $patrimonial,
            'RDO' => $rdo,
        ];

        $totais = [
            'concluidos' => collect($modulos)->sum('concluidos'),
            'andamento' => collect($modulos)->sum('andamento'),
            'atrasados' => collect($modulos)->sum('atrasados'),
            'pendencias' => collect($modulos)->sum('pendencias'),
        ];

        $kpis = [
            [
                'label' => 'Processos concluídos',
                'value' => $totais['concluidos'],
                'hint' => 'Entregas finalizadas nos módulos operacionais.',
                'icon' => 'badge-check',
                'tone' => 'emerald',
            ],
            [
                'label' => 'Em andamento',
                'value' => $totais['andamento'],
                'hint' => 'Itens ativos que ainda exigem acompanhamento.',
                'icon' => 'activity',
                'tone' => 'amber',
            ],
            [
                'label' => 'Atrasos críticos',
                'value' => $totais['atrasados'],
                'hint' => 'Onde a diretoria deve cobrar ação imediata.',
                'icon' => 'triangle-alert',
                'tone' => 'red',
            ],
            [
                'label' => 'Valor patrimonial',
                'value' => 'R$ '.number_format($patrimonial['valor_total'], 2, ',', '.'),
                'hint' => 'Ativos inventariados não baixados.',
                'icon' => 'landmark',
                'tone' => 'slate',
            ],
        ];

        $charts = $this->charts($modulos, $totais, $frequencias, $rdos);

        return view('welcome', [
            'kpis' => $kpis,
            'modulos' => $modulos,
            'charts' => $charts,
            'gargalos' => $this->gargalos($modulos),
            'topFaltas' => $frequencia['top_faltas'],
            'atualizadoEm' => now()->format('d/m/Y H:i'),
        ]);
    }

    private function rhResumo(Collection $colaboradores, Collection $beneficios): array
    {
        $mobilizados = $colaboradores->where('mobilizacao_status', 'mobilizacao_concluida')->count();
        $ativos = $colaboradores->where('status', 'ativo')->count();
        $crachaAtrasado = $colaboradores->filter(fn ($item) => $this->crachaAtrasado($item))->count();
        $beneficiosPendentes = $beneficios
            ->flatMap(fn ($beneficio) => $beneficio->colaboradores)
            ->filter(fn ($vinculo) => $vinculo->tem_direito && (! $vinculo->cartao_entregue || ! $vinculo->beneficio_ativo))
            ->count();

        return [
            'label' => 'RH',
            'concluidos' => $mobilizados,
            'andamento' => max(0, $ativos - $mobilizados) + $beneficiosPendentes,
            'atrasados' => $crachaAtrasado,
            'pendencias' => $beneficiosPendentes,
            'principal' => "{$mobilizados}/{$ativos} mobilizados",
            'rota' => route('rh.dashboard'),
        ];
    }

    private function sesmtResumo(Collection $sesmt): array
    {
        $concluidos = $sesmt->where('status', 'concluido')->count();
        $pendentes = $sesmt->whereIn('status', ['pendente', 'em_andamento'])->count();
        $atrasados = $sesmt
            ->filter(fn ($tarefa) => $tarefa->data_prevista && $tarefa->status !== 'concluido' && $tarefa->data_prevista->isPast())
            ->count();

        return [
            'label' => 'SESMT',
            'concluidos' => $concluidos,
            'andamento' => $pendentes,
            'atrasados' => $atrasados,
            'pendencias' => $pendentes,
            'principal' => "{$concluidos}/{$sesmt->count()} tarefas concluídas",
            'rota' => route('sesmt.index'),
        ];
    }

    private function recrutamentoResumo(Collection $vagas): array
    {
        $positions = 0;
        $approved = 0;
        $released = 0;
        $late = 0;
        $pending = 0;

        foreach ($vagas as $vaga) {
            $state = $vaga->form_state ?? [];
            $quantity = max(1, (int) ($state['vaga_quantidade'] ?? $vaga->quantidade ?? 1));
            $positions += $quantity;

            foreach (range(1, $quantity) as $position) {
                $isApproved = ($state["candidato_{$position}_status"] ?? null) === 'aprovado'
                    && filled($state["candidato_{$position}_nome_completo"] ?? null);

                if (! $isApproved) {
                    $pending++;
                    if ($this->daysSince($state['vaga_data_solicitacao'] ?? $vaga->data_solicitacao) > 7) {
                        $late++;
                    }
                    continue;
                }

                $approved++;

                $isReleased = filled($state["candidato_{$position}_liberacao_orientado_data"] ?? null)
                    && filled($state["candidato_{$position}_liberacao_epi_data"] ?? null)
                    && filled($state["candidato_{$position}_liberacao_rota_endereco"] ?? null);

                if ($isReleased) {
                    $released++;
                } else {
                    $pending++;
                }
            }
        }

        return [
            'label' => 'Recrutamento',
            'concluidos' => $released,
            'andamento' => $pending,
            'atrasados' => $late,
            'pendencias' => $positions - $released,
            'principal' => "{$approved}/{$positions} candidatos aprovados",
            'rota' => route('rh.recrutamento.index'),
        ];
    }

    private function veiculosResumo(Collection $veiculos): array
    {
        $concluidos = $veiculos->where('status', 'concluido')->count();
        $atrasados = $veiculos->filter(function ($solicitacao) {
            if ($solicitacao->status === 'concluido') {
                return false;
            }

            if ($solicitacao->svg_data_postagem && ! $solicitacao->vistoria_data_agendada) {
                return $solicitacao->svg_data_postagem->copy()->addDays(10)->isPast();
            }

            return $solicitacao->data_inicio_atividade && $solicitacao->data_inicio_atividade->isPast();
        })->count();

        return [
            'label' => 'Veículos',
            'concluidos' => $concluidos,
            'andamento' => $veiculos->where('status', 'em_andamento')->count(),
            'atrasados' => $atrasados,
            'pendencias' => max(0, $veiculos->count() - $concluidos),
            'principal' => $veiculos->count().' mobilizações',
            'rota' => route('veiculos.index'),
        ];
    }

    private function frequenciaResumo(Collection $frequencias, Collection $colaboradores): array
    {
        $inicioMes = today()->startOfMonth();
        $fimMes = today()->endOfMonth();
        $faltasMes = $frequencias
            ->whereBetween('data', [$inicioMes, $fimMes])
            ->where('status', 'falta')
            ->count();
        $presentesHoje = $frequencias->where('data', today())->where('status', 'presente')->count();
        $ativos = $colaboradores->where('status', 'ativo')->count();
        $top = $frequencias
            ->whereBetween('data', [$inicioMes, $fimMes])
            ->where('status', 'falta')
            ->groupBy('colaborador_id')
            ->map(fn ($items) => [
                'nome' => optional($items->first()->colaborador)->nome ?? 'Sem colaborador',
                'faltas' => $items->count(),
            ])
            ->sortByDesc('faltas')
            ->take(5)
            ->values()
            ->all();

        return [
            'label' => 'Frequência',
            'concluidos' => $presentesHoje,
            'andamento' => max(0, $ativos - $presentesHoje),
            'atrasados' => $faltasMes,
            'pendencias' => $faltasMes,
            'principal' => "{$presentesHoje}/{$ativos} presentes hoje",
            'rota' => route('rh.frequencia.index'),
            'top_faltas' => $top,
        ];
    }

    private function patrimonialResumo(Collection $patrimonios): array
    {
        $ativos = $patrimonios->whereIn('status', ['ativo', 'em_uso'])->count();
        $pendentes = $patrimonios
            ->filter(fn ($item) => $item->status !== 'baixado' && $item->proxima_conferencia && $item->proxima_conferencia->isPast())
            ->count();

        return [
            'label' => 'Patrimonial',
            'concluidos' => $ativos,
            'andamento' => $patrimonios->whereIn('status', ['reserva', 'em_manutencao'])->count(),
            'atrasados' => $pendentes,
            'pendencias' => $pendentes,
            'principal' => $patrimonios->count().' itens no inventário',
            'valor_total' => (float) $patrimonios->where('status', '!=', 'baixado')->sum('valor'),
            'rota' => route('patrimonial.index'),
        ];
    }

    private function rdoResumo(Collection $rdos): array
    {
        $hoje = $rdos->where('data', today())->count();
        $mes = $rdos->filter(fn ($rdo) => $rdo->data && $rdo->data->isSameMonth(today()))->count();
        $semEvidencia = $rdos->filter(fn ($rdo) => blank($rdo->evidencia_path))->count();

        return [
            'label' => 'RDO',
            'concluidos' => $mes,
            'andamento' => $hoje,
            'atrasados' => $semEvidencia,
            'pendencias' => $semEvidencia,
            'principal' => "{$hoje} RDOs hoje",
            'rota' => route('rdo.index'),
        ];
    }

    private function charts(array $modulos, array $totais, Collection $frequencias, Collection $rdos): array
    {
        $labels = array_keys($modulos);
        $base = [
            'fontFamily' => 'Instrument Sans, sans-serif',
            'toolbar' => ['show' => false],
            'foreColor' => '#5f6673',
        ];

        $months = collect(range(5, 0))->map(fn ($i) => today()->subMonths($i))->values();
        $monthLabels = $months->map(fn (Carbon $date) => $date->format('m/Y'))->all();

        return [
            'modulos' => [
                'chart' => $base + ['type' => 'bar', 'height' => 360, 'stacked' => true],
                'series' => [
                    ['name' => 'Concluídos', 'data' => collect($modulos)->pluck('concluidos')->all()],
                    ['name' => 'Em andamento', 'data' => collect($modulos)->pluck('andamento')->all()],
                    ['name' => 'Atrasados', 'data' => collect($modulos)->pluck('atrasados')->all()],
                ],
                'colors' => ['#059669', '#f59e0b', '#dc2626'],
                'plotOptions' => ['bar' => ['borderRadius' => 5, 'columnWidth' => '42%']],
                'xaxis' => ['categories' => $labels],
                'legend' => ['position' => 'top'],
                'grid' => ['borderColor' => '#eceef2'],
            ],
            'saude' => [
                'chart' => $base + ['type' => 'donut', 'height' => 330],
                'series' => [$totais['concluidos'], $totais['andamento'], $totais['atrasados']],
                'labels' => ['Concluído', 'Em andamento', 'Atrasado'],
                'colors' => ['#059669', '#f59e0b', '#dc2626'],
                'legend' => ['position' => 'bottom'],
                'stroke' => ['width' => 0],
            ],
            'tendencia' => [
                'chart' => $base + ['type' => 'area', 'height' => 320],
                'series' => [
                    [
                        'name' => 'RDOs',
                        'data' => $months->map(fn ($date) => $rdos->filter(fn ($rdo) => $rdo->data && $rdo->data->isSameMonth($date))->count())->all(),
                    ],
                    [
                        'name' => 'Faltas',
                        'data' => $months->map(fn ($date) => $frequencias->filter(fn ($item) => $item->data && $item->data->isSameMonth($date) && $item->status === 'falta')->count())->all(),
                    ],
                ],
                'colors' => ['#6f1731', '#dc2626'],
                'xaxis' => ['categories' => $monthLabels],
                'stroke' => ['curve' => 'smooth', 'width' => 3],
                'fill' => ['type' => 'gradient', 'gradient' => ['opacityFrom' => 0.35, 'opacityTo' => 0.04]],
                'grid' => ['borderColor' => '#eceef2'],
            ],
            'gargalos' => [
                'chart' => $base + ['type' => 'bar', 'height' => 330],
                'series' => [['name' => 'Pendências', 'data' => collect($modulos)->pluck('pendencias')->all()]],
                'colors' => ['#6f1731'],
                'plotOptions' => ['bar' => ['horizontal' => true, 'borderRadius' => 5]],
                'xaxis' => ['categories' => $labels],
                'grid' => ['borderColor' => '#eceef2'],
            ],
        ];
    }

    private function gargalos(array $modulos): array
    {
        return collect($modulos)
            ->map(fn ($item) => $item + ['score' => ($item['atrasados'] * 10) + $item['pendencias']])
            ->sortByDesc('score')
            ->take(5)
            ->values()
            ->all();
    }

    private function crachaAtrasado(Colaborador $colaborador): bool
    {
        if ($colaborador->mobilizacao_status === 'mobilizacao_concluida') {
            return false;
        }

        if ($colaborador->sgc_data_aprovacao) {
            return $colaborador->sgc_data_aprovacao->diffInDays(today(), false) > 2;
        }

        if ($colaborador->sgc_data_postagem) {
            return $colaborador->sgc_data_postagem->diffInDays(today(), false) > 5;
        }

        return false;
    }

    private function daysSince(mixed $date): int
    {
        if (blank($date)) {
            return 0;
        }

        try {
            return max(0, Carbon::parse($date)->startOfDay()->diffInDays(today(), false));
        } catch (\Throwable) {
            return 0;
        }
    }
}
