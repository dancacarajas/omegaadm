<?php

namespace App\Support\Rh;

use App\Models\Colaborador;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;

final class BeneficioAdesaoMatrizEmailTexto
{
    public static function saudacaoHorarioBrasilia(?CarbonInterface $momento = null): string
    {
        $hora = ($momento ?? now('America/Sao_Paulo'))->hour;

        if ($hora >= 6 && $hora < 12) {
            return 'bom dia';
        }

        if ($hora >= 12 && $hora < 18) {
            return 'boa tarde';
        }

        return 'boa noite';
    }

    public static function taglineContrato(?string $centroCusto): string
    {
        $cc = trim((string) $centroCusto);

        if ($cc === '') {
            return 'CONTRATO —';
        }

        if (Str::startsWith(mb_strtolower($cc), 'contrato')) {
            return mb_strtoupper($cc);
        }

        return 'CONTRATO '.$cc;
    }

    /**
     * @return array{substantivo: string, substantivo_titulo: string, assinado: string, pela: string}
     */
    public static function termosColaborador(?Colaborador $colaborador): array
    {
        $sexo = mb_strtolower(trim((string) ($colaborador?->sexo ?? '')));
        $feminino = in_array($sexo, ['f', 'feminino', 'fem', 'mulher', 'feminina'], true);

        return [
            'substantivo' => $feminino ? 'colaboradora' : 'colaborador',
            'substantivo_titulo' => $feminino ? 'Colaboradora' : 'Colaborador',
            'assinado' => $feminino ? 'assinada' : 'assinado',
            'pela' => $feminino ? 'pela colaboradora' : 'pelo colaborador',
        ];
    }

    public static function montarAssunto(
        string $nomeBeneficio,
        ?string $matricula,
        string $nomeColaborador,
    ): string {
        $matriculaFormatada = trim((string) $matricula);
        $identificacao = $matriculaFormatada !== ''
            ? $matriculaFormatada.' - '.$nomeColaborador
            : $nomeColaborador;

        return sprintf(
            'Solicitação de adesão à Matriz | %s | %s',
            $nomeBeneficio,
            $identificacao,
        );
    }
}
