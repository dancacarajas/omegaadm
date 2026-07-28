<?php

namespace App\Http\Controllers\PresencaObra;

use App\Http\Controllers\Controller;
use App\Models\Colaborador;
use App\Models\MedicaoPresencaObraAnexo;
use App\Support\PontoColaboradorService;
use App\Support\PresencaObraService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PresencaObraColaboradorController extends Controller
{
    public function __construct(
        private readonly PontoColaboradorService $ponto,
        private readonly PresencaObraService $presenca,
    ) {}

    public function showIdentificar(): RedirectResponse|View
    {
        if (session('presenca_obra_colaborador_id')) {
            return redirect()->route('presenca-obra.index');
        }

        return view('presenca-obra.identificar');
    }

    public function modoOffline(): View
    {
        return view('presenca-obra.modo-offline');
    }

    public function identificar(Request $request): RedirectResponse|JsonResponse
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
            if ($request->wantsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Matrícula ou CPF não encontrados, ou colaborador inativo.',
                ], 422);
            }

            return back()
                ->withInput()
                ->withErrors([
                    'identificacao' => 'Matrícula ou CPF não encontrados, ou colaborador inativo.',
                ]);
        }

        if (! $this->presenca->podeConfirmar($colaborador)) {
            if ($request->wantsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Seu acesso para confirmar presença na obra não está liberado. Solicite liberação ao RH ou à Medição.',
                ], 422);
            }

            return back()
                ->withInput()
                ->withErrors([
                    'identificacao' => 'Seu acesso para confirmar presença na obra não está liberado. Solicite liberação ao RH ou à Medição.',
                ]);
        }

        session(['presenca_obra_colaborador_id' => $colaborador->id]);

        $redirect = $this->redirectAposLogin($request);

        if ($request->wantsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Olá, '.$colaborador->nome.'! Confirme quem subiu para trabalhar.',
                'confirmador' => [
                    'id' => $colaborador->id,
                    'nome' => $colaborador->nome,
                    'matricula' => $colaborador->matricula,
                ],
                'cache' => $this->cachePayload($colaborador),
                'redirect' => $redirect,
            ]);
        }

        return redirect($redirect)
            ->with('success', 'Olá, '.$colaborador->nome.'! Confirme quem subiu para trabalhar.')
            ->with('presenca_obra_offline_bootstrap', [
                'matricula' => $dados['matricula'],
                'cpf' => $dados['cpf'],
                'confirmador' => [
                    'id' => $colaborador->id,
                    'nome' => $colaborador->nome,
                    'matricula' => $colaborador->matricula,
                ],
            ]);
    }

    public function index(Request $request): View
    {
        /** @var Colaborador $confirmador */
        $confirmador = $request->attributes->get('colaborador_presenca_obra');

        $data = $request->input('data', now()->toDateString());
        try {
            $data = Carbon::parse($data)->toDateString();
        } catch (\Throwable) {
            $data = now()->toDateString();
        }

        $busca = $request->input('busca');
        $centroCusto = $request->input('centro_custo');

        $colaboradores = $this->presenca->colaboradoresParaConfirmacao(
            is_string($busca) ? $busca : null,
            is_string($centroCusto) ? $centroCusto : null,
        );
        $statusDia = $this->presenca->statusDoDia($data);
        $registrosDia = $this->presenca->registrosDoDia($data);

        $bootstrap = session('presenca_obra_offline_bootstrap');
        if (is_array($bootstrap)) {
            $bootstrap['cache'] = $this->cachePayload($confirmador, $data);
        }

        return view('presenca-obra.index', [
            'confirmador' => $confirmador,
            'colaboradores' => $colaboradores,
            'statusDia' => $statusDia,
            'registrosDia' => $registrosDia,
            'data' => $data,
            'busca' => is_string($busca) ? $busca : '',
            'centroCusto' => is_string($centroCusto) ? $centroCusto : '',
            'centrosCusto' => $this->presenca->centrosCustoAtivos(),
            'totais' => [
                'lista' => $colaboradores->count(),
                'presentes' => collect($statusDia)->filter(fn ($s) => $s === 'presente')->count(),
                'ausentes' => collect($statusDia)->filter(fn ($s) => $s === 'ausente')->count(),
            ],
            'pageCachePayload' => $this->cachePayload($confirmador, $data),
            'offlineBootstrap' => is_array($bootstrap) ? $bootstrap : null,
        ]);
    }

    public function dashboard(Request $request): View|RedirectResponse
    {
        /** @var Colaborador $confirmador */
        $confirmador = $request->attributes->get('colaborador_presenca_obra');

        [$dataInicio, $dataFim] = $this->parsePeriodoDashboard($request);
        $centroCusto = $request->input('centro_custo');

        try {
            $painel = $this->presenca->dadosDashboardPainel(
                $dataInicio,
                $dataFim,
                is_string($centroCusto) && trim($centroCusto) !== '' ? $centroCusto : null,
            );
        } catch (ValidationException $e) {
            return redirect()
                ->route('presenca-obra.dashboard', $request->only(['data_inicio', 'data_fim', 'centro_custo']))
                ->withErrors($e->errors());
        }

        return view('presenca-obra.dashboard', array_merge($painel, [
            'confirmador' => $confirmador,
            'urlFiltro' => route('presenca-obra.dashboard'),
        ]));
    }

    public function salvarJustificativa(Request $request): JsonResponse
    {
        /** @var Colaborador $confirmador */
        $confirmador = $request->attributes->get('colaborador_presenca_obra');

        $validated = $request->validate([
            'data' => ['required', 'date'],
            'colaborador_id' => ['required', 'integer', 'exists:colaboradores,id'],
            'observacao' => ['nullable', 'string', 'max:500'],
            'status' => ['nullable', 'in:presente,ausente'],
            'anexos' => ['nullable', 'array'],
            'anexos.*' => ['file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx'],
        ]);

        $registro = $this->presenca->salvarJustificativa(
            $confirmador,
            $validated['data'],
            (int) $validated['colaborador_id'],
            $validated['observacao'] ?? null,
            $validated['status'] ?? null,
            $request->file('anexos', []) ?? [],
        );

        $anexos = $registro->anexos->map(fn (MedicaoPresencaObraAnexo $anexo) => [
            'id' => $anexo->id,
            'nome' => $anexo->nome_original,
            'url' => route('presenca-obra.anexos.visualizar', $anexo),
        ])->values()->all();

        return response()->json([
            'ok' => true,
            'message' => 'Justificativa salva com sucesso.',
            'observacao' => $registro->observacao,
            'anexos' => $anexos,
            'anexos_count' => (int) $registro->anexos_count,
        ]);
    }

    public function salvar(Request $request): RedirectResponse|JsonResponse
    {
        /** @var Colaborador $confirmador */
        $confirmador = $request->attributes->get('colaborador_presenca_obra');

        $validated = $request->validate([
            'data' => ['required', 'date'],
            'itens' => ['required', 'array', 'min:1'],
            'itens.*.status' => ['nullable', 'in:presente,ausente'],
            'itens.*.observacao' => ['nullable', 'string', 'max:500'],
            'anexos' => ['nullable', 'array'],
            'anexos.*' => ['nullable', 'array'],
            'anexos.*.*' => ['file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx'],
        ]);

        $itens = collect($validated['itens'])
            ->filter(fn ($row) => is_array($row) && ! empty($row['status']))
            ->all();

        if ($itens === []) {
            $mensagemErro = 'Marque ao menos um colaborador como presente ou ausente.';

            if ($request->wantsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => $mensagemErro,
                ], 422);
            }

            return back()
                ->withInput()
                ->withErrors(['itens' => $mensagemErro]);
        }

        $salvos = $this->presenca->salvarConfirmacao(
            $confirmador,
            $validated['data'],
            $itens,
        );

        $anexosSalvos = 0;
        $anexosPorColaborador = $request->file('anexos', []);
        if (is_array($anexosPorColaborador) && $anexosPorColaborador !== []) {
            $anexosSalvos = $this->presenca->salvarAnexos(
                $validated['data'],
                $anexosPorColaborador,
            );
        }

        $mensagem = "Presença confirmada para {$salvos} colaborador(es).";
        if ($anexosSalvos > 0) {
            $mensagem .= " {$anexosSalvos} documento(s) anexado(s).";
        }
        $redirectParams = [
            'data' => Carbon::parse($validated['data'])->toDateString(),
            'busca' => $request->input('busca'),
            'centro_custo' => $request->input('centro_custo'),
        ];

        if ($request->wantsJson()) {
            return response()->json([
                'ok' => true,
                'salvos' => $salvos,
                'message' => $mensagem,
            ]);
        }

        return redirect()
            ->route('presenca-obra.index', $redirectParams)
            ->with('success', $mensagem);
    }

    public function visualizarAnexo(MedicaoPresencaObraAnexo $anexo): StreamedResponse
    {
        $anexo->loadMissing('registro');
        abort_if($anexo->registro === null, 404);
        abort_unless(Storage::disk('public')->exists($anexo->caminho), 404);

        return Storage::disk('public')->response(
            $anexo->caminho,
            $anexo->nome_original,
            ['Content-Disposition' => 'inline; filename="'.str_replace('"', '', $anexo->nome_original).'"'],
        );
    }

    public function downloadAnexo(Request $request, MedicaoPresencaObraAnexo $anexo): StreamedResponse
    {
        $anexo->loadMissing('registro');
        $registro = $anexo->registro;
        abort_if($registro === null, 404);
        abort_unless(Storage::disk('public')->exists($anexo->caminho), 404);

        return Storage::disk('public')->download($anexo->caminho, $anexo->nome_original);
    }

    public function sair(): RedirectResponse
    {
        session()->forget('presenca_obra_colaborador_id');

        return redirect()
            ->route('medicao.presenca-obra.index')
            ->with('success', 'Você saiu da gestão de presenças.');
    }

    private function redirectAposLogin(Request $request): string
    {
        $redirect = $request->input('redirect');
        if (is_string($redirect) && str_starts_with($redirect, '/') && ! str_starts_with($redirect, '//')) {
            return $redirect;
        }

        return route('presenca-obra.index', absolute: false);
    }

    private function parsePeriodoDashboard(Request $request): array
    {
        $fim = $request->input('data_fim', now()->toDateString());
        $inicio = $request->input('data_inicio', now()->startOfMonth()->toDateString());

        try {
            $fim = Carbon::parse($fim)->toDateString();
            $inicio = Carbon::parse($inicio)->toDateString();
        } catch (\Throwable) {
            $fim = now()->toDateString();
            $inicio = now()->startOfMonth()->toDateString();
        }

        return [$inicio, $fim];
    }

    /**
     * @return array<string, mixed>
     */
    private function cachePayload(Colaborador $confirmador, ?string $data = null): array
    {
        $data ??= now()->toDateString();

        return [
            'confirmador' => [
                'id' => $confirmador->id,
                'nome' => $confirmador->nome,
                'matricula' => $confirmador->matricula,
            ],
            'data' => $data,
            'busca' => '',
            'centro_custo' => '',
            'colaboradores' => $this->presenca->colaboradoresParaConfirmacao()->map(fn ($c) => [
                'id' => $c->id,
                'nome' => $c->nome,
                'matricula' => $c->matricula,
                'cargo' => $c->cargo,
                'centro_custo' => $c->centro_custo,
            ])->values()->all(),
            'status_dia' => $this->presenca->statusDoDia($data),
            'centros_custo' => $this->presenca->centrosCustoAtivos(),
        ];
    }
}
