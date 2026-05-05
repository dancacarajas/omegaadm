<?php

namespace App\Http\Controllers\Rh;

use App\Http\Controllers\Controller;
use App\Models\HorarioEscala;
use App\Models\HorarioEscalaDia;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

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
        $escala = new HorarioEscala(['tipo' => 'semanal', 'status' => 'ativo']);
        $diasPorSemana = $this->diasPadraoVazios();

        return view('rh.horario_escalas.create', compact('escala', 'diasPorSemana'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->cabecalhoRules() + $this->diasRules());
        $data = collect($validated)->only(['nome', 'tipo', 'status'])->all();
        $dias = $this->normalizarDiasInput($validated['dias'] ?? []);

        DB::transaction(function () use ($data, $dias) {
            $escala = HorarioEscala::create($data);
            $this->syncDias($escala, $dias);
        });

        return redirect()
            ->route('rh.horarios.index')
            ->with('success', 'Cadastro de horários criado com sucesso.');
    }

    public function edit(HorarioEscala $horario_escala)
    {
        $horario_escala->load('dias');
        $diasPorSemana = $this->mapDiasParaFormulario($horario_escala);

        return view('rh.horario_escalas.edit', ['escala' => $horario_escala, 'diasPorSemana' => $diasPorSemana]);
    }

    public function update(Request $request, HorarioEscala $horario_escala)
    {
        $validated = $request->validate($this->cabecalhoRules() + $this->diasRules());
        $data = collect($validated)->only(['nome', 'tipo', 'status'])->all();
        $dias = $this->normalizarDiasInput($validated['dias'] ?? []);

        DB::transaction(function () use ($horario_escala, $data, $dias) {
            $horario_escala->update($data);
            $horario_escala->dias()->delete();
            $this->syncDias($horario_escala, $dias);
        });

        return redirect()
            ->route('rh.horarios.index')
            ->with('success', 'Cadastro de horários atualizado.');
    }

    public function destroy(HorarioEscala $horario_escala)
    {
        $horario_escala->delete();

        return redirect()
            ->route('rh.horarios.index')
            ->with('success', 'Cadastro de horários removido.');
    }

    /**
     * @return array<int, HorarioEscalaDia>
     */
    private function diasPadraoVazios(): array
    {
        $out = [];
        foreach (range(1, 7) as $dia) {
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
    private function mapDiasParaFormulario(HorarioEscala $escala): array
    {
        $base = $this->diasPadraoVazios();
        foreach ($escala->dias as $dia) {
            $base[$dia->dia_semana] = $dia;
        }

        return $base;
    }

    /**
     * @param  array<int, array<string, mixed>>  $dias
     */
    private function syncDias(HorarioEscala $escala, array $dias): void
    {
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
    }

    /**
     * @return array<string, array<int|string, mixed>>
     */
    private function cabecalhoRules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:255'],
            'tipo' => ['required', Rule::in(['semanal'])],
            'status' => ['required', Rule::in(['ativo', 'inativo'])],
        ];
    }

    /**
     * @return array<string, array<int|string, mixed>>
     */
    private function diasRules(): array
    {
        $rules = [
            'dias' => ['required', 'array'],
        ];
        foreach (range(1, 7) as $d) {
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
    private function normalizarDiasInput(array $dias): array
    {
        $out = [];
        foreach (range(1, 7) as $d) {
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
