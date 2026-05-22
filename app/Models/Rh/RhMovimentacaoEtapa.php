<?php

namespace App\Models\Rh;

use App\Models\User;
use App\Support\Rh\MovimentacaoEtapaStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RhMovimentacaoEtapa extends Model
{
    protected $table = 'rh_movimentacao_etapas';

    protected $fillable = [
        'chamado_id',
        'ordem',
        'slug',
        'nome',
        'descricao',
        'status',
        'obrigatoria',
        'papel_responsavel',
        'responsavel_id',
        'prazo',
        'iniciado_em',
        'concluido_em',
        'concluido_por_id',
        'observacao',
        'bloqueia_finalizacao',
        'dados_etapa_json',
    ];

    protected $casts = [
        'obrigatoria' => 'boolean',
        'bloqueia_finalizacao' => 'boolean',
        'dados_etapa_json' => 'array',
        'prazo' => 'date',
        'iniciado_em' => 'datetime',
        'concluido_em' => 'datetime',
    ];

    public function chamado(): BelongsTo
    {
        return $this->belongsTo(RhMovimentacaoChamado::class, 'chamado_id');
    }

    public function checklistItens(): HasMany
    {
        return $this->hasMany(RhMovimentacaoChecklistItem::class, 'etapa_id');
    }

    public function responsavel(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsavel_id');
    }

    public function isConcluida(): bool
    {
        return in_array($this->status, [
            MovimentacaoEtapaStatus::CONCLUIDA,
            MovimentacaoEtapaStatus::DISPENSADA,
        ], true);
    }

    public function isAtrasada(): bool
    {
        return ! $this->isConcluida()
            && $this->prazo !== null
            && $this->prazo->isPast();
    }
}
