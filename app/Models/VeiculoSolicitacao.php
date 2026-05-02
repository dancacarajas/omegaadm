<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VeiculoSolicitacao extends Model
{
    protected $table = 'veiculo_solicitacoes';

    protected $fillable = [
        'status',
        'data_inicio_atividade',
        'data_fim_atividade',
        'data_liberacao_inspecao',
        'contrato',
        'linha_contratual',
        'criterio_tecnico',
        'finalidade',
        'responsavel',
        'checklist_data',
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
        'crlv_path',
        'documentos_adicionais',
        'tag_checklist_data',
        'tag_numero_protocolo',
        'tag_data_solicitacao',
        'tag_evidencia_path',
        'tag_observacoes',
        'subcontratacao_checklist_data',
        'subcontratacao_data_analise',
        'subcontratacao_data_autorizacao',
        'subcontratacao_protocolo',
        'subcontratacao_cartao_cnpj_path',
        'subcontratacao_minuta_path',
        'subcontratacao_contrato_social_path',
        'subcontratacao_documento_veiculo_path',
        'subcontratacao_evidencia_path',
        'subcontratacao_observacoes',
        'svg_checklist_data',
        'svg_data_postagem',
        'svg_protocolo',
        'svg_evidencia_path',
        'svg_observacoes',
        'vistoria_checklist_data',
        'vistoria_previsao_inicio',
        'vistoria_previsao_fim',
        'vistoria_data_agendada',
        'vistoria_resultado',
        'vistoria_observacoes',
        'observacoes',
    ];

    protected $casts = [
        'data_inicio_atividade' => 'date',
        'data_fim_atividade' => 'date',
        'data_liberacao_inspecao' => 'date',
        'checklist_data' => 'array',
        'documentos_adicionais' => 'array',
        'tag_checklist_data' => 'array',
        'tag_data_solicitacao' => 'date',
        'subcontratacao_checklist_data' => 'array',
        'subcontratacao_data_analise' => 'date',
        'subcontratacao_data_autorizacao' => 'date',
        'svg_checklist_data' => 'array',
        'svg_data_postagem' => 'date',
        'vistoria_checklist_data' => 'array',
        'vistoria_previsao_inicio' => 'date',
        'vistoria_previsao_fim' => 'date',
        'vistoria_data_agendada' => 'date',
    ];
}
