<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicaoPresencaObraRegistro extends Model
{
    public const STATUS_PRESENTE = 'presente';

    public const STATUS_AUSENTE = 'ausente';

    protected $table = 'medicao_presenca_obra_registros';

    protected $fillable = [
        'data',
        'colaborador_id',
        'status',
        'confirmado_por_id',
        'centro_custo',
        'observacao',
        'confirmado_em',
    ];

    protected $casts = [
        'data' => 'date',
        'confirmado_em' => 'datetime',
    ];

    public function colaborador(): BelongsTo
    {
        return $this->belongsTo(Colaborador::class);
    }

    public function confirmadoPor(): BelongsTo
    {
        return $this->belongsTo(Colaborador::class, 'confirmado_por_id');
    }

    public function isPresente(): bool
    {
        return $this->status === self::STATUS_PRESENTE;
    }

    public function rotuloStatus(): string
    {
        return match ($this->status) {
            self::STATUS_PRESENTE => 'Presente',
            self::STATUS_AUSENTE => 'Ausente',
            default => (string) $this->status,
        };
    }
}
