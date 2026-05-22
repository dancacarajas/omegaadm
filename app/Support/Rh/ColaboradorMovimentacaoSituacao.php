<?php

namespace App\Support\Rh;

final class ColaboradorMovimentacaoSituacao
{
    public const PENDENTE = 'pendente';

    public const FINALIZADA = 'finalizada';

    public const CANCELADA = 'cancelada';

    /** @return array<string, string> */
    public static function labels(): array
    {
        return [
            self::PENDENTE => 'Pendente de finalização',
            self::FINALIZADA => 'Finalizada',
            self::CANCELADA => 'Cancelada',
        ];
    }

    public static function label(string $situacao): string
    {
        return self::labels()[$situacao] ?? $situacao;
    }

    /** Tipos que permanecem em aberto até o RH finalizar o processo. */
    public static function tipoPermitePendente(string $tipo): bool
    {
        return in_array($tipo, [
            ColaboradorMovimentacaoTipos::FERIAS,
            ColaboradorMovimentacaoTipos::AFASTAMENTO_INSS,
        ], true);
    }

    /** Efeito no cadastro do colaborador só após finalização (ou registro já finalizado). */
    public static function tipoFinalizaAoRegistrar(string $tipo): bool
    {
        return ! self::tipoPermitePendente($tipo);
    }
}
