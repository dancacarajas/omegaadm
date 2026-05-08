<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Patrimonio extends Model
{
    protected $fillable = [
        'tag_patrimonial',
        'nome',
        'categoria',
        'tipo',
        'marca',
        'modelo',
        'numero_serie',
        'contrato',
        'centro_custo',
        'fornecedor',
        'data_aquisicao',
        'data_entrada',
        'valor',
        'responsavel',
        'setor',
        'localizacao',
        'status',
        'condicao',
        'ultima_conferencia',
        'proxima_conferencia',
        'observacoes',
        'fluxo_state',
        'fluxo_step',
    ];

    protected $casts = [
        'data_aquisicao' => 'date',
        'data_entrada' => 'date',
        'ultima_conferencia' => 'date',
        'proxima_conferencia' => 'date',
        'valor' => 'decimal:2',
        'fluxo_state' => 'array',
    ];
}
