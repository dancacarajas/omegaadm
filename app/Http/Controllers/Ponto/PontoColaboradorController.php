<?php

namespace App\Http\Controllers\Ponto;

use App\Http\Controllers\Controller;
use App\Models\Colaborador;
use App\Support\EscalaPontoRegras;
use App\Support\PontoColaboradorService;
use Illuminate\Http\Request;

class PontoColaboradorController extends Controller
{
    public function __construct(
        private readonly PontoColaboradorService $ponto
    ) {}

    public function showIdentificar()
    {
        if (session('ponto_colaborador_id')) {
            return redirect()->route('ponto.index');
        }

        return view('ponto.identificar');
    }

    public function identificar(Request $request)
    {
        $dados = $request->validate([
            'matricula' => ['required', 'string', 'max:80'],
            'cpf' => ['required', 'string', 'max:20'],
        ]);

        $colaborador = $this->ponto->encontrarColaboradorAtivo(
            $dados['matricula'],
            $dados['cpf']
        );

        if (! $colaborador) {
            return back()
                ->withInput()
                ->withErrors([
                    'identificacao' => 'Matrícula ou CPF não encontrados, ou colaborador inativo.',
                ]);
        }

        session(['ponto_colaborador_id' => $colaborador->id]);

        return redirect()
            ->route('ponto.index')
            ->with('success', 'Olá, '.$colaborador->nome.'!');
    }

    public function index(Request $request)
    {
        /** @var Colaborador $colaborador */
        $colaborador = $request->attributes->get('colaborador_ponto');
        $colaborador->loadMissing('horarioEscala.dias');

        $registro = $this->ponto->registroDoDia($colaborador);
        $proxima = $this->ponto->proximaBatida($registro);
        $batidas = $this->ponto->resumoBatidas($registro, $colaborador);

        $avaliacao = app(EscalaPontoRegras::class)->avaliarMarcacao(
            $colaborador,
            $registro->data,
            true
        );

        $diaEscala = $colaborador->horarioEscalaDiaNaData($registro->data);

        return view('ponto.index', [
            'colaborador' => $colaborador,
            'registro' => $registro,
            'batidas' => $batidas,
            'proximaBatida' => $proxima,
            'podeRegistrar' => $avaliacao['permitido'] && $proxima !== null,
            'bloqueioMotivo' => $avaliacao['permitido'] ? null : $avaliacao['motivo'],
            'diaEscala' => $diaEscala,
        ]);
    }

    public function registrar(Request $request)
    {
        /** @var Colaborador $colaborador */
        $colaborador = $request->attributes->get('colaborador_ponto');

        try {
            $resultado = $this->ponto->registrarProximaBatida($colaborador);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()
                ->route('ponto.index')
                ->withErrors($e->errors());
        }

        return redirect()
            ->route('ponto.index')
            ->with('success', $resultado['mensagem']);
    }

    public function sair()
    {
        session()->forget('ponto_colaborador_id');

        return redirect()
            ->route('ponto.identificar')
            ->with('success', 'Você saiu do registro de ponto.');
    }
}
