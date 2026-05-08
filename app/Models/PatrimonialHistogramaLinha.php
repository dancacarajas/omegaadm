<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatrimonialHistogramaLinha extends Model
{
    protected $table = 'patrimonial_histograma_linhas';

    protected $fillable = [
        'contrato',
        'competencia',
        'tipo_linha',
        'ordem',
        'item_codigo',
        'descricao',
        'unidade',
        'mobilizacao',
        'pre_pgu',
        'pgu',
        'pos_pgu',
        'desmobilizacao',
    ];

    protected $casts = [
        'competencia' => 'date',
        'mobilizacao' => 'float',
        'pre_pgu' => 'float',
        'pgu' => 'float',
        'pos_pgu' => 'float',
        'desmobilizacao' => 'float',
    ];
}

