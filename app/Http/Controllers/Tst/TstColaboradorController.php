<?php

namespace App\Http\Controllers\Tst;

use App\Http\Controllers\Controller;
use App\Models\SsmaTstAtividade;
use App\Models\SsmaTstRegistro;
use App\Support\PontoColaboradorService;
use App\Support\SsmaTstRegistroService;
use App\Support\TstColaboradorAcesso;
use Illuminate\Http\Request;

class TstColaboradorController extends Controller
{
    public function __construct(
        private readonly PontoColaboradorService $ponto,
        private readonly SsmaTstRegistroService $registros,
    ) {}

    public function showIdentificar()
    {
        if (session('tst_colaborador_id')) {
            return redirect()->route('tst-campo.index');
        }

        return view('tst-campo.identificar');
    }

    public function identificar(Request $request)
    {
        $dados = $request->validate([
            'matricula' => ['required', 'string', 'max:80'],
            'cpf' => ['required', 'string', 'max:20'],
        ]);

        $colaborador = $this->ponto->encontrarColaboradorAtivo(
            $dados['matricula'],
            $dados['cpf'],
        );

        if (! $colaborador) {
            return back()
                ->withInput()
                ->withErrors([
                    'identificacao' => 'Matrícula ou CPF não encontrados, ou colaborador inativo.',
                ]);
        }

        session(['tst_colaborador_id' => $colaborador->id]);

        return redirect()
            ->route('tst-campo.index')
            ->with('success', 'Olá, '.$colaborador->nome.'!');
    }

    public function index(Request $request)
    {
        /** @var \App\Models\Colaborador $colaborador */
        $colaborador = $request->attributes->get('colaborador_tst');

        $atividades = SsmaTstAtividade::query()
            ->paraAppDoColaborador($colaborador)
            ->ordenadas()
            ->get(['id', 'nome']);

        $recentes = SsmaTstRegistro::query()
            ->with('atividade')
            ->withCount('fotos')
            ->where('colaborador_id', $colaborador->id)
            ->orderByDesc('data')
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        return view('tst-campo.index', [
            'colaborador' => $colaborador,
            'atividades' => $atividades,
            'todasAtividadesApp' => TstColaboradorAcesso::veTodasAtividadesNoApp($colaborador),
            'recentes' => $recentes,
            'dataHoje' => now()->toDateString(),
        ]);
    }

    public function store(Request $request)
    {
        /** @var \App\Models\Colaborador $colaborador */
        $colaborador = $request->attributes->get('colaborador_tst');

        $data = $this->registros->validar($request, true, $colaborador->id);

        $this->registros->criar(
            $data,
            $this->registros->extrairArquivos($request),
            null,
            SsmaTstRegistroService::ORIGEM_APP_COLABORADOR,
        );

        return redirect()
            ->route('tst-campo.index')
            ->with('success', 'Registro TST enviado com sucesso. Obrigado!');
    }

    public function sair()
    {
        session()->forget('tst_colaborador_id');

        return redirect()
            ->route('tst-campo.identificar')
            ->with('success', 'Você saiu do registro TST.');
    }
}
