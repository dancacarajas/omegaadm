<?php

namespace App\Http\Controllers\Rh;

use App\Http\Controllers\Controller;
use App\Models\Colaborador;
use App\Models\FrequenciaRegistro;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        $registros = FrequenciaRegistro::query()
            ->with(['colaborador.horarioEscala.dias'])
            ->whereDate('data', $data)
            ->when(request('busca'), function ($query, string $busca) {
                $query->whereHas('colaborador', function ($query) use ($busca) {
                    $query->where('nome', 'like', "%{$busca}%")
                        ->orWhere('matricula', 'like', "%{$busca}%")
                        ->orWhere('pis', 'like', "%{$busca}%")
                        ->orWhere('cargo', 'like', "%{$busca}%");
                });
            })
            ->orderByRaw("FIELD(status, 'falta', 'incompleto', 'presente', 'justificado')")
            ->paginate(10)
            ->withQueryString();

        $totalAtivos = Colaborador::where('status', 'ativo')->count();
        $presentes = FrequenciaRegistro::whereDate('data', $data)->where('status', 'presente')->count();
        $faltas = FrequenciaRegistro::whereDate('data', $data)->where('status', 'falta')->count();
        $justificados = FrequenciaRegistro::whereDate('data', $data)->where('status', 'justificado')->count();
        $ausencias = $faltas + $justificados;

        $indicadores = [
            'colaboradores' => $totalAtivos,
            'presentes' => $presentes,
            'faltas' => $faltas,
            'justificados' => $justificados,
        ];

        $diasPeriodo = max(1, $absenteismoInicio->diffInDays($absenteismoFim, false) + 1);
        $ausenciasPeriodo = FrequenciaRegistro::query()
            ->whereBetween('data', [$absenteismoInicio->toDateString(), $absenteismoFim->toDateString()])
            ->whereIn('status', ['falta', 'justificado'])
            ->count();
        $basePeriodo = $totalAtivos * $diasPeriodo;
        $absenteismo = [
            'inicio' => $absenteismoInicio->toDateString(),
            'fim' => $absenteismoFim->toDateString(),
            'dias' => $diasPeriodo,
            'ausencias' => $ausenciasPeriodo,
            'base' => $basePeriodo,
            'taxa' => $basePeriodo > 0 ? round(($ausenciasPeriodo / $basePeriodo) * 100, 1) : 0,
        ];

        $ranking = FrequenciaRegistro::query()
            ->select('colaborador_id', DB::raw('count(*) as total_faltas'))
            ->with('colaborador')
            ->whereBetween('data', [$inicioMes->toDateString(), $fimMes->toDateString()])
            ->where('status', 'falta')
            ->groupBy('colaborador_id')
            ->orderByDesc('total_faltas')
            ->limit(5)
            ->get();

        return view('rh.frequencia.index', compact('registros', 'indicadores', 'ranking', 'absenteismo', 'data', 'mes'));
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

        DB::transaction(function () use ($marcacoes, $datas) {
            foreach ($marcacoes as $marcacao) {
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

        return back()->with('success', 'AFD importado. Marcações lidas: '.count($marcacoes).'. Linhas ignoradas: '.$ignoradas.'.');
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

        return back()->with('success', 'Marcações manuais salvas para '.$registro->colaborador?->nome.'.');
    }

    public function justificar(Request $request, FrequenciaRegistro $registro)
    {
        $data = $request->validate([
            'justificativa_tipo' => ['required', 'in:atestado,justificativa,abono,outro'],
            'justificativa_texto' => ['nullable', 'string'],
            'anexo' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx', 'max:10240'],
        ]);

        if ($request->hasFile('anexo')) {
            $data['anexo_path'] = $request->file('anexo')->store('frequencia/justificativas', 'public');
        }

        unset($data['anexo']);

        $registro->update(array_merge($data, [
            'status' => 'justificado',
        ]));

        return back()->with('success', 'Justificativa registrada com sucesso.');
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

        $colaboradores = Colaborador::query()->where('status', 'ativo')->get(['id']);

        foreach ($datas as $data) {
            foreach ($colaboradores as $colaborador) {
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
     * Preenche horários vazios com a escala vinculada (incl. almoço: saída 1 e retorno / entrada 2).
     */
    private function garantirRegistrosDoDia(string $data): void
    {
        $colaboradores = Colaborador::query()
            ->where('status', 'ativo')
            ->with(['horarioEscala.dias'])
            ->get();

        foreach ($colaboradores as $colaborador) {
            $registro = FrequenciaRegistro::firstOrCreate(
                [
                    'colaborador_id' => $colaborador->id,
                    'data' => $data,
                ],
                [
                    'status' => 'falta',
                    'origem' => 'grade',
                ]
            );

            $this->preencherHorariosDaEscalaNosVazios($registro, $colaborador, $data);
        }
    }

    private function preencherHorariosDaEscalaNosVazios(FrequenciaRegistro $registro, Colaborador $colaborador, string $dataYmd): void
    {
        if ($registro->status === 'justificado') {
            return;
        }

        $dia = $colaborador->horarioEscalaDiaNaData($dataYmd);
        if (! $dia) {
            return;
        }

        $campos = ['entrada_1', 'saida_1', 'entrada_2', 'saida_2'];
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
}
