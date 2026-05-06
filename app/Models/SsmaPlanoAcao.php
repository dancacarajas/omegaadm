<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class SsmaPlanoAcao extends Model
{
    protected $table = 'ssma_planos_acao';

    public const ORIGENS = [
        'desvio' => 'Desvio',
        'inspecao' => 'Inspeção',
        'auditoria' => 'Auditoria',
        'acidente' => 'Acidente',
        'quase_acidente' => 'Quase acidente',
        'campanha' => 'Campanha',
        'outro' => 'Outro',
    ];

    public const TIPOS = [
        'corretiva' => 'Corretiva',
        'preventiva' => 'Preventiva',
        'melhoria' => 'Melhoria',
        'legal' => 'Legal',
        'emergencial' => 'Emergencial',
    ];

    public const STATUS = [
        'aberta' => 'Aberta',
        'em_andamento' => 'Em andamento',
        'aguardando_evidencia' => 'Aguardando evidência',
        'concluida' => 'Concluída',
        'validada' => 'Validada',
        'vencida' => 'Vencida',
        'cancelada' => 'Cancelada',
    ];

    public const PRIORIDADES = [
        'baixa' => 'Baixa',
        'media' => 'Média',
        'alta' => 'Alta',
        'critica' => 'Crítica',
    ];

    public const NIVEIS_RISCO = [
        'baixo' => 'Baixo',
        'medio' => 'Médio',
        'alto' => 'Alto',
        'critico' => 'Crítico',
    ];

    protected $fillable = [
        'origem',
        'origem_detalhe',
        'tipo',
        'descricao_desvio',
        'acao_necessaria',
        'responsavel',
        'prazo',
        'status',
        'prioridade',
        'nivel_risco',
        'data_conclusao',
        'evidencia_conclusao_path',
        'validacao_ssma',
        'validacao_ssma_em',
        'justificativa_atraso',
        'observacoes',
    ];

    protected $casts = [
        'prazo' => 'date',
        'data_conclusao' => 'date',
        'validacao_ssma_em' => 'date',
    ];

    public function rotuloOrigem(): string
    {
        return self::ORIGENS[$this->origem] ?? $this->origem;
    }

    public function rotuloTipo(): string
    {
        return self::TIPOS[$this->tipo] ?? $this->tipo;
    }

    public function rotuloStatus(): string
    {
        return self::STATUS[$this->status] ?? $this->status;
    }

    public function rotuloPrioridade(): string
    {
        return self::PRIORIDADES[$this->prioridade] ?? $this->prioridade;
    }

    public function rotuloNivelRisco(): string
    {
        return self::NIVEIS_RISCO[$this->nivel_risco] ?? $this->nivel_risco;
    }

    /** Fora do prazo (calendário) e ainda não encerrada por sucesso. */
    public function estaAtrasada(): bool
    {
        if (in_array($this->status, ['concluida', 'validada', 'cancelada'], true)) {
            return false;
        }

        return $this->prazo->copy()->startOfDay()->lt(now()->startOfDay());
    }

    public function scopeFiltrar(Builder $q, ?string $busca = null, ?string $status = null, ?string $origem = null, ?string $responsavel = null): Builder
    {
        if ($busca !== null && $busca !== '') {
            $b = "%{$busca}%";
            $q->where(function (Builder $w) use ($b) {
                $w->where('acao_necessaria', 'like', $b)
                    ->orWhere('descricao_desvio', 'like', $b)
                    ->orWhere('responsavel', 'like', $b)
                    ->orWhere('origem_detalhe', 'like', $b);
            });
        }
        if ($status !== null && $status !== '') {
            $q->where('status', $status);
        }
        if ($origem !== null && $origem !== '') {
            $q->where('origem', $origem);
        }
        if ($responsavel !== null && $responsavel !== '') {
            $q->where('responsavel', 'like', "%{$responsavel}%");
        }

        return $q;
    }
}
