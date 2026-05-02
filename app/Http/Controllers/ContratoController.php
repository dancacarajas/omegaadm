<?php

namespace App\Http\Controllers;

use App\Models\Contrato;
use App\Support\ContratoAccess;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ContratoController extends Controller
{
    public function index()
    {
        $base = ContratoAccess::applyContratoModel(Contrato::query());

        $query = (clone $base)
            ->when(request('busca'), function ($query, string $busca) {
                $query->where(function ($query) use ($busca) {
                    $query->where('numero', 'like', "%{$busca}%")
                        ->orWhere('nome', 'like', "%{$busca}%")
                        ->orWhere('cliente', 'like', "%{$busca}%")
                        ->orWhere('contratada', 'like', "%{$busca}%")
                        ->orWhere('gestor', 'like', "%{$busca}%")
                        ->orWhere('centro_custo', 'like', "%{$busca}%");
                });
            });

        $indicadores = [
            'total' => (clone $base)->count(),
            'ativos' => (clone $base)->where('status', 'ativo')->count(),
            'em_analise' => (clone $base)->where('status', 'em_analise')->count(),
            'vencendo' => (clone $base)->where('status', 'ativo')
                ->whereBetween('data_fim', [today()->toDateString(), today()->addDays(60)->toDateString()])
                ->count(),
            'valor_total' => (clone $base)->whereNotIn('status', ['encerrado', 'cancelado'])->sum('valor'),
        ];

        $contratos = $query
            ->orderByRaw("FIELD(status, 'vencido', 'ativo', 'em_analise', 'suspenso', 'encerrado', 'cancelado')")
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('contratos.index', compact('contratos', 'indicadores'));
    }

    public function create()
    {
        return view('contratos.create', [
            'contrato' => new Contrato([
                'status' => 'ativo',
                'contratada' => 'Omega286',
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $contrato = Contrato::create($this->validatedData($request));

        return redirect()
            ->route('contratos.show', $contrato)
            ->with('success', 'Contrato cadastrado com sucesso.');
    }

    public function show(Contrato $contrato)
    {
        $this->authorizeContrato($contrato);

        return view('contratos.show', compact('contrato'));
    }

    public function edit(Contrato $contrato)
    {
        $this->authorizeContrato($contrato);

        return view('contratos.edit', compact('contrato'));
    }

    public function update(Request $request, Contrato $contrato)
    {
        $this->authorizeContrato($contrato);

        $contrato->update($this->validatedData($request, $contrato));

        return redirect()
            ->route('contratos.show', $contrato)
            ->with('success', 'Contrato atualizado com sucesso.');
    }

    public function destroy(Contrato $contrato)
    {
        $this->authorizeContrato($contrato);

        $contrato->delete();

        return redirect()
            ->route('contratos.index')
            ->with('success', 'Contrato removido com sucesso.');
    }

    private function authorizeContrato(Contrato $contrato): void
    {
        if (! ContratoAccess::shouldRestrict()) {
            return;
        }

        abort_unless(in_array($contrato->id, ContratoAccess::contratoIds(), true), 404);
    }

    private function validatedData(Request $request, ?Contrato $contrato = null): array
    {
        return $request->validate([
            'numero' => [
                'required',
                'string',
                'max:120',
                Rule::unique('contratos', 'numero')->ignore($contrato?->id),
            ],
            'nome' => ['required', 'string', 'max:255'],
            'cliente' => ['nullable', 'string', 'max:255'],
            'contratada' => ['nullable', 'string', 'max:255'],
            'objeto' => ['nullable', 'string'],
            'tipo' => ['nullable', 'string', 'max:80'],
            'centro_custo' => ['nullable', 'string', 'max:120'],
            'local_execucao' => ['nullable', 'string', 'max:255'],
            'gestor' => ['nullable', 'string', 'max:255'],
            'fiscal' => ['nullable', 'string', 'max:255'],
            'data_inicio' => ['nullable', 'date'],
            'data_fim' => ['nullable', 'date', 'after_or_equal:data_inicio'],
            'valor' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'in:ativo,em_analise,suspenso,encerrado,cancelado,vencido'],
            'descricao' => ['nullable', 'string'],
            'observacoes' => ['nullable', 'string'],
        ]);
    }
}
