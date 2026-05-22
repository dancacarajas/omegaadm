<?php

namespace App\Support\Rh;

/**
 * Definição dos fluxos por tipo (fase 1: código; fase 2: tela admin).
 */
final class MovimentacaoWorkflowCatalog
{
    /** @return array<string, array{label: string, status_inicial: string, etapas: list<array>}> */
    public static function todos(): array
    {
        return [
            MovimentacaoChamadoTipo::DESLIGAMENTO => self::desligamento(),
            MovimentacaoChamadoTipo::TRANSFERENCIA_CONTRATO => self::transferenciaContrato(),
            MovimentacaoChamadoTipo::PROMOCAO => self::promocao(),
            MovimentacaoChamadoTipo::MUDANCA_FUNCAO => self::mudancaFuncao(),
            MovimentacaoChamadoTipo::AFASTAMENTO_INSS => self::afastamentoInss(),
        ];
    }

    /** @return array{label: string, status_inicial: string, etapas: list<array>}|null */
    public static function paraTipo(string $tipo): ?array
    {
        return self::todos()[$tipo] ?? null;
    }

    public static function statusChamadoParaEtapa(string $tipo, string $slug): ?string
    {
        $workflow = self::paraTipo($tipo);
        if ($workflow === null) {
            return null;
        }

        foreach ($workflow['etapas'] as $def) {
            if (($def['slug'] ?? null) === $slug) {
                return $def['status_chamado'] ?? null;
            }
        }

        return null;
    }

    /** @return array{label: string, status_inicial: string, etapas: list<array>} */
    private static function desligamento(): array
    {
        return [
            'label' => 'Desligamento',
            'status_inicial' => MovimentacaoChamadoStatus::ABERTO,
            'etapas' => [
                self::etapa('solicitacao', 'Solicitação de desligamento', 'rh_operacional', 2, [
                    'Dados do colaborador, contrato e função conferidos',
                    'Data prevista e último dia trabalhado informados',
                    'Tipo de rescisão e motivo registrados',
                    'Gestor e substituição de vaga informados',
                ]),
                self::etapa('cadastro_sigo', 'Cadastro do desligamento no SIGO', 'rh_operacional', 3, [
                    'Desligamento cadastrado no SIGO',
                    'Folha de ponto anexada',
                    'Documento do desligamento anexado',
                    'Anexos obrigatórios por tipo de rescisão conferidos',
                ], MovimentacaoChamadoStatus::EM_EXECUCAO),
                self::etapa('nada_consta', 'Nada Consta Demissional', 'rh_operacional', 5, [
                    'Checklist por área preenchido',
                    'Pendências tratadas ou autorizadas',
                    'Nada Consta assinado anexado',
                    'Validação final do RH',
                ], MovimentacaoChamadoStatus::EM_TRIAGEM),
                self::etapa('triagem_rh', 'Triagem do RH', 'rh_operacional', 3, self::checklistTriagemRhDesligamento(), MovimentacaoChamadoStatus::EM_TRIAGEM),
                self::etapa('aprovacao', 'Aprovação do desligamento', 'gestor', 2, ['Aprovação registrada'], MovimentacaoChamadoStatus::AGUARDANDO_APROVACAO),
                self::etapa('comunicacao', 'Comunicação ao colaborador', 'rh_operacional', 2, ['Comunicação registrada']),
                self::etapa('exame_demissional', 'Exame demissional / ASO', 'tst_ssma', 5, ['ASO anexado ou dispensa justificada'], MovimentacaoChamadoStatus::AGUARDANDO_EXAME_ASO),
                self::etapa('dp_folha', 'DP/Folha/eSocial', 'dp_folha', 5, self::checklistDpDesligamento(), MovimentacaoChamadoStatus::AGUARDANDO_DP_FOLHA),
                self::etapa('beneficios', 'Benefícios e acessos', 'beneficios', 3, self::checklistBeneficiosDesligamento()),
                self::etapa('mobilizacao', 'Mobilização / Cliente / Operação', 'mobilizacao', 3, self::checklistMobilizacaoDesligamento(), MovimentacaoChamadoStatus::AGUARDANDO_MOBILIZACAO),
                self::etapa('finalizacao', 'Finalização do desligamento', 'rh_admin', 1, ['RH validou finalização'], MovimentacaoChamadoStatus::AGUARDANDO_FINALIZACAO, false),
            ],
        ];
    }

