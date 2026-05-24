<?php

namespace App\Models\Almoxarifado;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MobilizacaoMaterialHistorico extends Model
{
    public $timestamps = false;

    protected $table = 'mobilizacao_material_historicos';

    protected $fillable = [
        'mobilizacao_material_id',
        'usuario_id',
        'campo_alterado',
        'valor_anterior',
        'valor_novo',
        'observacao',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function material(): BelongsTo
    {
        return $this->belongsTo(MobilizacaoMaterial::class, 'mobilizacao_material_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
