<?php

namespace App\Http\Controllers\Rh;

use App\Http\Controllers\Controller;
use App\Models\FrequenciaFeriado;
use App\Support\FeriadoPontoService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FeriadoController extends Controller
{
    public function index(Request $request)
    {
        $ano = (int) ($request->input('ano') ?: now()->year);

        $feriados = FrequenciaFeriado::query()
            ->when($request->filled('busca'), function ($query) use ($request) {
                $busca = trim((string) $request->input('busca'));
                $query->where(function ($q) use ($busca) {
                    $q->where('nome', 'like', "%{$busca}%")
                        ->orWhere('observacoes', 'like', "%{$busca}%");
                });
            })
            ->where(function ($query) use ($ano) {
                $query->whereYear('data', $ano)
                    ->orWhere('recorrente', true);
            })
            ->orderByRaw('MONTH(data), DAY(data)')
            ->paginate(20)
            ->withQueryString();

        return view('rh.frequencia.feriados.index', compact('feriados', 'ano'));
    }

    public function create()
    {
        return view('rh.frequencia.feriados.create', [
            'feriado' => new FrequenciaFeriado([
                'ativo' => true,
                'recorrente' => false,
                'data' => now()->toDateString(),
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $feriado = FrequenciaFeriado::create($this->validated($request));
        FeriadoPontoService::limparCache();
        $this->sincronizarFeriado($feriado);

        return redirect()
            ->route('rh.frequencia.feriados.index', ['ano' => $feriado->data->year])
            ->with('success', 'Feriado cadastrado e aplicado no ponto dos colaboradores.');
    }

    public function edit(FrequenciaFeriado $feriado)
    {
        return view('rh.frequencia.feriados.edit', compact('feriado'));
    }

    public function update(Request $request, FrequenciaFeriado $feriado)
    {
        $dataAntiga = $feriado->data->toDateString();
        $feriado->update($this->validated($request, $feriado));
        FeriadoPontoService::limparCache();

        if ($feriado->wasChanged('data') && ! $feriado->recorrente) {
            app(FeriadoPontoService::class)->sincronizarData($dataAntiga);
        }

        $this->sincronizarFeriado($feriado);

        return redirect()
            ->route('rh.frequencia.feriados.index', ['ano' => $feriado->data->year])
            ->with('success', 'Feriado atualizado e ponto sincronizado.');
    }

    public function destroy(FrequenciaFeriado $feriado)
    {
        $ano = $feriado->data->year;
        $feriado->delete();
        FeriadoPontoService::limparCache();

        return redirect()
            ->route('rh.frequencia.feriados.index', ['ano' => $ano])
            ->with('success', 'Feriado removido. Registros já gravados no ponto não foram alterados.');
    }

    private function sincronizarFeriado(FrequenciaFeriado $feriado): void
    {
        if (! $feriado->ativo) {
            return;
        }

        $servico = app(FeriadoPontoService::class);

        $base = Carbon::parse($feriado->data);
        $dataAplicar = $feriado->recorrente
            ? Carbon::create(now()->year, $base->month, $base->day)->toDateString()
            : $base->toDateString();

        $servico->sincronizarData($dataAplicar);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?FrequenciaFeriado $feriado = null): array
    {
        $data = $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'data' => ['required', 'date'],
            'recorrente' => ['sometimes', 'boolean'],
            'ativo' => ['required', Rule::in(['0', '1', 0, 1, true, false])],
            'observacoes' => ['nullable', 'string', 'max:2000'],
        ]);

        return [
            'nome' => $data['nome'],
            'data' => $data['data'],
            'recorrente' => $request->boolean('recorrente'),
            'ativo' => $request->boolean('ativo'),
            'observacoes' => $data['observacoes'] ?? null,
        ];
    }
}
