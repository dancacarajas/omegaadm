<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Beneficio extends Model
{
    protected $fillable = [
        'nome',
        'tipo',
        'fornecedor',
        'codigo',
        'valor',
        'periodicidade',
        'elegibilidade',
        'status',
        'descricao',
        'observacoes',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
    ];

    public function colaboradores()
    {
        return $this->hasMany(ColaboradorBeneficio::class);
    }
}
