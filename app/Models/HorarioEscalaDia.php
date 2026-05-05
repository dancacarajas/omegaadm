<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HorarioEscalaDia extends Model
{
    protected $table = 'horario_escala_dias';

    protected $fillable = [
        'horario_escala_id',
        'dia_semana',
        'entrada_1',
        'saida_1',
        'entrada_2',
        'saida_2',
        'almoco_livre',
        'compensado',
        'neutro',
        'noturno',
    ];

    protected $casts = [
        'almoco_livre' => 'boolean',
        'compensado' => 'boolean',
        'neutro' => 'boolean',
        'noturno' => 'boolean',
    ];

    public function escala(): BelongsTo
    {
        return $this->belongsTo(HorarioEscala::class, 'horario_escala_id');
    }

    /** Resumo legível dos horários do dia (ex.: 08:00–12:00 · 13:00–18:00). */
    public function textoGrade(): string
    {
        $fmt = static function ($v) {
            if ($v === null || $v === '') {
                return '';
            }

            return substr((string) $v, 0, 5);
        };

        $blocos = [];
        if ($fmt($this->entrada_1) !== '' && $fmt($this->saida_1) !== '') {
            $blocos[] = $fmt($this->entrada_1).'–'.$fmt($this->saida_1);
        }
        if ($fmt($this->entrada_2) !== '' && $fmt($this->saida_2) !== '') {
            $blocos[] = $fmt($this->entrada_2).'–'.$fmt($this->saida_2);
        }

        return $blocos === [] ? 'Sem jornada neste dia' : implode(' · ', $blocos);
    }
}
