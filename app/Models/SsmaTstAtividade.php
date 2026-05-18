<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SsmaTstAtividade extends Model
{
    protected $table = 'ssma_tst_atividades';

    protected $fillable = [
        'nome',
        'ativo',
        'ordem',
    ];

    protected $casts = [
        'ativo' => 'boolean',
        'ordem' => 'integer',
    ];

    public function registros(): HasMany
    {
        return $this->hasMany(SsmaTstRegistro::class, 'ssma_tst_atividade_id');
    }

    public function scopeAtivas($query)
    {
        return $query->where('ativo', true);
    }

    public function scopeOrdenadas($query)
    {
        return $query->orderBy('ordem')->orderBy('nome');
    }

    public function scopeFiltrar($query, ?string $busca, ?string $status)
    {
        return $query
            ->when($busca !== null && $busca !== '', function ($q) use ($busca) {
                $q->where('nome', 'like', '%'.$busca.'%');
            })
            ->when($status === 'ativas', fn ($q) => $q->where('ativo', true))
            ->when($status === 'inativas', fn ($q) => $q->where('ativo', false));
    }
}
