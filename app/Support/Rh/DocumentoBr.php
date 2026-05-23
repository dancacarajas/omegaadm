<?php

namespace App\Support\Rh;

final class DocumentoBr
{
    public static function cpfDigitos(?string $cpf): string
    {
        return preg_replace('/\D/', '', (string) $cpf) ?? '';
    }

    public static function cpfTemOnzeDigitos(string $digitos): bool
    {
        return strlen($digitos) === 11 && $digitos !== str_repeat('0', 11);
    }

    public static function cpf(?string $cpf): string
    {
        $digits = self::cpfDigitos($cpf);

        if (strlen($digits) !== 11) {
            return filled($cpf) ? trim((string) $cpf) : '—';
        }

        return substr($digits, 0, 3).'.'
            .substr($digits, 3, 3).'.'
            .substr($digits, 6, 3).'-'
            .substr($digits, 9, 2);
    }
}
