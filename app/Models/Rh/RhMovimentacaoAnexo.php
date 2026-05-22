<?php

namespace App\Models\Rh;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RhMovimentacaoAnexo extends Model
{
    protected $table = 'rh_movimentacao_anexos';

    protected $fillable = [
        'chamado_id',
        'etapa_id',
        'nome_arquivo',
        'caminho',
        'tipo_documento',
        'obrigatorio',
        'uploaded_by',
    ];

    public function chamado(): BelongsTo
    {
        return $this->belongsTo(RhMovimentacaoChamado::class, 'chamado_id');
    }
}
