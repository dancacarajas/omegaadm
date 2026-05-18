<?php

namespace App\Http\Controllers;

use App\Models\SsmaTstAtividade;
use Illuminate\Http\Request;

class SsmaTstAtividadeController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeView();

        $atividades = SsmaTstAtividade::query()
            ->withCount('registros')
            ->filtrar(
                $request->filled('busca') ? trim((string) $request->input('busca')) : null,
                $request->filled('status') ? (string) $request->input('status') : null,
            )
            ->orderBy('ordem')
            ->orderBy('nome')
            ->paginate(20)
            ->withQueryString();

        $podeEditar = auth()->user()?->podeAcaoNoModulo('sesmt', 'editar') ?? false;

        return view('sesmt.registros-tst.atividades.index', compact('atividades', 'podeEditar'));
    }

    public function create()
    {
        $this->authorizeEdit();

        return view('sesmt.registros-tst.atividades.create', [
            'atividade' => new SsmaTstAtividade(['ativo' => true]),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeEdit();

        SsmaTstAtividade::create($this->validated($request));

        return redirect()
            ->route('sesmt.registros-tst.atividades.index')
            ->with('success', 'Atividade cadastrada.');
    }

    public function edit(SsmaTstAtividade $atividade)
    {
        $this->authorizeEdit();

        return view('sesmt.registros-tst.atividades.edit', compact('atividade'));
    }

    public function update(Request $request, SsmaTstAtividade $atividade)
    {
        $this->authorizeEdit();

        $atividade->update($this->validated($request, $atividade));

        return redirect()
            ->route('sesmt.registros-tst.atividades.index')
            ->with('success', 'Atividade atualizada.');
    }

    public function destroy(SsmaTstAtividade $atividade)
    {
        $this->authorizeEdit();

        if ($atividade->registros()->exists()) {
            return redirect()
                ->route('sesmt.registros-tst.atividades.index')
                ->with('error', 'Não é possível excluir: existem registros vinculados a esta atividade.');
        }

        $atividade->delete();

        return redirect()
            ->route('sesmt.registros-tst.atividades.index')
            ->with('success', 'Atividade removida.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?SsmaTstAtividade $atividade = null): array
    {
        $unique = 'unique:ssma_tst_atividades,nome';
        if ($atividade) {
            $unique .= ','.$atividade->id;
        }

        return $request->validate([
            'nome' => ['required', 'string', 'max:255', $unique],
            'ativo' => ['sometimes', 'boolean'],
            'ordem' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ], [], [
            'nome' => 'nome da atividade',
        ]) + [
            'ativo' => $request->boolean('ativo'),
            'ordem' => (int) $request->input('ordem', $atividade?->ordem ?? 0),
        ];
    }

    private function authorizeView(): void
    {
        abort_unless(
            auth()->user()?->podeSecaoSesmt('registros_tst'),
            403,
            'Seu perfil não tem acesso a esta área do SSMA.'
        );
    }

    private function authorizeEdit(): void
    {
        $this->authorizeView();

        abort_unless(
            auth()->user()?->podeAcaoNoModulo('sesmt', 'editar'),
            403,
            'Seu perfil não pode gerenciar atividades TST.'
        );
    }
}
