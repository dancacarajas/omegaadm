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
        ];
    }

    /** @return array{label: string, status_inicial: string, etapas: list<array>}|null */
    public static function paraTipo(string $tipo): ?array
    {
        return self::todos()[$tipo] ?? null;
    }

    /** @return array{label: string, status_inicial: string, etapas: list<array>} */
    private static function desligamento(): array
    {
        return [
            'label' => 'Desligamento',
            'status_inicial' => MovimentacaoChamadoStatus::ABERTO,
            'etapas' => [
                self::etapa('solicitacao', 'Solicitação de desligamento', 'rh_operacional', 2, [
                    'Conferir dados do colaborador informados na solicitação',
                ]),
                self::etapa('triagem_rh', 'Triagem do RH', 'rh_operacional', 3, self::checklistTriagemRhDesligamento(), MovimentacaoChamadoStatus::EM_TRIAGEM),
                self::etapa('aprovacao', 'Aprovação do desligamento', 'gestor', 2, ['Aprovação registrada'], MovimentacaoChamadoStatus::AGUARDANDO_APROVACAO),
                self::etapa('comunicacao', 'Comunicação ao colaborador', 'rh_operacional', 2, ['Comunicação registrada']),
                self::etapa('exame_demissional', 'Exame demissional / ASO', 'tst_ssma', 5, ['ASO anexado ou dispensa justificada'], MovimentacaoChamadoStatus::AGUARDANDO_EXAME_ASO),
                self::etapa('dp_folha', 'DP/Folha/eSocial', 'dp_folha', 5, self::checklistDpDesligamento(), MovimentacaoChamadoStatus::AGUARDANDO_DP_FOLHA),
                self::etapa('beneficios', 'Benefícios e acessos', 'beneficios', 3, self::checklistBeneficiosDesligamento()),
                self::etapa('mobilizacao', 'Mobilização / Cliente / Operação', 'mobilizacao', 3, self::checklistMobilizacaoDesligamento(), MovimentacaoChamadoStatus::AGUARDANDO_MOBILIZACAO),
                self::etapa('finalizacao', 'Finalização do desligamento', 'rh_admin', 1, ['RH validou finalização'], MovimentacaoChamadoStatus::EM_EXECUCAO, false),
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
            'Cálculo de rescisão realizado',
            'TRCT gerado',
            'Pagamento programado/realizado',
            'eSocial S-2299 registrado',
            'Protocolo eSocial informado',
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
