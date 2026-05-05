<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ContratosHistogramaCatalog;
use App\Models\ContratoHistogramaRecorte;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PguDashboardController extends Controller
{
    use ContratosHistogramaCatalog;

    public function index(Request $request)
    {
        $contratos = $this->contratosDisponiveis();
        $contrato = $request->input('contrato', $contratos[0] ?? '');
        $competencia = $request->input('competencia', now()->format('Y-m'));
        $dataLimite = $request->input('data_limite_etapa_2');
        if (! filled($dataLimite) && $contrato !== '') {
            $compDate = Carbon::createFromFormat('Y-m', $competencia)->startOfMonth()->toDateString();
            $recorte = ContratoHistogramaRecorte::query()
                ->where('contrato', $contrato)
                ->whereDate('competencia', $compDate)
                ->first();
            $dataLimite = $recorte?->data_limite_etapa_2?->format('Y-m-d');
        }

        return view('dashboard.pgu', [
            'contratos' => $contratos,
            'contratoDefault' => $contrato,
            'competenciaDefault' => $competencia,
            'dataLimiteDefault' => $dataLimite,
        ]);
    }
}
