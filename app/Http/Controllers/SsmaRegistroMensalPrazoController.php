<?php

namespace App\Http\Controllers;

use App\Models\SsmaRegistroMensal;
use App\Models\SsmaRegistroMensalPrazo;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SsmaRegistroMensalPrazoController extends Controller
{
    public function index()
    {
        $this->authorizeView();

        $prazos = SsmaRegistroMensalPrazo::query()
            ->orderByDesc('competencia')
            ->get();

        $linhas = $prazos->map(function (SsmaRegistroMensalPrazo $prazo) {
            $mesRef = $prazo->recorrente
                ? now()->startOfMonth()
                : Carbon::parse($prazo->competencia)->startOfMonth();

            return array_merge(
                ['prazo' => $prazo, 'mes_ref_situacao' => $mesRef],
                $prazo->situacaoNoMes($mesRef)
            );
        });

        $podeEditar = auth()->user()->podeAcaoNoModulo('sesmt', 'editar');

        return view('sesmt.registros.prazos.index', compact('linhas', 'podeEditar'));
    }

    public function create()
    {
        $this->authorizeEdit();

        return view('sesmt.registros.prazos.create', [
            'prazo' => new SsmaRegistroMensalPrazo([
                'exige_finalizado' => true,
                'recorrente' => false,
                'data_limite' => now()->addDays(10)->seconds(0),
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeEdit();

        $data = $request->validate([
            'competencia' => ['required', 'date_format:Y-m'],
            'data_limite' => ['required', 'date'],
            'recorrente' => ['required', 'in:0,1'],
            'exige_finalizado' => ['nullable', 'boolean'],
            'observacao' => ['nullable', 'string', 'max:500'],
        ]);

        $recorrente = ($data['recorrente'] ?? '0') === '1' || $data['recorrente'] === 1;
        $competencia = Carbon::createFromFormat('Y-m', $data['competencia'])->startOfMonth()->toDateString();

        if ($recorrente) {
            if (SsmaRegistroMensalPrazo::query()->where('recorrente', true)->exists()) {
                throw ValidationException::withMessages([
                    'recorrente' => 'Já existe um prazo recorrente. Edite ou exclua o cadastro existente antes de criar outro.',
                ]);
            }
        } elseif (SsmaRegistroMensalPrazo::query()->where('recorrente', false)->whereDate('competencia', $competencia)->exists()) {
            throw ValidationException::withMessages([
                'competencia' => 'Já existe um prazo único para esta competência.',
            ]);
        }

        SsmaRegistroMensalPrazo::create([
            'competencia' => $competencia,
            'recorrente' => $recorrente,
            'data_limite' => Carbon::parse($data['data_limite']),
            'exige_finalizado' => (bool) ($data['exige_finalizado'] ?? true),
            'observacao' => $data['observacao'] ?? null,
        ]);

        return redirect()
            ->route('sesmt.registros.prazos.index')
            ->with('success', 'Prazo (SLA) cadastrado.');
    }

    public function edit(SsmaRegistroMensalPrazo $prazo)
    {
        $this->authorizeEdit();

        return view('sesmt.registros.prazos.edit', compact('prazo'));
    }

    public function update(Request $request, SsmaRegistroMensalPrazo $prazo)
    {
        $this->authorizeEdit();

        $data = $request->validate([
            'competencia' => ['required', 'date_format:Y-m'],
            'data_limite' => ['required', 'date'],
            'recorrente' => ['required', 'in:0,1'],
            'exige_finalizado' => ['nullable', 'boolean'],
            'observacao' => ['nullable', 'string', 'max:500'],
        ]);

        $recorrente = ($data['recorrente'] ?? '0') === '1' || $data['recorrente'] === 1;
        $competencia = Carbon::createFromFormat('Y-m', $data['competencia'])->startOfMonth()->toDateString();

        if ($recorrente) {
            if (SsmaRegistroMensalPrazo::query()->where('recorrente', true)->where('id', '!=', $prazo->id)->exists()) {
                throw ValidationException::withMessages([
                    'recorrente' => 'Já existe outro prazo recorrente. Mantenha apenas um ou desmarque a recorrência.',
                ]);
            }
        } elseif (SsmaRegistroMensalPrazo::query()->where('recorrente', false)->whereDate('competencia', $competencia)->where('id', '!=', $prazo->id)->exists()) {
            throw ValidationException::withMessages([
                'competencia' => 'Já existe um prazo único para esta competência.',
            ]);
        }

        $prazo->update([
            'competencia' => $competencia,
            'recorrente' => $recorrente,
            'data_limite' => Carbon::parse($data['data_limite']),
            'exige_finalizado' => (bool) ($data['exige_finalizado'] ?? true),
            'observacao' => $data['observacao'] ?? null,
        ]);

        return redirect()
            ->route('sesmt.registros.prazos.index')
            ->with('success', 'Prazo atualizado.');
    }

    public function destroy(SsmaRegistroMensalPrazo $prazo)
    {
        $this->authorizeEdit();

        $prazo->delete();

        return redirect()
            ->route('sesmt.registros.prazos.index')
            ->with('success', 'Prazo removido.');
    }

    private function authorizeView(): void
    {
        abort_unless(
            auth()->user()?->podeSecaoSesmt('prazos_sla'),
            403,
            'Seu perfil não tem acesso a esta área do SSMA.'
        );
    }

    private function authorizeEdit(): void
    {
        abort_unless(
            auth()->user()?->podeAcaoNoModulo('sesmt', 'editar'),
            403,
            'Seu perfil não pode gerenciar prazos do registro mensal.'
        );
    }
}
