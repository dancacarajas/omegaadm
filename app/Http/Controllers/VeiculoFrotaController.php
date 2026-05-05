<?php

namespace App\Http\Controllers;

use App\Models\VeiculoManutencao;
use App\Models\VeiculoSolicitacao;
use App\Models\VeiculoTelemetria;
use App\Support\ContratoAccess;
use Carbon\Carbon;
use Illuminate\Http\Request;

class VeiculoFrotaController extends Controller
{
    public function index(Request $request)
    {
        $mes = $request->input('mes', now()->format('Y-m'));
        $inicio = Carbon::createFromFormat('Y-m', $mes)->startOfMonth();
        $fim = $inicio->copy()->endOfMonth();

        $frotaQuery = ContratoAccess::applyContratoString(
            VeiculoSolicitacao::query()->where('status', 'concluido')->whereNotNull('placa'),
            'contrato'
        );

        $frota = $frotaQuery->get();
        $placas = $frota->pluck('placa')->filter()->unique()->values();

        $manutencoesMes = ContratoAccess::applyContratoString(
            VeiculoManutencao::query()->whereBetween('data_solicitacao', [$inicio->toDateString(), $fim->toDateString()]),
            'contrato'
        )->get();

        $manutencoesAtivas = ContratoAccess::applyContratoString(
            VeiculoManutencao::query()->whereIn('status', ['aberto', 'em_andamento']),
            'contrato'
        )->get();

        $telemetriaMes = ContratoAccess::applyContratoString(
            VeiculoTelemetria::query()->whereBetween('data', [$inicio->toDateString(), $fim->toDateString()]),
            'contrato'
        )->get();

        $itensFrota = $frota->map(function (VeiculoSolicitacao $s) use ($manutencoesAtivas, $telemetriaMes) {
            $placa = (string) $s->placa;
            $manutencaoAberta = $manutencoesAtivas->firstWhere('placa_tag', $placa);
            $tele = $telemetriaMes->where('placa_tag', $placa);

            return [
                'id' => $s->id,
                'veiculo' => trim(($s->marca ?? '').' '.($s->modelo ?? '')) ?: ('Veículo #'.$s->id),
                'placa' => $placa ?: '-',
                'tipo' => $s->tipo ?: '-',
                'contrato' => $s->contrato ?: '-',
                'disponivel' => $manutencaoAberta === null,
                'status_manutencao' => $manutencaoAberta?->status,
                'km_rodado' => (float) $tele->sum('km_rodado'),
                'horas_operacao_min' => $tele->sum(fn ($r) => $this->hhmmToMinutos($r->horas_operacao)),
                'tempo_ocioso_min' => $tele->sum(fn ($r) => $this->hhmmToMinutos($r->tempo_ocioso)),
                'desvios' => (int) $tele->where('desvio_rota', true)->count(),
                'excesso_velocidade' => (int) $tele->sum('excesso_velocidade'),
                'alertas' => (int) $tele->sum('alertas_gerados'),
            ];
        })->values();

        $indicadores = [
            'ativos_total' => $itensFrota->count(),
            'disponiveis' => $itensFrota->where('disponivel', true)->count(),
            'indisponiveis' => $itensFrota->where('disponivel', false)->count(),
            'manutencoes_mes' => $manutencoesMes->count(),
            'dias_parados' => (int) $manutencoesMes->sum('dias_parado'),
            'impacto_financeiro' => (float) $manutencoesMes->sum('impacto_financeiro'),
            'km_total' => (float) $itensFrota->sum('km_rodado'),
            'horas_total_min' => (int) $itensFrota->sum('horas_operacao_min'),
            'ociosidade_total_min' => (int) $itensFrota->sum('tempo_ocioso_min'),
        ];

        $manutencoes = ContratoAccess::applyContratoString(
            VeiculoManutencao::query(),
            'contrato'
        )->orderByDesc('data_solicitacao')->paginate(10)->withQueryString();

        return view('veiculos.frota.index', compact('mes', 'indicadores', 'itensFrota', 'manutencoes'));
    }

    private function hhmmToMinutos(?string $value): int
    {
        if (! $value || ! preg_match('/^(\d{1,2}):(\d{2})$/', $value, $m)) {
            return 0;
        }
        return ((int) $m[1] * 60) + (int) $m[2];
    }
}
