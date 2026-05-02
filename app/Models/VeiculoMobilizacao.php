<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VeiculoMobilizacao extends Model
{
    public const ETAPAS_PADRAO = [
        'ETAPA_INICIAL',
        'VEICULO',
        'TAG',
        'SUBCONTRATACAO',
        'SVG',
        'APROVACAO_VALE',
        'VISTORIA',
        'FINALIZACAO',
    ];

    public const LABELS = [
        'ETAPA_INICIAL' => 'Inicial',
        'VEICULO' => 'Veiculo',
        'TAG' => 'TAG',
        'SUBCONTRATACAO' => 'Subcontratacao',
        'SVG' => 'SVG',
        'APROVACAO_VALE' => 'Aprovacao Vale',
        'VISTORIA' => 'Vistoria',
        'FINALIZACAO' => 'Finalizacao',
    ];

    protected $fillable = [
        'veiculo_id',
        'etapa',
        'status',
        'data_prevista',
        'data_realizada',
        'numero_solicitacao',
        'responsavel',
        'link_evidencia',
        'checklist_data',
        'observacoes',
    ];

    protected $casts = [
        'data_prevista' => 'date',
        'data_realizada' => 'date',
        'checklist_data' => 'array',
    ];

    public function veiculo()
    {
        return $this->belongsTo(Veiculo::class);
    }

    public function getLabelAttribute(): string
    {
        return self::LABELS[$this->etapa] ?? $this->etapa;
    }
}
