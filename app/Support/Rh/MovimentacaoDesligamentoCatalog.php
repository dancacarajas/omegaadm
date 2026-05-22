<?php

namespace App\Support\Rh;

/**
 * Catálogo do fluxo de desligamento: anexos, Nada Consta por área e status.
 */
final class MovimentacaoDesligamentoCatalog
{
    public const ANEXO_FOLHA_PONTO = 'folha_ponto';

    public const ANEXO_NADA_CONSTA_ASSINADO = 'nada_consta_assinado';

    public const ANEXO_DOCUMENTO_DESLIGAMENTO = 'documento_desligamento';

    public const ANEXO_CARTA_PEDIDO_DEMISSAO = 'carta_pedido_demissao';

    public const ANEXO_COMUNICACAO_DISPENSA = 'comunicacao_dispensa';

    public const ANEXO_AVISO_DESLIGAMENTO = 'aviso_desligamento';

    public const ANEXO_CIENCIA_COLABORADOR = 'ciencia_colaborador';

    public const ANEXO_EMAIL_AUTORIZACAO = 'email_autorizacao_interna';

    public const ANEXO_CHAMADO_PDF = 'chamado_resumo_pdf';

    public const NC_STATUS_PENDENTE_PREENCHIMENTO = 'pendente_preenchimento';

    public const NC_STATUS_EM_COLETA = 'em_coleta_assinaturas';

    public const NC_STATUS_COM_PENDENCIA = 'com_pendencia';

    public const NC_STATUS_AGUARDANDO_REGULARIZACAO = 'aguardando_regularizacao';

    public const NC_STATUS_AGUARDANDO_AUTORIZACAO = 'aguardando_autorizacao_desconto';

    public const NC_STATUS_REGULARIZADO = 'regularizado';

    public const NC_STATUS_VALIDADO_RH = 'validado_rh';

    public const TRATATIVA_SEM_PENDENCIA = 'sem_pendencia';

    public const TRATATIVA_PENDENTE = 'pendente';

    public const TRATATIVA_REGULARIZADO = 'regularizado';

    public const TRATATIVA_AUTORIZADO_DESCONTO = 'autorizado_desconto';

    public const TRATATIVA_DISPENSADO = 'dispensado_justificativa';

    public const TRATATIVA_AGUARDANDO_RH = 'aguardando_validacao_rh';

    /** @return array<string, string> */
    public static function permissaoArea(string $area): string
    {
        return 'chamados_movimentacao_area_'.$area;
    }

    /** @return array<string, string> */
    public static function labelsAnexos(): array
    {
        return [
            self::ANEXO_FOLHA_PONTO => 'Folha de ponto',
            self::ANEXO_NADA_CONSTA_ASSINADO => 'Nada Consta Demissional (preenchido e assinado)',
            self::ANEXO_DOCUMENTO_DESLIGAMENTO => 'Documento do desligamento (conforme tipo de rescisão)',
            self::ANEXO_CARTA_PEDIDO_DEMISSAO => 'Carta/pedido de demissão assinado pelo colaborador',
            self::ANEXO_COMUNICACAO_DISPENSA => 'Comunicação de dispensa',
            self::ANEXO_AVISO_DESLIGAMENTO => 'Aviso de desligamento',
            self::ANEXO_CIENCIA_COLABORADOR => 'Documento de ciência do colaborador',
            self::ANEXO_EMAIL_AUTORIZACAO => 'E-mail ou autorização interna',
            self::ANEXO_CHAMADO_PDF => 'PDF completo do chamado (gerado pelo sistema)',
        ];
    }

    /** @return array<string, string> */
    public static function statusNadaConsta(): array
    {
        return [
            self::NC_STATUS_PENDENTE_PREENCHIMENTO => 'Pendente de preenchimento',
            self::NC_STATUS_EM_COLETA => 'Em coleta de assinaturas',
            self::NC_STATUS_COM_PENDENCIA => 'Com pendência',
            self::NC_STATUS_AGUARDANDO_REGULARIZACAO => 'Aguardando regularização',
            self::NC_STATUS_AGUARDANDO_AUTORIZACAO => 'Aguardando autorização de desconto',
            self::NC_STATUS_REGULARIZADO => 'Regularizado',
            self::NC_STATUS_VALIDADO_RH => 'Validado pelo RH',
        ];
    }

