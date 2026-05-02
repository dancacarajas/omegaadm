<?php

namespace App\Http\Controllers\Rh;

use App\Http\Controllers\Controller;
use App\Models\Beneficio;
use App\Models\Colaborador;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BeneficioController extends Controller
{
    public function index()
    {
        $beneficios = Beneficio::query()
            ->when(request('busca'), function ($query, string $busca) {
                $query->where(function ($query) use ($busca) {
                    $query->where('nome', 'like', "%{$busca}%")
                        ->orWhere('tipo', 'like', "%{$busca}%")
                        ->orWhere('fornecedor', 'like', "%{$busca}%")
                        ->orWhere('codigo', 'like', "%{$busca}%");
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('rh.beneficios.index', compact('beneficios'));
    }

    public function create()
    {
        return view('rh.beneficios.create', [
            'beneficio' => new Beneficio(['status' => 'ativo']),
        ]);
    }

    public function store(Request $request)
    {
        Beneficio::create($this->validatedData($request));

        return redirect()
            ->route('rh.beneficios.index')
            ->with('success', 'Beneficio cadastrado com sucesso.');
    }

    public function show(Beneficio $beneficio)
    {
        $beneficio->load(['colaboradores.colaborador']);

        $colaboradoresDisponiveis = Colaborador::query()
            ->whereNotIn('id', $beneficio->colaboradores->pluck('colaborador_id'))
            ->orderBy('nome')
            ->get(['id', 'nome', 'cargo', 'matricula']);

        return view('rh.beneficios.show', compact('beneficio', 'colaboradoresDisponiveis'));
    }

    public function edit(Beneficio $beneficio)
    {
        return view('rh.beneficios.edit', compact('beneficio'));
    }

    public function update(Request $request, Beneficio $beneficio)
    {
        $beneficio->update($this->validatedData($request, $beneficio));

        return redirect()
            ->route('rh.beneficios.show', $beneficio)
            ->with('success', 'Beneficio atualizado com sucesso.');
    }

    public function destroy(Beneficio $beneficio)
    {
        $beneficio->delete();

        return redirect()
            ->route('rh.beneficios.index')
            ->with('success', 'Beneficio removido com sucesso.');
    }

    private function validatedData(Request $request, ?Beneficio $beneficio = null): array
    {
        return $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'tipo' => ['nullable', 'string', 'max:80'],
            'fornecedor' => ['nullable', 'string', 'max:255'],
            'codigo' => ['nullable', 'string', 'max:80', Rule::unique('beneficios', 'codigo')->ignore($beneficio)],
            'valor' => ['nullable', 'numeric', 'min:0'],
            'periodicidade' => ['nullable', 'string', 'max:80'],
            'elegibilidade' => ['nullable', 'string'],
            'status' => ['required', 'string', Rule::in(['ativo', 'inativo', 'suspenso'])],
            'descricao' => ['nullable', 'string'],
            'observacoes' => ['nullable', 'string'],
        ]);
    }
}
