<?php

namespace App\Http\Controllers\Rh;

use App\Http\Controllers\Controller;
use App\Models\Colaborador;
use App\Models\HorarioEscala;
use App\Models\HorarioEscalaDia;
use App\Models\HorarioEscalaExcecao;
use App\Support\HorarioEscalaDiasUteis;
use App\Support\HorarioEscalaSemanalAlternada;
use App\Support\HorarioEscalaVeiculos;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class HorarioEscalaController extends Controller
{
    public function index()
    {
        $escalas = HorarioEscala::query()
            ->withCount(['dias', 'colaboradores'])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('rh.horario_escalas.index', compact('escalas'));
    }

    public function create()
    {
        $escala = new HorarioEscala([
            'tipo' => 'semanal',
            'status' => 'ativo',
            'ciclo_dias' => 2,
            'data_inicio_ciclo' => now()->toDateString(),
        ]);

        return view('rh.horario_escalas.create', $this->dadosFormulario($escala));
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rulesCompletas($request));
        $tipo = $validated['tipo'];
        $this->validarColaboradoresRotativaPorPosicao($request, $tipo, (int) ($validated['ciclo_dias'] ?? HorarioEscalaDiasUteis::POSICOES_PADRAO));
        $data = $this->extrairCabecalho($validated, $tipo);
        $dias = $this->normalizarDiasInput(
            $validated['dias'] ?? [],
            $tipo,
            $this->quantidadeDiasGrade($tipo, (int) ($data['ciclo_dias'] ?? 7))
        );

        DB::transaction(function () use ($request, $data, $dias, $tipo) {
            $escala = HorarioEscala::create($data);
            $this->syncDias($escala, $dias);
            $this->syncColaboradores($escala, $request->input('escala_colaboradores', []), $tipo);
            $this->syncExcecoes($escala, $request);
        });

        return redirect()
            ->route('rh.horarios.index')
            ->with('success', 'Cadastro de horários criado com sucesso.');
    }

    public function edit(HorarioEscala $horario_escala)
    {
        $horario_escala->load(['dias', 'colaboradores', 'excecoes.colaboradorAusente', 'excecoes.colaboradorCobertura']);

        return view('rh.horario_escalas.edit', $this->dadosFormulario($horario_escala, 'escala'));
    }

    public function update(Request $request, HorarioEscala $horario_escala)
    {
        $validated = $request->validate($this->rulesCompletas($request));
        $tipo = $validated['tipo'];
        $this->validarColaboradoresRotativaPorPosicao($request, $tipo, (int) ($validated['ciclo_dias'] ?? HorarioEscalaDiasUteis::POSICOES_PADRAO));
        $data = $this->extrairCabecalho($validated, $tipo);
        $dias = $this->normalizarDiasInput(
            $validated['dias'] ?? [],
            $tipo,
            $this->quantidadeDiasGrade($tipo, (int) ($data['ciclo_dias'] ?? 7))
        );

        DB::transaction(function () use ($request, $horario_escala, $data, $dias, $tipo) {
            $horario_escala->update($data);
            $this->syncDias($horario_escala, $dias);
            $this->syncColaboradores($horario_escala, $request->input('escala_colaboradores', []), $tipo);
            $this->syncExcecoes($horario_escala, $request);
        });

        $horario_escala->refresh();
        $horario_escala->load('dias');

        $template = match (true) {
            $horario_escala->isRotativaSemanal() => HorarioEscalaSemanalAlternada::templateDia($horario_escala),
            $horario_escala->isRotativaDiasUteis() => HorarioEscalaDiasUteis::templateDia($horario_escala),
            $horario_escala->isRotativaVeiculos() => HorarioEscalaVeiculos::templateDia($horario_escala),
            default => $horario_escala->dias->first(),
        };

        return redirect()
            ->route('rh.horarios.edit', $horario_escala)
            ->with('success', 'Cadastro de horários atualizado.')
            ->with('horario_gravado_resumo', $template?->textoGrade() ?? 'Sem jornada gravada');
    }

    public function destroy(HorarioEscala $horario_escala)
    {
        $horario_escala->delete();

        return redirect()
            ->route('rh.horarios.index')
            ->with('success', 'Cadastro de horários removido.');
    }

    /**
     * @return array<string, mixed>
     */
    private function dadosFormulario(HorarioEscala $escala, string $escalaKey = 'escala'): array
    {
        $tipo = old('tipo', $escala->tipo ?? 'semanal');
        $cicloPadrao = match ($tipo) {
            'rotativa_dias_uteis' => HorarioEscalaDiasUteis::POSICOES_PADRAO,
            'rotativa_veiculos' => HorarioEscalaVeiculos::POSICOES,
            default => 2,
        };
        $ciclo = (int) old('ciclo_dias', $escala->ciclo_dias ?? $cicloPadrao);
        $numDias = $this->quantidadeDiasGrade($tipo, $ciclo);

        $colaboradoresDisponiveis = Colaborador::query()
            ->where('status', 'ativo')
            ->where(function ($query) use ($escala) {
                $query->whereNull('horario_escala_id');
                if ($escala->exists) {
                    $query->orWhere('horario_escala_id', $escala->id);
                }
            })
            ->orderBy('nome')
            ->get(['id', 'nome', 'matricula', 'horario_escala_id', 'horario_escala_ciclo_offset']);

        return [
            $escalaKey => $escala,
            'diasPorSemana' => $this->mapDiasParaFormulario($escala, $numDias),
            'numDiasGrade' => $numDias,
            'diasGradeLabels' => $this->labelsGrade($tipo, $numDias),
            'tipoAtual' => $tipo,
            'colaboradoresDisponiveis' => $colaboradoresDisponiveis,
            'excecoesEscala' => $escala->exists ? $escala->excecoes : collect(),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function labelsGrade(string $tipo, int $numDias): array
    {
        $out = [];
        for ($d = 1; $d <= $numDias; $d++) {
            if ($tipo === 'rotativa_semanal' || $tipo === 'rotativa_dias_uteis' || $tipo === 'rotativa_veiculos') {
                $out[$d] = 'Horário nos dias de trabalho';
            } elseif ($tipo === 'rotativa') {
                $out[$d] = $numDias === 2
                    ? ($d === 1 ? 'Dia 1 do ciclo (ex.: trabalho)' : 'Dia 2 do ciclo (ex.: folga)')
                    : "Dia {$d} do ciclo";
            } else {
                $out[$d] = [
                    1 => '2ª feira',
                    2 => '3ª feira',
                    3 => '4ª feira',
                    4 => '5ª feira',
                    5 => '6ª feira',
                    6 => 'Sábado',
                    7 => 'Domingo',
                ][$d] ?? "Dia {$d}";
            }
        }

        return $out;
    }

    /**
     * @return array<int, HorarioEscalaDia>
     */
    private function diasPadraoVazios(int $quantidade): array
    {
        $out = [];
        foreach (range(1, $quantidade) as $dia) {
            $out[$dia] = new HorarioEscalaDia([
                'dia_semana' => $dia,
                'almoco_livre' => false,
                'compensado' => false,
                'neutro' => false,
                'noturno' => false,
            ]);
        }

        return $out;
    }

    /**
     * @return array<int, HorarioEscalaDia>
     */
    private function mapDiasParaFormulario(HorarioEscala $escala, int $quantidade): array
    {
        $base = $this->diasPadraoVazios($quantidade);
        foreach ($escala->dias as $dia) {
            if ($dia->dia_semana >= 1 && $dia->dia_semana <= $quantidade) {
                $base[$dia->dia_semana] = $dia;
            }
        }

        return $base;
    }

    /**
     * @param  array<int, array<string, mixed>>  $dias
     */
    private function syncDias(HorarioEscala $escala, array $dias): void
    {
        HorarioEscalaDia::query()
            ->where('horario_escala_id', $escala->id)
            ->delete();

        $escala->unsetRelation('dias');

        foreach ($dias as $diaSemana => $campos) {
            HorarioEscalaDia::create([
                'horario_escala_id' => $escala->id,
                'dia_semana' => (int) $diaSemana,
                'entrada_1' => $campos['entrada_1'],
                'saida_1' => $campos['saida_1'],
                'entrada_2' => $campos['entrada_2'],
                'saida_2' => $campos['saida_2'],
                'almoco_livre' => (bool) ($campos['almoco_livre'] ?? false),
                'compensado' => (bool) ($campos['compensado'] ?? false),
                'neutro' => (bool) ($campos['neutro'] ?? false),
                'noturno' => (bool) ($campos['noturno'] ?? false),
            ]);
        }

        $escala->load('dias');
    }

    /**
     * @return array<string, array<int|string, mixed>>
     */
    private function rulesCompletas(Request $request): array
    {
        $tipo = $request->input('tipo', 'semanal');

        return $this->cabecalhoRules()
            + $this->diasRules($tipo, $request)
            + $this->colaboradoresRules()
            + $this->excecoesRules();
    }

    /**
     * @return array<string, array<int|string, mixed>>
     */
    private function colaboradoresRules(): array
    {
        return [
            'escala_colaboradores' => ['nullable', 'array'],
            'escala_colaboradores.*.colaborador_id' => ['required', 'exists:colaboradores,id'],
            'escala_colaboradores.*.ciclo_offset' => ['nullable', 'integer', 'min:0', 'max:13'],
        ];
    }

    private function validarColaboradoresRotativaPorPosicao(Request $request, string $tipo, int $cicloDias): void
    {
        if (! in_array($tipo, ['rotativa_dias_uteis', 'rotativa_veiculos'], true)) {
            return;
        }

        $quantidade = $tipo === 'rotativa_veiculos'
            ? HorarioEscalaVeiculos::POSICOES
            : max(
                HorarioEscalaDiasUteis::POSICOES_MIN,
                min(HorarioEscalaDiasUteis::POSICOES_MAX, $cicloDias)
            );

        $rows = collect($request->input('escala_colaboradores', []))
            ->filter(fn ($row) => is_array($row) && ! empty($row['colaborador_id']))
            ->values();

        $errors = [];

        if ($rows->count() !== $quantidade) {
            $errors['escala_colaboradores'] = "Selecione exatamente {$quantidade} colaboradores para esta escala.";
        }

        $offsets = $rows->map(fn (array $row) => (int) ($row['ciclo_offset'] ?? -1));
        $foraDoLimite = $offsets->filter(fn (int $o) => $o < 0 || $o >= $quantidade);
        if ($foraDoLimite->isNotEmpty()) {
            $errors['escala_colaboradores'] = $errors['escala_colaboradores']
                ?? 'Cada posição deve estar entre 0 e '.($quantidade - 1).'.';
        }

        $duplicados = $offsets->duplicates()->unique()->values();
        if ($duplicados->isNotEmpty()) {
            $errors['escala_colaboradores'] = 'Dois colaboradores não podem ocupar a mesma posição.';
        }

        $ocupadas = $offsets->unique()->all();
        for ($pos = 0; $pos < $quantidade; $pos++) {
            if (! in_array($pos, $ocupadas, true)) {
                $visual = $pos + 1;
                $errors['escala_colaboradores'] = "A posição {$visual} ainda não possui colaborador.";
                break;
            }
        }

        if ($rows->count() === $quantidade && $duplicados->isEmpty() && count($ocupadas) !== $quantidade) {
            $errors['escala_colaboradores'] = 'Cada posição do rodízio deve ser atribuída a um único colaborador.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * @return array<string, array<int|string, mixed>>
     */
    private function excecoesRules(): array
    {
        return [
            'excecoes' => ['nullable', 'array'],
            'excecoes.*.id' => ['nullable', 'integer', 'exists:horario_escala_excecoes,id'],
            'excecoes.*.colaborador_ausente_id' => ['nullable', 'exists:colaboradores,id'],
            'excecoes.*.colaborador_cobertura_id' => ['nullable', 'exists:colaboradores,id'],
            'excecoes.*.data_inicio' => ['nullable', 'date'],
            'excecoes.*.data_fim' => ['nullable', 'date'],
            'excecoes.*.motivo' => ['nullable', 'string', 'max:500'],
            'excecoes_remover' => ['nullable', 'array'],
            'excecoes_remover.*' => ['integer', 'exists:horario_escala_excecoes,id'],
        ];
    }

    /**
     * @param  array<int|string, array<string, mixed>>  $rows
     */
    private function syncColaboradores(HorarioEscala $escala, array $rows, string $tipo): void
    {
        $vinculados = collect($rows)
            ->filter(fn (array $row) => ! empty($row['colaborador_id']))
            ->mapWithKeys(fn (array $row) => [
                (int) $row['colaborador_id'] => (int) ($row['ciclo_offset'] ?? 0),
            ]);

        if ($vinculados->isNotEmpty()) {
            Colaborador::query()
                ->where('horario_escala_id', $escala->id)
                ->whereNotIn('id', $vinculados->keys())
                ->update([
                    'horario_escala_id' => null,
                    'horario_escala_ciclo_offset' => 0,
                ]);
        }

        $maxOffset = match ($tipo) {
            'rotativa_semanal' => 1,
            'rotativa_dias_uteis' => max(1, (int) ($escala->ciclo_dias ?? HorarioEscalaDiasUteis::POSICOES_PADRAO)) - 1,
            'rotativa_veiculos' => HorarioEscalaVeiculos::POSICOES - 1,
            default => max(1, (int) ($escala->ciclo_dias ?? 2)) - 1,
        };

        $usaOffset = in_array($tipo, ['rotativa', 'rotativa_semanal', 'rotativa_dias_uteis', 'rotativa_veiculos'], true);

        foreach ($vinculados as $colaboradorId => $offset) {
            $data = ['horario_escala_id' => $escala->id];
            if ($usaOffset) {
                $data['horario_escala_ciclo_offset'] = min(max(0, $offset), $maxOffset);
            } else {
                $data['horario_escala_ciclo_offset'] = 0;
            }

            Colaborador::query()->whereKey($colaboradorId)->update($data);
        }
    }

    private function syncExcecoes(HorarioEscala $escala, Request $request): void
    {
        $remover = array_filter(array_map('intval', (array) $request->input('excecoes_remover', [])));
        if ($remover !== []) {
            HorarioEscalaExcecao::query()
                ->where('horario_escala_id', $escala->id)
                ->whereIn('id', $remover)
                ->delete();
        }

        foreach ((array) $request->input('excecoes', []) as $row) {
            if (empty($row['colaborador_ausente_id']) || empty($row['data_inicio']) || empty($row['data_fim'])) {
                continue;
            }

            $payload = [
                'colaborador_ausente_id' => (int) $row['colaborador_ausente_id'],
                'colaborador_cobertura_id' => ! empty($row['colaborador_cobertura_id'])
                    ? (int) $row['colaborador_cobertura_id']
                    : null,
                'data_inicio' => $row['data_inicio'],
                'data_fim' => $row['data_fim'],
                'motivo' => $row['motivo'] ?? null,
            ];

            if (! empty($row['id'])) {
                HorarioEscalaExcecao::query()
                    ->where('horario_escala_id', $escala->id)
                    ->whereKey((int) $row['id'])
                    ->update($payload);
            } else {
                HorarioEscalaExcecao::create(array_merge($payload, [
                    'horario_escala_id' => $escala->id,
                ]));
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function extrairCabecalho(array $validated, string $tipo): array
    {
        $data = collect($validated)->only(['nome', 'tipo', 'status'])->all();

        if ($tipo === 'rotativa') {
            $data['ciclo_dias'] = (int) $validated['ciclo_dias'];
            $data['data_inicio_ciclo'] = $validated['data_inicio_ciclo'];
        } elseif ($tipo === 'rotativa_semanal') {
            $data['ciclo_dias'] = 14;
            $data['data_inicio_ciclo'] = $this->normalizarSegundaInicio($validated['data_inicio_ciclo']);
        } elseif ($tipo === 'rotativa_dias_uteis') {
            $data['ciclo_dias'] = max(
                HorarioEscalaDiasUteis::POSICOES_MIN,
                min(HorarioEscalaDiasUteis::POSICOES_MAX, (int) $validated['ciclo_dias'])
            );
            $data['data_inicio_ciclo'] = $this->normalizarSegundaInicio($validated['data_inicio_ciclo']);
        } elseif ($tipo === 'rotativa_veiculos') {
            $data['ciclo_dias'] = HorarioEscalaVeiculos::POSICOES;
            $data['data_inicio_ciclo'] = $validated['data_inicio_ciclo'];
        } else {
            $data['ciclo_dias'] = null;
            $data['data_inicio_ciclo'] = null;
        }

        return $data;
    }

    private function normalizarSegundaInicio(string $dataYmd): string
    {
        $data = Carbon::parse($dataYmd)->startOfDay();

        return ($data->isoWeekday() === Carbon::MONDAY
            ? $data
            : $data->copy()->startOfWeek(Carbon::MONDAY)
        )->toDateString();
    }

    private function quantidadeDiasGrade(string $tipo, int $ciclo): int
    {
        return match ($tipo) {
            'rotativa_semanal', 'rotativa_dias_uteis', 'rotativa_veiculos' => 1,
            'rotativa' => max(2, min(14, $ciclo)),
            default => 7,
        };
    }

    /**
     * @return array<string, array<int|string, mixed>>
     */
    private function cabecalhoRules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:255'],
            'tipo' => ['required', Rule::in(['semanal', 'rotativa', 'rotativa_semanal', 'rotativa_dias_uteis', 'rotativa_veiculos'])],
            'status' => ['required', Rule::in(['ativo', 'inativo'])],
            'ciclo_dias' => [
                'required_if:tipo,rotativa,rotativa_dias_uteis,rotativa_veiculos',
                'nullable',
                'integer',
                'min:2',
                'max:14',
            ],
            'data_inicio_ciclo' => [
                'required_if:tipo,rotativa,rotativa_semanal,rotativa_dias_uteis,rotativa_veiculos',
                'nullable',
                'date',
            ],
        ];
    }

    /**
     * @return array<string, array<int|string, mixed>>
     */
    private function diasRules(string $tipo, Request $request): array
    {
        $max = match ($tipo) {
            'rotativa_semanal', 'rotativa_dias_uteis', 'rotativa_veiculos' => 1,
            'rotativa' => max(2, min(14, (int) $request->input('ciclo_dias', 2))),
            default => 7,
        };

        $rules = ['dias' => ['required', 'array']];
        foreach (range(1, $max) as $d) {
            $p = "dias.$d";
            $rules["$p.entrada_1"] = ['nullable', 'date_format:H:i'];
            $rules["$p.saida_1"] = ['nullable', 'date_format:H:i'];
            $rules["$p.entrada_2"] = ['nullable', 'date_format:H:i'];
            $rules["$p.saida_2"] = ['nullable', 'date_format:H:i'];
            $rules["$p.almoco_livre"] = ['nullable', 'in:0,1'];
            $rules["$p.compensado"] = ['nullable', 'in:0,1'];
            $rules["$p.neutro"] = ['nullable', 'in:0,1'];
            $rules["$p.noturno"] = ['nullable', 'in:0,1'];
        }

        return $rules;
    }

    /**
     * @param  array<int|string, array<string, mixed>>  $dias
     * @return array<int, array<string, mixed>>
     */
    private function normalizarDiasInput(array $dias, string $tipo, int $quantidade): array
    {
        $out = [];
        foreach (range(1, $quantidade) as $d) {
            $row = $dias[$d] ?? $dias[(string) $d] ?? [];
            $out[$d] = [
                'entrada_1' => $this->normalizeTime($row['entrada_1'] ?? null),
                'saida_1' => $this->normalizeTime($row['saida_1'] ?? null),
                'entrada_2' => $this->normalizeTime($row['entrada_2'] ?? null),
                'saida_2' => $this->normalizeTime($row['saida_2'] ?? null),
                'almoco_livre' => (bool) (($row['almoco_livre'] ?? 0) == 1),
                'compensado' => (bool) (($row['compensado'] ?? 0) == 1),
                'neutro' => (bool) (($row['neutro'] ?? 0) == 1),
                'noturno' => (bool) (($row['noturno'] ?? 0) == 1),
            ];
        }

        return $out;
    }

    private function normalizeTime(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return strlen($value) === 5 ? $value.':00' : $value;
    }
}
