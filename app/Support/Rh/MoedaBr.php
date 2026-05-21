<?php

namespace App\Support\Rh;

/**
 * Converte e formata valores monetários no padrão brasileiro (3.091,91).
 */
final class MoedaBr
{
    public static function parse(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value) || is_float($value)) {
            return round((float) $value, 2);
        }

        $v = trim(preg_replace('/[R$\s]/u', '', (string) $value) ?? '');
        if ($v === '') {
            return null;
        }

        if (str_contains($v, ',')) {
            $v = str_replace('.', '', $v);
            $v = str_replace(',', '.', $v);

            return round((float) $v, 2);
        }

        if (preg_match('/^\d+\.\d{1,2}$/', $v)) {
            return round((float) $v, 2);
        }

        if (preg_match('/^\d{1,3}(\.\d{3})+$/', $v)) {
            return round((float) str_replace('.', '', $v), 2);
        }

        return round((float) $v, 2);
    }

    public static function format(?float $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return 'R$ '.number_format($value, 2, ',', '.');
    }

    /** Valor para input de formulário (3.091,91). */
    public static function paraInput(mixed $value): string
    {
        $n = self::parse($value);
        if ($n === null) {
            return '';
        }

        return number_format($n, 2, ',', '.');
    }
}
