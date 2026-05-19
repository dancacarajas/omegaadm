<?php

namespace App\Support\Rh;

use App\Models\Colaborador;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;

/**
 * Define se o colaborador já estava no quadro em uma data (admissão / demissão).
 */
class ColaboradorVinculoPonto
{
    public static function contaPontoNaData(Colaborador $colaborador, DateTimeInterface|string $data): bool
    {
        $dia = $data instanceof Carbon
            ? $data->copy()->startOfDay()
            : Carbon::parse($data)->startOfDay();

        if ($colaborador->data_admissao && $dia->lt($colaborador->data_admissao->copy()->startOfDay())) {
            return false;
        }

        if ($colaborador->data_demissao && $dia->gt($colaborador->data_demissao->copy()->startOfDay())) {
            return false;
        }

        return true;
    }

    /**
     * Restringe colaboradores cujo vínculo cobre a data do registro de frequência (join implícito).
     */
    public static function aplicarFiltroRegistroNaData(Builder $colaboradorQuery): void
    {
        $colaboradorQuery->where(function ($w) {
            $w->whereNull('data_admissao')
                ->orWhereColumn('data_admissao', '<=', 'frequencia_registros.data');
        })->where(function ($w) {
            $w->whereNull('data_demissao')
                ->orWhereColumn('data_demissao', '>=', 'frequencia_registros.data');
        });
    }
}
