<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SsmaRegistroMensal extends Model
{
    public const ETAPAS = [
        'auditoria_mensal' => 'Auditoria Mensal',
        'inspecao_mensal_canteiro' => 'Inspeção Mensal - Canteiro',
        'treinamentos_mensais' => 'Treinamentos Mensais',
        'registro_acoes_proativas' => 'Registro - Ações Proativas',
        'boas_praticas_kaizen' => 'Boas Práticas - Kayzen',
        'acoes_reativas' => 'Ações Reativas',
        'campanha_seguranca' => 'Campanha de Segurança',
        'registro_acidente' => 'Registro de Acidente',
    ];

    protected $table = 'ssma_registros_mensais';

    public const STATUS = [
        'rascunho' => 'Rascunho',
        'enviado' => 'Enviado',
        'validado' => 'Validado',
        'finalizado' => 'Finalizado',
    ];

    protected $fillable = [
        'competencia',
        'titulo',
        'responsavel',
        'contrato',
        'local_base',
        'efetivo_ativo_mes',
        'hht_mes',
        'comentario_executivo',
        'observacoes_gerais_mes',
        'status',
        'etapas',
    ];

    protected $casts = [
        'competencia' => 'date',
        'etapas' => 'array',
        'efetivo_ativo_mes' => 'integer',
        'hht_mes' => 'decimal:2',
    ];

    public function rotuloStatus(): string
    {
        return self::STATUS[$this->status] ?? $this->status;
    }
}
