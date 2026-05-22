<?php

namespace App\Support\Rh;

final class MovimentacaoChamadoTipo
{
    public const DESLIGAMENTO = 'desligamento';

    public const TRANSFERENCIA_CONTRATO = 'transferencia_contrato';

    public const PROMOCAO = 'promocao';

    public const MUDANCA_FUNCAO = 'mudanca_funcao';

    public const AFASTAMENTO_INSS = 'afastamento_inss';

    /** @return array<string, string> */
    public static function labels(): array
    {
        return [
            self::DESLIGAMENTO => 'Desligamento',
            self::TRANSFERENCIA_CONTRATO => 'Transferência de contrato',
            self::PROMOCAO => 'Promoção',
            self::MUDANCA_FUNCAO => 'Mudança de função',
            self::AFASTAMENTO_INSS => 'Afastamento INSS / Previdenciário',
        ];
    }

    /** @return list<string> */
    public static function todos(): array
    {
        return array_keys(self::labels());
    }

    public static function label(string $tipo): string
    {
        return self::labels()[$tipo] ?? $tipo;
    }

    /** Mapeia tipo de chamado → tipo legado em colaborador_movimentacoes na finalização. */
    public static function tipoMovimentacaoLegado(string $tipo): string
    {
        return match ($tipo) {
            self::DESLIGAMENTO => ColaboradorMovimentacaoTipos::DESLIGAMENTO,
            self::TRANSFERENCIA_CONTRATO => ColaboradorMovimentacaoTipos::TRANSFERENCIA_CONTRATO,
            self::PROMOCAO => ColaboradorMovimentacaoTipos::PROMOCAO,
            self::MUDANCA_FUNCAO => ColaboradorMovimentacaoTipos::MUDANCA_FUNCAO,
            self::AFASTAMENTO_INSS => ColaboradorMovimentacaoTipos::AFASTAMENTO_INSS,
            default => $tipo,
        };
    }
}
