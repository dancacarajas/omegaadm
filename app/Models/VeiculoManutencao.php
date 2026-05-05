<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VeiculoManutencao extends Model
{
    protected $table = 'veiculo_manutencoes';

    protected $fillable = [
        'veiculo_solicitacao_id',
        'contrato',
        'veiculo_equipamento',
        'placa_tag',
        'tipo',
        'data_solicitacao',
        'responsavel_solicitacao',
        'motivo',
        'data_envio',
        'data_retorno',
        'dias_parado',
        'status',
        'evidencia_path',
        'impacto_operacao',
        'impacto_financeiro',
        'observacao',
    ];

    protected $casts = [
        'data_solicitacao' => 'date',
        'data_envio' => 'date',
        'data_retorno' => 'date',
        'impacto_financeiro' => 'decimal:2',
    ];

    public function solicitacao(): BelongsTo
    {
        return $this->belongsTo(VeiculoSolicitacao::class, 'veiculo_solicitacao_id');
    }
}
