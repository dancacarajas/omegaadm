<?php

namespace App\Http\Controllers;

use App\Models\MedicaoContratualItem;
use App\Models\VeiculoManutencao;
use App\Support\ContratoAccess;
use Carbon\Carbon;
use Illuminate\Http\Request;

class MedicaoFluxoFinanceiroController extends Controller
{
    public function index(Request $request)
    {
        $mes = $request->input('mes', now()->format('Y-m'));
        $contrato = $request->input('contrato');
        $valorGlobalContrato = (float) ($request->input('valor_global_contrato') ?? 0);
        $inicio = Carbon::createFromFormat('Y-m', $mes)->startOfMonth();
        $fim = $inicio->copy()->endOfMonth();

        $baseQuery = ContratoAccess::applyContratoString(MedicaoContratualItem::query(), 'contrato')
            ->when($contrato, fn ($q) => $q->where('contrato', $contrato));

        $mesItens = (clone $baseQuery)
            ->whereBetween('competencia', [$inicio->toDateString(), $fim->toDateString()])
            ->get();

        $acumuladoItens = (clone $baseQuery)
            ->whereDate('competencia', '<=', $fim->toDateString())
            ->get();

        $manutencaoMes = ContratoAccess::applyContratoString(VeiculoManutencao::query(), 'contrato')
            ->when($contrato, fn ($q) => $q->where('contrato', $contrato))
            ->whereBetween('data_solicitacao', [$inicio->toDateString(), $fim->toDateString()])
            ->get();

        $fluxo = [
            'valor_global_contrato' => $valorGlobalContrato,
            'valor_medido_mes' => (float) $mesItens->sum('valor_medido'),
            'valor_acumulado_medido' => (float) $acumuladoItens->sum('valor_medido'),
            'valores_nao_medidos' => (float) $mesItens->sum(fn ($i) => max(0, (float) $i->valor_previsto - (float) $i->valor_medido)),
            'valores_pendentes' => (float) $mesItens->sum('valor_executado_nao_medido'),
            'impacto_glosas' => (float) $mesItens->sum('valor_glosado'),
            'impacto_indisponibilidade_equip' => (float) $manutencaoMes->sum('impacto_financeiro'),
            'impacto_faltas_mobilizacao_desmobilizacao' => (float) $mesItens->sum('valor_mobilizacao')
                + (float) $mesItens->sum('valor_nao_executado')
                + (float) $mesItens->sum('valor_nao_programado'),
            'hora_extra' => (float) $mesItens->sum('valor_hora_extra'),
            'valores_adicionais' => (float) $mesItens->sum('valor_adicional'),
        ];

        $fluxo['saldo_contratual'] = $fluxo['valor_global_contrato'] > 0
            ? max(0, $fluxo['valor_global_contrato'] - $fluxo['valor_acumulado_medido'])
            : 0;

        $resumoMes = $mesItens
            ->sortByDesc('valor_medido')
            ->take(12)
            ->values();

        return view('medicao.fluxo-financeiro.index', [
            'mes' => $mes,
            'contrato' => $contrato,
            'fluxo' => $fluxo,
            'resumoMes' => $resumoMes,
            'contratos' => $this->contratosDisponiveis(),
        ]);
    }

    private function contratosDisponiveis()
    {
        return ContratoAccess::applyContratoString(MedicaoContratualItem::query(), 'contrato')
            ->whereNotNull('contrato')
            ->distinct()
            ->orderBy('contrato')
            ->pluck('contrato');
    }
}
