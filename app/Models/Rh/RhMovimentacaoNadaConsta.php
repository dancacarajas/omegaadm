<?php

namespace App\Models\Rh;

use App\Models\Colaborador;
use App\Models\User;
use App\Support\Rh\MovimentacaoDesligamentoCatalog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RhMovimentacaoNadaConsta extends Model
{
    protected $table = 'rh_movimentacao_nada_consta';

    protected $fillable = [
        'chamado_id',
        'colaborador_id',
        'data_emissao',
        'status',
        'validado_rh',
        'validado_rh_por',
        'validado_rh_em',
        'assinatura_colaborador',
        'assinatura_gestor',
        'gestor_contrato',
        'responsavel_rh',
        'observacao',
        'arquivo_pdf_id',
    ];

    protected $casts = [
        'chamado_id' => 'integer',
        'colaborador_id' => 'integer',
        'data_emissao' => 'date',
        'validado_rh' => 'boolean',
        'validado_rh_em' => 'datetime',
    ];

    public function chamado(): BelongsTo
    {
        return $this->belongsTo(RhMovimentacaoChamado::class, 'chamado_id');
    }

    public function colaborador(): BelongsTo
    {
        return $this->belongsTo(Colaborador::class);
    }

    public function itens(): HasMany
    {
        return $this->hasMany(RhMovimentacaoNadaConstaItem::class, 'nada_consta_id');
    }

    public function arquivoPdf(): BelongsTo
    {
        return $this->belongsTo(RhMovimentacaoAnexo::class, 'arquivo_pdf_id');
    }

    public function validadoRhPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validado_rh_por');
    }

    public function statusLabel(): string
    {
        return MovimentacaoDesligamentoCatalog::statusNadaConsta()[$this->status] ?? $this->status;
    }
}
