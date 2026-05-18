<?php

namespace App\Http\Controllers;

use App\Models\Colaborador;
use App\Models\SsmaTstAtividade;
use App\Models\SsmaTstRegistro;
use App\Support\SsmaTstRegistroIndicadores;
use App\Support\SsmaTstRegistroService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SsmaTstRegistroController extends Controller
{
    public function __construct(
        private readonly SsmaTstRegistroService $registroService,
    ) {}

    public function index(Request $request)
    {
        $this->authorizeView();

        $filtros = $this->filtrosIndex($request);
        $queryBase = SsmaTstRegistroIndicadores::queryFiltrada(...$filtros);
        $indicadores = new SsmaTstRegistroIndicadores($queryBase);

        $registros = (clone $queryBase)
            ->with(['colaborador', 'atividade', 'usuario'])
            ->withCount('fotos')
            ->orderByDesc('data')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $cartoes = $indicadores->cartoes();
        $serieMensal = $indicadores->serieMensal(12);
        $porAtividade = $indicadores->porAtividade();
        $topColaboradores = $indicadores->topColaboradores();

        $atividades = SsmaTstAtividade::query()->ativas()->ordenadas()->get(['id', 'nome']);
        $colaboradores = Colaborador::query()
            ->where('status', 'ativo')
            ->orderBy('nome')
            ->get(['id', 'nome', 'matricula']);

        $podeEditar = auth()->user()?->podeAcaoNoModulo('sesmt', 'editar') ?? false;
        $podeCriar = $this->usuarioPodeRegistrar();
        $filtrosAtivos = collect($filtros)->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty();

        return view('sesmt.registros-tst.registros.index', compact(
            'registros',
            'atividades',
            'colaboradores',
            'podeEditar',
            'podeCriar',
            'cartoes',
            'serieMensal',
            'porAtividade',
            'topColaboradores',
            'filtrosAtivos',
        ));
    }

    public function create()
    {
        abort_unless($this->usuarioPodeRegistrar(), 403, 'Seu perfil não pode criar registros TST.');

        return view('sesmt.registros-tst.registros.create', [
            'registro' => new SsmaTstRegistro(['data' => now()->toDateString()]),
            'atividades' => SsmaTstAtividade::query()->ativas()->ordenadas()->get(['id', 'nome']),
            'colaboradores' => Colaborador::query()
                ->where('status', 'ativo')
                ->orderBy('nome')
                ->get(['id', 'nome', 'matricula']),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless($this->usuarioPodeRegistrar(), 403, 'Seu perfil não pode criar registros TST.');

        $data = $this->registroService->validar($request, true);
        $this->registroService->criar(
            $data,
            $this->registroService->extrairArquivos($request),
            auth()->id(),
            SsmaTstRegistroService::ORIGEM_SISTEMA,
        );

        return redirect()
            ->route('sesmt.registros-tst.registros.index')
            ->with('success', 'Registro TST enviado com sucesso.');
    }

    public function show(SsmaTstRegistro $registro)
    {
        $this->authorizeView();

        $registro->load(['colaborador', 'atividade', 'usuario', 'fotos']);

        $podeEditar = auth()->user()?->podeAcaoNoModulo('sesmt', 'editar') ?? false;

        return view('sesmt.registros-tst.registros.show', compact('registro', 'podeEditar'));
    }

    public function edit(SsmaTstRegistro $registro)
    {
        $this->authorizeEdit();

        $registro->load('fotos');

        return view('sesmt.registros-tst.registros.edit', [
            'registro' => $registro,
            'atividades' => SsmaTstAtividade::query()->ativas()->ordenadas()->get(['id', 'nome']),
            'colaboradores' => Colaborador::query()
                ->where('status', 'ativo')
                ->orderBy('nome')
                ->get(['id', 'nome', 'matricula']),
        ]);
    }

    public function update(Request $request, SsmaTstRegistro $registro)
    {
        $this->authorizeEdit();

        $registro->loadCount('fotos');
        $data = $this->registroService->validar($request, false, null, (int) $registro->fotos_count);

        $novas = $this->registroService->extrairArquivos($request);
        if ($novas !== []) {
            $this->registroService->anexarFotos($registro, $novas);
        }

        $registro->update([
            'ssma_tst_atividade_id' => $data['ssma_tst_atividade_id'] ?? null,
            'data' => $data['data'],
            'colaborador_id' => $data['colaborador_id'],
            'descricao' => $data['descricao'],
        ]);

        return redirect()
            ->route('sesmt.registros-tst.registros.show', $registro)
            ->with('success', 'Registro TST atualizado.');
    }

    public function destroy(SsmaTstRegistro $registro)
    {
        $this->authorizeEdit();

        $registro->removerTodosArquivos();
        $registro->delete();

        return redirect()
            ->route('sesmt.registros-tst.registros.index')
            ->with('success', 'Registro TST removido.');
    }

    private function authorizeView(): void
    {
        abort_unless(
            auth()->user()?->podeSecaoSesmt('registros_tst'),
            403,
            'Seu perfil não tem acesso a esta área do SSMA.'
        );
    }

    private function authorizeEdit(): void
    {
        $this->authorizeView();

        abort_unless(
            auth()->user()?->podeAcaoNoModulo('sesmt', 'editar'),
            403,
            'Seu perfil não pode gerenciar registros TST.'
        );
    }

    private function usuarioPodeRegistrar(): bool
    {
        $user = auth()->user();
        if (! $user?->podeSecaoSesmt('registros_tst')) {
            return false;
        }

        return $user->podeAcaoNoModulo('sesmt', 'criar')
            || $user->podeAcaoNoModulo('sesmt', 'editar');
    }

    /**
     * @return array{0: ?string, 1: ?string, 2: ?string, 3: ?int, 4: ?int}
     */
    private function filtrosIndex(Request $request): array
    {
        return [
            $request->filled('busca') ? trim((string) $request->input('busca')) : null,
            $request->filled('data_de') ? (string) $request->input('data_de') : null,
            $request->filled('data_ate') ? (string) $request->input('data_ate') : null,
            $request->filled('atividade_id') ? (int) $request->input('atividade_id') : null,
            $request->filled('colaborador_id') ? (int) $request->input('colaborador_id') : null,
        ];
    }
}
