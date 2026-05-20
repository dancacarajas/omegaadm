<?php

namespace App\Http\Controllers\Rh;

use App\Http\Controllers\Controller;
use App\Models\Colaborador;
use App\Models\FrequenciaRegistro;
use App\Models\HorarioEscala;
use App\Models\FrequenciaJustificativaTipo;
use App\Support\EscalaPontoRegras;
use App\Support\FrequenciaPontoCsvImport;
use App\Support\JustificativaPontoService;
use App\Support\Rh\CartaoPontoService;
use App\Support\Rh\ColaboradorVinculoPonto;
use App\Support\Rh\FrequenciaRegistroReconciliacao;
use App\Support\Rh\GarantirFrequenciaRegistrosPeriodo;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ApuracaoPontoController extends Controller
{
    public function index(Request $request)
    {
        $dataFim = $request->filled('data_fim')
            ? Carbon::parse($request->input('data_fim'))->startOfDay()
            : today()->startOfDay();

        $dataInicio = $request->filled('data_inicio')
            ? Carbon::parse($request->input('data_inicio'))->startOfDay()
            : $dataFim->copy()->subDays(29);

        if ($dataInicio->gt($dataFim)) {
            [$dataInicio, $dataFim] = [$dataFim->copy(), $dataInicio->copy()];
        }

        $colaboradores = $this->colaboradoresFiltrados($request);
        $colaboradorId = (int) $request->input('colaborador_id');
        if ($colaboradorId <= 0) {
            $colaboradorId = (int) $colaboradores->first()?->id;
        }

        $colaborador = $colaboradorId > 0
            ? Colaborador::query()->with(['horarioEscala.dias', 'horarioEscala.excecoes'])->find($colaboradorId)
            : null;

        $resumoRegistros = $colaborador
            ? $this->resumoRegistrosPeriodo($colaborador->id, $dataInicio->toDateString(), $dataFim->toDateString())
            : null;

        $cartao = null;
        if ($colaborador) {
            app(FrequenciaRegistroReconciliacao::class)->corrigirFaltasIndevidasNoPeriodo(
                $dataInicio->toDateString(),
                $dataFim->toDateString(),
                $colaborador->id
            );

            GarantirFrequenciaRegistrosPeriodo::gerarFaltasColaboradorNoPeriodo(
                $colaborador,
                $dataInicio->toDateString(),
                $dataFim->toDateString(),
                false
            );

            $cartoes = app(CartaoPontoService::class)->montarCartoes(
                collect([$colaborador]),
                $dataInicio->toDateString(),
                $dataFim->toDateString()
            );
            $cartao = $cartoes[0] ?? null;
        }

        $filtrosAtivos = $this->filtrosAtivos($request);

        return view('rh.frequencia.apuracao.index', [
            'dataInicio' => $dataInicio->toDateString(),
            'dataFim' => $dataFim->toDateString(),
            'colaboradores' => $colaboradores,
            'colaborador' => $colaborador,
            'colaboradorId' => $colaborador?->id,
            'cartao' => $cartao,
            'filtrosAtivos' => $filtrosAtivos,
            'departamentos' => $this->valoresDistintos('departamento'),
            'centrosCusto' => $this->valoresDistintos('centro_custo'),
            'cargos' => $this->valoresDistintos('cargo'),
            'horarios' => HorarioEscala::query()->where('status', 'ativo')->orderBy('nome')->get(['id', 'nome']),
            'buscaColaborador' => trim((string) $request->input('busca', '')),
            'resumoRegistros' => $resumoRegistros,
            'tiposJustificativa' => FrequenciaJustificativaTipo::query()
                ->where('ativo', true)
                ->orderBy('ordem')
                ->orderBy('nome')
                ->get(),
            'redirectApuracao' => $this->urlApuracao($request, $colaborador?->id, $dataInicio->toDateString(), $dataFim->toDateString()),
        ]);
    }

    public function aplicarJustificativa(Request $request)
    {
        $data = $request->validate([
            'colaborador_id' => ['required', 'integer', 'exists:colaboradores,id'],
            'data_inicio' => ['required', 'date'],
            'data_fim' => ['required', 'date', 'after_or_equal:data_inicio'],
            'justificativa_tipo_id' => ['required', 'integer', 'exists:frequencia_justificativa_tipos,id'],
            'observacao' => ['nullable', 'string', 'max:2000'],
            'anexo' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx', 'max:10240'],
        ]);

        $colaborador = Colaborador::query()->findOrFail((int) $data['colaborador_id']);
        $tipo = FrequenciaJustificativaTipo::query()->findOrFail((int) $data['justificativa_tipo_id']);

        $anexoPath = null;
        if ($request->hasFile('anexo')) {
            $anexoPath = $request->file('anexo')->store('frequencia/justificativas', 'public');
        }

        $dias = app(JustificativaPontoService::class)->aplicarPeriodo(
            $colaborador,
            $data['data_inicio'],
            $data['data_fim'],
            $tipo,
            $data['observacao'] ?? null,
            $anexoPath
        );

        return redirect()
            ->to($request->input('redirect', $this->urlApuracao($request, $colaborador->id, $data['data_inicio'], $data['data_fim'])))
            ->with('success', "Justificativa «{$tipo->nome}» aplicada em {$dias} dia(s).");
    }

    public function salvarMarcacao(Request $request)
    {
        $data = $request->validate([
            'registro_id' => ['required', 'integer', 'exists:frequencia_registros,id'],
            'entrada_1' => ['nullable', 'date_format:H:i'],
            'saida_1' => ['nullable', 'date_format:H:i'],
            'entrada_2' => ['nullable', 'date_format:H:i'],
            'saida_2' => ['nullable', 'date_format:H:i'],
            'redirect' => ['nullable', 'string'],
        ]);

        $registro = FrequenciaRegistro::query()->findOrFail((int) $data['registro_id']);
        $registro->loadMissing('colaborador.horarioEscala.excecoes');

        $horarios = collect(['entrada_1', 'saida_1', 'entrada_2', 'saida_2'])
            ->mapWithKeys(fn (string $c) => [$c => ($data[$c] ?? '') === '' ? null : $data[$c]])
            ->all();

        $preenchidos = collect($horarios)->filter()->count();

        $avaliacao = app(EscalaPontoRegras::class)->avaliarMarcacao(
            $registro->colaborador,
            $registro->data,
            $preenchidos > 0
        );

        if (! $avaliacao['permitido']) {
            return redirect()
                ->to($data['redirect'] ?? route('rh.frequencia.apuracao.index'))
                ->withErrors(['marcacao' => $avaliacao['motivo']]);
        }

        $hora = static fn (?string $v) => $v ? ($v.':00') : null;

        $status = match (true) {
            $preenchidos >= 2 => 'presente',
            $preenchidos === 1 => 'incompleto',
            default => 'falta',
        };

        $registro->update([
            'entrada_1' => $hora($horarios['entrada_1']),
            'saida_1' => $hora($horarios['saida_1']),
            'entrada_2' => $hora($horarios['entrada_2']),
            'saida_2' => $hora($horarios['saida_2']),
            'status' => $status,
            'origem' => 'manual',
            'justificativa_tipo' => null,
            'justificativa_tipo_id' => null,
            'justificativa_texto' => null,
        ]);

        return redirect()
            ->to($data['redirect'] ?? route('rh.frequencia.apuracao.index'))
            ->with('success', 'Marcações salvas.');
    }

    public function limparMarcacoes(Request $request, FrequenciaRegistro $registro)
    {
        $registro->loadMissing('colaborador.horarioEscala.excecoes');

        $registro->update([
            'entrada_1' => null,
            'saida_1' => null,
            'entrada_2' => null,
            'saida_2' => null,
            'status' => 'falta',
            'origem' => 'manual',
            'justificativa_tipo' => null,
            'justificativa_tipo_id' => null,
            'justificativa_texto' => null,
        ]);

        return redirect()
            ->to($request->input('redirect', route('rh.frequencia.apuracao.index')))
            ->with('success', 'Batidas removidas.');
    }

    public function removerJustificativa(Request $request, FrequenciaRegistro $registro)
    {
        app(JustificativaPontoService::class)->removerJustificativa($registro);

        return redirect()
            ->to($request->input('redirect', route('rh.frequencia.apuracao.index')))
            ->with('success', 'Justificativa removida do dia.');
    }

    private function urlApuracao(Request $request, ?int $colaboradorId, string $dataInicio, string $dataFim): string
    {
        return route('rh.frequencia.apuracao.index', array_filter([
            'colaborador_id' => $colaboradorId,
            'data_inicio' => $dataInicio,
            'data_fim' => $dataFim,
            'departamento' => $request->input('departamento'),
            'centro_custo' => $request->input('centro_custo'),
            'cargo' => $request->input('cargo'),
            'horario_escala_id' => $request->input('horario_escala_id'),
            'busca' => $request->input('busca'),
        ], fn ($v) => $v !== null && $v !== ''));
    }

    /**
     * @return array{total: int, com_batida: int, csv_ponto: int, origens: array<string, int>}
     */
    private function resumoRegistrosPeriodo(int $colaboradorId, string $dataInicio, string $dataFim): array
    {
        $registros = FrequenciaRegistro::query()
            ->where('colaborador_id', $colaboradorId)
            ->whereDate('data', '>=', $dataInicio)
            ->whereDate('data', '<=', $dataFim)
            ->get(['origem', 'entrada_1', 'saida_1', 'entrada_2', 'saida_2', 'status']);

        $comBatida = 0;
        $origens = [];

        foreach ($registros as $registro) {
            $origens[$registro->origem ?? '—'] = ($origens[$registro->origem ?? '—'] ?? 0) + 1;
            foreach (['entrada_1', 'saida_1', 'entrada_2', 'saida_2'] as $campo) {
                if (! \App\Support\FrequenciaCalculo::horarioArmazenadoVazio($registro->getAttribute($campo))) {
                    $comBatida++;

                    break 2;
                }
            }
        }

        return [
            'total' => $registros->count(),
            'com_batida' => $comBatida,
            'csv_ponto' => $origens[FrequenciaPontoCsvImport::ORIGEM] ?? 0,
            'origens' => $origens,
        ];
    }

    /**
     * @return Collection<int, Colaborador>
     */
    private function colaboradoresFiltrados(Request $request): Collection
    {
        $query = Colaborador::query()
            ->where('status', 'ativo')
            ->with(['horarioEscala'])
            ->orderBy('nome');

        if ($request->filled('departamento')) {
            $query->where('departamento', $request->input('departamento'));
        }

        if ($request->filled('centro_custo')) {
            $query->where('centro_custo', $request->input('centro_custo'));
        }

        if ($request->filled('cargo')) {
            $query->where('cargo', $request->input('cargo'));
        }

        if ($request->filled('horario_escala_id')) {
            $query->where('horario_escala_id', (int) $request->input('horario_escala_id'));
        }

        if ($request->filled('busca')) {
            $busca = trim((string) $request->input('busca'));
            $query->where(function ($q) use ($busca) {
                $q->where('nome', 'like', "%{$busca}%")
                    ->orWhere('matricula', 'like', "%{$busca}%")
                    ->orWhere('cpf', 'like', "%{$busca}%");
            });
        }

        return $query->get(['id', 'nome', 'matricula', 'cpf', 'rg', 'cargo', 'departamento', 'centro_custo', 'horario_escala_id']);
    }

    /**
     * @return list<array{label: string, valor: string}>
     */
    private function filtrosAtivos(Request $request): array
    {
        $tags = [];

        if ($request->filled('departamento')) {
            $tags[] = ['label' => 'Departamento', 'valor' => (string) $request->input('departamento')];
        } else {
            $tags[] = ['label' => 'Departamentos', 'valor' => 'Todos'];
        }

        if ($request->filled('centro_custo')) {
            $tags[] = ['label' => 'Centro de custo', 'valor' => (string) $request->input('centro_custo')];
        } else {
            $tags[] = ['label' => 'Centros de custo', 'valor' => 'Todos'];
        }

        if ($request->filled('cargo')) {
            $tags[] = ['label' => 'Cargo', 'valor' => (string) $request->input('cargo')];
        } else {
            $tags[] = ['label' => 'Cargos', 'valor' => 'Todos'];
        }

        if ($request->filled('horario_escala_id')) {
            $nome = HorarioEscala::query()->find((int) $request->input('horario_escala_id'))?->nome ?? 'Selecionado';
            $tags[] = ['label' => 'Horário', 'valor' => $nome];
        } else {
            $tags[] = ['label' => 'Horários', 'valor' => 'Todos'];
        }

        $tags[] = ['label' => 'Funcionários', 'valor' => $request->filled('busca') ? 'Filtrado' : 'Todos'];

        return $tags;
    }

    /**
     * @return list<string>
     */
    private function valoresDistintos(string $coluna): array
    {
        return Colaborador::query()
            ->where('status', 'ativo')
            ->whereNotNull($coluna)
            ->where($coluna, '!=', '')
            ->distinct()
            ->orderBy($coluna)
            ->pluck($coluna)
            ->map(fn ($v) => (string) $v)
            ->values()
            ->all();
    }
}
