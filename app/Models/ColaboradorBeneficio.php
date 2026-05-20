<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ColaboradorBeneficio extends Model
{
    protected $fillable = [
        'colaborador_id',
        'beneficio_id',
        'tem_direito',
        'cartao_entregue',
        'beneficio_ativo',
        'data_direito',
        'data_entrega_cartao',
        'numero_cartao',
        'observacoes',
    ];

    protected $casts = [
        'colaborador_id' => 'integer',
        'beneficio_id' => 'integer',
        'tem_direito' => 'boolean',
        'cartao_entregue' => 'boolean',
        'beneficio_ativo' => 'boolean',
        'data_direito' => 'date',
        'data_entrega_cartao' => 'date',
    ];

    public function colaborador()
    {
        return $this->belongsTo(Colaborador::class);
    }

    public function beneficio()
    {
        return $this->belongsTo(Beneficio::class);
    }
}
