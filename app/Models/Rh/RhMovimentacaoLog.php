<?php

namespace App\Models\Rh;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RhMovimentacaoLog extends Model
{
    protected $table = 'rh_movimentacao_logs';

    public $timestamps = false;

    protected $fillable = [
        'chamado_id',
        'usuario_id',
        'acao',
        'campo',
        'valor_anterior',
        'valor_novo',
        'ip',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function chamado(): BelongsTo
    {
        return $this->belongsTo(RhMovimentacaoChamado::class, 'chamado_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
