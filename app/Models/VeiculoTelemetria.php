<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VeiculoTelemetria extends Model
{
    protected $table = 'veiculo_telemetrias';

    protected $fillable = [
        'data',
        'contrato',
        'veiculo_solicitacao_id',
        'veiculo',
        'placa_tag',
        'motorista_responsavel',
        'km_inicial',
        'km_final',
        'km_rodado',
        'horas_operacao',
        'tempo_ocioso',
        'tempo_parado',
        'rota_prevista',
        'rota_realizada',
        'desvio_rota',
        'desvio_justificativa',
        'velocidade_media',
        'excesso_velocidade',
        'frenagens_bruscas',
        'aceleracoes_bruscas',
        'localizacao',
        'consumo_estimado',
        'alertas_gerados',
        'eventos_criticos',
        'eventos_criticos_qtd',
        'evidencia_path',
        'observacao',
    ];

    protected $casts = [
        'data' => 'date',
        'desvio_rota' => 'boolean',
        'km_inicial' => 'decimal:2',
        'km_final' => 'decimal:2',
        'km_rodado' => 'decimal:2',
        'velocidade_media' => 'decimal:2',
        'consumo_estimado' => 'decimal:2',
    ];

    public function solicitacao(): BelongsTo
    {
        return $this->belongsTo(VeiculoSolicitacao::class, 'veiculo_solicitacao_id');
    }
}
