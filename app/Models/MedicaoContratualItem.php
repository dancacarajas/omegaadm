<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MedicaoContratualItem extends Model
{
    protected $table = 'medicao_contratual_itens';

    protected $fillable = [
        'competencia',
        'contrato',
        'item_contratual',
        'descricao',
        'valor_unitario_previsto',
        'quantidade_prevista',
        'valor_previsto',
        'quantidade_medida',
        'valor_medido',
        'diferenca',
        'desvio_percentual',
        'justificativa',
        'evidencia_path',
        'valor_glosado',
        'valor_nao_executado',
        'valor_executado_nao_medido',
        'valor_hora_extra',
        'valor_adicional',
        'valor_mobilizacao',
        'valor_nao_programado',
    ];

    protected $casts = [
        'competencia' => 'date',
        'valor_unitario_previsto' => 'decimal:2',
        'quantidade_prevista' => 'decimal:2',
        'valor_previsto' => 'decimal:2',
        'quantidade_medida' => 'decimal:2',
        'valor_medido' => 'decimal:2',
        'diferenca' => 'decimal:2',
        'desvio_percentual' => 'decimal:2',
        'valor_glosado' => 'decimal:2',
        'valor_nao_executado' => 'decimal:2',
        'valor_executado_nao_medido' => 'decimal:2',
        'valor_hora_extra' => 'decimal:2',
        'valor_adicional' => 'decimal:2',
        'valor_mobilizacao' => 'decimal:2',
        'valor_nao_programado' => 'decimal:2',
    ];
}
