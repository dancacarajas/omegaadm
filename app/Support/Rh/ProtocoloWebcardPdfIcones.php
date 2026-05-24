<?php

namespace App\Support\Rh;

final class ProtocoloWebcardPdfIcones
{
    /** @return array<string, string> */
    public static function caminhos(): array
    {
        $base = resource_path('pdf/protocolo-webcard');

        return [
            'empresa' => $base.'/empresa.png',
            'contrato' => $base.'/contrato.png',
            'documento' => $base.'/documento.png',
            'check' => $base.'/check.png',
            'pessoa' => $base.'/pessoa.png',
        ];
    }

    /** @return array<string, string|null> */
    public static function caminhosExistentes(): array
    {
        $icones = [];
        foreach (self::caminhos() as $nome => $caminho) {
            $icones[$nome] = is_file($caminho) ? $caminho : null;
        }

        return $icones;
    }
}
