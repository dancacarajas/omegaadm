<?php

namespace App\Http\Controllers;

use App\Models\MedicaoPresencaObraAnexo;
use App\Models\MedicaoPresencaObraRegistro;
use App\Support\ContratoAccess;
use App\Support\Medicao\PresencaObraFolhaExcelExport;
use App\Support\PresencaObraService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MedicaoPresencaObraController extends Controller
{
    public function __construct(
        private readonly PresencaObraService $presenca,
    ) {}

    public function portal(Request $request)
    {
        if (! session('presenca_obra_colaborador_id')) {
            return view('medicao.presenca-obra.login');
        }

        return view('medicao.presenca-obra.portal', $this->dadosConsulta($request, [
            'urlFiltro' => route('medicao.presenca-obra.index'),
            'podeExportar' => auth()->check(),
        ]));
    }

    public function dashboard(Request $request)
    {
        if (! session('presenca_obra_colaborador_id')) {
            return view('medicao.presenca-obra.login');
        }

        $fim = $request->input('data_fim', now()->toDateString());
        $inicio = $request->input('data_inicio', now()->startOfMonth()->toDateString());
        try {
            $fim = Carbon::parse($fim)->toDateString();
            $inicio = Carbon::parse($inicio)->toDateString();
        } catch (\Throwable) {
            $fim = now()->toDateString();
            $inicio = now()->startOfMonth()->toDateString();
        }

        $centroCusto = $request->input('centro_custo');

        try {
            $painel = $this->presenca->dadosDashboardPainel(
                $inicio,
                $fim,
                is_string($centroCusto) && trim($centroCusto) !== '' ? $centroCusto : null,
            );
        } catch (ValidationException $e) {
            return redirect()
                ->route('medicao.presenca-obra.dashboard', $request->only(['data_inicio', 'data_fim', 'centro_custo']))
                ->withErrors($e->errors());
        }

        return view('medicao.presenca-obra.dashboard', array_merge($painel, [
            'urlFiltro' => route('medicao.presenca-obra.dashboard'),
        ]));
    }

    public function consulta(Request $request)
    {
        return view('medicao.presenca-obra.index', $this->dadosConsulta($request, [
            'urlFiltro' => route('medicao.presenca-obra.consulta'),
            'podeExportar' => true,
            'urlPublica' => route('presenca-obra.identificar'),
        ]));
    }

    public function exportarExcel(Request $request)
    {
        $validated = $request->validate([
            'data_inicio' => ['required', 'date'],
            'data_fim' => ['required', 'date', 'after_or_equal:data_inicio'],
            'centro_custo' => ['nullable', 'string', 'max:120'],
        ]);

        try {
            $dados = $this->presenca->dadosParaFolhaExport(
                $validated['data_inicio'],
                $validated['data_fim'],
                $validated['centro_custo'] ?? null,
            );
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        $inicio = Carbon::parse($validated['data_inicio']);
        $fim = Carbon::parse($validated['data_fim']);
        $centroCusto = isset($validated['centro_custo']) && trim((string) $validated['centro_custo']) !== ''
            ? (string) $validated['centro_custo']
            : null;

        return PresencaObraFolhaExcelExport::download(
            $dados['colaboradores'],
            $dados['dias'],
            $dados['marcacoes'],
            $inicio,
            $fim,
            $centroCusto,
        );
    }

    public function visualizarAnexo(Request $request, MedicaoPresencaObraAnexo $anexo): StreamedResponse
    {
        abort_unless(
            auth()->check() || session('presenca_obra_colaborador_id'),
            403,
        );

        $anexo->loadMissing('registro');
        abort_if($anexo->registro === null, 404);
        abort_unless(Storage::disk('public')->exists($anexo->caminho), 404);

        return Storage::disk('public')->response(
            $anexo->caminho,
            $anexo->nome_original,
            ['Content-Disposition' => 'inline; filename="'.str_replace('"', '', $anexo->nome_original).'"'],
        );
    }

    /**
     * @param  array<string, mixed>  $extras
     * @return array<string, mixed>
     */
    private function dadosConsulta(Request $request, array $extras = []): array
    {
        $data = $request->input('data', now()->toDateString());
        try {
            $data = Carbon::parse($data)->toDateString();
        } catch (\Throwable) {
            $data = now()->toDateString();
        }

        $status = $request->input('status');
        $centroCusto = $request->input('centro_custo');
        $busca = $request->input('busca');

        $query = MedicaoPresencaObraRegistro::query()
            ->with([
                'colaborador:id,nome,matricula,cargo,centro_custo',
                'confirmadoPor:id,nome,matricula',
                'anexos:id,registro_id,nome_original,caminho,mime,tamanho',
            ])
            ->withCount('anexos')
            ->whereDate('data', $data)
            ->latest('confirmado_em')
            ->latest('id');

        if (in_array($status, [
            MedicaoPresencaObraRegistro::STATUS_PRESENTE,
            MedicaoPresencaObraRegistro::STATUS_AUSENTE,
        ], true)) {
            $query->where('status', $status);
        }

        if (is_string($centroCusto) && trim($centroCusto) !== '') {
            $query->where('centro_custo', $centroCusto);
        }

        if (is_string($busca) && trim($busca) !== '') {
            $termo = '%'.trim($busca).'%';
            $query->whereHas('colaborador', function ($q) use ($termo) {
                $q->where('nome', 'like', $termo)
                    ->orWhere('matricula', 'like', $termo);
            });
        }

        if (ContratoAccess::shouldRestrict()) {
            $valores = ContratoAccess::contratoValores();
            if ($valores === []) {
                $query->whereRaw('1 = 0');
            } else {
                $query->where(function ($q) use ($valores) {
                    $q->whereIn('centro_custo', $valores)
                        ->orWhereHas('colaborador', function ($cq) use ($valores) {
                            $cq->whereIn('centro_custo', $valores)
                                ->orWhereIn('tipo_contrato', $valores);
                        });
                });
            }
        }

        $registros = $query->paginate(50)->withQueryString();

        $resumoBase = MedicaoPresencaObraRegistro::query()->whereDate('data', $data);
        if (ContratoAccess::shouldRestrict()) {
            $valores = ContratoAccess::contratoValores();
            if ($valores === []) {
                $resumoBase->whereRaw('1 = 0');
            } else {
                $resumoBase->where(function ($q) use ($valores) {
                    $q->whereIn('centro_custo', $valores)
                        ->orWhereHas('colaborador', function ($cq) use ($valores) {
                            $cq->whereIn('centro_custo', $valores)
                                ->orWhereIn('tipo_contrato', $valores);
                        });
                });
            }
        }

        $presentes = (clone $resumoBase)->where('status', MedicaoPresencaObraRegistro::STATUS_PRESENTE)->count();
        $ausentes = (clone $resumoBase)->where('status', MedicaoPresencaObraRegistro::STATUS_AUSENTE)->count();

        return array_merge([
            'registros' => $registros,
            'data' => $data,
            'status' => is_string($status) ? $status : '',
            'centroCusto' => is_string($centroCusto) ? $centroCusto : '',
            'busca' => is_string($busca) ? $busca : '',
            'centrosCusto' => $this->presenca->centrosCustoAtivos(),
            'resumo' => [
                'presentes' => $presentes,
                'ausentes' => $ausentes,
                'total' => $presentes + $ausentes,
            ],
            'dataInicioPadrao' => Carbon::parse($data)->startOfMonth()->toDateString(),
            'dataFimPadrao' => Carbon::parse($data)->endOfMonth()->toDateString(),
        ], $extras);
    }
}
