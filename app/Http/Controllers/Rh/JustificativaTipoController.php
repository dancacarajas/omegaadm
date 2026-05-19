<?php

namespace App\Http\Controllers\Rh;

use App\Http\Controllers\Controller;
use App\Models\FrequenciaJustificativaTipo;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class JustificativaTipoController extends Controller
{
    public function index(Request $request)
    {
        $tipos = FrequenciaJustificativaTipo::query()
            ->when($request->filled('busca'), function ($query) use ($request) {
                $busca = trim((string) $request->input('busca'));
                $query->where('nome', 'like', "%{$busca}%");
            })
            ->orderBy('ordem')
            ->orderBy('nome')
            ->paginate(25)
            ->withQueryString();

        return view('rh.frequencia.justificativa-tipos.index', compact('tipos'));
    }

    public function create()
    {
        return view('rh.frequencia.justificativa-tipos.create', [
            'tipo' => new FrequenciaJustificativaTipo([
                'ativo' => true,
                'limpa_batidas' => true,
                'categoria' => 'justificativa',
                'ordem' => 0,
            ]),
        ]);
    }

    public function store(Request $request)
    {
        FrequenciaJustificativaTipo::create($this->validated($request));

        return redirect()
            ->route('rh.frequencia.justificativa-tipos.index')
            ->with('success', 'Tipo de justificativa cadastrado.');
    }

    public function edit(FrequenciaJustificativaTipo $justificativa_tipo)
    {
        return view('rh.frequencia.justificativa-tipos.edit', [
            'tipo' => $justificativa_tipo,
        ]);
    }

    public function update(Request $request, FrequenciaJustificativaTipo $justificativa_tipo)
    {
        $justificativa_tipo->update($this->validated($request, $justificativa_tipo));

        return redirect()
            ->route('rh.frequencia.justificativa-tipos.index')
            ->with('success', 'Tipo de justificativa atualizado.');
    }

    public function destroy(FrequenciaJustificativaTipo $justificativa_tipo)
    {
        if ($justificativa_tipo->registros()->exists()) {
            return back()->with('error', 'Este tipo já foi usado no ponto e não pode ser excluído. Desative-o em vez disso.');
        }

        $justificativa_tipo->delete();

        return redirect()
            ->route('rh.frequencia.justificativa-tipos.index')
            ->with('success', 'Tipo removido.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?FrequenciaJustificativaTipo $tipo = null): array
    {
        $data = $request->validate([
            'nome' => [
                'required',
                'string',
                'max:255',
                Rule::unique('frequencia_justificativa_tipos', 'nome')->ignore($tipo?->id),
            ],
            'categoria' => ['required', Rule::in(array_keys(FrequenciaJustificativaTipo::CATEGORIAS))],
            'ordem' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'limpa_batidas' => ['sometimes', 'boolean'],
            'ativo' => ['required', Rule::in(['0', '1', 0, 1, true, false])],
        ]);

        return [
            'nome' => $data['nome'],
            'categoria' => $data['categoria'],
            'ordem' => (int) ($data['ordem'] ?? 0),
            'limpa_batidas' => $request->boolean('limpa_batidas'),
            'ativo' => $request->boolean('ativo'),
        ];
    }
}
