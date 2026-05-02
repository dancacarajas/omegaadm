<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecrutamentoVaga extends Model
{
    protected $fillable = [
        'titulo',
        'quantidade',
        'prioridade',
        'tipo',
        'contrato',
        'gestor',
        'local',
        'data_solicitacao',
        'previsao_inicio',
        'salario',
        'status',
        'descricao',
        'requisitos',
        'form_state',
    ];

    protected $casts = [
        'data_solicitacao' => 'date',
        'previsao_inicio' => 'date',
        'form_state' => 'array',
    ];
}
