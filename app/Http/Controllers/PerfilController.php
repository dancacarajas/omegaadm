<?php

namespace App\Http\Controllers;

use App\Models\Perfil;
use App\Models\User;
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
            'sesmt' => 'SSMA',
            'contratos' => 'Contratos',
            'patrimonial' => 'Patrimonial',
            'medicao' => 'Medição',
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
            'sesmtSecoes' => User::sesmtSecoesDefinicao(),
            'sesmtSecoesInicial' => $this->sesmtSecoesValoresFormulario(new Perfil(['ativo' => true])),
            'rhSecoes' => User::rhSecoesDefinicao(),
            'rhSecoesInicial' => $this->rhSecoesValoresFormulario(new Perfil(['ativo' => true])),
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
            'sesmtSecoes' => User::sesmtSecoesDefinicao(),
            'rhSecoes' => User::rhSecoesDefinicao(),
        ]);
    }

    public function edit(Perfil $perfi)
    {
        return view('perfis.edit', [
            'perfil' => $perfi,
            'modulos' => self::modulos(),
            'acoes' => self::acoes(),
            'sesmtSecoes' => User::sesmtSecoesDefinicao(),
            'sesmtSecoesInicial' => $this->sesmtSecoesValoresFormulario($perfi),
            'rhSecoes' => User::rhSecoesDefinicao(),
            'rhSecoesInicial' => $this->rhSecoesValoresFormulario($perfi),
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

        $normalized['sesmt']['secoes'] = [];
        foreach (array_keys(User::sesmtSecoesDefinicao()) as $key) {
            $normalized['sesmt']['secoes'][$key] = (bool) data_get($permissoes, "sesmt.secoes.{$key}");
        }

        $normalized['rh']['secoes'] = [];
        foreach (array_keys(User::rhSecoesDefinicao()) as $key) {
            $normalized['rh']['secoes'][$key] = (bool) data_get($permissoes, "rh.secoes.{$key}");
        }

        return $normalized;
    }

    /**
     * @return array<string, bool>
     */
    private function sesmtSecoesValoresFormulario(Perfil $perfil): array
    {
        $stored = data_get($perfil->permissoes, 'sesmt.secoes');
        $legacy = ! is_array($stored);
        $out = [];
        foreach (array_keys(User::sesmtSecoesDefinicao()) as $key) {
            $out[$key] = $legacy ? true : (bool) data_get($stored, $key, false);
        }

        return $out;
    }

    /**
     * @return array<string, bool>
     */
    private function rhSecoesValoresFormulario(Perfil $perfil): array
    {
        $stored = data_get($perfil->permissoes, 'rh.secoes');
        $legacy = ! is_array($stored);
        $out = [];
        foreach (array_keys(User::rhSecoesDefinicao()) as $key) {
            $out[$key] = $legacy ? true : (bool) data_get($stored, $key, false);
        }

        return $out;
    }
}
