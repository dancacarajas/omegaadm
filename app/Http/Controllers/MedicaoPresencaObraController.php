<?php

namespace App\Http\Controllers;

use App\Models\MedicaoPresencaObraRegistro;
use App\Support\ContratoAccess;
use App\Support\Medicao\PresencaObraFolhaExcelExport;
use App\Support\PresencaObraService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class MedicaoPresencaObraController extends Controller
{
    public function __construct(
        private readonly PresencaObraService $presenca,
    ) {}

    public function portal()
    {
        return view('medicao.presenca-obra.portal', [
            'urlConfirmacao' => route('presenca-obra.identificar'),
            'urlConsulta' => route('medicao.presenca-obra.consulta'),
            'urlLoginConsulta' => route('login', ['redirect' => route('medicao.presenca-obra.consulta', [], false)]),
            'usuarioLogado' => auth()->user(),
        ]);
    }

    public function consulta(Request $request)
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
            ->with(['colaborador:id,nome,matricula,cargo,centro_custo', 'confirmadoPor:id,nome,matricula'])
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

        return view('medicao.presenca-obra.index', [
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
            'urlPublica' => route('presenca-obra.identificar'),
            'dataInicioPadrao' => Carbon::parse($data)->startOfMonth()->toDateString(),
            'dataFimPadrao' => Carbon::parse($data)->endOfMonth()->toDateString(),
        ]);
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
}
