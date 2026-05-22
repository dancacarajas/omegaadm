<?php

namespace App\Models\Rh;

use App\Models\User;
use App\Support\Rh\MovimentacaoDesligamentoCatalog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RhMovimentacaoNadaConstaItem extends Model
{
    protected $table = 'rh_movimentacao_nada_consta_itens';

    protected $fillable = [
        'nada_consta_id',
        'area',
        'item',
        'tem_debito',
        'descricao_pendencia',
        'valor_pendencia',
        'status_tratativa',
        'responsavel_nome',
        'validado_por',
        'validado_em',
        'anexo_evidencia_id',
        'anexo_termo_baixa_id',
        'anexo_autorizacao_desconto_id',
        'observacao',
    ];

    protected $casts = [
        'nada_consta_id' => 'integer',
        'tem_debito' => 'boolean',
        'valor_pendencia' => 'decimal:2',
        'validado_em' => 'datetime',
    ];

    public function nadaConsta(): BelongsTo
    {
        return $this->belongsTo(RhMovimentacaoNadaConsta::class, 'nada_consta_id');
    }

    public function anexoEvidencia(): BelongsTo
    {
        return $this->belongsTo(RhMovimentacaoAnexo::class, 'anexo_evidencia_id');
    }

    public function anexoTermoBaixa(): BelongsTo
    {
        return $this->belongsTo(RhMovimentacaoAnexo::class, 'anexo_termo_baixa_id');
    }

    public function anexoAutorizacaoDesconto(): BelongsTo
    {
        return $this->belongsTo(RhMovimentacaoAnexo::class, 'anexo_autorizacao_desconto_id');
    }

    public function statusTratativaLabel(): string
    {
        return MovimentacaoDesligamentoCatalog::statusTratativa()[$this->status_tratativa] ?? $this->status_tratativa;
    }

    public function pendenciaAberta(): bool
    {
        if ($this->tem_debito !== true) {
            return false;
        }

        return ! in_array($this->status_tratativa, MovimentacaoDesligamentoCatalog::tratativasResolvidas(), true);
    }
}
