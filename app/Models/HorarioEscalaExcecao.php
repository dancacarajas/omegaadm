<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HorarioEscalaExcecao extends Model
{
    protected $table = 'horario_escala_excecoes';

    protected $fillable = [
        'horario_escala_id',
        'colaborador_ausente_id',
        'colaborador_cobertura_id',
        'data_inicio',
        'data_fim',
        'motivo',
    ];

    protected $casts = [
        'data_inicio' => 'date',
        'data_fim' => 'date',
    ];

    public function escala(): BelongsTo
    {
        return $this->belongsTo(HorarioEscala::class, 'horario_escala_id');
    }

    public function colaboradorAusente(): BelongsTo
    {
        return $this->belongsTo(Colaborador::class, 'colaborador_ausente_id');
    }

    public function colaboradorCobertura(): BelongsTo
    {
        return $this->belongsTo(Colaborador::class, 'colaborador_cobertura_id');
    }

    public function cobreData(CarbonInterface|string $data): bool
    {
        $d = $data instanceof CarbonInterface
            ? Carbon::parse($data)->startOfDay()
            : Carbon::parse((string) $data)->startOfDay();

        return $d->between(
            $this->data_inicio->copy()->startOfDay(),
            $this->data_fim->copy()->endOfDay()
        );
    }
}
