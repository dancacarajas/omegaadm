<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Contrato extends Model
{
    protected $fillable = [
        'numero',
        'nome',
        'cliente',
        'contratada',
        'objeto',
        'tipo',
        'centro_custo',
        'local_execucao',
        'gestor',
        'fiscal',
        'data_inicio',
        'data_fim',
        'valor',
        'status',
        'gestao_histograma',
        'descricao',
        'observacoes',
    ];

    protected $casts = [
        'data_inicio' => 'date',
        'data_fim' => 'date',
        'valor' => 'decimal:2',
        'gestao_histograma' => 'boolean',
    ];

    public function usuarios(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }
}
