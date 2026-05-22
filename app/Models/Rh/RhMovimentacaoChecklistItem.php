<?php

namespace App\Models\Rh;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RhMovimentacaoChecklistItem extends Model
{
    protected $table = 'rh_movimentacao_checklist_itens';

    protected $fillable = [
        'etapa_id',
        'slug',
        'nome',
        'status',
        'obrigatorio',
        'observacao',
        'concluido_por_id',
        'concluido_em',
    ];

    protected $casts = [
        'obrigatorio' => 'boolean',
        'concluido_em' => 'datetime',
    ];

    public function etapa(): BelongsTo
    {
        return $this->belongsTo(RhMovimentacaoEtapa::class, 'etapa_id');
    }
}