    private static function transferenciaContrato(): array
    {
        return [
            'label' => 'Transferência de contrato',
            'status_inicial' => MovimentacaoChamadoStatus::ABERTO,
            'etapas' => [
                self::etapa('solicitacao', 'Solicitação de transferência', 'rh_operacional', 2),
                self::etapa('aprovacao_gestores', 'Aprovação dos gestores', 'gestor', 3, [], MovimentacaoChamadoStatus::AGUARDANDO_APROVACAO),
                self::etapa('validacao_contratual', 'Validação contratual e orçamentária', 'rh_operacional', 3),
                self::etapa('tst_ssma', 'Validação de função, risco e segurança', 'tst_ssma', 5, ['PCMSO/PGR conferido', 'Exame mudança de risco, se aplicável'], MovimentacaoChamadoStatus::AGUARDANDO_EXAME_ASO),
                self::etapa('mobilizacao', 'Mobilização no contrato destino', 'mobilizacao', 5, [], MovimentacaoChamadoStatus::AGUARDANDO_MOBILIZACAO),
                self::etapa('dp_folha', 'DP/Folha/eSocial', 'dp_folha', 5, ['Evento eSocial registrado'], MovimentacaoChamadoStatus::AGUARDANDO_DP_FOLHA),
                self::etapa('ciencia', 'Ciência do colaborador', 'rh_operacional', 2),
                self::etapa('finalizacao', 'Finalização da transferência', 'rh_admin', 1, [], MovimentacaoChamadoStatus::EM_EXECUCAO, false),
            ],
        ];
    }

    private static function promocao(): array
    {
        return [
            'label' => 'Promoção',
            'status_inicial' => MovimentacaoChamadoStatus::ABERTO,
            'etapas' => [
                self::etapa('solicitacao', 'Solicitação de promoção', 'rh_operacional', 2),
                self::etapa('elegibilidade', 'Análise de elegibilidade', 'rh_operacional', 3, [], MovimentacaoChamadoStatus::EM_TRIAGEM),
                self::etapa('aprovacao', 'Aprovação', 'gestor', 3, [], MovimentacaoChamadoStatus::AGUARDANDO_APROVACAO),
                self::etapa('tst_ssma', 'Validação de segurança e risco', 'tst_ssma', 5, [], MovimentacaoChamadoStatus::AGUARDANDO_EXAME_ASO),
                self::etapa('ciencia', 'Aditivo/ciência do colaborador', 'rh_operacional', 2),
                self::etapa('dp_folha', 'DP/Folha/eSocial', 'dp_folha', 5, [], MovimentacaoChamadoStatus::AGUARDANDO_DP_FOLHA),
                self::etapa('finalizacao', 'Finalização da promoção', 'rh_admin', 1, [], MovimentacaoChamadoStatus::EM_EXECUCAO, false),
            ],
        ];
    }

    private static function mudancaFuncao(): array
    {
        return [
            'label' => 'Mudança de função',
            'status_inicial' => MovimentacaoChamadoStatus::ABERTO,
            'etapas' => [
                self::etapa('solicitacao', 'Solicitação de mudança de função', 'rh_operacional', 2),
                self::etapa('validacao_rh', 'Validação de RH', 'rh_operacional', 3, [], MovimentacaoChamadoStatus::EM_TRIAGEM),
                self::etapa('aprovacao', 'Aprovação do gestor/RH', 'gestor', 3, [], MovimentacaoChamadoStatus::AGUARDANDO_APROVACAO),
                self::etapa('tst_ssma', 'Validação TST/SSMA', 'tst_ssma', 5, [], MovimentacaoChamadoStatus::AGUARDANDO_EXAME_ASO),
                self::etapa('ciencia', 'Ciência do colaborador', 'rh_operacional', 2),
                self::etapa('dp_folha', 'DP/Folha/eSocial', 'dp_folha', 5, [], MovimentacaoChamadoStatus::AGUARDANDO_DP_FOLHA),
                self::etapa('finalizacao', 'Finalização da mudança de função', 'rh_admin', 1, [], MovimentacaoChamadoStatus::EM_EXECUCAO, false),
            ],
        ];
    }

