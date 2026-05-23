<?php

namespace App\Support;

/**
 * Formata textos apenas para exibição na assinatura de e-mail (não altera o cadastro).
 */
final class TextoApresentacaoAssinatura
{
    /** @var list<string> */
    private const PARTICULAS_NOME = ['de', 'da', 'do', 'das', 'dos', 'e'];

    public static function nome(string $texto): string
    {
        return self::tituloPalavras($texto, true, false, false);
    }

    public static function funcao(string $texto): string
    {
        return self::tituloPalavras($texto, true, false, true);
    }

    public static function contrato(string $texto): string
    {
        $texto = trim($texto);
        if ($texto === '') {
            return '';
        }

        if (! str_contains($texto, '-')) {
            return self::tituloPalavras($texto, false, true, false);
        }

        $partes = preg_split('/\s*-\s*/u', $texto) ?: [];

        return implode(' - ', array_map(
            fn (string $parte) => self::tituloPalavras($parte, false, true, false),
            $partes
        ));
    }

    public static function email(string $texto): string
    {
        return mb_strtolower(trim($texto), 'UTF-8');
    }

    private static function tituloPalavras(
        string $texto,
        bool $usarParticulasNome,
        bool $preservarSiglas,
        bool $preservarRomanos,
    ): string {
        $texto = trim($texto);
        if ($texto === '') {
            return '';
        }

        $originais = preg_split('/\s+/u', $texto, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $minusculas = array_map(fn (string $p) => mb_strtolower($p, 'UTF-8'), $originais);

        $formatadas = [];
        foreach ($minusculas as $i => $palavra) {
            $original = $originais[$i] ?? $palavra;

            if ($usarParticulasNome && $i > 0 && in_array($palavra, self::PARTICULAS_NOME, true)) {
                $formatadas[] = $palavra;

                continue;
            }

            if ($preservarRomanos && self::isNumeralRomano($palavra)) {
                $formatadas[] = mb_strtoupper($palavra, 'UTF-8');

                continue;
            }

            if ($preservarSiglas && self::preservarSigla($original)) {
                $formatadas[] = mb_strtoupper($original, 'UTF-8');

                continue;
            }

            $formatadas[] = mb_convert_case($palavra, MB_CASE_TITLE, 'UTF-8');
        }

        return implode(' ', $formatadas);
    }

    /** Siglas curtas de centro de custo (ex.: CT, RH) — não nomes em caixa alta. */
    private static function preservarSigla(string $palavra): bool
    {
        return (bool) preg_match('/^[A-Z]{2,3}$/u', $palavra);
    }

    private static function isNumeralRomano(string $palavra): bool
    {
        $u = mb_strtoupper(trim($palavra), 'UTF-8');
        if ($u === '' || ! preg_match('/^[IVXLCDM]+$/u', $u)) {
            return false;
        }

        return (bool) preg_match('/^(?:M{0,3})(?:CM|CD|D?C{0,3})(?:XC|XL|L?X{0,3})(?:IX|IV|V?I{0,3})$/', $u);
    }
}
