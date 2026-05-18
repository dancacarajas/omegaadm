<?php

namespace App\Support\Rh;

use Carbon\Carbon;

final class CartaoPontoPeriodo
{
    /**
     * Competência padrão (21 do mês anterior até 20 do mês informado).
     *
     * @return array{inicio: string, fim: string}
     */
    public static function competenciaPorMes(?string $mesYm): array
    {
        $ref = $mesYm
            ? Carbon::createFromFormat('Y-m', $mesYm)->startOfMonth()
            : now()->startOfMonth();

        $fim = $ref->copy()->day(20);
        $inicio = $fim->copy()->subMonth()->day(21);

        return [
            'inicio' => $inicio->toDateString(),
            'fim' => $fim->toDateString(),
        ];
    }
}
