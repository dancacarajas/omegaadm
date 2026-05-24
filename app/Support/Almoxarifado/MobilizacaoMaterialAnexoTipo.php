<?php

namespace App\Support\Almoxarifado;

final class MobilizacaoMaterialAnexoTipo
{
    public const PRINT_SIGO = 'PRINT_SIGO';

    public const OC = 'OC';

    public const NOTA_FISCAL = 'NOTA_FISCAL';

    public const COMPROVANTE_RECEBIMENTO = 'COMPROVANTE_RECEBIMENTO';

    public const OUTROS = 'OUTROS';

    /** @return array<string, string> */
    public static function labels(): array
    {
        return [
            self::PRINT_SIGO => 'Print SIGO',
            self::OC => 'Ordem de compra',
            self::NOTA_FISCAL => 'Nota fiscal',
            self::COMPROVANTE_RECEBIMENTO => 'Comprovante de recebimento',
            self::OUTROS => 'Outros',
        ];
    }
}
