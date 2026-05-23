<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ColaboradorBeneficioWebcardSolicitacao extends Model
{
    protected $table = 'colaborador_beneficio_webcard_solicitacoes';

    protected $fillable = [
        'colaborador_beneficio_id',
        'data_solicitacao',
        'valor',
        'observacao',
        'registrado_por_id',
    ];

    protected function casts(): array
    {
        return [
            'colaborador_beneficio_id' => 'integer',
            'data_solicitacao' => 'date',
            'valor' => 'decimal:2',
            'registrado_por_id' => 'integer',
        ];
    }

    public function vinculo(): BelongsTo
    {
        return $this->belongsTo(ColaboradorBeneficio::class, 'colaborador_beneficio_id');
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por_id');
    }
}
