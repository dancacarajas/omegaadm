<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ContratoHistogramaLinha extends Model
{
    protected $table = 'contrato_histograma_linhas';

    /**
     * Histograma Mão de obra (métricas PGU):
     * - pre_pgu: vagas já contratadas / mobilizadas (Pré-PGU)
     * - pgu: necessidade total prevista na competência (PGU)
     * - Pendência por função = max(0, pgu - pre_pgu); coberto = min(pre_pgu, pgu)
     */
    protected $fillable = [
        'contrato',
        'competencia',
        'tipo_linha',
        'ordem',
        'item_codigo',
        'descricao',
        'acao_recomendada',
        'responsavel',
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

    /**
     * Linhas que entram nas métricas do dashboard PGU (igual às linhas editáveis como “Item” no histograma).
     * Inclui tipo nulo (legado) e exclui apenas “grupo”.
     */
    public function scopeItensParaMetricasPgu(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->where('tipo_linha', 'item')
                ->orWhereNull('tipo_linha')
                ->orWhere('tipo_linha', '');
        });
    }
}
