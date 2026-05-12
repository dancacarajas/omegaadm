<?php

namespace App\Http\Controllers;

use App\Models\SsmaAmbientalRegistro;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
class SsmaMeioAmbienteController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeView();

        $competenciaFiltro = (string) $request->query('competencia', now()->format('Y-m'));
        $historico = $request->boolean('historico');

        $busca = $request->filled('busca') ? trim((string) $request->input('busca')) : null;

        $registros = SsmaAmbientalRegistro::query()
            ->when(
                $historico,
                fn ($q) => $q->filtrar(null, $busca),
                fn ($q) => $q->filtrar($competenciaFiltro !== '' ? $competenciaFiltro : null, $busca),
            )
            ->orderByDesc('competencia')
            ->orderByDesc('id')
            ->paginate(12)
            ->withQueryString();

        $mesRef = Carbon::createFromFormat('Y-m', $competenciaFiltro)->startOfMonth();
        $registroMes = SsmaAmbientalRegistro::query()
            ->whereDate('competencia', $mesRef->toDateString())
            ->first();

        $painel = $this->montarPainelDiretoria($registroMes);
        $tendencia = $this->montarTendenciaMensal();

        $podeEditar = auth()->user()?->podeAcaoNoModulo('sesmt', 'editar') ?? false;

        return view('sesmt.meio-ambiente.index', compact(
            'registros',
            'competenciaFiltro',
            'historico',
            'registroMes',
            'painel',
            'tendencia',
            'podeEditar',
        ));
    }

    public function create(Request $request)
    {
        $this->authorizeEdit();

        $competenciaPref = (string) $request->query('competencia', now()->format('Y-m'));
        try {
            $ck = Carbon::createFromFormat('Y-m', $competenciaPref)->startOfMonth()->toDateString();
            $existente = SsmaAmbientalRegistro::query()->whereDate('competencia', $ck)->first();
            if ($existente) {
                return redirect()->route('sesmt.meio-ambiente.edit', $existente);
            }
        } catch (\Throwable) {
            $competenciaPref = now()->format('Y-m');
        }

        return view('sesmt.meio-ambiente.create', [
            'registro' => new SsmaAmbientalRegistro([
                'competencia' => Carbon::createFromFormat('Y-m', $competenciaPref)->startOfMonth(),
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeEdit();

        $data = $this->validated($request);
        $data['evidencia_destinacao_path'] = $this->storeEvidencia($request, null);

        SsmaAmbientalRegistro::create($data);

        return redirect()
            ->route('sesmt.meio-ambiente.index', ['competencia' => $request->input('competencia')])
            ->with('success', 'Registro ambiental do mês salvo.');
    }

    public function edit(SsmaAmbientalRegistro $ambiental)
    {
        $this->authorizeEdit();

        return view('sesmt.meio-ambiente.edit', compact('ambiental'));
    }

    public function update(Request $request, SsmaAmbientalRegistro $ambiental)
    {
        $this->authorizeEdit();

        $data = $this->validated($request, $ambiental);
        $path = $this->storeEvidencia($request, $ambiental->evidencia_destinacao_path);
        if ($path !== null) {
            $data['evidencia_destinacao_path'] = $path;
        }

        $ambiental->update($data);

        return redirect()
            ->route('sesmt.meio-ambiente.index', ['competencia' => $request->input('competencia')])
            ->with('success', 'Registro ambiental atualizado.');
    }

    public function destroy(SsmaAmbientalRegistro $ambiental)
    {
        $this->authorizeEdit();

        if ($ambiental->evidencia_destinacao_path) {
            Storage::disk('public')->delete($ambiental->evidencia_destinacao_path);
        }
        $ambiental->delete();

        return redirect()
            ->route('sesmt.meio-ambiente.index')
            ->with('success', 'Registro ambiental removido.');
    }

    /**
     * @return array<string, mixed>
     */
    private function montarPainelDiretoria(?SsmaAmbientalRegistro $registroMes): array
    {
        if ($registroMes === null) {
            return [
                'residuos_destinados_corretamente' => null,
                'ocorrencias_ambientais' => null,
                'vazamentos_derramamentos' => null,
                'acoes_ambientais_concluidas' => null,
                'nao_conformidades_ambientais' => null,
                'tem_registro' => false,
            ];
        }

        return [
            'residuos_destinados_corretamente' => $registroMes->quantidade_residuos_destinados_corretamente,
            'ocorrencias_ambientais' => $registroMes->ocorrencias_ambientais,
            'vazamentos_derramamentos' => $registroMes->vazamentos_derramamentos,
            'acoes_ambientais_concluidas' => $registroMes->acoes_ambientais_concluidas,
            'nao_conformidades_ambientais' => $registroMes->nao_conformidades_ambientais,
            'tem_registro' => true,
        ];
    }

    /**
     * @return list<array{rotulo: string, residuos: float|int, ocorrencias: int, vazamentos: int, acoes: int, nc: int}>
     */
    private function montarTendenciaMensal(): array
    {
        $inicio = Carbon::now()->startOfMonth()->subMonths(11);
        $serie = [];

        for ($i = 0; $i < 12; $i++) {
            $mes = $inicio->copy()->addMonths($i);
            $r = SsmaAmbientalRegistro::query()
                ->whereDate('competencia', $mes->copy()->startOfMonth()->toDateString())
                ->first();

            $serie[] = [
                'rotulo' => $mes->translatedFormat('M/y'),
                'residuos' => $r ? (float) ($r->quantidade_residuos_destinados_corretamente ?? 0) : 0.0,
                'ocorrencias' => $r ? (int) $r->ocorrencias_ambientais : 0,
                'vazamentos' => $r ? (int) $r->vazamentos_derramamentos : 0,
                'acoes' => $r ? (int) $r->acoes_ambientais_concluidas : 0,
                'nc' => $r ? (int) $r->nao_conformidades_ambientais : 0,
            ];
        }

        return $serie;
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?SsmaAmbientalRegistro $existente = null): array
    {
        $compNormalizada = Carbon::createFromFormat('Y-m', (string) $request->input('competencia'))->startOfMonth()->toDateString();

        $data = $request->validate([
            'competencia' => [
                'required',
                'date_format:Y-m',
                function (string $attribute, mixed $value, \Closure $fail) use ($existente, $compNormalizada): void {
                    $q = SsmaAmbientalRegistro::query()->whereDate('competencia', $compNormalizada);
                    if ($existente !== null) {
                        $q->where('id', '!=', $existente->id);
                    }
                    if ($q->exists()) {
                        $fail('Já existe registro ambiental para esta competência.');
                    }
                },
            ],
            'residuos_gerados' => ['nullable', 'string', 'max:20000'],
            'residuos_destinados' => ['nullable', 'string', 'max:20000'],
            'quantidade_residuos_destinados_corretamente' => ['nullable', 'numeric', 'min:0'],
            'coleta_seletiva' => ['nullable', 'string', 'max:20000'],
            'vazamentos_derramamentos' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'produtos_quimicos' => ['nullable', 'string', 'max:20000'],
            'armazenamento_residuos' => ['nullable', 'string', 'max:20000'],
            'consumo_agua_m3' => ['nullable', 'numeric', 'min:0'],
            'consumo_energia_kwh' => ['nullable', 'numeric', 'min:0'],
            'ocorrencias_ambientais' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'licencas_condicionantes' => ['nullable', 'string', 'max:20000'],
            'acoes_ambientais_realizadas' => ['nullable', 'string', 'max:20000'],
            'acoes_ambientais_concluidas' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'campanhas_ambientais' => ['nullable', 'string', 'max:20000'],
            'nao_conformidades_ambientais' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'observacoes' => ['nullable', 'string', 'max:20000'],
            'evidencia_destinacao' => ['nullable', 'file', 'max:10240', 'mimes:jpeg,jpg,png,gif,webp,pdf'],
        ]);

        unset($data['evidencia_destinacao']);
        $data['competencia'] = $compNormalizada;

        return $data;
    }

    private function storeEvidencia(Request $request, ?string $previous): ?string
    {
        if (! $request->hasFile('evidencia_destinacao')) {
            return null;
        }

        if ($previous) {
            Storage::disk('public')->delete($previous);
        }

        return $request->file('evidencia_destinacao')->store('ssma/meio-ambiente/evidencias', 'public');
    }

    private function authorizeView(): void
    {
        abort_unless(
            auth()->user()?->podeSecaoSesmt('meio_ambiente'),
            403,
            'Seu perfil não tem acesso a esta área do SSMA.'
        );
    }

    private function authorizeEdit(): void
    {
        abort_unless(
            auth()->user()?->podeAcaoNoModulo('sesmt', 'editar'),
            403,
            'Seu perfil não pode gerenciar indicadores ambientais.'
        );
    }
}
