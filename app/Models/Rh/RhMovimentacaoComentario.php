<?php

namespace App\Models\Rh;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RhMovimentacaoComentario extends Model
{
    protected $table = 'rh_movimentacao_comentarios';

    protected $fillable = [
        'chamado_id',
        'etapa_id',
        'usuario_id',
        'comentario',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
