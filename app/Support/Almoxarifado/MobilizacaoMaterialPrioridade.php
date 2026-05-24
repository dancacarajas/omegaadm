<?php

namespace App\Support\Almoxarifado;

final class MobilizacaoMaterialPrioridade
{
    public const BAIXA = 'BAIXA';

    public const MEDIA = 'MEDIA';

    public const ALTA = 'ALTA';

    public const CRITICA = 'CRITICA';

    /** @return array<string, string> */
    public static function labels(): array
    {
        return [
            self::BAIXA => 'Baixa',
            self::MEDIA => 'Média',
            self::ALTA => 'Alta',
            self::CRITICA => 'Crítica',
        ];
    }

    /** @return list<string> */
    public static function all(): array
    {
        return array_keys(self::labels());
    }
}
