<?php

namespace App\Support\Rh;

use App\Models\Colaborador;

final class ColaboradorSnapshot
{
    /** @return array<string, mixed> */
    public static function capturar(Colaborador $colaborador): array
    {
        $colaborador->loadMissing('horarioEscala');

        return [
            'id' => $colaborador->id,
            'matricula' => $colaborador->matricula,
            'nome' => $colaborador->nome,
            'status' => $colaborador->status,
            'cargo' => $colaborador->cargo,
            'cbo' => $colaborador->cbo,
            'departamento' => $colaborador->departamento,
            'centro_custo' => $colaborador->centro_custo,
            'tipo_contrato' => $colaborador->tipo_contrato,
            'local_trabalho' => $colaborador->local_trabalho,
            'salario_inicial' => $colaborador->salario_inicial,
            'data_admissao' => $colaborador->data_admissao?->format('Y-m-d'),
            'data_demissao' => $colaborador->data_demissao?->format('Y-m-d'),
            'horario_escala_id' => $colaborador->horario_escala_id,
            'capturado_em' => now()->toIso8601String(),
        ];
    }
}
