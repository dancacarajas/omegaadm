<?php

namespace App\Support\Rh;

final class ColaboradorMovimentacaoTipos
{
    public const DESLIGAMENTO = 'desligamento';

    public const TRANSFERENCIA_CONTRATO = 'transferencia_contrato';

    public const PROMOCAO = 'promocao';

    public const MUDANCA_FUNCAO = 'mudanca_funcao';

    public const FERIAS = 'ferias';

    public const AFASTAMENTO_INSS = 'afastamento_inss';

    /** @return array<string, string> */
    public static function labels(): array
    {
        return [
            self::DESLIGAMENTO => 'Desligamento',
            self::TRANSFERENCIA_CONTRATO => 'Transferência de contrato',
            self::PROMOCAO => 'Promoção',
            self::MUDANCA_FUNCAO => 'Mudança de função',
            self::FERIAS => 'Férias',
            self::AFASTAMENTO_INSS => 'Afastamento INSS / benefício',
        ];
    }

    /** @return list<string> */
    public static function todos(): array
    {
        return array_keys(self::labels());
    }

    public static function label(string $tipo): string
    {
        return self::labels()[$tipo] ?? $tipo;
    }

    /** @return list<string> */
    public static function tiposRescisao(): array
    {
        return [
            'sem_justa_causa' => 'Sem justa causa',
            'justa_causa' => 'Justa causa',
            'pedido_demissao' => 'Pedido de demissão',
            'acordo' => 'Acordo',
            'termino_contrato' => 'Término de contrato',
            'aposentadoria' => 'Aposentadoria',
            'falecimento' => 'Falecimento',
            'outro' => 'Outro',
        ];
    }

    /** @return list<string> */
    public static function especiesInss(): array
    {
        return [
            'auxilio_doenca' => 'Auxílio-doença',
            'acidente_trabalho' => 'Acidente de trabalho (CAT / afastamento)',
            'auxilio_acidente' => 'Auxílio-acidente (após retorno)',
            'aposentadoria_invalidez' => 'Aposentadoria por invalidez',
            'salario_maternidade' => 'Salário-maternidade',
            'outro' => 'Outro',
        ];
    }

    public static function labelEspecieInss(?string $especie): string
    {
        if ($especie === null || $especie === '') {
            return '';
        }

        return self::especiesInss()[$especie] ?? $especie;
    }
}
