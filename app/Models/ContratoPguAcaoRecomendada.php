<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContratoPguAcaoRecomendada extends Model
{
    protected $table = 'contrato_pgu_acoes_recomendadas';

    protected $fillable = [
        'contrato',
        'competencia',
        'funcao',
        'ordem',
        'pendencias_snapshot',
        'acao_recomendada',
        'responsavel',
    ];

    protected $casts = [
        'competencia' => 'date',
        'ordem' => 'integer',
        'pendencias_snapshot' => 'integer',
    ];
}
