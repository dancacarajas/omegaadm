<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HorarioEscala extends Model
{
    protected $table = 'horario_escalas';

    protected $fillable = [
        'nome',
        'tipo',
        'status',
    ];

    public function dias(): HasMany
    {
        return $this->hasMany(HorarioEscalaDia::class)->orderBy('dia_semana');
    }

    public function colaboradores(): HasMany
    {
        return $this->hasMany(Colaborador::class, 'horario_escala_id');
    }
}
