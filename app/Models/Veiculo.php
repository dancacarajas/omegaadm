<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Veiculo extends Model
{
    protected $fillable = [
        'placa',
        'renavam',
        'tipo',
        'marca',
        'modelo',
        'ano_fabricacao',
        'ano_modelo',
        'cor',
        'proprietario',
        'fornecedor',
        'contrato',
        'linha_contratual',
        'criterio_tecnico',
        'data_inicio_atividade',
        'data_fim_atividade',
        'data_liberacao_inspecao',
        'status',
        'mobilizacao_status',
        'observacoes',
    ];

    protected $casts = [
        'data_inicio_atividade' => 'date',
        'data_fim_atividade' => 'date',
        'data_liberacao_inspecao' => 'date',
    ];

    public function mobilizacoes()
    {
        return $this->hasMany(VeiculoMobilizacao::class);
    }
}
