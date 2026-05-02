<?php

namespace App\Http\Controllers\Rh;

use App\Http\Controllers\Controller;
use App\Models\Beneficio;
use App\Models\Colaborador;
use App\Models\RecrutamentoVaga;
use App\Models\SesmtTarefa;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $colaboradores = Colaborador::query()->get();
        $vagas = RecrutamentoVaga::query()->latest()->get();
        $beneficios = Beneficio::query()->with('colaboradores')->get();
        $sesmtTarefas = SesmtTarefa::query()->get();

        $recrutamento = $this->recrutamentoResumo($vagas);
        $mobilizados = $colaboradores->where('mobilizacao_status', 'mobilizacao_concluida')->count();
        $colaboradoresEmAndamento = $colaboradores->where('mobilizacao_status', '!=', 'mobilizacao_concluida')->count();
        $crachaAtrasado = $colaboradores
            ->filter(fn ($colaborador) => $this->crachaAtrasado($colaborador))
            ->count();
        $beneficiosPendentes = $beneficios
            ->flatMap(fn ($beneficio) => $beneficio->colaboradores)
            ->filter(fn ($vinculo) => $vinculo->tem_direito && (! $vinculo->cartao_entregue || ! $vinculo->beneficio_ativo))
            ->count();
        $sesmtPendentes = $sesmtTarefas->whereIn('status', ['pendente', 'em_andamento'])->count();
        $totalAtrasados = $recrutamento['atrasados'] + $crachaAtrasado + $this->sesmtAtrasados($sesmtTarefas);
        $emAndamento = $recrutamento['em_andamento'] + $colaboradoresEmAndamento + $beneficiosPendentes + $sesmtPendentes;

        $kpis = [
            [
                'label' => 'Colaboradores mobilizados',
                'value' => $mobilizados,
                'hint' => 'Crachá entregue e mobilização concluída.',
                'tone' => 'emerald',
                'icon' => 'badge-check',
            ],
            [
                'label' => 'Processos em andamento',
                'value' => $emAndamento,
                'hint' => 'Recrutamento, SGC, benefícios e SESMT ainda abertos.',
                'tone' => 'amber',
                'icon' => 'activity',
            ],
            [
                'label' => 'Atrasos críticos',
                'value' => $totalAtrasados,
                'hint' => 'Etapas fora do SLA operacional.',
                'tone' => 'red',
                'icon' => 'triangle-alert',
            ],
            [
                'label' => 'Vagas abertas',
                'value' => $recrutamento['vagas_abertas'],
                'hint' => 'Demandas de recrutamento em aberto.',
                'tone' => 'burgundy',
                'icon' => 'briefcase-business',
            ],
        ];

        $submodulos = [
            ['label' => 'Recrutamento', 'pendentes' => $recrutamento['em_andamento'], 'atrasados' => $recrutamento['atrasados']],
            ['label' => 'Efetivo / Crachá', 'pendentes' => $colaboradoresEmAndamento, 'atrasados' => $crachaAtrasado],
            ['label' => 'Benefícios', 'pendentes' => $beneficiosPendentes, 'atrasados' => $beneficiosPendentes],
            ['label' => 'SESMT', 'pendentes' => $sesmtPendentes, 'atrasados' => $this->sesmtAtrasados($sesmtTarefas)],
        ];

        return view('rh.dashboard', [
            'kpis' => $kpis,
            'funil' => $recrutamento['funil'],
            'gargalos' => $recrutamento['gargalos'],
            'submodulos' => $submodulos,
            'alertas' => $this->alertas($recrutamento['gargalos'], $submodulos),
            'mobilizados' => $mobilizados,
            'totalColaboradores' => $colaboradores->count(),
        ]);
    }

    private function recrutamentoResumo(Collection $vagas): array
    {
        $funil = [
            'Vagas solicitadas' => 0,
            'Candidatos aprovados' => 0,
            'Treinamentos concluídos' => 0,
            'Assinaturas concluídas' => 0,
            'SGC mobilizado' => 0,
            'Liberados' => 0,
        ];
        $gargalos = [
            'Recrutamento e seleção' => ['pendentes' => 0, 'atrasados' => 0, 'sla' => 'Aceite até 7 dias'],
            'Treinamentos' => ['pendentes' => 0, 'atrasados' => 0, 'sla' => 'Até 5 dias'],
            'Assinatura documental' => ['pendentes' => 0, 'atrasados' => 0, 'sla' => 'Até 2 dias após treinamento'],
            'Postagem SGC' => ['pendentes' => 0, 'atrasados' => 0, 'sla' => 'Até 3 dias após assinatura'],
            'Liberação para atividades' => ['pendentes' => 0, 'atrasados' => 0, 'sla' => 'Orientação, EPI e rota'],
        ];
        $today = today();

        foreach ($vagas as $vaga) {
            $state = $vaga->form_state ?? [];
            $quantity = max(1, (int) ($state['vaga_quantidade'] ?? $vaga->quantidade ?? 1));
            $funil['Vagas solicitadas'] += $quantity;
            $approvedPositions = 0;

            foreach (range(1, $quantity) as $position) {
                $approved = ($state["candidato_{$position}_status"] ?? null) === 'aprovado'
                    && filled($state["candidato_{$position}_nome_completo"] ?? null);

                if (! $approved) {
                    $gargalos['Recrutamento e seleção']['pendentes']++;
                    $requestedAt = $state['vaga_data_solicitacao'] ?? $vaga->data_solicitacao;
                    if ($this->daysSince($requestedAt, $today) > 7) {
                        $gargalos['Recrutamento e seleção']['atrasados']++;
                    }
                    continue;
                }

                $approvedPositions++;
                $funil['Candidatos aprovados']++;

                $trainingDone = $this->trainingDone($state, $position);
                if ($trainingDone) {
                    $funil['Treinamentos concluídos']++;
                } else {
                    $gargalos['Treinamentos']['pendentes']++;
                }
                if ($this->trainingLate($state, $position, $today)) {
                    $gargalos['Treinamentos']['atrasados']++;
                }

                $signatureDone = filled($state["candidato_{$position}_assinatura_data_confirmacao"] ?? null);
                if ($signatureDone) {
                    $funil['Assinaturas concluídas']++;
                } elseif ($trainingDone) {
                    $gargalos['Assinatura documental']['pendentes']++;
                    $trainingConfirmedAt = $state["candidato_{$position}_treinamentos_data_confirmacao"] ?? null;
                    if ($this->daysSince($trainingConfirmedAt, $today) > 2) {
                        $gargalos['Assinatura documental']['atrasados']++;
                    }
                }

                $sgcDone = filled($state["candidato_{$position}_sgc_data_mobilizacao"] ?? null);
                if ($sgcDone) {
                    $funil['SGC mobilizado']++;
                } elseif ($signatureDone) {
                    $gargalos['Postagem SGC']['pendentes']++;
                    $signatureConfirmedAt = $state["candidato_{$position}_assinatura_data_confirmacao"] ?? null;
                    if ($this->daysSince($signatureConfirmedAt, $today) > 3) {
                        $gargalos['Postagem SGC']['atrasados']++;
                    }
                }

                $released = filled($state["candidato_{$position}_liberacao_orientado_data"] ?? null)
                    && filled($state["candidato_{$position}_liberacao_epi_data"] ?? null)
                    && filled($state["candidato_{$position}_liberacao_rota_endereco"] ?? null);

                if ($released) {
                    $funil['Liberados']++;
                } elseif ($sgcDone) {
                    $gargalos['Liberação para atividades']['pendentes']++;
                }
            }
        }

        return [
            'funil' => $this->withPercent($funil),
            'gargalos' => $gargalos,
            'vagas_abertas' => $vagas->whereNotIn('status', ['Finalizada', 'Cancelada'])->count(),
            'em_andamento' => collect($gargalos)->sum('pendentes'),
            'atrasados' => collect($gargalos)->sum('atrasados'),
        ];
    }

    private function withPercent(array $items): array
    {
        $max = max(max($items), 1);

        return collect($items)
            ->map(fn ($value, $label) => [
                'label' => $label,
                'value' => $value,
                'percent' => (int) round(($value / $max) * 100),
            ])
            ->values()
            ->all();
    }

    private function trainingDone(array $state, int $position): bool
    {
        return filled($state["candidato_{$position}_treinamentos_data_inicio"] ?? null)
            && filled($state["candidato_{$position}_treinamentos_data_confirmacao"] ?? null);
    }

    private function trainingLate(array $state, int $position, Carbon $today): bool
    {
        $start = $state["candidato_{$position}_treinamentos_data_inicio"] ?? null;
        $plannedEnd = $state["candidato_{$position}_treinamentos_data_fim"] ?? null;
        $confirmedAt = $state["candidato_{$position}_treinamentos_data_confirmacao"] ?? null;

        if (blank($plannedEnd) && filled($start)) {
            $plannedEnd = Carbon::parse($start)->addDays(5)->toDateString();
        }

        if (blank($plannedEnd)) {
            return false;
        }

        $reference = filled($confirmedAt) ? Carbon::parse($confirmedAt) : $today;

        return Carbon::parse($plannedEnd)->startOfDay()->diffInDays($reference->startOfDay(), false) > 0;
    }

    private function daysSince(mixed $date, Carbon $today): int
    {
        if (blank($date)) {
            return 0;
        }

        try {
            return max(0, Carbon::parse($date)->startOfDay()->diffInDays($today->copy()->startOfDay(), false));
        } catch (\Throwable $e) {
            return 0;
        }
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

    private function sesmtAtrasados(Collection $tarefas): int
    {
        return $tarefas
            ->filter(fn ($tarefa) => $tarefa->data_prevista && $tarefa->status !== 'concluido' && $tarefa->data_prevista->isPast())
            ->count();
    }

    private function alertas(array $gargalos, array $submodulos): array
    {
        $alertas = collect($gargalos)
            ->map(fn ($item, $label) => [
                'label' => $label,
                'tipo' => 'Etapa do recrutamento',
                'pendentes' => $item['pendentes'],
                'atrasados' => $item['atrasados'],
                'sla' => $item['sla'],
            ])
            ->merge(collect($submodulos)->map(fn ($item) => [
                'label' => $item['label'],
                'tipo' => 'Submódulo RH',
                'pendentes' => $item['pendentes'],
                'atrasados' => $item['atrasados'],
                'sla' => 'Acompanhar fila operacional',
            ]))
            ->filter(fn ($item) => $item['pendentes'] > 0 || $item['atrasados'] > 0)
            ->sortByDesc(fn ($item) => ($item['atrasados'] * 10) + $item['pendentes'])
            ->take(5)
            ->values()
            ->all();

        return $alertas;
    }
}
