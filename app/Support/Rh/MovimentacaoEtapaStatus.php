<?php

namespace App\Support\Rh;

final class MovimentacaoEtapaStatus
{
    public const PENDENTE = 'pendente';

    public const EM_ANDAMENTO = 'em_andamento';

    public const CONCLUIDA = 'concluida';

    public const REPROVADA = 'reprovada';

    public const DISPENSADA = 'dispensada';

    public const BLOQUEADA = 'bloqueada';

    /** @return array<string, string> */
    public static function labels(): array
    {
        return [
            self::PENDENTE => 'Pendente',
            self::EM_ANDAMENTO => 'Em andamento',
            self::CONCLUIDA => 'Concluída',
            self::REPROVADA => 'Reprovada',
            self::DISPENSADA => 'Dispensada com justificativa',
            self::BLOQUEADA => 'Bloqueada',
        ];
    }
}
