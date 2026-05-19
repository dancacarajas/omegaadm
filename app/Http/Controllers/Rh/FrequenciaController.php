<?php

namespace App\Http\Controllers\Rh;

use App\Http\Controllers\Controller;
use App\Models\Colaborador;
use App\Models\Contrato;
use App\Models\FrequenciaRegistro;
use App\Support\ContratoAccess;
use App\Support\Rh\CartaoPontoPeriodo;
use App\Support\AfdExport;
use App\Support\EscalaPontoRegras;
use App\Support\FeriadoPontoService;
use App\Support\FrequenciaPontoCsvImport;
use App\Support\Rh\AbsenteismoPeriodo;
use App\Support\Rh\ExtratoFaltasPeriodo;
use App\Support\Rh\ColaboradorVinculoPonto;
use App\Support\Rh\FrequenciaRegistroReconciliacao;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class FrequenciaController extends Controller
{
    public function index()
    {
        $data = request('data')
            ? Carbon::parse(request('data'))->toDateString()
            : today()->toDateString();

        $this->garantirRegistrosDoDia($data);

        $mes = request('mes') ?: Carbon::parse($data)->format('Y-m');
        $inicioMes = Carbon::createFromFormat('Y-m', $mes)->startOfMonth();
        $fimMes = $inicioMes->copy()->endOfMonth();

        $absenteismoInicio = request('absenteismo_inicio')
            ? Carbon::parse(request('absenteismo_inicio'))->startOfDay()
            : $inicioMes->copy();
        $absenteismoFim = request('absenteismo_fim')
            ? Carbon::parse(request('absenteismo_fim'))->startOfDay()
            : $fimMes->copy();

        if ($absenteismoFim->lt($absenteismoInicio)) {
            [$absenteismoInicio, $absenteismoFim] = [$absenteismoFim->copy(), $absenteismoInicio->copy()];
        }

        $rankingInicio = $absenteismoInicio->toDateString();
        $rankingFim = $absenteismoFim->toDateString();

        app(FrequenciaRegistroReconciliacao::class)->removerRegistrosForaDoVinculoNoPeriodo(
            $inicioMes->toDateString(),
            $fimMes->toDateString()
        );

        app(FrequenciaRegistroReconciliacao::class)->removerRegistrosForaDoVinculoNoPeriodo(
            $rankingInicio,
            $rankingFim
        );

        if (request()->boolean('absenteismo_calcular')) {
            app(FrequenciaRegistroReconciliacao::class)->corrigirFaltasIndevidasNoPeriodo(
                $absenteismoInicio->toDateString(),
                $absenteismoFim->toDateString()
            );
        }

        $ordenacao = request('ordenacao', 'prioridade');

        $registrosQuery = FrequenciaRegistro::query()
            ->with(['colaborador.horarioEscala.dias', 'colaborador.horarioEscala.excecoes'])
            ->whereDate('data', $data)
            ->whereHas('colaborador', function ($q) {
                $q->where('status', 'ativo');
                ColaboradorVinculoPonto::aplicarFiltroRegistroNaData($q);
            })
            ->when(request('busca'), function ($query, string $busca) {
                $query->whereHas('colaborador', function ($query) use ($busca) {
                    $query->where('nome', 'like', "%{$busca}%")
                        ->orWhere('matricula', 'like', "%{$busca}%")
                        ->orWhere('pis', 'like', "%{$busca}%")
                        ->orWhere('cargo', 'like', "%{$busca}%");
                });
            })
            ->when(request('cargo'), function ($query, string $cargo) {
                $query->whereHas('colaborador', fn ($q) => $q->where('cargo', $cargo));
            });

        if ($ordenacao === 'alfabetica') {
            $registrosQuery
                ->join('colaboradores', 'colaboradores.id', '=', 'frequencia_registros.colaborador_id')
                ->orderBy('colaboradores.nome')
                ->select('frequencia_registros.*');
        } else {
            $registrosQuery->orderByRaw("CASE frequencia_registros.status WHEN 'falta' THEN 1 WHEN 'incompleto' THEN 2 WHEN 'presente' THEN 3 WHEN 'justificado' THEN 4 ELSE 5 END");
        }

        $registros = $registrosQuery->paginate(10)->withQueryString();

        $totalAtivos = Colaborador::where('status', 'ativo')->count();
        $indicadoresBase = fn () => FrequenciaRegistro::query()
            ->whereDate('data', $data)
            ->whereHas('colaborador', function ($q) {
                $q->where('status', 'ativo');
                ColaboradorVinculoPonto::aplicarFiltroRegistroNaData($q);
            });
        $presentes = (clone $indicadoresBase())->where('status', 'presente')->count();
        $faltas = (clone $indicadoresBase())->where('status', 'falta')->count();
        $justificados = (clone $indicadoresBase())->where('status', 'justificado')->count();
        $ausencias = $faltas + $justificados;

        $indicadores = [
            'colaboradores' => $totalAtivos,
            'presentes' => $presentes,
            'faltas' => $faltas,
            'justificados' => $justificados,
        ];

        $colaboradoresAtivos = Colaborador::query()
            ->where('status', 'ativo')
            ->orderBy('nome')
            ->get(['id', 'nome', 'matricula', 'cpf', 'cargo']);

        $absenteismoColaboradorId = request()->filled('absenteismo_colaborador_id')
            ? (int) request('absenteismo_colaborador_id')
            : null;

        $absenteismo = app(AbsenteismoPeriodo::class)->calcular(
            $absenteismoInicio,
            $absenteismoFim,
            $absenteismoColaboradorId
        );

        $absenteismoColaborador = $absenteismoColaboradorId
            ? $colaboradoresAtivos->firstWhere('id', $absenteismoColaboradorId)
            : null;

        $rankingColaboradorFiltro = fn ($q) => $q->where('status', 'ativo')
            ->when($absenteismoColaboradorId !== null, fn ($c) => $c->where('id', $absenteismoColaboradorId))
            ->where(function ($c) {
                ColaboradorVinculoPonto::aplicarFiltroRegistroNaData($c);
            });

        $ranking = FrequenciaRegistro::query()
            ->select('colaborador_id', DB::raw('count(*) as total_faltas'))
            ->with('colaborador')
            ->whereDate('data', '>=', $rankingInicio)
            ->whereDate('data', '<=', $rankingFim)
            ->where('status', 'falta')
            ->whereHas('colaborador', $rankingColaboradorFiltro)
            ->groupBy('colaborador_id')
            ->orderByDesc('total_faltas')
            ->limit(5)
            ->get();

        $rankingAtestados = FrequenciaRegistro::query()
            ->select('colaborador_id', DB::raw('count(*) as total_atestados'))
            ->with('colaborador')
            ->whereDate('data', '>=', $rankingInicio)
            ->whereDate('data', '<=', $rankingFim)
            ->atestadoMedico()
            ->whereHas('colaborador', $rankingColaboradorFiltro)
            ->groupBy('colaborador_id')
            ->orderByDesc('total_atestados')
            ->limit(5)
            ->get();

        $contratosAtivos = ContratoAccess::applyContratoModel(Contrato::query())
            ->where('status', 'ativo')
            ->orderBy('centro_custo')
            ->orderBy('nome')
            ->get(['id', 'centro_custo', 'numero', 'nome']);

        $cartaoPeriodo = CartaoPontoPeriodo::competenciaPorMes($mes);

        $funcoes = $this->funcoesDistintasColaboradores();

        return view('rh.frequencia.index', compact(
            'registros',
            'indicadores',
            'ranking',
            'rankingAtestados',
            'absenteismo',
            'absenteismoColaborador',
            'absenteismoColaboradorId',
            'data',
            'mes',
            'contratosAtivos',
            'cartaoPeriodo',
            'colaboradoresAtivos',
            'ordenacao',
            'funcoes'
        ));
    }

    public function extratoFaltas(Request $request)
    {
        $mes = $request->input('mes') ?: now()->format('Y-m');
        $inicioMes = Carbon::createFromFormat('Y-m', $mes)->startOfMonth();
        $fimMes = $inicioMes->copy()->endOfMonth();

        $dataInicio = $request->filled('data_inicio')
            ? Carbon::parse($request->input('data_inicio'))->toDateString()
            : ($request->filled('absenteismo_inicio')
                ? Carbon::parse($request->input('absenteismo_inicio'))->toDateString()
                : $inicioMes->toDateString());

        $dataFim = $request->filled('data_fim')
            ? Carbon::parse($request->input('data_fim'))->toDateString()
            : ($request->filled('absenteismo_fim')
                ? Carbon::parse($request->input('absenteismo_fim'))->toDateString()
                : $fimMes->toDateString());

        $colaboradorId = $request->filled('colaborador_id')
            ? (int) $request->input('colaborador_id')
            : ($request->filled('absenteismo_colaborador_id')
                ? (int) $request->input('absenteismo_colaborador_id')
                : null);

        app(FrequenciaRegistroReconciliacao::class)->corrigirFaltasIndevidasNoPeriodo(
            $dataInicio,
            $dataFim,
            $colaboradorId
        );

        $extrato = app(ExtratoFaltasPeriodo::class)->montar($dataInicio, $dataFim, $colaboradorId);

        $colaboradorFiltro = $colaboradorId
            ? Colaborador::query()->find($colaboradorId)
            : null;

        $colaboradoresAtivos = Colaborador::query()
            ->where('status', 'ativo')
            ->orderBy('nome')
            ->get(['id', 'nome', 'matricula']);

        return view('rh.frequencia.extrato-faltas', [
            'extrato' => $extrato,
            'dataInicio' => $dataInicio,
            'dataFim' => $dataFim,
            'colaboradorId' => $colaboradorId,
            'colaboradorFiltro' => $colaboradorFiltro,
            'colaboradoresAtivos' => $colaboradoresAtivos,
            'mes' => $mes,
        ]);
    }

    public function importarAfd(Request $request)
    {
        $request->validate([
            'arquivo' => ['required', 'file', 'max:20480'],
        ]);

        $content = file_get_contents($request->file('arquivo')->getRealPath());
        $linhas = preg_split('/\r\n|\r|\n/', $content) ?: [];
        $marcacoes = [];
        $datasDetectadas = collect();
        $ignoradas = 0;

        foreach ($linhas as $linha) {
            $marcacao = $this->parseAfdLine($linha);

            if (! $marcacao) {
                $ignoradas++;
                continue;
            }

            $datasDetectadas->push($marcacao['data']);
            $colaborador = $this->findColaboradorByDocument($marcacao['pis']);

            if (! $colaborador) {
                $ignoradas++;
                continue;
            }

            $key = $colaborador->id.'|'.$marcacao['data'];
            $marcacoes[$key]['colaborador'] = $colaborador;
            $marcacoes[$key]['data'] = $marcacao['data'];
            $marcacoes[$key]['horarios'][] = $marcacao['hora'];
        }

        $datas = $datasDetectadas
            ->merge(collect($marcacoes)->pluck('data'))
            ->unique()
            ->values();

        $regrasPonto = app(EscalaPontoRegras::class);
        $bloqueadasRotina = 0;

        DB::transaction(function () use ($marcacoes, $datas, $regrasPonto, &$bloqueadasRotina) {
            foreach ($marcacoes as $marcacao) {
                if (! $regrasPonto->deveTrabalharNoDia($marcacao['colaborador'], $marcacao['data'])) {
                    $bloqueadasRotina++;

                    continue;
                }

                $horarios = collect($marcacao['horarios'])->unique()->sort()->values();

                FrequenciaRegistro::updateOrCreate(
                    [
                        'colaborador_id' => $marcacao['colaborador']->id,
                        'data' => $marcacao['data'],
                    ],
                    [
                        'entrada_1' => $horarios->get(0),
                        'saida_1' => $horarios->get(1),
                        'entrada_2' => $horarios->get(2),
                        'saida_2' => $horarios->get(3),
                        'status' => $horarios->count() >= 2 ? 'presente' : 'incompleto',
                        'origem' => 'afd',
                        'importado_em' => now(),
                    ]
                );
            }

            $this->criarFaltasDosDiasImportados($datas);
        });

        $msg = 'AFD importado. Marcações lidas: '.count($marcacoes).'. Linhas ignoradas: '.$ignoradas.'.';
        if ($bloqueadasRotina > 0) {
            $msg .= ' Marcações bloqueadas (folga/ausência na escala): '.$bloqueadasRotina.'.';
        }

        return back()->with('success', $msg);
    }

    public function importarCsv(Request $request)
    {
        $validated = $request->validate([
            'arquivo' => ['required', 'file', 'max:51200'],
            'data_inicio' => ['required', 'date'],
            'data_fim' => ['required', 'date', 'after_or_equal:data_inicio'],
            'escopo_colaboradores' => ['required', Rule::in(['todos', 'colaborador', 'selecionados'])],
            'colaborador_id' => ['required_if:escopo_colaboradores,colaborador', 'nullable', 'integer', 'exists:colaboradores,id'],
            'colaborador_ids' => ['required_if:escopo_colaboradores,selecionados', 'nullable', 'array', 'min:1'],
            'colaborador_ids.*' => ['integer', 'exists:colaboradores,id'],
        ]);

        $colaboradorIds = match ($validated['escopo_colaboradores']) {
            'colaborador' => [(int) $validated['colaborador_id']],
            'selecionados' => array_values(array_unique(array_map('intval', $validated['colaborador_ids'] ?? []))),
            default => null,
        };

        try {
            $resultado = app(FrequenciaPontoCsvImport::class)->importar(
                $request->file('arquivo')->getRealPath(),
                $validated['data_inicio'],
                $validated['data_fim'],
                $colaboradorIds
            );
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        $periodoFmt = Carbon::parse($validated['data_inicio'])->format('d/m/Y')
            .' a '.Carbon::parse($validated['data_fim'])->format('d/m/Y');

        if ($resultado['importados'] === 0) {
            $msg = 'Nenhum registro importado no período '.$periodoFmt.'.';
            if (($resultado['fora_periodo'] ?? 0) > 0) {
                $msg .= ' Linhas fora do período: '.$resultado['fora_periodo'].'.';
            }
            if ($resultado['colaboradores_nao_encontrados'] !== []) {
                $msg .= ' Matrículas/CPFs não encontrados no efetivo: '
                    .implode(', ', array_slice($resultado['colaboradores_nao_encontrados'], 0, 15));
                if (count($resultado['colaboradores_nao_encontrados']) > 15) {
                    $msg .= '…';
                }
            }

            return back()->with('error', $msg)->withInput();
        }

        $msg = 'CSV importado ('.$periodoFmt.'): '.$resultado['importados'].' dia(s) gravado(s).';
        if (($resultado['fora_escopo_colaborador'] ?? 0) > 0) {
            $msg .= ' Linhas de outros colaboradores (não importadas): '.$resultado['fora_escopo_colaborador'].'.';
        }
        if (($resultado['fora_periodo'] ?? 0) > 0) {
            $msg .= ' Fora do período (não importadas): '.$resultado['fora_periodo'].'.';
        }
        if ($resultado['ignorados'] > 0) {
            $msg .= ' Outras linhas ignoradas: '.$resultado['ignorados'].'.';
        }
        if ($resultado['colaboradores_nao_encontrados'] !== []) {
            $msg .= ' Sem cadastro no efetivo: '
                .count($resultado['colaboradores_nao_encontrados']).' identificador(es).';
        }

        $corrigidos = app(FrequenciaRegistroReconciliacao::class)->corrigirFaltasIndevidasNoPeriodo(
            $resultado['periodo']['inicio'],
            $resultado['periodo']['fim']
        );
        if ($corrigidos > 0) {
            $msg .= ' Ajuste automático: '.$corrigidos.' dia(s) de folga/feriado (antes marcados como falta).';
        }

        return back()->with('success', $msg);
    }

    public function exportarAfd(Request $request): Response
    {
        $mes = $request->input('mes') ?: now()->format('Y-m');
        $inicioMes = Carbon::createFromFormat('Y-m', $mes)->startOfMonth();
        $fimMes = $inicioMes->copy()->endOfMonth();

        $validated = $request->validate([
            'data_inicio' => ['nullable', 'date'],
            'data_fim' => ['nullable', 'date', 'after_or_equal:data_inicio'],
            'busca' => ['nullable', 'string', 'max:120'],
            'filtrar_busca' => ['nullable', 'boolean'],
        ]);

        $dataDia = $request->filled('data')
            ? Carbon::parse($request->input('data'))->toDateString()
            : null;

        $dataInicio = $validated['data_inicio'] ?? $dataDia ?? $inicioMes->toDateString();
        $dataFim = $validated['data_fim'] ?? $dataDia ?? $fimMes->toDateString();

        $busca = $request->boolean('filtrar_busca')
            ? trim((string) ($validated['busca'] ?? $request->input('busca', '')))
            : null;
        $busca = $busca !== '' ? $busca : null;

        $resultado = app(AfdExport::class)->gerar($dataInicio, $dataFim, $busca);

        if ($resultado['total_marcacoes'] === 0) {
            if ($resultado['registros_com_horario'] > 0 && $resultado['colaboradores_sem_identificador'] > 0) {
                return back()->with(
                    'error',
                    'Há batidas no período, mas '.$resultado['colaboradores_sem_identificador'].' colaborador(es) sem PIS, CPF ou matrícula. Cadastre o identificador na ficha do efetivo e tente novamente.'
                );
            }

            if ($busca !== null) {
                return back()->with(
                    'error',
                    'Nenhuma marcação encontrada no período com o filtro de busca aplicado. Desmarque «Aplicar busca da listagem» ou amplie o período.'
                );
            }

            return back()->with('error', 'Nenhuma marcação com horário encontrada no período selecionado.');
        }

        return response($resultado['conteudo'], 200, [
            'Content-Type' => 'text/plain; charset=ISO-8859-1',
            'Content-Disposition' => 'attachment; filename="'.$resultado['nome_arquivo'].'"',
        ]);
    }

    public function marcacaoManual(Request $request, FrequenciaRegistro $registro)
    {
        $payload = [];
        foreach (['entrada_1', 'saida_1', 'entrada_2', 'saida_2'] as $campo) {
            $v = $request->input($campo);
            $payload[$campo] = (is_string($v) && trim($v) === '') ? null : $v;
        }

        $validated = validator($payload, [
            'entrada_1' => ['nullable', 'date_format:H:i'],
            'saida_1' => ['nullable', 'date_format:H:i'],
            'entrada_2' => ['nullable', 'date_format:H:i'],
            'saida_2' => ['nullable', 'date_format:H:i'],
        ])->validate();

        $preenchidos = collect($validated)->filter()->count();

        $registro->loadMissing('colaborador.horarioEscala.excecoes');
        $avaliacao = app(EscalaPontoRegras::class)->avaliarMarcacao(
            $registro->colaborador,
            $registro->data,
            $preenchidos > 0
        );
        if (! $avaliacao['permitido']) {
            return back()
                ->withErrors(['marcacao' => $avaliacao['motivo']])
                ->withInput();
        }

        $status = match (true) {
            $preenchidos >= 2 => 'presente',
            $preenchidos === 1 => 'incompleto',
            default => 'falta',
        };

        $hora = static fn (?string $v) => $v ? ($v.':00') : null;

        $registro->update([
            'entrada_1' => $hora($validated['entrada_1'] ?? null),
            'saida_1' => $hora($validated['saida_1'] ?? null),
            'entrada_2' => $hora($validated['entrada_2'] ?? null),
            'saida_2' => $hora($validated['saida_2'] ?? null),
            'status' => $status,
            'origem' => 'manual',
        ]);

        return $this->redirectAposAcao($request, 'Marcações manuais salvas para '.$registro->colaborador?->nome.'.');
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
        ]);

        return $this->redirectAposAcao(
            $request,
            'Batidas do dia removidas para '.$registro->colaborador?->nome.'. O colaborador pode marcar de novo no app /ponto.'
        );
    }

    public function justificar(Request $request, FrequenciaRegistro $registro)
    {
        $data = $request->validate([
            'justificativa_tipo_id' => ['nullable', 'integer', 'exists:frequencia_justificativa_tipos,id'],
            'justificativa_tipo' => ['required_without:justificativa_tipo_id', 'in:atestado,justificativa,abono,outro'],
            'justificativa_texto' => ['nullable', 'string'],
            'anexo' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx', 'max:10240'],
            'redirect' => ['nullable', 'string'],
        ]);

        if (! empty($data['justificativa_tipo_id'])) {
            $registro->loadMissing('colaborador');
            $tipo = \App\Models\FrequenciaJustificativaTipo::query()->findOrFail((int) $data['justificativa_tipo_id']);
            app(\App\Support\JustificativaPontoService::class)->aplicarDia(
                $registro->colaborador,
                $registro->data instanceof \DateTimeInterface
                    ? $registro->data->format('Y-m-d')
                    : (string) $registro->data,
                $tipo,
                $data['justificativa_texto'] ?? null,
                null
            );

            if ($request->hasFile('anexo')) {
                $registro->update([
                    'anexo_path' => $request->file('anexo')->store('frequencia/justificativas', 'public'),
                ]);
            }

            return $this->redirectAposAcao($request, 'Justificativa registrada com sucesso.');
        }

        if ($request->hasFile('anexo')) {
            $data['anexo_path'] = $request->file('anexo')->store('frequencia/justificativas', 'public');
        }

        unset($data['anexo'], $data['justificativa_tipo_id'], $data['redirect']);

        $registro->update(array_merge($data, [
            'status' => 'justificado',
            'justificativa_tipo_id' => null,
        ]));

        return $this->redirectAposAcao($request, 'Justificativa registrada com sucesso.');
    }

    private function redirectAposAcao(Request $request, string $mensagem): \Illuminate\Http\RedirectResponse
    {
        if ($request->filled('redirect')) {
            return redirect()->to($request->input('redirect'))->with('success', $mensagem);
        }

        return back()->with('success', $mensagem);
    }

    private function parseAfdLine(string $linha): ?array
    {
        $numbers = preg_replace('/\D+/', '', $linha) ?: '';

        if (strlen($numbers) < 20) {
            return null;
        }

        if (! preg_match('/(20\d{2}[01]\d[0-3]\d|[0-3]\d[01]\d20\d{2})([0-2]\d[0-5]\d)/', $numbers, $match)) {
            return null;
        }

        $rawDate = $match[1];
        $rawTime = $match[2];
        $date = str_starts_with($rawDate, '20')
            ? Carbon::createFromFormat('Ymd', $rawDate)
            : Carbon::createFromFormat('dmY', $rawDate);

        $afterTime = substr($numbers, strpos($numbers, $match[0]) + strlen($match[0]));
        preg_match('/\d{10,12}/', $afterTime, $pisMatch);
        $pis = $pisMatch[0] ?? null;

        if (! $pis) {
            preg_match('/\d{10,12}/', $numbers, $pisMatch);
            $pis = $pisMatch[0] ?? null;
        }

        if (! $pis) {
            return null;
        }

        return [
            'data' => $date->toDateString(),
            'hora' => substr($rawTime, 0, 2).':'.substr($rawTime, 2, 2).':00',
            'pis' => ltrim($pis, '0'),
        ];
    }

    private function findColaboradorByDocument(string $document): ?Colaborador
    {
        $normalized = ltrim(preg_replace('/\D+/', '', $document) ?: '', '0');

        return Colaborador::query()
            ->whereRaw("TRIM(LEADING '0' FROM REPLACE(REPLACE(REPLACE(pis, '.', ''), '-', ''), '/', '')) = ?", [$normalized])
            ->orWhereRaw("TRIM(LEADING '0' FROM REPLACE(REPLACE(REPLACE(matricula, '.', ''), '-', ''), '/', '')) = ?", [$normalized])
            ->first();
    }

    private function criarFaltasDosDiasImportados($datas): void
    {
        if ($datas->isEmpty()) {
            return;
        }

        $colaboradores = Colaborador::query()
            ->where('status', 'ativo')
            ->get(['id', 'data_admissao', 'data_demissao']);

        foreach ($datas as $data) {
            foreach ($colaboradores as $colaborador) {
                if (! ColaboradorVinculoPonto::contaPontoNaData($colaborador, $data)) {
                    continue;
                }

                FrequenciaRegistro::firstOrCreate(
                    [
                        'colaborador_id' => $colaborador->id,
                        'data' => $data,
                    ],
                    [
                        'status' => 'falta',
                        'origem' => 'afd',
                        'importado_em' => now(),
                    ]
                );
            }
        }
    }

    /**
     * Garante uma linha por colaborador ativo na data, para permitir ponto manual e justificativas sem AFD.
     * Preenche apenas intervalo (saída 1 / entrada 2) com a escala — entrada e saída final vêm das batidas reais.
     */
    private function garantirRegistrosDoDia(string $data): void
    {
        $regrasPonto = app(EscalaPontoRegras::class);
        $feriadoPonto = app(FeriadoPontoService::class);
        $feriadoDia = $feriadoPonto->feriadoNaData($data);
        $colaboradores = Colaborador::query()
            ->where('status', 'ativo')
            ->with(['horarioEscala.dias'])
            ->get();

        foreach ($colaboradores as $colaborador) {
            if (! ColaboradorVinculoPonto::contaPontoNaData($colaborador, $data)) {
                continue;
            }

            $registro = FrequenciaRegistro::query()
                ->where('colaborador_id', $colaborador->id)
                ->whereDate('data', $data)
                ->first();

            if ($feriadoDia && $feriadoPonto->deveAplicarFeriadoNoRegistro($registro)) {
                $feriadoPonto->aplicarFeriadoNoColaborador($colaborador->id, $data, $feriadoDia);

                continue;
            }

            $folgaEscala = $regrasPonto->diaAbonadoPorFolgaEscala($colaborador, $data);

            $criado = false;
            if (! $registro) {
                $registro = FrequenciaRegistro::create([
                    'colaborador_id' => $colaborador->id,
                    'data' => $data,
                    'status' => $folgaEscala ? 'folga' : 'falta',
                    'origem' => 'grade',
                ]);
                $criado = true;
            } elseif ($folgaEscala && $registro->status === 'falta' && \App\Support\FrequenciaCalculo::minutosTrabalhados($registro) === 0) {
                $registro->update(['status' => 'folga']);
            }

            if ($criado && ! $folgaEscala) {
                $this->preencherHorariosDaEscalaNosVazios($registro, $colaborador, $data);
            }
        }
    }

    private function preencherHorariosDaEscalaNosVazios(FrequenciaRegistro $registro, Colaborador $colaborador, string $dataYmd): void
    {
        if ($registro->status === 'justificado') {
            return;
        }

        if (! app(EscalaPontoRegras::class)->deveTrabalharNoDia($colaborador, $dataYmd)) {
            return;
        }

        $dia = $colaborador->horarioEscalaDiaNaData($dataYmd);
        if (! $dia) {
            return;
        }

        $campos = ['saida_1', 'entrada_2'];
        $alterou = false;

        foreach ($campos as $campo) {
            if (! \App\Support\FrequenciaCalculo::horarioArmazenadoVazio($registro->getAttribute($campo))) {
                continue;
            }
            $valorEscala = $dia->getAttribute($campo);
            if (\App\Support\FrequenciaCalculo::horarioArmazenadoVazio($valorEscala)) {
                continue;
            }
            $registro->setAttribute($campo, $this->normalizarHoraParaBanco($valorEscala));
            $alterou = true;
        }

        if (! $alterou) {
            return;
        }

        $preenchidos = 0;
        foreach ($campos as $campo) {
            if (! \App\Support\FrequenciaCalculo::horarioArmazenadoVazio($registro->getAttribute($campo))) {
                $preenchidos++;
            }
        }

        $registro->status = match (true) {
            $preenchidos >= 2 => 'presente',
            $preenchidos === 1 => 'incompleto',
            default => 'falta',
        };

        $registro->save();
    }

    private function normalizarHoraParaBanco(mixed $valor): string
    {
        $s = trim((string) $valor);
        if (strlen($s) === 5 && preg_match('/^\d{2}:\d{2}$/', $s)) {
            return $s.':00';
        }

        return $s;
    }

    /**
     * @return list<string>
     */
    private function funcoesDistintasColaboradores(): array
    {
        return Colaborador::query()
            ->whereNotNull('cargo')
            ->where('cargo', '!=', '')
            ->distinct()
            ->orderBy('cargo')
            ->pluck('cargo')
            ->map(fn ($v) => (string) $v)
            ->values()
            ->all();
    }
}
