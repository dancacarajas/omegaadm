<?php

namespace App\Http\Controllers;

use App\Models\VeiculoSolicitacao;
use App\Models\VeiculoTelemetria;
use App\Support\ContratoAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VeiculoTelemetriaController extends Controller
{
    public function index(Request $request)
    {
        $mes = $request->input('mes', now()->format('Y-m'));
        $inicio = \Carbon\Carbon::createFromFormat('Y-m', $mes)->startOfMonth();
        $fim = $inicio->copy()->endOfMonth();

        $query = ContratoAccess::applyContratoString(VeiculoTelemetria::query())
            ->with('solicitacao')
            ->whereBetween('data', [$inicio->toDateString(), $fim->toDateString()])
            ->when($request->input('busca'), function ($q, $busca) {
                $q->where(function ($sub) use ($busca) {
                    $sub->where('veiculo', 'like', "%{$busca}%")
                        ->orWhere('placa_tag', 'like', "%{$busca}%")
                        ->orWhere('motorista_responsavel', 'like', "%{$busca}%")
                        ->orWhere('rota_realizada', 'like', "%{$busca}%");
                });
            });

        $telemetrias = $query
            ->orderByDesc('data')
            ->paginate(15)
            ->withQueryString();

        $base = (clone $query)->get();
        $indicadores = [
            'km_rodado' => (float) $base->sum('km_rodado'),
            'horas_operacao_min' => $base->sum(fn ($r) => $this->hhmmToMinutos($r->horas_operacao)),
            'tempo_ocioso_min' => $base->sum(fn ($r) => $this->hhmmToMinutos($r->tempo_ocioso)),
            'desvios' => (int) $base->where('desvio_rota', true)->count(),
            'excesso_velocidade' => (int) $base->sum('excesso_velocidade'),
            'eventos_criticos' => (int) $base->sum('eventos_criticos_qtd'),
            'alertas' => (int) $base->sum('alertas_gerados'),
            'consumo_estimado' => (float) $base->sum('consumo_estimado'),
        ];

        return view('veiculos.telemetria.index', compact('telemetrias', 'indicadores', 'mes'));
    }

    public function create()
    {
        $telemetria = new VeiculoTelemetria([
            'data' => now()->toDateString(),
            'desvio_rota' => false,
        ]);

        return view('veiculos.telemetria.create', [
            'telemetria' => $telemetria,
            'solicitacoes' => $this->solicitacoesDisponiveis(),
        ]);
    }

    public function store(Request $request)
    {
        VeiculoTelemetria::create($this->validatedData($request));

        return redirect()
            ->route('veiculos.telemetria.index')
            ->with('success', 'Registro de telemetria cadastrado com sucesso.');
    }

    public function edit(VeiculoTelemetria $telemetrium)
    {
        $this->authorizeContrato($telemetrium->contrato);

        return view('veiculos.telemetria.edit', [
            'telemetria' => $telemetrium,
            'solicitacoes' => $this->solicitacoesDisponiveis(),
        ]);
    }

    public function update(Request $request, VeiculoTelemetria $telemetrium)
    {
        $this->authorizeContrato($telemetrium->contrato);
        $telemetrium->update($this->validatedData($request, $telemetrium));

        return redirect()
            ->route('veiculos.telemetria.index')
            ->with('success', 'Registro de telemetria atualizado.');
    }

    public function destroy(VeiculoTelemetria $telemetrium)
    {
        $this->authorizeContrato($telemetrium->contrato);
        if ($telemetrium->evidencia_path) {
            Storage::disk('public')->delete($telemetrium->evidencia_path);
        }
        $telemetrium->delete();

        return redirect()
            ->route('veiculos.telemetria.index')
            ->with('success', 'Registro de telemetria removido.');
    }

    private function solicitacoesDisponiveis()
    {
        return ContratoAccess::applyContratoString(
            VeiculoSolicitacao::query()->whereNotNull('placa')->orderByDesc('id'),
            'contrato'
        )->get(['id', 'placa', 'marca', 'modelo', 'contrato']);
    }

    private function validatedData(Request $request, ?VeiculoTelemetria $telemetria = null): array
    {
        $data = $request->validate([
            'data' => ['required', 'date'],
            'contrato' => ['nullable', 'string', 'max:255'],
            'veiculo_solicitacao_id' => ['nullable', 'exists:veiculo_solicitacoes,id'],
            'veiculo' => ['required', 'string', 'max:255'],
            'placa_tag' => ['nullable', 'string', 'max:60'],
            'motorista_responsavel' => ['nullable', 'string', 'max:255'],
            'km_inicial' => ['nullable', 'numeric', 'min:0'],
            'km_final' => ['nullable', 'numeric', 'min:0'],
            'km_rodado' => ['nullable', 'numeric', 'min:0'],
            'horas_operacao' => ['nullable', 'regex:/^\d{1,2}:\d{2}$/'],
            'tempo_ocioso' => ['nullable', 'regex:/^\d{1,2}:\d{2}$/'],
            'tempo_parado' => ['nullable', 'regex:/^\d{1,2}:\d{2}$/'],
            'rota_prevista' => ['nullable', 'string'],
            'rota_realizada' => ['nullable', 'string'],
            'desvio_rota' => ['nullable', 'in:0,1'],
            'desvio_justificativa' => ['nullable', 'string'],
            'velocidade_media' => ['nullable', 'numeric', 'min:0'],
            'excesso_velocidade' => ['nullable', 'integer', 'min:0'],
            'frenagens_bruscas' => ['nullable', 'integer', 'min:0'],
            'aceleracoes_bruscas' => ['nullable', 'integer', 'min:0'],
            'localizacao' => ['nullable', 'string'],
            'consumo_estimado' => ['nullable', 'numeric', 'min:0'],
            'alertas_gerados' => ['nullable', 'integer', 'min:0'],
            'eventos_criticos' => ['nullable', 'string'],
            'eventos_criticos_qtd' => ['nullable', 'integer', 'min:0'],
            'evidencia' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp,xls,xlsx,csv,doc,docx', 'max:10240'],
            'observacao' => ['nullable', 'string'],
        ]);

        if (blank($data['km_rodado'] ?? null) && filled($data['km_inicial'] ?? null) && filled($data['km_final'] ?? null)) {
            $data['km_rodado'] = max(0, (float) $data['km_final'] - (float) $data['km_inicial']);
        }

        $data['desvio_rota'] = (($data['desvio_rota'] ?? '0') === '1');
        foreach (['excesso_velocidade', 'frenagens_bruscas', 'aceleracoes_bruscas', 'alertas_gerados', 'eventos_criticos_qtd'] as $k) {
            $data[$k] = (int) ($data[$k] ?? 0);
        }

        if ($request->hasFile('evidencia')) {
            if ($telemetria?->evidencia_path) {
                Storage::disk('public')->delete($telemetria->evidencia_path);
            }
            $data['evidencia_path'] = $request->file('evidencia')->store('veiculos/telemetria/evidencias', 'public');
        }

        unset($data['evidencia']);

        return $data;
    }

    private function authorizeContrato(?string $contrato): void
    {
        if (! ContratoAccess::shouldRestrict()) {
            return;
        }
        abort_unless($contrato && in_array($contrato, ContratoAccess::contratoValores(), true), 404);
    }

    private function hhmmToMinutos(?string $value): int
    {
        if (! $value || ! preg_match('/^(\d{1,2}):(\d{2})$/', $value, $m)) {
            return 0;
        }
        return ((int) $m[1] * 60) + (int) $m[2];
    }
}
