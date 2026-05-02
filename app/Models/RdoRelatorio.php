<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RdoRelatorio extends Model
{
    protected $table = 'rdo_relatorios';

    protected $fillable = [
        'offline_uuid',
        'data',
        'titulo',
        'contrato',
        'frente',
        'area',
        'disciplina',
        'supervisor_nome',
        'supervisor_matricula',
        'encarregado_nome',
        'encarregado_matricula',
        'condicao_climatica',
        'atividades',
        'equipe',
        'observacoes',
        'ocorrencias',
        'evidencia_path',
        'status',
        'transmitido_em',
    ];

    protected $casts = [
        'data' => 'date',
        'atividades' => 'array',
        'equipe' => 'array',
        'transmitido_em' => 'datetime',
    ];
}
