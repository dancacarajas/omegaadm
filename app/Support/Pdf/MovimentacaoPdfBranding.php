<?php

namespace App\Support\Pdf;

use App\Models\Colaborador;

final class MovimentacaoPdfBranding
{
    public static function logoBase64(): ?string
    {
        $path = public_path('logo.png');
        if (! is_file($path)) {
            return null;
        }

        return 'data:image/png;base64,'.base64_encode(file_get_contents($path));
    }

    /**
     * @param  array<string, mixed>  $dados
     */
    public static function resolverSetorTrabalho(Colaborador $colaborador, array $dados): string
    {
        $centroCusto = trim((string) ($colaborador->centro_custo ?? ''));
        if ($centroCusto === '') {
            $centroCusto = trim((string) ($dados['centro_custo'] ?? ''));
        }

        $localTrabalho = trim((string) ($colaborador->local_trabalho ?? ''));
        if ($centroCusto !== '' && $localTrabalho !== '' && ! str_contains(strtoupper($centroCusto), strtoupper($localTrabalho))) {
            return $centroCusto.' - '.$localTrabalho;
        }

        return $centroCusto !== '' ? $centroCusto : $localTrabalho;
    }
}
