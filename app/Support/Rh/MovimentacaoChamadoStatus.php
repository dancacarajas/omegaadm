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

    public const CONCLUIDO = 'concluido';

    public const CANCELADO = 'cancelado';

    public const REPROVADO = 'reprovado';

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
            self::CONCLUIDO => 'Concluído',
            self::CANCELADO => 'Cancelado',
            self::REPROVADO => 'Reprovado',
        ];
    }

    public static function label(string $status): string
    {
        return self::labels()[$status] ?? $status;
    }
}