    /** @return array<string, string> */
    public static function statusTratativa(): array
    {
        return [
            self::TRATATIVA_SEM_PENDENCIA => 'Sem pendência',
            self::TRATATIVA_PENDENTE => 'Pendente',
            self::TRATATIVA_REGULARIZADO => 'Regularizado / baixado',
            self::TRATATIVA_AUTORIZADO_DESCONTO => 'Autorizado para desconto',
            self::TRATATIVA_DISPENSADO => 'Dispensado com justificativa',
            self::TRATATIVA_AGUARDANDO_RH => 'Aguardando validação RH',
        ];
    }

    /** @return list<string> */
    public static function tratativasResolvidas(): array
    {
        return [
            self::TRATATIVA_SEM_PENDENCIA,
            self::TRATATIVA_REGULARIZADO,
            self::TRATATIVA_AUTORIZADO_DESCONTO,
            self::TRATATIVA_DISPENSADO,
        ];
    }

    /**
     * Anexos obrigatórios para qualquer desligamento.
     *
     * @return list<string>
     */
    public static function anexosObrigatoriosBase(): array
    {
        return [
            self::ANEXO_FOLHA_PONTO,
            self::ANEXO_NADA_CONSTA_ASSINADO,
            self::ANEXO_DOCUMENTO_DESLIGAMENTO,
        ];
    }

    /**
     * @return list<string>
     */
    public static function anexosObrigatoriosPorTipoRescisao(?string $tipoRescisao): array
    {
        $lista = self::anexosObrigatoriosBase();

        if ($tipoRescisao === 'pedido_demissao') {
            $lista[] = self::ANEXO_CARTA_PEDIDO_DEMISSAO;
        }

        return $lista;
    }

    /**
     * @return array<string, list<array{slug: string, nome: string}>>
     */
    public static function areasNadaConsta(): array
    {
        return [
            'almoxarifado_obra' => [
                ['slug' => 'almoxarifado_canteiro', 'nome' => 'Almoxarifado do canteiro'],
                ['slug' => 'ficha_epi_sesmt', 'nome' => 'Ficha de EPIs atualizada pelo SESMT'],
            ],
            'almoxarifado_central' => [
                ['slug' => 'devolucao_uniformes_epi', 'nome' => 'Devolução de uniformes e EPIs'],
                ['slug' => 'ficha_epi_sesmt_central', 'nome' => 'Ficha de EPIs atualizada pelo SESMT'],
            ],
            'patrimonio' => [
                ['slug' => 'ferramentas', 'nome' => 'Devolução de ferramentas e outros'],
                ['slug' => 'celular_chip', 'nome' => 'Celular/chip'],
                ['slug' => 'computador', 'nome' => 'Computador/notebook'],
                ['slug' => 'carimbo', 'nome' => 'Carimbo'],
            ],
            'transportes' => [
                ['slug' => 'cracha_telemetria', 'nome' => 'Devolução do crachá/chave de telemetria'],
                ['slug' => 'auto_infracao', 'nome' => 'Verificar auto de infração no veículo'],
                ['slug' => 'avarias', 'nome' => 'Verificar pendência de avarias'],
                ['slug' => 'devolucao_veiculo', 'nome' => 'Devolução do veículo ou equipamento (checklist)'],
            ],
            'financeiro' => [
                ['slug' => 'emprestimo_consignado', 'nome' => 'Empréstimo consignado'],
                ['slug' => 'adiantamentos', 'nome' => 'Adiantamentos'],
                ['slug' => 'despesas_prestacao', 'nome' => 'Despesas e prestações de conta'],
            ],
            'rh' => [
                ['slug' => 'cracha_funcional', 'nome' => 'Devolução de crachá funcional'],
                ['slug' => 'webcard_adiantamentos', 'nome' => 'Cartão Web Card ou outros adiantamentos'],
            ],
        ];
    }

    /** @return array<string, string> */
    public static function labelsAreas(): array
    {
        return [
            'almoxarifado_obra' => 'Almoxarifado Obra',
            'almoxarifado_central' => 'Almoxarifado Central',
            'patrimonio' => 'Patrimônio',
            'transportes' => 'Transportes',
            'financeiro' => 'Financeiro',
            'rh' => 'RH',
        ];
    }

    public static function labelArea(string $area): string
    {
        return self::labelsAreas()[$area] ?? $area;
    }

    public static function isDispensaEmpresa(?string $tipoRescisao): bool
    {
        return in_array($tipoRescisao, ['sem_justa_causa', 'justa_causa'], true);
    }
}
