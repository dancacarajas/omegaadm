<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FrequenciaRegistro extends Model
{
    protected $table = 'frequencia_registros';

    protected $fillable = [
        'colaborador_id',
        'data',
        'entrada_1',
        'saida_1',
        'entrada_2',
        'saida_2',
        'status',
        'origem',
        'justificativa_tipo',
        'justificativa_tipo_id',
        'justificativa_texto',
        'anexo_path',
        'importado_em',
    ];

    protected $casts = [
        'data' => 'date',
        'importado_em' => 'datetime',
    ];

    public function colaborador()
    {
        return $this->belongsTo(Colaborador::class);
    }

    public function justificativaTipoCatalogo()
    {
        return $this->belongsTo(FrequenciaJustificativaTipo::class, 'justificativa_tipo_id');
    }
}