    /** @return array{label: string, status_inicial: string, etapas: list<array>} */
    private static function afastamentoInss(): array
    {
        return [
            'label' => 'Afastamento INSS / Previdenciário',
            'status_inicial' => MovimentacaoChamadoStatus::ATESTADO_RECEBIDO,
            'etapas' => [
                self::etapa('registro_atestado', 'Registro inicial do atestado', 'rh_operacional', 2, [
                    'Dados do colaborador e atestado conferidos na abertura',
                    'Anexo de atestado médico incluído',
                ], MovimentacaoChamadoStatus::ATESTADO_RECEBIDO),
                self::etapa('triagem_rh', 'Triagem do RH', 'rh_operacional', 3, self::checklistTriagemAfastamentoInss(), MovimentacaoChamadoStatus::EM_ANALISE_RH),
                self::etapa('classificacao', 'Classificação do afastamento', 'rh_operacional', 2, [
                    'Classificação do afastamento registrada',
                    'Regra de 15 dias / INSS aplicada',
                    'Encaminhamento TST avaliado',
                ], MovimentacaoChamadoStatus::EM_ANALISE_RH),
                self::etapa('tst_ssma', 'Validação TST/SSMA', 'tst_ssma', 5, self::checklistTstAfastamentoInss(), MovimentacaoChamadoStatus::AGUARDANDO_EXAME_ASO, true, 'tst'),
                self::etapa('dp_folha', 'DP/Folha/eSocial', 'dp_folha', 5, self::checklistDpAfastamentoInss(), MovimentacaoChamadoStatus::AGUARDANDO_DP_FOLHA),
                self::etapa('encaminhamento_inss', 'Encaminhamento ao INSS', 'rh_operacional', 10, self::checklistInssAfastamentoInss(), MovimentacaoChamadoStatus::AGUARDANDO_REQUERIMENTO_INSS, true, 'inss'),
                self::etapa('beneficios', 'Benefícios', 'beneficios', 3, self::checklistBeneficiosAfastamentoInss()),
                self::etapa('acompanhamento', 'Acompanhamento do afastamento', 'rh_operacional', 15, [
                    'Registro de acompanhamento periódico',
                ], MovimentacaoChamadoStatus::EM_EXECUCAO),
                self::etapa('retorno', 'Retorno de afastamento', 'rh_operacional', 5, self::checklistRetornoAfastamentoInss(), MovimentacaoChamadoStatus::AGUARDANDO_RETORNO_TRABALHO, true, 'retorno'),
                self::etapa('aso_retorno', 'ASO de retorno', 'tst_ssma', 5, self::checklistAsoRetorno(), MovimentacaoChamadoStatus::AGUARDANDO_ASO_RETORNO, true, 'aso_retorno'),
                self::etapa('finalizacao', 'Finalização do afastamento', 'rh_admin', 2, [
                    'Validação final RH/DP concluída',
                    'Resultado do processo definido',
                ], MovimentacaoChamadoStatus::AGUARDANDO_FINALIZACAO, false),
            ],
        ];
    }

    /**
     * @param  list<string>  $checklists
     * @return array<string, mixed>
     */
    private static function etapa(
        string $slug,
        string $nome,
        string $papel,
        int $prazoDias,
        array $checklists = [],
        ?string $statusChamadoAoIniciar = null,
        bool $bloqueiaFinalizacao = true,
        ?string $condicional = null,
    ): array {
        return [
            'slug' => $slug,
            'nome' => $nome,
            'papel_responsavel' => $papel,
            'prazo_dias' => $prazoDias,
            'obrigatoria' => true,
            'bloqueia_finalizacao' => $bloqueiaFinalizacao,
            'status_chamado' => $statusChamadoAoIniciar,
            'checklists' => $checklists,
            'condicional' => $condicional,
        ];
    }

    /** @return list<string> */
    private static function checklistTriagemAfastamentoInss(): array
    {
        return [
            'Atestado legível',
            'Nome do colaborador conferido',
            'Data de emissão conferida',
            'Período de afastamento conferido',
            'Assinatura/carimbo/CRM ou CRO conferidos',
            'Validade do atestado conferida',
            'Atestados anteriores relacionados verificados',
            'Ultrapassa 15 dias verificado',
            'Suspeita de nexo ocupacional avaliada',
            'Necessidade TST/SSMA avaliada',
            'Necessidade de CAT avaliada',
            'Observações internas registradas',
        ];
    }

