<?php

namespace App\Models\Almoxarifado;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MobilizacaoMaterialRecebimento extends Model
{
    protected $table = 'mobilizacao_material_recebimentos';

    protected $fillable = [
        'mobilizacao_material_id',
        'data_recebimento',
        'quantidade_recebida',
        'responsavel_recebimento',
        'numero_nf',
        'observacao',
        'created_by',
    ];

    protected $casts = [
        'data_recebimento' => 'date',
        'quantidade_recebida' => 'decimal:2',
    ];

    public function material(): BelongsTo
    {
        return $this->belongsTo(MobilizacaoMaterial::class, 'mobilizacao_material_id');
    }

    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
