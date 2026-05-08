<?php

namespace App\Http\Controllers\Rh;

use App\Http\Controllers\Controller;
use App\Models\Colaborador;
use App\Models\Contrato;
use App\Models\ContratoHistogramaLinha;
use App\Models\RecrutamentoVaga;
use App\Support\ContratoAccess;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class RecrutamentoController extends Controller
{
    public function index()
    {
        $contratoSelecionado = trim((string) request('contrato'));

        $centrosDeCusto = ContratoAccess::applyContratoModel(Contrato::query())
            ->where('status', 'Ativo')
            ->orderBy('centro_custo')
            ->get(['centro_custo'])
            ->pluck('centro_custo')
            ->map(fn ($valor) => trim((string) $valor))
            ->filter()
            ->unique()
            ->values();

        $vagasQuery = ContratoAccess::applyContratoString(RecrutamentoVaga::query())
            ->when($contratoSelecionado === '', fn ($query) => $query->whereRaw('1 = 0'))
            ->when($contratoSelecionado !== '', function ($query) use ($contratoSelecionado) {
                $query->where('contrato', $contratoSelecionado);
            })
            ->when(request('busca'), function ($query, string $busca) {
                $query->where(function ($query) use ($busca) {
                    $query->where('titulo', 'like', "%{$busca}%")
                        ->orWhere('contrato', 'like', "%{$busca}%")
                        ->orWhere('gestor', 'like', "%{$busca}%")
                        ->orWhere('local', 'like', "%{$busca}%")
                        ->orWhere('status', 'like', "%{$busca}%");
                });
            });

        $vagas = (clone $vagasQuery)
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $indicadores = $this->buildIndicadores((clone $vagasQuery)->get());

        return view('rh.recrutamento.index', compact('vagas', 'centrosDeCusto', 'contratoSelecionado', 'indicadores'));
    }

    /**
     * Painel consolidado: candidatos com ficha preenchida (nome/data) e posições ainda vagas.
     */
    public function painelPreenchimento()
    {
        $contratoSelecionado = trim((string) request('contrato'));

        $centrosDeCusto = ContratoAccess::applyContratoModel(Contrato::query())
            ->where('status', 'Ativo')
            ->orderBy('centro_custo')
            ->get(['centro_custo'])
            ->pluck('centro_custo')
            ->map(fn ($valor) => trim((string) $valor))
            ->filter()
            ->unique()
            ->values();

        $preenchidos = [];
        $vagasAbertas = [];
        $vagasLista = collect();

        if ($contratoSelecionado !== '') {
            $vagasQuery = ContratoAccess::applyContratoString(RecrutamentoVaga::query())
                ->where('contrato', $contratoSelecionado)
                ->when(request('busca'), function ($query, string $busca) {
                    $query->where(function ($query) use ($busca) {
                        $query->where('titulo', 'like', "%{$busca}%")
                            ->orWhere('gestor', 'like', "%{$busca}%")
                            ->orWhere('local', 'like', "%{$busca}%")
                            ->orWhere('status', 'like', "%{$busca}%");
                    });
                });

            $vagasLista = $vagasQuery->latest()->get();

            foreach ($vagasLista as $vaga) {
                $state = $vaga->form_state ?? [];
                $quantity = max(1, (int) ($state['vaga_quantidade'] ?? $vaga->quantidade ?? 1));

                foreach (range(1, $quantity) as $position) {
                    $nome = trim((string) ($state["candidato_{$position}_nome_completo"] ?? ''));
                    $dataAceite = $state["candidato_{$position}_data_aceite"] ?? null;
                    $telefone = trim((string) ($state["candidato_{$position}_celular"] ?? ''));
                    $temDados = $nome !== '' || filled($dataAceite) || $telefone !== '';

                    if ($temDados) {
                        $preenchidos[] = [
                            'vaga_id' => $vaga->id,
                            'vaga_titulo' => $vaga->titulo,
                            'contrato' => $vaga->contrato,
                            'posicao' => $position,
                            'nome' => $nome !== '' ? $nome : '—',
                            'telefone' => $telefone !== '' ? $telefone : '—',
                            'data_aceite_br' => $this->formatDateBr($dataAceite),
                            'status_candidato' => (string) ($state["candidato_{$position}_status"] ?? 'pendente'),
                            'fase' => $this->candidatoFaseAtualLabel($vaga, $position),
                        ];
                    } else {
                        $vagasAbertas[] = [
                            'vaga_id' => $vaga->id,
                            'vaga_titulo' => $vaga->titulo,
                            'contrato' => $vaga->contrato,
                            'local' => $vaga->local,
                            'posicao' => $position,
                        ];
                    }
                }
            }
        }

        usort($preenchidos, function (array $a, array $b): int {
            return [$a['vaga_titulo'] ?? '', $a['posicao']]
                <=> [$b['vaga_titulo'] ?? '', $b['posicao']];
        });
        usort($vagasAbertas, function (array $a, array $b): int {
            return [$a['vaga_titulo'] ?? '', $a['posicao']]
                <=> [$b['vaga_titulo'] ?? '', $b['posicao']];
        });

        $ordemNome = (string) request('ordem_nome', 'padrao');
        if (! in_array($ordemNome, ['padrao', 'az', 'za'], true)) {
            $ordemNome = 'padrao';
        }

        if ($ordemNome !== 'padrao') {
            $this->sortPreenchidosPorNome($preenchidos, $ordemNome === 'za');
        }

        $preenchidas = count($preenchidos);
        $faltando = count($vagasAbertas);
        $posicoes = $preenchidas + $faltando;

        $totaisPainel = [
            'fichas' => $vagasLista->count(),
            'posicoes' => $posicoes,
            'preenchidas' => $preenchidas,
            'faltando' => $faltando,
            'pct_preenchido' => $posicoes > 0 ? (int) round(($preenchidas / $posicoes) * 100) : 0,
        ];

        return view('rh.recrutamento.painel-preenchimento', compact(
            'centrosDeCusto',
            'contratoSelecionado',
            'preenchidos',
            'vagasAbertas',
            'totaisPainel',
            'ordemNome'
        ));
    }

    public function create()
    {
        $defaults = $this->loggedContratoDefaults();

        return view('rh.recrutamento.form', [
            'vaga' => new RecrutamentoVaga([
                'status' => 'Em abertura',
                'quantidade' => 1,
                'form_state' => array_merge([
                    'vaga_status' => 'Em abertura',
                    'vaga_quantidade' => '1',
                ], $defaults),
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $finish = $request->boolean('finish_rh_flow');
        $vaga = RecrutamentoVaga::create($this->vagaData($request));
        $this->syncHistogramaFromRecrutamento($vaga);
        $this->syncCandidatosAssinadosComEfetivo($vaga);

        if ($finish) {
            return redirect()
                ->route('rh.recrutamento.index')
                ->with('success', 'Fluxo de recrutamento concluído e salvo.');
        }

        $step = $this->migrateRhFlowStepForRedirect($vaga->form_state ?? []);

        return redirect()
            ->route('rh.recrutamento.edit', ['recrutamento' => $vaga, 'step' => $step])
            ->with('success', 'Vaga criada. O fluxo de recrutamento foi salvo.');
    }

    public function edit(RecrutamentoVaga $recrutamento)
    {
        $this->authorizeContratoString($recrutamento->contrato);

        $state = $this->applyContratoCatalogData($recrutamento->form_state ?? []);
        if (($recrutamento->form_state ?? []) !== $state) {
            $recrutamento->forceFill([
                'form_state' => $state,
                'gestor' => $state['vaga_gestor'] ?? $recrutamento->gestor,
                'local' => $state['vaga_local'] ?? $recrutamento->local,
            ])->saveQuietly();
            $recrutamento->refresh();
        }

        return view('rh.recrutamento.form', ['vaga' => $recrutamento]);
    }

    public function update(Request $request, RecrutamentoVaga $recrutamento)
    {
        $this->authorizeContratoString($recrutamento->contrato);

        $finish = $request->boolean('finish_rh_flow');
        $tituloAnterior = (string) $recrutamento->titulo;
        $recrutamento->update($this->vagaData($request));
        $this->syncHistogramaFromRecrutamento($recrutamento, $tituloAnterior);
        $this->syncCandidatosAssinadosComEfetivo($recrutamento);

        if ($finish) {
            return redirect()
                ->route('rh.recrutamento.index')
                ->with('success', 'Fluxo de recrutamento concluído e salvo.');
        }

        $step = $this->migrateRhFlowStepForRedirect($recrutamento->form_state ?? []);

        return redirect()
            ->route('rh.recrutamento.edit', ['recrutamento' => $recrutamento, 'step' => $step])
            ->with('success', 'Vaga atualizada. Os dados continuam salvos nesta ficha.');
    }

    public function destroy(RecrutamentoVaga $recrutamento)
    {
        $this->authorizeContratoString($recrutamento->contrato);

        $recrutamento->delete();

        return redirect()
            ->route('rh.recrutamento.index')
            ->with('success', 'Vaga removida com sucesso.');
    }

    private function authorizeContratoString(?string $contrato): void
    {
        if (! ContratoAccess::shouldRestrict()) {
            return;
        }

        abort_unless($contrato && in_array($contrato, ContratoAccess::contratoValores(), true), 404);
    }

    /**
     * Antes do fluxo v2, `step-treinamentos` era o exame médico. URL de redirect alinhada ao JS (RH_FLOW_SCHEMA).
     */
    private function migrateRhFlowStepForRedirect(array $state): string
    {
        $step = $state['currentStep'] ?? 'step-recrutamento';
        $ver = (int) ($state['rhFlowSchemaVersion'] ?? 0);
        if ($step === 'step-treinamentos' && $ver < 2) {
            return 'step-exameMedico';
        }

        return $step;
    }

    private function vagaData(Request $request): array
    {
        $validated = $request->validate([
            'form_state' => ['nullable', 'string'],
        ]);

        $state = json_decode($validated['form_state'] ?? '{}', true) ?: [];
        $state = $this->enforceContratoState($state);
        $state = $this->applyContratoCatalogData($state);

        return [
            'titulo' => $state['vaga_titulo'] ?? null,
            'quantidade' => max(1, (int) ($state['vaga_quantidade'] ?? 1)),
            'prioridade' => $state['vaga_prioridade'] ?? null,
            'tipo' => $state['vaga_tipo'] ?? null,
            'contrato' => $state['vaga_contrato'] ?? null,
            'gestor' => $state['vaga_gestor'] ?? null,
            'local' => $state['vaga_local'] ?? null,
            'data_solicitacao' => $state['vaga_data_solicitacao'] ?? null,
            'previsao_inicio' => $state['vaga_previsao_inicio'] ?? null,
            'salario' => $state['vaga_salario'] ?? null,
            'status' => $state['vaga_status'] ?? 'Em abertura',
            'descricao' => $state['vaga_descricao'] ?? null,
            'requisitos' => $state['vaga_requisitos'] ?? null,
            'form_state' => $state,
        ];
    }

    private function loggedContratoDefaults(): array
    {
        $user = ContratoAccess::user();
        if (! $user) {
            return [];
        }

        $contrato = $user->contratos()->orderBy('contratos.id')->first();
        if (! $contrato && $user->todos_contratos) {
            $contrato = Contrato::query()
                ->where('status', 'Ativo')
                ->orderBy('id')
                ->first()
                ?? Contrato::query()->orderBy('id')->first();
        }

        if (! $contrato) {
            return [];
        }

        return [
            'vaga_contrato' => $contrato->numero ?: ($contrato->centro_custo ?: $contrato->nome),
            'vaga_gestor' => $contrato->gestor,
            'vaga_local' => $contrato->local_execucao,
        ];
    }

    private function enforceContratoState(array $state): array
    {
        if (! ContratoAccess::shouldRestrict()) {
            return $state;
        }

        $allowed = ContratoAccess::contratoValores();
        $selected = (string) ($state['vaga_contrato'] ?? '');
        if ($selected !== '' && in_array($selected, $allowed, true)) {
            return $state;
        }

        return array_merge($state, $this->loggedContratoDefaults());
    }

    private function applyContratoCatalogData(array $state): array
    {
        $selected = trim((string) ($state['vaga_contrato'] ?? ''));
        if ($selected === '') {
            return $state;
        }

        $contrato = Contrato::query()
            ->where('numero', $selected)
            ->orWhere('centro_custo', $selected)
            ->orWhere('nome', $selected)
            ->first();

        if (! $contrato) {
            return $state;
        }

        $state['vaga_gestor'] = (string) ($contrato->gestor ?? '');
        $state['vaga_local'] = (string) ($contrato->local_execucao ?? '');

        return $state;
    }

    /**
     * Se a vaga veio do histograma, renomear no recrutamento também renomeia a linha correspondente no histograma.
     */
    private function syncHistogramaFromRecrutamento(RecrutamentoVaga $vaga, ?string $tituloAnterior = null): void
    {
        $state = $vaga->form_state ?? [];
        if (! ($state['origem_histograma'] ?? false)) {
            return;
        }

        $novoTitulo = trim((string) ($state['vaga_titulo'] ?? $vaga->titulo ?? ''));
        if ($novoTitulo === '') {
            return;
        }

        $competenciaYm = trim((string) ($state['origem_histograma_competencia'] ?? ''));
        if ($competenciaYm === '') {
            return;
        }

        try {
            $competenciaDate = Carbon::createFromFormat('Y-m', $competenciaYm)->startOfMonth()->toDateString();
        } catch (\Throwable) {
            return;
        }

        $itemCodigo = trim((string) ($state['origem_histograma_item_codigo'] ?? ''));
        $query = ContratoHistogramaLinha::query()
            ->where('contrato', $vaga->contrato)
            ->whereDate('competencia', $competenciaDate)
            ->where(function ($q) {
                $q->whereNull('tipo_linha')
                    ->orWhere('tipo_linha', '!=', 'grupo');
            });

        if ($itemCodigo !== '') {
            $query->where('item_codigo', $itemCodigo);
        } else {
            $descricoes = collect([
                trim((string) ($state['origem_histograma_descricao'] ?? '')),
                trim((string) ($tituloAnterior ?? '')),
                trim((string) ($vaga->titulo ?? '')),
            ])->filter()->unique()->values();
            if ($descricoes->isEmpty()) {
                return;
            }
            $query->whereIn('descricao', $descricoes->all());
        }

        $linha = $query->orderBy('id')->first();
        if (! $linha) {
            return;
        }

        if (trim((string) $linha->descricao) !== $novoTitulo) {
            $linha->update(['descricao' => $novoTitulo]);
        }

        $state['origem_histograma_descricao'] = $novoTitulo;
        $state['origem_histograma_key'] = implode('|', ['histograma', $vaga->contrato, $competenciaYm, $novoTitulo]);
        if (($vaga->form_state ?? []) !== $state) {
            $vaga->forceFill(['form_state' => $state])->saveQuietly();
        }
    }

    private function syncCandidatosAssinadosComEfetivo(RecrutamentoVaga $vaga): void
    {
        $state = $vaga->form_state ?? [];
        $quantity = max(1, (int) ($state['vaga_quantidade'] ?? $vaga->quantidade ?? 1));
        $changed = false;

        DB::transaction(function () use ($vaga, &$state, $quantity, &$changed) {
            foreach (range(1, $quantity) as $position) {
                $name = trim((string) ($state["candidato_{$position}_nome_completo"] ?? ''));
                $phone = trim((string) ($state["candidato_{$position}_celular"] ?? ''));
                $status = $state["candidato_{$position}_status"] ?? 'pendente';
                $signedAt = $state["candidato_{$position}_assinatura_data_confirmacao"] ?? null;

                if ($status !== 'aprovado' || $name === '' || blank($signedAt)) {
                    continue;
                }

                $colaborador = null;
                $colaboradorId = $state["candidato_{$position}_colaborador_id"] ?? null;

                if ($colaboradorId) {
                    $colaborador = Colaborador::query()->find($colaboradorId);
                }

                $colaborador ??= Colaborador::query()
                    ->where('recrutamento_vaga_id', $vaga->id)
                    ->where('recrutamento_posicao', $position)
                    ->first();

                $data = [
                    'nome' => $name,
                    'telefone' => $phone ?: null,
                    'status' => 'ativo',
                    'cargo' => $vaga->titulo,
                    'centro_custo' => $vaga->contrato,
                    'local_trabalho' => $vaga->local,
                    'recrutamento_vaga_id' => $vaga->id,
                    'recrutamento_posicao' => $position,
                ];

                if ($colaborador) {
                    $colaborador->update($data);
                } else {
                    $colaborador = Colaborador::create($data);
                }

                if (($state["candidato_{$position}_colaborador_id"] ?? null) !== $colaborador->id) {
                    $state["candidato_{$position}_colaborador_id"] = $colaborador->id;
                    $changed = true;
                }
            }
        });

        if ($changed) {
            $vaga->forceFill(['form_state' => $state])->saveQuietly();
        }
    }

    /**
     * @param  Collection<int, RecrutamentoVaga>  $vagas
     * @return array<string, int>
     */
    private function buildIndicadores(Collection $vagas): array
    {
        $totalVagas = (int) $vagas->sum(fn (RecrutamentoVaga $vaga) => max(1, (int) $vaga->quantidade));
        $fichas = $vagas->count();
        $emAbertura = $vagas->where('status', 'Em abertura')->count();

        $aprovados = 0;
        $liberados = 0;

        foreach ($vagas as $vaga) {
            $state = $vaga->form_state ?? [];
            $quantity = max(1, (int) ($state['vaga_quantidade'] ?? $vaga->quantidade ?? 1));

            foreach (range(1, $quantity) as $position) {
                $name = trim((string) ($state["candidato_{$position}_nome_completo"] ?? ''));
                $status = (string) ($state["candidato_{$position}_status"] ?? 'pendente');
                if ($status !== 'aprovado' || $name === '') {
                    continue;
                }

                $aprovados++;
                $doneLiberacao = filled($state["candidato_{$position}_liberacao_orientado_data"] ?? null)
                    && filled($state["candidato_{$position}_liberacao_epi_data"] ?? null)
                    && filled($state["candidato_{$position}_liberacao_rota_endereco"] ?? null);
                if ($doneLiberacao) {
                    $liberados++;
                }
            }
        }

        return [
            'fichas' => $fichas,
            'total_vagas' => $totalVagas,
            'em_abertura' => $emAbertura,
            'aprovados' => $aprovados,
            'liberados' => $liberados,
        ];
    }

    private function formatDateBr(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($value)->format('d/m/Y');
        } catch (\Throwable) {
            return null;
        }
    }

    private function candidatoFaseAtualLabel(RecrutamentoVaga $vaga, int $position): string
    {
        $state = $vaga->form_state ?? [];

        if (blank($state["candidato_{$position}_data_aceite"] ?? null)) {
            $nome = trim((string) ($state["candidato_{$position}_nome_completo"] ?? ''));

            return $nome !== ''
                ? 'Cadastro — aguardando data de aceite'
                : 'Cadastro iniciado';
        }

        if (! $this->candidatoEtapaTreinamentosConcluida($state, $position)) {
            return 'Exame médico / treinamentos';
        }
        if (! $this->candidatoEtapaAssinaturaConcluida($state, $position)) {
            return 'Assinatura';
        }
        if (! $this->candidatoEtapaSgcConcluida($state, $position)) {
            return 'SGC / mobilização';
        }
        if (! $this->candidatoEtapaLiberacaoConcluida($state, $position)) {
            return 'Liberação';
        }

        return 'Concluído';
    }

    private function candidatoEtapaTreinamentosConcluida(array $state, int $position): bool
    {
        $trainingStart = $state["candidato_{$position}_treinamentos_data_inicio"] ?? null;
        $trainingConfirmedAt = $state["candidato_{$position}_treinamentos_data_confirmacao"] ?? null;

        return filled($trainingStart)
            && filled($trainingConfirmedAt)
            && ! $this->hasLegacyMirroredTrainingData($state, $position);
    }

    private function candidatoEtapaAssinaturaConcluida(array $state, int $position): bool
    {
        return filled($state["candidato_{$position}_assinatura_data_confirmacao"] ?? null);
    }

    private function candidatoEtapaSgcConcluida(array $state, int $position): bool
    {
        $hasPendency = filled($state["candidato_{$position}_sgc_pendencia_descricao"] ?? null);
        $pendencyDone = $hasPendency
            ? filled($state["candidato_{$position}_sgc_data_nova_postagem"] ?? null)
            : filled($state["candidato_{$position}_sgc_data_mobilizacao"] ?? null);

        return filled($state["candidato_{$position}_sgc_data_postagem"] ?? null)
            && filled($state["candidato_{$position}_sgc_numero_postagem"] ?? null)
            && $pendencyDone
            && filled($state["candidato_{$position}_sgc_data_mobilizacao"] ?? null);
    }

    private function candidatoEtapaLiberacaoConcluida(array $state, int $position): bool
    {
        return filled($state["candidato_{$position}_liberacao_orientado_data"] ?? null)
            && filled($state["candidato_{$position}_liberacao_epi_data"] ?? null)
            && filled($state["candidato_{$position}_liberacao_rota_endereco"] ?? null);
    }

    private function hasLegacyMirroredTrainingData(array $state, int $position): bool
    {
        $trainingStart = trim((string) ($state["candidato_{$position}_treinamentos_data_inicio"] ?? ''));
        $trainingConfirmed = trim((string) ($state["candidato_{$position}_treinamentos_data_confirmacao"] ?? ''));
        if ($trainingStart === '' || $trainingConfirmed === '') {
            return false;
        }

        $exameStart = trim((string) ($state["candidato_{$position}_exameMedico_data_inicio"] ?? ''));
        $exameConfirmed = trim((string) ($state["candidato_{$position}_exameMedico_data_confirmacao"] ?? ''));
        if ($exameStart === '' || $exameConfirmed === '') {
            return false;
        }

        $sgcPosted = filled($state["candidato_{$position}_sgc_data_postagem"] ?? null);
        $signed = filled($state["candidato_{$position}_assinatura_data_confirmacao"] ?? null);
        if ($sgcPosted || $signed) {
            return false;
        }

        return $trainingStart === $exameStart && $trainingConfirmed === $exameConfirmed;
    }

    /**
     * @param  array<int, array<string, mixed>>  $preenchidos
     */
    private function sortPreenchidosPorNome(array &$preenchidos, bool $descendente): void
    {
        $semNome = static function (string $nome): bool {
            $nome = trim($nome);

            return $nome === '' || $nome === '—';
        };

        usort($preenchidos, function (array $a, array $b) use ($semNome, $descendente): int {
            $na = (string) ($a['nome'] ?? '');
            $nb = (string) ($b['nome'] ?? '');
            $pa = $semNome($na);
            $pb = $semNome($nb);
            if ($pa !== $pb) {
                return $pa ? 1 : -1;
            }

            $ca = mb_strtolower($na, 'UTF-8');
            $cb = mb_strtolower($nb, 'UTF-8');
            $cmp = strnatcasecmp($ca, $cb);

            if ($cmp !== 0) {
                return $descendente ? -$cmp : $cmp;
            }

            return [$a['vaga_titulo'] ?? '', $a['posicao']]
                <=> [$b['vaga_titulo'] ?? '', $b['posicao']];
        });
    }
}