    /** @return list<string> */
    private static function checklistTstAfastamentoInss(): array
    {
        return [
            'Acidente/incidente verificado',
            'Necessidade de CAT avaliada',
            'Data e local do acidente registrados',
            'Descrição do ocorrido registrada',
            'Investigação/relatório anexados, se houver',
            'Atividade laboral validada',
            'Trajeto validado, se aplicável',
            'Testemunhas registradas, se houver',
            'Cliente comunicado, se aplicável',
            'Pendências EPI/treinamento avaliadas',
        ];
    }

    /** @return list<string> */
    private static function checklistDpAfastamentoInss(): array
    {
        return [
            'Afastamento lançado na folha',
            'Data início informada',
            'Data final prevista informada',
            'Impacto salarial verificado',
            'Impacto em benefícios verificado',
            'Impacto em ponto/frequência verificado',
            'Evento S-2230 eSocial avaliado',
            'Envio eSocial registrado',
            'Protocolo/recibo eSocial informado',
            'Pagamento empresa x INSS definido',
        ];
    }

    /** @return list<string> */
    private static function checklistInssAfastamentoInss(): array
    {
        return [
            'Colaborador orientado sobre Meu INSS',
            'Requerimento realizado ou agendado',
            'Perícia/análise documental registrada',
            'Resultado do pedido registrado',
            'Benefício deferido/indeferido registrado',
            'Prorrogação registrada, se houver',
            'Documentos INSS anexados',
        ];
    }

    /** @return list<string> */
    private static function checklistBeneficiosAfastamentoInss(): array
    {
        return [
            'Vale-alimentação/refeição tratados',
            'Vale-transporte tratado',
            'Plano de saúde tratado',
            'Seguro de vida tratado',
            'Webcard/adiantamento tratados',
            'Descontos ativos verificados',
            'Regra ACT/CCT/política interna aplicada',
            'Decisão e evidência registradas',
        ];
    }

    /** @return list<string> */
    private static function checklistRetornoAfastamentoInss(): array
    {
        return [
            'Documento de alta/cessação conferido',
            'Fim do benefício conferido',
            'Afastamento ≥ 30 dias: exame retorno avaliado',
            'ASO de retorno agendado, se obrigatório',
            'Gestor comunicado',
            'DP/Folha comunicados',
            'Ponto/frequência reativados',
            'Benefícios reativados, se aplicável',
        ];
    }

    /** @return list<string> */
    private static function checklistAsoRetorno(): array
    {
        return [
            'Exame obrigatório definido',
            'Agendamento/realização registrados',
            'Resultado ASO registrado (apto/inapto/restrição)',
            'ASO anexado, se obrigatório',
            'Restrições comunicadas ao gestor/RH/TST',
        ];
    }

    /** @return list<string> */
    private static function checklistTriagemRhDesligamento(): array
    {
        return [
            'Dados do colaborador conferidos',
            'Contrato e função conferidos',
            'Férias/afastamentos conferidos',
            'Estabilidade conferida',
            'Ponto/frequência conferido',
            'Pendências de EPI/uniforme/crachá',
            'Benefícios ativos conferidos',
        ];
    }

    /** @return list<string> */
    private static function checklistDpDesligamento(): array
    {
        return [
            'Desligamento cadastrado no SIGO',
            'Folha de ponto anexada',
            'Documento de desligamento anexado',
            'Nada Consta anexado',
            'Pendências do Nada Consta tratadas',
            'Descontos autorizados lançados, quando houver',
            'Cálculo de rescisão realizado',
            'Conferência de verbas',
            'Conferência de descontos',
            'TRCT gerado',
            'Pagamento programado',
            'Pagamento realizado',
            'Evento eSocial registrado',
            'Protocolo eSocial informado',
            'Documentos rescisórios anexados',
        ];
    }

    /** @return list<string> */
    private static function checklistBeneficiosDesligamento(): array
    {
        return [
            'Vale-alimentação/refeição cancelado',
            'Planos e acessos cancelados',
            'Usuário do sistema bloqueado',
        ];
    }

    /** @return list<string> */
    private static function checklistMobilizacaoDesligamento(): array
    {
        return [
            'Baixa de crachá/passaporte',
            'Recolhimento de EPI/uniforme',
            'Cliente comunicado, se aplicável',
        ];
    }
}
