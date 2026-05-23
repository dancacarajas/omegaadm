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
        'requer_controle_adesao',
        'adesao_automatica_admissao',
        'exige_formulario_colaborador',
        'descricao',
        'observacoes',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
        'requer_controle_adesao' => 'boolean',
        'adesao_automatica_admissao' => 'boolean',
        'exige_formulario_colaborador' => 'boolean',
    ];

    public function colaboradores()
    {
        return $this->hasMany(ColaboradorBeneficio::class);
    }

    public function extratoRegra()
    {
        return $this->hasOne(BeneficioExtratoRegra::class);
    }
}
