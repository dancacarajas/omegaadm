<?php

namespace App\Http\Controllers;

use App\Models\Contrato;
use App\Models\Patrimonio;
use App\Support\ContratoAccess;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PatrimonialController extends Controller
{
    public function index()
    {
        $base = ContratoAccess::applyContratoString(Patrimonio::query());

        $query = (clone $base)
            ->when(request('busca'), function ($query, string $busca) {
                $query->where(function ($query) use ($busca) {
                    $query->where('tag_patrimonial', 'like', "%{$busca}%")
                        ->orWhere('nome', 'like', "%{$busca}%")
                        ->orWhere('categoria', 'like', "%{$busca}%")
                        ->orWhere('modelo', 'like', "%{$busca}%")
                        ->orWhere('numero_serie', 'like', "%{$busca}%")
                        ->orWhere('contrato', 'like', "%{$busca}%")
                        ->orWhere('responsavel', 'like', "%{$busca}%")
                        ->orWhere('localizacao', 'like', "%{$busca}%");
                });
            });

        $indicadores = [
            'total' => (clone $base)->count(),
            'ativos' => (clone $base)->whereIn('status', ['ativo', 'em_uso'])->count(),
            'manutencao' => (clone $base)->where('status', 'em_manutencao')->count(),
            'pendentes_conferencia' => (clone $base)->whereNot('status', 'baixado')
                ->whereDate('proxima_conferencia', '<=', now()->toDateString())
                ->count(),
            'valor_total' => (clone $base)->whereNot('status', 'baixado')->sum('valor'),
        ];

        $patrimonios = $query
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('patrimonial.index', compact('patrimonios', 'indicadores'));
    }

    public function create()
    {
        $defaults = $this->loggedContratoDefaultsForPatrimonio();

        return view('patrimonial.create', ['patrimonio' => new Patrimonio(array_merge([
            'status' => 'ativo',
            'condicao' => 'bom',
        ], $defaults))]);
    }

    public function store(Request $request)
    {
        $data = $this->enforceContratoFields($this->validatedData($request));
        $patrimonio = Patrimonio::create($data);

        return redirect()
            ->route('patrimonial.show', $patrimonio)
            ->with('success', 'Patrimônio cadastrado com sucesso.');
    }

    public function show(Patrimonio $patrimonio)
    {
        $this->authorizeContratoString($patrimonio->contrato);

        return view('patrimonial.show', compact('patrimonio'));
    }

    public function edit(Patrimonio $patrimonio)
    {
        $this->authorizeContratoString($patrimonio->contrato);

        return view('patrimonial.edit', compact('patrimonio'));
    }

    public function update(Request $request, Patrimonio $patrimonio)
    {
        $this->authorizeContratoString($patrimonio->contrato);

        $patrimonio->update($this->enforceContratoFields($this->validatedData($request, $patrimonio)));

        return redirect()
            ->route('patrimonial.index')
            ->with('success', 'Patrimônio atualizado com sucesso.');
    }

    public function destroy(Patrimonio $patrimonio)
    {
        $this->authorizeContratoString($patrimonio->contrato);

        $patrimonio->delete();

        return redirect()
            ->route('patrimonial.index')
            ->with('success', 'Patrimônio removido do inventário.');
    }

    private function authorizeContratoString(?string $contrato): void
    {
        if (! ContratoAccess::shouldRestrict()) {
            return;
        }

        abort_unless($contrato && in_array($contrato, ContratoAccess::contratoValores(), true), 404);
    }

    /**
     * @return array<string, string|null>
     */
    private function loggedContratoDefaultsForPatrimonio(): array
    {
        $user = ContratoAccess::user();
        if (! $user) {
            return [];
        }

        $nomeCadastrante = trim((string) $user->name);
        $defaults = array_filter([
            'responsavel' => $nomeCadastrante !== '' ? $nomeCadastrante : null,
        ], fn ($v) => $v !== null && $v !== '');

        $contrato = $user->contratos()->orderBy('contratos.id')->first();
        if (! $contrato && $user->todos_contratos) {
            $contrato = Contrato::query()
                ->where('status', 'Ativo')
                ->orderBy('id')
                ->first()
                ?? Contrato::query()->orderBy('id')->first();
        }

        if (! $contrato) {
            return $defaults;
        }

        return array_merge($defaults, [
            'contrato' => $contrato->numero ?: ($contrato->centro_custo ?: $contrato->nome),
            'centro_custo' => $contrato->centro_custo,
            'localizacao' => $contrato->local_execucao,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function enforceContratoFields(array $data): array
    {
        if (! ContratoAccess::shouldRestrict()) {
            return $data;
        }

        $allowed = ContratoAccess::contratoValores();
        $selected = (string) ($data['contrato'] ?? '');
        if ($selected !== '' && in_array($selected, $allowed, true)) {
            return $data;
        }

        return array_merge($data, $this->loggedContratoDefaultsForPatrimonio());
    }

    private function validatedData(Request $request, ?Patrimonio $patrimonio = null): array
    {
        return $request->validate([
            'tag_patrimonial' => [
                'required',
                'string',
                'max:80',
                Rule::unique('patrimonios', 'tag_patrimonial')->ignore($patrimonio?->id),
            ],
            'nome' => ['required', 'string', 'max:255'],
            'categoria' => ['nullable', 'string', 'max:120'],
            'tipo' => ['nullable', 'string', 'max:120'],
            'marca' => ['nullable', 'string', 'max:120'],
            'modelo' => ['nullable', 'string', 'max:120'],
            'numero_serie' => ['nullable', 'string', 'max:120'],
            'contrato' => ['nullable', 'string', 'max:120'],
            'centro_custo' => ['nullable', 'string', 'max:120'],
            'fornecedor' => ['nullable', 'string', 'max:255'],
            'data_aquisicao' => ['nullable', 'date'],
            'data_entrada' => ['nullable', 'date'],
            'valor' => ['nullable', 'numeric', 'min:0'],
            'responsavel' => ['nullable', 'string', 'max:255'],
            'setor' => ['nullable', 'string', 'max:120'],
            'localizacao' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:ativo,em_uso,em_manutencao,reserva,baixado'],
            'condicao' => ['required', 'in:novo,bom,regular,danificado,inutilizado'],
            'ultima_conferencia' => ['nullable', 'date'],
            'proxima_conferencia' => ['nullable', 'date'],
            'observacoes' => ['nullable', 'string'],
        ]);
    }
}
