<?php

namespace App\Http\Controllers;

use App\Models\Colaborador;
use App\Models\Contrato;
use App\Models\Perfil;
use App\Models\User;
use App\Services\AuthEmailService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UsuarioController extends Controller
{
    public function index()
    {
        $query = User::query()
            ->with(['perfil', 'contratos'])
            ->when(request('busca'), function ($query, string $busca) {
                $query->where(function ($query) use ($busca) {
                    $query->where('name', 'like', "%{$busca}%")
                        ->orWhere('email', 'like', "%{$busca}%")
                        ->orWhere('telefone', 'like', "%{$busca}%")
                        ->orWhere('cargo', 'like', "%{$busca}%");
                });
            })
            ->when(request('status'), fn ($query, string $status) => $query->where('status', $status));

        $indicadores = [
            'total' => User::count(),
            'ativos' => User::where('status', 'ativo')->count(),
            'inativos' => User::where('status', 'inativo')->count(),
            'perfis' => Perfil::where('ativo', true)->count(),
        ];

        $usuarios = $query->latest()->paginate(10)->withQueryString();

        return view('usuarios.index', compact('usuarios', 'indicadores'));
    }

    public function create()
    {
        return view('usuarios.create', [
            'usuario' => new User(['status' => 'ativo']),
            'perfis' => Perfil::where('ativo', true)->orderBy('nome')->get(),
            'contratos' => Contrato::whereNotIn('status', ['encerrado', 'cancelado'])->orderBy('numero')->get(),
            'colaboradores' => $this->colaboradoresParaSelect(),
        ]);
    }

    public function store(Request $request, AuthEmailService $authEmail)
    {
        $data = $this->validatedData($request);
        $contratos = $data['contratos'] ?? [];
        $senhaPlana = $data['password'];
        unset($data['contratos']);

        $usuario = User::create($data);
        $usuario->contratos()->sync($usuario->todos_contratos ? [] : $contratos);

        $emailEnviado = $authEmail->enviarUsuarioCadastrado(
            $usuario,
            $senhaPlana,
            $request->user()?->name
        );

        $flash = $emailEnviado
            ? ['success' => 'Usuário cadastrado com sucesso. Um e-mail com os dados de acesso foi enviado.']
            : ['warning' => 'Usuário cadastrado com sucesso, porém o e-mail de acesso não foi enviado. Configure o SMTP em Configurações → E-mail e informe a senha ao usuário manualmente.'];

        return redirect()->route('usuarios.show', $usuario)->with($flash);
    }

    public function show(User $usuario)
    {
        $usuario->load(['perfil', 'contratos']);

        return view('usuarios.show', compact('usuario'));
    }

    public function edit(User $usuario)
    {
        return view('usuarios.edit', [
            'usuario' => $usuario->load('colaborador'),
            'perfis' => Perfil::where('ativo', true)->orderBy('nome')->get(),
            'contratos' => Contrato::whereNotIn('status', ['encerrado', 'cancelado'])->orderBy('numero')->get(),
            'colaboradores' => $this->colaboradoresParaSelect(),
        ]);
    }

    public function update(Request $request, User $usuario, AuthEmailService $authEmail)
    {
        $data = $this->validatedData($request, $usuario);
        $contratos = $data['contratos'] ?? [];
        unset($data['contratos']);

        $senhaAlterada = filled($data['password'] ?? null);
        if (! $senhaAlterada) {
            unset($data['password']);
        }

        $usuario->update($data);
        $usuario->contratos()->sync($usuario->todos_contratos ? [] : $contratos);

        $flash = ['success' => 'Usuário atualizado com sucesso.'];

        if ($senhaAlterada) {
            $emailEnviado = $authEmail->enviarSenhaAlteradaAdmin($usuario, $request->user()?->name);
            if (! $emailEnviado) {
                $flash = ['warning' => 'Usuário atualizado e senha alterada, porém o e-mail de aviso não foi enviado. Configure o SMTP em Configurações → E-mail.'];
            }
        }

        return redirect()->route('usuarios.show', $usuario)->with($flash);
    }

    public function destroy(User $usuario)
    {
        $usuario->delete();

        return redirect()->route('usuarios.index')->with('success', 'Usuário removido com sucesso.');
    }

    private function validatedData(Request $request, ?User $usuario = null): array
    {
        return $request->validate([
            'perfil_id' => ['nullable', 'exists:perfis,id'],
            'colaborador_id' => [
                'nullable',
                'integer',
                'exists:colaboradores,id',
                Rule::unique('users', 'colaborador_id')->ignore($usuario?->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($usuario?->id)],
            'telefone' => ['nullable', 'string', 'max:40'],
            'cargo' => ['nullable', 'string', 'max:120'],
            'status' => ['required', 'in:ativo,inativo,bloqueado'],
            'todos_contratos' => ['nullable', 'boolean'],
            'contratos' => ['nullable', 'array'],
            'contratos.*' => ['integer', 'exists:contratos,id'],
            'password' => [$usuario ? 'nullable' : 'required', 'string', 'min:6', 'confirmed'],
        ]) + [
            'todos_contratos' => $request->boolean('todos_contratos'),
            'colaborador_id' => $request->filled('colaborador_id') ? (int) $request->input('colaborador_id') : null,
        ];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Colaborador>
     */
    private function colaboradoresParaSelect()
    {
        return Colaborador::query()
            ->where('status', 'ativo')
            ->orderBy('nome')
            ->get(['id', 'nome', 'matricula', 'telefone', 'cargo']);
    }
}
