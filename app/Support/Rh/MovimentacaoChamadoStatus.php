<?php

namespace App\Support\Rh;

final class MovimentacaoChamadoStatus
{
    public const ABERTO = 'aberto';

    public const EM_TRIAGEM = 'em_triagem';

    public const AGUARDANDO_APROVACAO = 'aguardando_aprovacao';

    public const AGUARDANDO_DOCUMENTOS = 'aguardando_documentos';

    public const AGUARDANDO_EXAME_ASO = 'aguardando_exame_aso';

    public const AGUARDANDO_DP_FOLHA = 'aguardando_dp_folha';

    public const AGUARDANDO_MOBILIZACAO = 'aguardando_mobilizacao';

    public const EM_EXECUCAO = 'em_execucao';

    /** Todas as etapas concluídas; falta o botão "Finalizar processo" (aplica no cadastro). */
    public const AGUARDANDO_FINALIZACAO = 'aguardando_finalizacao';

    public const CONCLUIDO = 'concluido';

    public const CANCELADO = 'cancelado';

    public const REPROVADO = 'reprovado';

    // —— Afastamento INSS / previdenciário ——
    public const ATESTADO_RECEBIDO = 'atestado_recebido';

    public const EM_ANALISE_RH = 'em_analise_rh';

    public const AGUARDANDO_ENVIO_ESOCIAL = 'aguardando_envio_esocial';

    public const AGUARDANDO_REQUERIMENTO_INSS = 'aguardando_requerimento_inss';

    public const AGUARDANDO_PERICIA_INSS = 'aguardando_pericia_inss';

    public const AGUARDANDO_RESULTADO_INSS = 'aguardando_resultado_inss';

    public const BENEFICIO_DEFERIDO = 'beneficio_deferido';

    public const BENEFICIO_INDEFERIDO = 'beneficio_indeferido';

    public const BENEFICIO_PRORROGADO = 'beneficio_prorrogado';

    public const AGUARDANDO_RETORNO_TRABALHO = 'aguardando_retorno_trabalho';

    public const AGUARDANDO_ASO_RETORNO = 'aguardando_aso_retorno';

    public const RETORNO_LIBERADO = 'retorno_liberado';

    public const RETORNO_BLOQUEADO = 'retorno_bloqueado';

    public const AFASTAMENTO_ENCERRADO = 'afastamento_encerrado';

    /** @return array<string, string> */
    public static function labels(): array
    {
        return [
            self::ABERTO => 'Aberto',
            self::EM_TRIAGEM => 'Em triagem',
            self::AGUARDANDO_APROVACAO => 'Aguardando aprovação',
            self::AGUARDANDO_DOCUMENTOS => 'Aguardando documentos',
            self::AGUARDANDO_EXAME_ASO => 'Aguardando exame/ASO',
            self::AGUARDANDO_DP_FOLHA => 'Aguardando DP/Folha',
            self::AGUARDANDO_MOBILIZACAO => 'Aguardando mobilização',
            self::EM_EXECUCAO => 'Em execução',
            self::AGUARDANDO_FINALIZACAO => 'Aguardando finalização',
            self::CONCLUIDO => 'Concluído',
            self::CANCELADO => 'Cancelado',
            self::REPROVADO => 'Reprovado',
            self::ATESTADO_RECEBIDO => 'Atestado recebido',
            self::EM_ANALISE_RH => 'Em análise pelo RH',
            self::AGUARDANDO_ENVIO_ESOCIAL => 'Aguardando envio ao eSocial',
            self::AGUARDANDO_REQUERIMENTO_INSS => 'Aguardando requerimento INSS',
            self::AGUARDANDO_PERICIA_INSS => 'Aguardando perícia INSS',
            self::AGUARDANDO_RESULTADO_INSS => 'Aguardando resultado INSS',
            self::BENEFICIO_DEFERIDO => 'Benefício deferido',
            self::BENEFICIO_INDEFERIDO => 'Benefício indeferido',
            self::BENEFICIO_PRORROGADO => 'Benefício prorrogado',
            self::AGUARDANDO_RETORNO_TRABALHO => 'Aguardando retorno ao trabalho',
            self::AGUARDANDO_ASO_RETORNO => 'Aguardando ASO de retorno',
            self::RETORNO_LIBERADO => 'Retorno liberado',
            self::RETORNO_BLOQUEADO => 'Retorno bloqueado',
            self::AFASTAMENTO_ENCERRADO => 'Afastamento encerrado',
        ];
    }

    /** Status usados no fluxo de afastamento INSS (filtros e KPIs). */
    public static function isStatusAfastamentoInss(string $status): bool
    {
        return in_array($status, [
            self::ATESTADO_RECEBIDO,
            self::EM_ANALISE_RH,
            self::AGUARDANDO_ENVIO_ESOCIAL,
            self::AGUARDANDO_REQUERIMENTO_INSS,
            self::AGUARDANDO_PERICIA_INSS,
            self::AGUARDANDO_RESULTADO_INSS,
            self::BENEFICIO_DEFERIDO,
            self::BENEFICIO_INDEFERIDO,
            self::BENEFICIO_PRORROGADO,
            self::AGUARDANDO_RETORNO_TRABALHO,
            self::AGUARDANDO_ASO_RETORNO,
            self::RETORNO_LIBERADO,
            self::RETORNO_BLOQUEADO,
            self::AFASTAMENTO_ENCERRADO,
        ], true);
    }

    public static function label(string $status): string
    {
        return self::labels()[$status] ?? $status;
    }
}
