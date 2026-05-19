<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class FrequenciaFeriado extends Model
{
    protected $table = 'frequencia_feriados';

    protected $fillable = [
        'data',
        'nome',
        'recorrente',
        'ativo',
        'observacoes',
    ];

    protected $casts = [
        'data' => 'date',
        'recorrente' => 'boolean',
        'ativo' => 'boolean',
    ];

    public function rotuloPonto(): string
    {
        return 'Feriado: '.$this->nome;
    }

    public function ocorreNaData(Carbon|string $data): bool
    {
        $alvo = $data instanceof Carbon ? $data->copy()->startOfDay() : Carbon::parse((string) $data)->startOfDay();

        if ($this->recorrente) {
            $base = $this->data instanceof Carbon
                ? $this->data
                : Carbon::parse($this->data);

            return (int) $alvo->month === (int) $base->month
                && (int) $alvo->day === (int) $base->day;
        }

        $diaFeriado = $this->data instanceof Carbon
            ? $this->data->toDateString()
            : Carbon::parse($this->data)->toDateString();

        return $alvo->toDateString() === $diaFeriado;
    }
}
