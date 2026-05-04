<?php

namespace App\Http\Controllers\Rh;

use App\Http\Controllers\Controller;
use App\Models\Colaborador;
use App\Models\Contrato;
use App\Models\RecrutamentoVaga;
use App\Support\ContratoAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RecrutamentoController extends Controller
{
    public function index()
    {
        $vagas = ContratoAccess::applyContratoString(RecrutamentoVaga::query())
            ->when(request('busca'), function ($query, string $busca) {
                $query->where(function ($query) use ($busca) {
                    $query->where('titulo', 'like', "%{$busca}%")
                        ->orWhere('contrato', 'like', "%{$busca}%")
                        ->orWhere('gestor', 'like', "%{$busca}%")
                        ->orWhere('local', 'like', "%{$busca}%")
                        ->orWhere('status', 'like', "%{$busca}%");
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('rh.recrutamento.index', compact('vagas'));
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
        $this->syncCandidatosAssinadosComEfetivo($vaga);

        if ($finish) {
            return redirect()
                ->route('rh.recrutamento.index')
                ->with('success', 'Fluxo de recrutamento concluído e salvo.');
        }

        $step = $vaga->form_state['currentStep'] ?? 'step-recrutamento';

        return redirect()
            ->route('rh.recrutamento.edit', ['recrutamento' => $vaga, 'step' => $step])
            ->with('success', 'Vaga criada. O fluxo de recrutamento foi salvo.');
    }

    public function edit(RecrutamentoVaga $recrutamento)
    {
        $this->authorizeContratoString($recrutamento->contrato);

        return view('rh.recrutamento.form', ['vaga' => $recrutamento]);
    }

    public function update(Request $request, RecrutamentoVaga $recrutamento)
    {
        $this->authorizeContratoString($recrutamento->contrato);

        $finish = $request->boolean('finish_rh_flow');
        $recrutamento->update($this->vagaData($request));
        $this->syncCandidatosAssinadosComEfetivo($recrutamento);

        if ($finish) {
            return redirect()
                ->route('rh.recrutamento.index')
                ->with('success', 'Fluxo de recrutamento concluído e salvo.');
        }

        $step = $recrutamento->form_state['currentStep'] ?? 'step-recrutamento';

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

    private function vagaData(Request $request): array
    {
        $validated = $request->validate([
            'form_state' => ['nullable', 'string'],
        ]);

        $state = json_decode($validated['form_state'] ?? '{}', true) ?: [];
        $state = $this->enforceContratoState($state);

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
}
