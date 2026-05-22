<?php

namespace App\Support\Rh;

final class DocumentoBr
{
    public static function cpf(?string $cpf): string
    {
        $digits = preg_replace('/\D/', '', (string) $cpf) ?? '';

        if (strlen($digits) !== 11) {
            return filled($cpf) ? trim((string) $cpf) : '—';
        }

        return substr($digits, 0, 3).'.'
            .substr($digits, 3, 3).'.'
            .substr($digits, 6, 3).'-'
            .substr($digits, 9, 2);
    }
}
