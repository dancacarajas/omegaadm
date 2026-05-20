<?php

namespace App\Support\Rh;

/**
 * @deprecated Use {@see ExtratoAusenciasPeriodo}
 */
class ExtratoFaltasPeriodo
{
    public function montar(string $inicio, string $fim, ?int $colaboradorId = null): array
    {
        $extrato = app(ExtratoAusenciasPeriodo::class)->montar(
            $inicio,
            $fim,
            $colaboradorId,
            ExtratoAusenciasPeriodo::NATUREZA_INJUSTIFICADA
        );

        $colaboradores = [];
        foreach ($extrato['colaboradores'] as $item) {
            $colaboradores[] = [
                'colaborador' => $item['colaborador'],
                'total_faltas' => $item['qtd_ocorrencias'],
                'faltas' => $item['ocorrencias'],
            ];
        }

        return [
            'inicio' => $extrato['inicio'],
            'fim' => $extrato['fim'],
            'dias' => $extrato['dias'],
            'total_faltas' => $extrato['total_ocorrencias'],
            'colaborador_id' => $extrato['colaborador_id'],
            'colaboradores' => $colaboradores,
        ];
    }
}
