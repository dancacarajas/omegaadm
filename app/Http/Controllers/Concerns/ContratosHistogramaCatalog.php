<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Contrato;
use App\Support\ContratoAccess;

trait ContratosHistogramaCatalog
{
    /**
     * Identificadores de contrato usados no histograma (centro de custo com fallback em número).
     *
     * @return array<int, string>
     */
    protected function contratosDisponiveis(): array
    {
        if (ContratoAccess::shouldRestrict()) {
            return ContratoAccess::applyContratoModel(Contrato::query())
                ->get(['numero', 'centro_custo'])
                ->map(function ($c) {
                    $chave = trim((string) ($c->centro_custo ?: $c->numero));

                    return $chave !== '' ? $chave : null;
                })
                ->filter()
                ->unique()
                ->values()
                ->all();
        }

        return Contrato::query()
            ->orderBy('numero')
            ->get(['numero', 'centro_custo'])
            ->map(function ($c) {
                $chave = trim((string) ($c->centro_custo ?: $c->numero));

                return $chave !== '' ? $chave : null;
            })
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
