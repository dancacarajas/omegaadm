<?php

namespace App\Support\Almoxarifado;

final class MobilizacaoMaterialStatus
{
    public const SEM_TRATATIVA = 'SEM_TRATATIVA';

    public const PEDIDO_NO_SIGO = 'PEDIDO_NO_SIGO';

    public const EM_COMPRAS = 'EM_COMPRAS';

    public const COMPRA_PARCIAL = 'COMPRA_PARCIAL';

    public const RECEBIDO_PARCIAL = 'RECEBIDO_PARCIAL';

    public const RECEBIDO_TOTAL = 'RECEBIDO_TOTAL';

    public const CANCELADO_NAO_NECESSARIO = 'CANCELADO_NAO_NECESSARIO';

    /** @return array<string, string> */
    public static function labels(): array
    {
        return [
            self::SEM_TRATATIVA => 'Sem tratativa',
            self::PEDIDO_NO_SIGO => 'Pedido no SIGO',
            self::EM_COMPRAS => 'Em compras',
            self::COMPRA_PARCIAL => 'Compra parcial',
            self::RECEBIDO_PARCIAL => 'Recebido parcial',
            self::RECEBIDO_TOTAL => 'Recebido total',
            self::CANCELADO_NAO_NECESSARIO => 'Cancelado / não necessário',
        ];
    }

    /** @return array<string, string> Tailwind badge classes */
    public static function badgeClasses(): array
    {
        return [
            self::SEM_TRATATIVA => 'border-zinc-300 bg-zinc-100 text-zinc-700',
            self::PEDIDO_NO_SIGO => 'border-amber-200 bg-amber-50 text-amber-800',
            self::EM_COMPRAS => 'border-blue-200 bg-blue-50 text-blue-800',
            self::COMPRA_PARCIAL => 'border-orange-200 bg-orange-50 text-orange-800',
            self::RECEBIDO_PARCIAL => 'border-orange-200 bg-orange-50 text-orange-800',
            self::RECEBIDO_TOTAL => 'border-emerald-200 bg-emerald-50 text-emerald-800',
            self::CANCELADO_NAO_NECESSARIO => 'border-zinc-400 bg-zinc-700 text-white',
        ];
    }

    /** @return list<string> */
    public static function all(): array
    {
        return array_keys(self::labels());
    }
}
