<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContratoHistogramaRecorte extends Model
{
    protected $table = 'contrato_histograma_recortes';

    protected $fillable = [
        'contrato',
        'competencia',
        'data_limite_etapa_2',
    ];

    protected $casts = [
        'competencia' => 'date',
        'data_limite_etapa_2' => 'date',
    ];
}
