<?php

namespace App\Http\Controllers;

use App\Models\SsmaPlanoAcao;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SsmaPlanoAcaoController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeView();

        $query = SsmaPlanoAcao::query()
            ->when($request->boolean('somente_atrasadas'), function ($q) {
                $q->whereNotIn('status', ['concluida', 'validada', 'cancelada'])
                    ->whereDate('prazo', '<', now()->toDateString());
            })
            ->filtrar(
                $request->filled('busca') ? (string) $request->input('busca') : null,
                $request->filled('status') ? (string) $request->input('status') : null,
                $request->filled('origem') ? (string) $request->input('origem') : null,
                $request->filled('responsavel') ? (string) $request->input('responsavel') : null,
            );

        $planos = $query
            ->orderByRaw("CASE WHEN status IN ('concluida','validada','cancelada') THEN 1 ELSE 0 END")
            ->orderBy('prazo')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $totais = [
            'abertas' => SsmaPlanoAcao::whereNotIn('status', ['concluida', 'validada', 'cancelada'])->count(),
            'atrasadas' => SsmaPlanoAcao::whereNotIn('status', ['concluida', 'validada', 'cancelada'])
                ->whereDate('prazo', '<', now()->toDateString())
                ->count(),
            'validadas' => SsmaPlanoAcao::where('status', 'validada')->count(),
        ];

        $podeEditar = auth()->user()?->podeAcaoNoModulo('sesmt', 'editar') ?? false;

        return view('sesmt.plano-acao.index', compact('planos', 'totais', 'podeEditar'));
    }

    public function create()
    {
        $this->authorizeEdit();

        return view('sesmt.plano-acao.create', ['plano' => new SsmaPlanoAcao([
            'status' => 'aberta',
            'prioridade' => 'media',
            'nivel_risco' => 'medio',
            'prazo' => now()->addDays(15)->toDateString(),
        ])]);
    }

    public function store(Request $request)
    {
        $this->authorizeEdit();

        $data = $this->validated($request);
        $data['evidencia_conclusao_path'] = $this->storeEvidencia($request, null);

        SsmaPlanoAcao::create($data);

        return redirect()
            ->route('sesmt.plano-acao.index')
            ->with('success', 'Plano de ação cadastrado.');
    }

    public function edit(SsmaPlanoAcao $plano)
    {
        $this->authorizeEdit();

        return view('sesmt.plano-acao.edit', compact('plano'));
    }

    public function update(Request $request, SsmaPlanoAcao $plano)
    {
        $this->authorizeEdit();

        $data = $this->validated($request);
        $path = $this->storeEvidencia($request, $plano->evidencia_conclusao_path);
        if ($path !== null) {
            $data['evidencia_conclusao_path'] = $path;
        }

        $plano->update($data);

        return redirect()
            ->route('sesmt.plano-acao.index')
            ->with('success', 'Plano de ação atualizado.');
    }

    public function destroy(SsmaPlanoAcao $plano)
    {
        $this->authorizeEdit();

        if ($plano->evidencia_conclusao_path) {
            Storage::disk('public')->delete($plano->evidencia_conclusao_path);
        }
        $plano->delete();

        return redirect()
            ->route('sesmt.plano-acao.index')
            ->with('success', 'Plano de ação removido.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'origem' => ['required', 'in:'.implode(',', array_keys(SsmaPlanoAcao::ORIGENS))],
            'origem_detalhe' => ['nullable', 'string', 'max:500'],
            'tipo' => ['required', 'in:'.implode(',', array_keys(SsmaPlanoAcao::TIPOS))],
            'descricao_desvio' => ['required', 'string', 'max:20000'],
            'acao_necessaria' => ['required', 'string', 'max:20000'],
            'responsavel' => ['nullable', 'string', 'max:255'],
            'prazo' => ['required', 'date'],
            'status' => ['required', 'in:'.implode(',', array_keys(SsmaPlanoAcao::STATUS))],
            'prioridade' => ['required', 'in:'.implode(',', array_keys(SsmaPlanoAcao::PRIORIDADES))],
            'nivel_risco' => ['required', 'in:'.implode(',', array_keys(SsmaPlanoAcao::NIVEIS_RISCO))],
            'data_conclusao' => ['nullable', 'date'],
            'validacao_ssma' => ['nullable', 'string', 'max:10000'],
            'validacao_ssma_em' => ['nullable', 'date'],
            'justificativa_atraso' => ['nullable', 'string', 'max:10000'],
            'observacoes' => ['nullable', 'string', 'max:20000'],
            'evidencia_conclusao' => ['nullable', 'file', 'max:10240', 'mimes:jpeg,jpg,png,gif,webp,pdf'],
        ]);

        unset($data['evidencia_conclusao']);

        if (($data['status'] ?? '') === 'validada' && empty($data['validacao_ssma_em'])) {
            $data['validacao_ssma_em'] = now()->toDateString();
        }

        if (! in_array($data['status'] ?? '', ['concluida', 'validada'], true)) {
            $data['data_conclusao'] = $data['data_conclusao'] ?? null;
        }

        return $data;
    }

    private function storeEvidencia(Request $request, ?string $previous): ?string
    {
        if (! $request->hasFile('evidencia_conclusao')) {
            return null;
        }

        if ($previous) {
            Storage::disk('public')->delete($previous);
        }

        return $request->file('evidencia_conclusao')->store('ssma/plano-acao/evidencias', 'public');
    }

    private function authorizeView(): void
    {
        abort_unless(
            auth()->user()?->podeSecaoSesmt('plano_acao'),
            403,
            'Seu perfil não tem acesso a esta área do SSMA.'
        );
    }

    private function authorizeEdit(): void
    {
        abort_unless(
            auth()->user()?->podeAcaoNoModulo('sesmt', 'editar'),
            403,
            'Seu perfil não pode gerenciar planos de ação.'
        );
    }
}
