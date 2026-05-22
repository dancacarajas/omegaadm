<?php

namespace App\Models\Rh;

use App\Models\Colaborador;
use App\Models\ColaboradorMovimentacao;
use App\Models\Contrato;
use App\Models\User;
use App\Support\Rh\MovimentacaoChamadoStatus;
use App\Support\Rh\MovimentacaoChamadoTipo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class RhMovimentacaoChamado extends Model
{
    protected $table = 'rh_movimentacao_chamados';

    protected $fillable = [
        'protocolo',
        'colaborador_id',
        'chamado_origem_id',
        'tipo',
        'status',
        'etapa_atual_id',
        'solicitante_id',
        'responsavel_atual_id',
        'contrato_origem_id',
        'contrato_destino_id',
        'data_abertura',
        'data_prevista',
        'data_efetiva',
        'motivo',
        'observacao',
        'dados_antes_json',
        'dados_depois_json',
        'colaborador_movimentacao_id',
        'finalizado_em',
        'finalizado_por_id',
        'cancelado_em',
        'cancelado_por_id',
        'motivo_cancelamento',
    ];

    protected $casts = [
        'colaborador_id' => 'integer',
        'chamado_origem_id' => 'integer',
        'dados_antes_json' => 'array',
        'dados_depois_json' => 'array',
        'data_abertura' => 'date',
        'data_prevista' => 'date',
        'data_efetiva' => 'date',
        'finalizado_em' => 'datetime',
        'cancelado_em' => 'datetime',
    ];

    public function colaborador(): BelongsTo
    {
        return $this->belongsTo(Colaborador::class);
    }

    public function chamadoOrigem(): BelongsTo
    {
        return $this->belongsTo(self::class, 'chamado_origem_id');
    }

    public function etapaAtual(): BelongsTo
    {
        return $this->belongsTo(RhMovimentacaoEtapa::class, 'etapa_atual_id');
    }

    public function etapas(): HasMany
    {
        return $this->hasMany(RhMovimentacaoEtapa::class, 'chamado_id')->orderBy('ordem');
    }

    public function solicitante(): BelongsTo
    {
        return $this->belongsTo(User::class, 'solicitante_id');
    }

    public function responsavelAtual(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsavel_atual_id');
    }

    public function movimentacaoLegada(): BelongsTo
    {
        return $this->belongsTo(ColaboradorMovimentacao::class, 'colaborador_movimentacao_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(RhMovimentacaoLog::class, 'chamado_id')->latest();
    }

    public function comentarios(): HasMany
    {
        return $this->hasMany(RhMovimentacaoComentario::class, 'chamado_id')->latest();
    }

    public function anexos(): HasMany
    {
        return $this->hasMany(RhMovimentacaoAnexo::class, 'chamado_id');
    }

    public function nadaConsta(): HasOne
    {
        return $this->hasOne(RhMovimentacaoNadaConsta::class, 'chamado_id');
    }

    public function isAberto(): bool
    {
        return ! in_array($this->status, [
            MovimentacaoChamadoStatus::CONCLUIDO,
            MovimentacaoChamadoStatus::CANCELADO,
        ], true);
    }

    public function isAtrasado(): bool
    {
        return $this->isAberto()
            && $this->data_prevista !== null
            && $this->data_prevista->isPast();
    }

    public function tipoLabel(): string
    {
        return MovimentacaoChamadoTipo::label($this->tipo);
    }

    public function statusLabel(): string
    {
        return MovimentacaoChamadoStatus::label($this->status);
    }
}
