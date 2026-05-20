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
        $driver = $colaboradorQuery->getConnection()->getDriverName();
        $colunaDataRegistro = $driver === 'sqlite'
            ? "date(frequencia_registros.data)"
            : 'frequencia_registros.data';

        $colaboradorQuery->where(function ($w) use ($colunaDataRegistro) {
            $w->whereNull('data_admissao')
                ->orWhereRaw("date(data_admissao) <= {$colunaDataRegistro}");
        })->where(function ($w) use ($colunaDataRegistro) {
            $w->whereNull('data_demissao')
                ->orWhereRaw("date(data_demissao) >= {$colunaDataRegistro}");
        });
    }
}
