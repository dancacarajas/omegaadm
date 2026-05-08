<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatrimonialHistogramaRecorte extends Model
{
    protected $table = 'patrimonial_histograma_recortes';

    protected $fillable = [
        'contrato',
        'competencia',
        'inicio_monitoramento',
        'data_limite_etapa_2',
    ];

    protected $casts = [
        'competencia' => 'date',
        'inicio_monitoramento' => 'date',
        'data_limite_etapa_2' => 'date',
    ];
}

