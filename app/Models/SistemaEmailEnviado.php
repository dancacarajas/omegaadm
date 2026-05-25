<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SistemaEmailEnviado extends Model
{
    protected $table = 'sistema_emails_enviados';

    protected $fillable = [
        'categoria',
        'tipo',
        'nome',
        'assunto',
        'mailer',
        'from_address',
        'from_name',
        'destinatario',
        'anexos_qtd',
        'referencia_tipo',
        'referencia_id',
        'enviado_por_id',
        'status',
        'enviado_em',
    ];

    protected $casts = [
        'anexos_qtd' => 'integer',
        'referencia_id' => 'integer',
        'enviado_em' => 'datetime',
    ];

    public function enviadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'enviado_por_id');
    }
}
