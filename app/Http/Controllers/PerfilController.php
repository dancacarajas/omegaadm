<?php

namespace App\Http\Controllers;

use App\Models\Perfil;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PerfilController extends Controller
{
    public static function modulos(): array
    {
        return [
            'dashboard' => 'Painel',
            'rh' => 'RH',
            'veiculos' => 'Veículos',
            'sesmt' => 'SESMT',
            'contratos' => 'Contratos',
            'patrimonial' => 'Patrimonial',
            'rdo' => 'RDO',
            'acessos' => 'Acessos',
        ];
    }

    public static function acoes(): array
    {
        return [
            'visualizar' => 'Visualizar',
            'criar' => 'Criar',
            'editar' => 'Editar',
            'excluir' => 'Excluir',
        ];
    }

    public function index()
    {
        $query = Perfil::query()
            ->withCount('usuarios')
            ->when(request('busca'), function ($query, string $busca) {
                $query->where('nome', 'like', "%{$busca}%")
                    ->orWhere('descricao', 'like', "%{$busca}%");
            });

        $indicadores = [
            'total' => Perfil::count(),
            'ativos' => Perfil::where('ativo', true)->count(),
            'inativos' => Perfil::where('ativo', false)->count(),
            'usuarios' => \App\Models\User::count(),
        ];

        $perfis = $query->orderBy('nome')->paginate(10)->withQueryString();

        return view('perfis.index', compact('perfis', 'indicadores'));
    }

    public function create()
    {
        return view('perfis.create', [
            'perfil' => new Perfil(['ativo' => true]),
            'modulos' => self::modulos(),
            'acoes' => self::acoes(),
        ]);
    }

    public function store(Request $request)
    {
        $perfil = Perfil::create($this->validatedData($request));

        return redirect()->route('perfis.show', $perfil)->with('success', 'Perfil cadastrado com sucesso.');
    }

    public function show(Perfil $perfi)
    {
        $perfi->loadCount('usuarios');

        return view('perfis.show', [
            'perfil' => $perfi,
            'modulos' => self::modulos(),
            'acoes' => self::acoes(),
        ]);
    }

    public function edit(Perfil $perfi)
    {
        return view('perfis.edit', [
            'perfil' => $perfi,
            'modulos' => self::modulos(),
            'acoes' => self::acoes(),
        ]);
    }

    public function update(Request $request, Perfil $perfi)
    {
        $perfi->update($this->validatedData($request, $perfi));

        return redirect()->route('perfis.show', $perfi)->with('success', 'Perfil atualizado com sucesso.');
    }

    public function destroy(Perfil $perfi)
    {
        if ($perfi->usuarios()->exists()) {
            return redirect()->route('perfis.index')->with('success', 'Perfil possui usuários vinculados e não pode ser excluído.');
        }

        $perfi->delete();

        return redirect()->route('perfis.index')->with('success', 'Perfil removido com sucesso.');
    }

    private function validatedData(Request $request, ?Perfil $perfil = null): array
    {
        $data = $request->validate([
            'nome' => ['required', 'string', 'max:120', Rule::unique('perfis', 'nome')->ignore($perfil?->id)],
            'descricao' => ['nullable', 'string', 'max:255'],
            'ativo' => ['nullable', 'boolean'],
            'permissoes' => ['nullable', 'array'],
        ]);

        $data['ativo'] = $request->boolean('ativo');
        $data['permissoes'] = $this->normalizePermissoes($request->input('permissoes', []));

        return $data;
    }

    private function normalizePermissoes(array $permissoes): array
    {
        $normalized = [];

        foreach (self::modulos() as $modulo => $label) {
            foreach (self::acoes() as $acao => $acaoLabel) {
                $normalized[$modulo][$acao] = (bool) data_get($permissoes, "{$modulo}.{$acao}");
            }
        }

        return $normalized;
    }
}
