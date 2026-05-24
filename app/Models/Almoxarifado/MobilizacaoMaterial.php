<?php

namespace App\Models\Almoxarifado;

use App\Models\Contrato;
use App\Models\User;
use App\Support\Almoxarifado\MobilizacaoMaterialCalculoService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MobilizacaoMaterial extends Model
{
    use SoftDeletes;

    protected $table = 'mobilizacao_materiais';

    protected $fillable = [
        'contrato_id',
        'categoria_id',
        'disciplina',
        'categoria_descricao',
        'situacao_tratativa',
        'codigo_material',
        'descricao_material',
        'unidade_medida',
        'quantidade_necessaria',
        'quantidade_pedida_sigo',
        'quantidade_em_compra',
        'quantidade_recebida',
        'saldo_a_comprar',
        'saldo_a_receber',
        'status',
        'situacao_sigo_descricao',
        'acao_do_dia',
        'numero_pm',
        'numero_oc',
        'fornecedor',
        'comprador_responsavel',
        'data_pedido_sigo',
        'data_inicio_compra',
        'previsao_entrega',
        'data_recebimento_total',
        'observacao_almoxarife',
        'observacao_gestao',
        'prioridade',
        'origem_cadastro',
        'ativo',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'quantidade_necessaria' => 'decimal:2',
        'quantidade_pedida_sigo' => 'decimal:2',
        'quantidade_em_compra' => 'decimal:2',
        'quantidade_recebida' => 'decimal:2',
        'saldo_a_comprar' => 'decimal:2',
        'saldo_a_receber' => 'decimal:2',
        'data_pedido_sigo' => 'date',
        'data_inicio_compra' => 'date',
        'previsao_entrega' => 'date',
        'data_recebimento_total' => 'date',
        'ativo' => 'boolean',
    ];

    public function contrato(): BelongsTo
    {
        return $this->belongsTo(Contrato::class);
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(MobilizacaoMaterialCategoria::class, 'categoria_id');
    }

    public function recebimentos(): HasMany
    {
        return $this->hasMany(MobilizacaoMaterialRecebimento::class)->orderByDesc('data_recebimento');
    }

    public function anexos(): HasMany
    {
        return $this->hasMany(MobilizacaoMaterialAnexo::class)->orderByDesc('created_at');
    }

    public function historicos(): HasMany
    {
        return $this->hasMany(MobilizacaoMaterialHistorico::class)->orderByDesc('created_at');
    }

    public function criador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function temMovimentacao(): bool
    {
        return $this->recebimentos()->exists()
            || filled($this->numero_pm)
            || filled($this->numero_oc)
            || (float) $this->quantidade_recebida > 0
            || (float) $this->quantidade_em_compra > 0;
    }

    public function recalcular(bool $forcarStatus = true): self
    {
        return app(MobilizacaoMaterialCalculoService::class)->recalcular($this, $forcarStatus);
    }
}
