<?php

namespace App\Http\Controllers\Rh;

use App\Http\Controllers\Controller;
use App\Models\Beneficio;
use App\Models\BeneficioExtratoRegra;
use App\Models\Colaborador;
use App\Models\ColaboradorBeneficio;
use App\Services\Rh\BeneficioAdesaoMatrizNotificacaoService;
use App\Services\Rh\BeneficioAdesaoService;
use App\Support\Rh\BeneficioAdesaoStatus;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\Rule;

class BeneficioController extends Controller
{
    private const VINCULOS_POR_PAGINA = 25;

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

        $resumoBeneficios = [
            'total' => Beneficio::query()->count(),
            'ativos' => Beneficio::query()->where('status', 'ativo')->count(),
            'no_extrato' => BeneficioExtratoRegra::query()->where('ativo', true)->distinct('beneficio_id')->count('beneficio_id'),
            'adesao_andamento' => ColaboradorBeneficio::query()
                ->whereHas('beneficio', fn ($q) => $q->where('requer_controle_adesao', true))
                ->whereIn('status_adesao', BeneficioAdesaoStatus::emAndamento())
                ->count(),
        ];

        return view('rh.beneficios.index', compact('beneficios', 'resumoBeneficios'));
    }

    public function create()
    {
        return view('rh.beneficios.create', [
            'beneficio' => new Beneficio([
                'status' => 'ativo',
                'requer_controle_adesao' => true,
                'adesao_automatica_admissao' => false,
                'exige_formulario_colaborador' => true,
            ]),
        ]);
    }

    public function store(Request $request)
    {
        Beneficio::create($this->validatedData($request, null, $request));

        return redirect()
            ->route('rh.beneficios.index')
            ->with('success', 'Beneficio cadastrado com sucesso.');
    }

    public function show(Request $request, Beneficio $beneficio)
    {
        if ($request->boolean('debug_beneficio') && config('app.debug')) {
            dd([
                'method' => $request->method(),
                'path' => $request->path(),
                'request_uri' => $request->getRequestUri(),
                'base_url' => $request->getBaseUrl(),
                'script_name' => $request->server->get('SCRIPT_NAME'),
                'expected_route' => 'rh/beneficios/'.$beneficio->getKey(),
            ]);
        }

        if ($request->isMethod('POST')) {
            if (config('app.debug')) {
                logger()->info('beneficio.show.post', [
                    'beneficio_id' => $beneficio->id,
                    'path' => $request->path(),
                    'uri' => $request->getRequestUri(),
                    'payload' => $request->except(['_token']),
                ]);
            }

            return app(BeneficioColaboradorController::class)->store($request, $beneficio);
        }

        $beneficio->load(['colaboradores.colaborador', 'extratoRegra']);

        $ordenacao = $request->input('ordenacao', 'alfabetica');
        $busca = trim((string) $request->input('busca', ''));
        $cartao = $request->input('cartao', 'todos');

        if (! in_array($cartao, ['todos', 'entregue', 'pendente'], true)) {
            $cartao = 'todos';
        }

        $vinculosFiltrados = $this->filtrarOrdenarVinculos($beneficio->colaboradores, $busca, $ordenacao, $cartao);
        $pagina = max(1, (int) $request->input('page', 1));
        $colaboradoresVinculados = new LengthAwarePaginator(
            $vinculosFiltrados->forPage($pagina, self::VINCULOS_POR_PAGINA)->values(),
            $vinculosFiltrados->count(),
            self::VINCULOS_POR_PAGINA,
            $pagina,
            [
                'path' => route('rh.beneficios.show', $beneficio),
                'query' => $request->except('page'),
            ]
        );

        $colaboradoresDisponiveis = Colaborador::query()
            ->elegivelVinculoBeneficio()
            ->whereNotIn('id', $beneficio->colaboradores->pluck('colaborador_id'))
            ->when($busca !== '', function ($query) use ($busca) {
                $query->where(function ($query) use ($busca) {
                    $query->where('nome', 'like', "%{$busca}%")
                        ->orWhere('cargo', 'like', "%{$busca}%")
                        ->orWhere('matricula', 'like', "%{$busca}%");
                });
            })
            ->orderBy('nome')
            ->get(['id', 'nome', 'cargo', 'matricula']);

        return view('rh.beneficios.show', [
            'beneficio' => $beneficio,
            'colaboradoresDisponiveis' => $colaboradoresDisponiveis,
            'colaboradoresVinculados' => $colaboradoresVinculados,
            'ordenacao' => $ordenacao,
            'busca' => $busca,
            'cartao' => $cartao,
            'adesaoService' => app(BeneficioAdesaoService::class),
            'statusAdesaoOpcoes' => \App\Support\Rh\BeneficioAdesaoStatus::rotulos(),
            'emailMatrizDiagnostico' => $beneficio->requer_controle_adesao
                ? app(BeneficioAdesaoMatrizNotificacaoService::class)->diagnosticoEnvio()
                : null,
        ]);
    }

    private function filtrarOrdenarVinculos($vinculos, string $busca, string $ordenacao, string $cartao = 'todos')
    {
        $filtrados = $vinculos->filter(function ($vinculo) use ($busca, $cartao) {
            if ($cartao === 'entregue' && ! $vinculo->cartao_entregue) {
                return false;
            }

            if ($cartao === 'pendente' && (! $vinculo->tem_direito || $vinculo->cartao_entregue)) {
                return false;
            }

            if ($busca === '') {
                return true;
            }

            $colaborador = $vinculo->colaborador;
            if ($colaborador === null) {
                return false;
            }

            $termo = mb_strtolower($busca);

            return str_contains(mb_strtolower((string) $colaborador->nome), $termo)
                || str_contains(mb_strtolower((string) ($colaborador->cargo ?? '')), $termo)
                || str_contains(mb_strtolower((string) ($colaborador->matricula ?? '')), $termo);
        });

        if ($ordenacao === 'recentes') {
            return $filtrados->sortByDesc('id')->values();
        }

        return $filtrados
            ->sortBy(fn ($vinculo) => mb_strtolower((string) ($vinculo->colaborador->nome ?? '')))
            ->values();
    }

    public function edit(Beneficio $beneficio)
    {
        return view('rh.beneficios.edit', compact('beneficio'));
    }

    public function update(Request $request, Beneficio $beneficio)
    {
        $beneficio->update($this->validatedData($request, $beneficio, $request));

        return redirect()
            ->route('rh.beneficios.show', $beneficio)
            ->with('success', 'Beneficio atualizado com sucesso.');
    }

    public function destroy(Beneficio $beneficio)
    {
        $nome = $beneficio->nome;
        $beneficio->delete();

        return redirect()
            ->route('rh.beneficios.index')
            ->with('success', "Benefício «{$nome}» excluído com sucesso.");
    }

    private function validatedData(Request $request, ?Beneficio $beneficio = null, ?Request $flags = null): array
    {
        $flags ??= $request;
        $data = $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'tipo' => ['nullable', 'string', 'max:80'],
            'fornecedor' => ['nullable', 'string', 'max:255'],
            'codigo' => ['nullable', 'string', 'max:80', Rule::unique('beneficios', 'codigo')->ignore($beneficio)],
            'valor' => ['nullable', 'numeric', 'min:0'],
            'periodicidade' => ['nullable', 'string', 'max:80'],
            'elegibilidade' => ['nullable', 'string'],
            'status' => ['required', 'string', Rule::in(['ativo', 'inativo', 'suspenso'])],
            'requer_controle_adesao' => ['sometimes', 'boolean'],
            'adesao_automatica_admissao' => ['sometimes', 'boolean'],
            'exige_formulario_colaborador' => ['sometimes', 'boolean'],
            'descricao' => ['nullable', 'string'],
            'observacoes' => ['nullable', 'string'],
        ]);

        $data['requer_controle_adesao'] = $flags->boolean('requer_controle_adesao');
        $data['adesao_automatica_admissao'] = $flags->boolean('adesao_automatica_admissao');
        $data['exige_formulario_colaborador'] = $flags->boolean('exige_formulario_colaborador');

        return $data;
    }
}
