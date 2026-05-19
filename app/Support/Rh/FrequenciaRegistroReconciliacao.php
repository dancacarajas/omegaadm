<?php

namespace App\Support\Rh;

use App\Models\FrequenciaRegistro;
use App\Support\EscalaPontoRegras;
use App\Support\FeriadoPontoService;
use App\Support\FrequenciaCalculo;

/**
 * Corrige registros importados como falta em dias de folga na escala ou feriado.
 */
class FrequenciaRegistroReconciliacao
{
    public function corrigirFaltasIndevidasNoPeriodo(string $dataInicio, string $dataFim, ?int $colaboradorId = null): int
    {
        return $this->removerRegistrosForaDoVinculoNoPeriodo($dataInicio, $dataFim, $colaboradorId)
            + $this->corrigirFaltasFolgaFeriadoNoPeriodo($dataInicio, $dataFim, $colaboradorId);
    }

    public function removerRegistrosForaDoVinculoNoPeriodo(string $dataInicio, string $dataFim, ?int $colaboradorId = null): int
    {
        $removidos = 0;

        FrequenciaRegistro::query()
            ->whereDate('data', '>=', $dataInicio)
            ->whereDate('data', '<=', $dataFim)
            ->when($colaboradorId !== null, fn ($q) => $q->where('colaborador_id', $colaboradorId))
            ->with('colaborador:id,data_admissao,data_demissao')
            ->orderBy('id')
            ->chunkById(100, function ($registros) use (&$removidos) {
                foreach ($registros as $registro) {
                    $colaborador = $registro->colaborador;
                    if ($colaborador === null || ! ColaboradorVinculoPonto::contaPontoNaData($colaborador, $registro->data)) {
                        $registro->delete();
                        $removidos++;
                    }
                }
            });

        return $removidos;
    }

    private function corrigirFaltasFolgaFeriadoNoPeriodo(string $dataInicio, string $dataFim, ?int $colaboradorId = null): int
    {
        $corrigidos = 0;
        $regras = app(EscalaPontoRegras::class);
        $feriadoPonto = app(FeriadoPontoService::class);

        FrequenciaRegistro::query()
            ->whereDate('data', '>=', $dataInicio)
            ->whereDate('data', '<=', $dataFim)
            ->when($colaboradorId !== null, fn ($q) => $q->where('colaborador_id', $colaboradorId))
            ->where('status', 'falta')
            ->whereHas('colaborador', function ($q) {
                ColaboradorVinculoPonto::aplicarFiltroRegistroNaData($q);
            })
            ->with(['colaborador.horarioEscala.dias'])
            ->orderBy('id')
            ->chunkById(100, function ($registros) use ($regras, $feriadoPonto, &$corrigidos) {
                foreach ($registros as $registro) {
                    $colaborador = $registro->colaborador;
                    if ($colaborador === null) {
                        continue;
                    }

                    if (FrequenciaCalculo::minutosTrabalhados($registro) > 0) {
                        continue;
                    }

                    if ($regras->diaAbonadoPorFolgaEscala($colaborador, $registro->data)) {
                        $registro->update(['status' => 'folga']);
                        $corrigidos++;

                        continue;
                    }

                    $feriado = $feriadoPonto->feriadoNaData($registro->data);
                    if ($feriado !== null && $feriadoPonto->deveAplicarFeriadoNoRegistro($registro)) {
                        $feriadoPonto->aplicarFeriadoNoColaborador(
                            $colaborador->id,
                            $registro->data->format('Y-m-d'),
                            $feriado
                        );
                        $corrigidos++;
                    }
                }
            });

        return $corrigidos;
    }
}
