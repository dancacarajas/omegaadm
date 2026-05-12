<?php

namespace App\Http\Controllers;

use App\Models\SsmaRisco;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SsmaRiscoController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeView();

        $query = SsmaRisco::query()
            ->filtrar(
                $request->filled('busca') ? (string) $request->input('busca') : null,
                $request->filled('status') ? (string) $request->input('status') : null,
                $request->filled('categoria') ? (string) $request->input('categoria') : null,
                $request->filled('classificacao') ? (string) $request->input('classificacao') : null,
            );

        $riscos = $query
            ->orderByRaw("CASE classificacao_final WHEN 'critico' THEN 1 WHEN 'alto' THEN 2 WHEN 'medio' THEN 3 WHEN 'baixo' THEN 4 ELSE 5 END")
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $painel = $this->montarPainel();

        $podeEditar = auth()->user()?->podeAcaoNoModulo('sesmt', 'editar') ?? false;

        return view('sesmt.riscos.index', compact('riscos', 'painel', 'podeEditar'));
    }

    public function create()
    {
        $this->authorizeEdit();

        return view('sesmt.riscos.create', [
            'risco' => new SsmaRisco([
                'status' => 'identificado',
                'probabilidade' => 3,
                'severidade' => 3,
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeEdit();

        $data = $this->validated($request);
        $data['evidencia_path'] = $this->storeEvidencia($request, null);

        SsmaRisco::create($data);

        return redirect()
            ->route('sesmt.riscos.index')
            ->with('success', 'Risco cadastrado.');
    }

    public function edit(SsmaRisco $risco)
    {
        $this->authorizeEdit();

        return view('sesmt.riscos.edit', compact('risco'));
    }

    public function update(Request $request, SsmaRisco $risco)
    {
        $this->authorizeEdit();

        $data = $this->validated($request);
        $path = $this->storeEvidencia($request, $risco->evidencia_path);
        if ($path !== null) {
            $data['evidencia_path'] = $path;
        }

        $risco->update($data);

        return redirect()
            ->route('sesmt.riscos.index')
            ->with('success', 'Risco atualizado.');
    }

    public function destroy(SsmaRisco $risco)
    {
        $this->authorizeEdit();

        if ($risco->evidencia_path) {
            Storage::disk('public')->delete($risco->evidencia_path);
        }
        $risco->delete();

        return redirect()
            ->route('sesmt.riscos.index')
            ->with('success', 'Risco removido.');
    }

    /**
     * @return array<string, mixed>
     */
    private function montarPainel(): array
    {
        $base = SsmaRisco::query()->where('status', '!=', 'cancelado');

        $matriz = [];
        for ($p = 1; $p <= 5; $p++) {
            for ($s = 1; $s <= 5; $s++) {
                $matriz[$p][$s] = 0;
            }
        }
        $celulas = (clone $base)
            ->select('probabilidade', 'severidade', DB::raw('COUNT(*) as total'))
            ->groupBy('probabilidade', 'severidade')
            ->get();
        foreach ($celulas as $row) {
            $p = (int) $row->probabilidade;
            $s = (int) $row->severidade;
            if ($p >= 1 && $p <= 5 && $s >= 1 && $s <= 5) {
                $matriz[$p][$s] = (int) $row->total;
            }
        }

        $criticos = (clone $base)->where('classificacao_final', 'critico')->count();

        $porArea = (clone $base)
            ->select('area_local', DB::raw('COUNT(*) as total'))
            ->groupBy('area_local')
            ->orderByDesc('total')
            ->limit(12)
            ->get()
            ->map(function ($row) {
                return [
                    'rotulo' => $row->area_local !== null && $row->area_local !== '' ? $row->area_local : '(Sem área)',
                    'total' => (int) $row->total,
                ];
            });

        $porCategoria = (clone $base)
            ->select('categoria', DB::raw('COUNT(*) as total'))
            ->groupBy('categoria')
            ->get()
            ->keyBy('categoria');

        $evolucao = [];
        $inicio = Carbon::now()->startOfMonth()->subMonths(11);
        for ($i = 0; $i < 12; $i++) {
            $mes = $inicio->copy()->addMonths($i);
            $inicioMes = $mes->copy()->startOfMonth()->toDateString();
            $fimMes = $mes->copy()->endOfMonth()->toDateString();
            $qtd = SsmaRisco::query()
                ->where('status', 'tratado')
                ->whereNotNull('tratado_em')
                ->whereBetween('tratado_em', [$inicioMes, $fimMes])
                ->count();
            $evolucao[] = [
                'rotulo' => $mes->translatedFormat('M/y'),
                'total' => $qtd,
            ];
        }

        return [
            'matriz' => $matriz,
            'criticos' => $criticos,
            'por_area' => $porArea,
            'por_categoria' => $porCategoria,
            'evolucao' => $evolucao,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'risco_identificado' => ['required', 'string', 'max:20000'],
            'area_local' => ['nullable', 'string', 'max:255'],
            'atividade' => ['required', 'string', 'max:20000'],
            'categoria' => ['required', 'in:'.implode(',', array_keys(SsmaRisco::CATEGORIAS))],
            'probabilidade' => ['required', 'integer', 'min:1', 'max:5'],
            'severidade' => ['required', 'integer', 'min:1', 'max:5'],
            'medida_controle_existente' => ['nullable', 'string', 'max:20000'],
            'medida_adicional_necessaria' => ['nullable', 'string', 'max:20000'],
            'responsavel' => ['nullable', 'string', 'max:255'],
            'prazo' => ['nullable', 'date'],
            'status' => ['required', 'in:'.implode(',', array_keys(SsmaRisco::STATUS))],
            'evidencia' => ['nullable', 'file', 'max:10240', 'mimes:jpeg,jpg,png,gif,webp,pdf'],
        ]);

        unset($data['evidencia']);

        return $data;
    }

    private function storeEvidencia(Request $request, ?string $previous): ?string
    {
        if (! $request->hasFile('evidencia')) {
            return null;
        }

        if ($previous) {
            Storage::disk('public')->delete($previous);
        }

        return $request->file('evidencia')->store('ssma/riscos/evidencias', 'public');
    }

    private function authorizeView(): void
    {
        abort_unless(
            auth()->user()?->podeSecaoSesmt('gestao_riscos'),
            403,
            'Seu perfil não tem acesso a esta área do SSMA.'
        );
    }

    private function authorizeEdit(): void
    {
        abort_unless(
            auth()->user()?->podeAcaoNoModulo('sesmt', 'editar'),
            403,
            'Seu perfil não pode gerenciar riscos.'
        );
    }
}
