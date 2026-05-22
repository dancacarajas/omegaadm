<?php

namespace App\Support\Rh;

/**
 * Catálogo de classificações, resultados e metadados do fluxo Afastamento INSS.
 */
final class MovimentacaoAfastamentoInssCatalog
{
    /** Classificações obrigatórias na etapa 3. */
    public const CLASS_ATESTATO_15 = 'atestado_ate_15_dias';

    public const CLASS_SUPERIOR_15_INSS = 'afastamento_superior_15_inss';

    public const CLASS_ACIDENTE_TRABALHO = 'acidente_trabalho';

    public const CLASS_ACIDENTE_TRAJETO = 'acidente_trajeto';

    public const CLASS_DOENCA_OCUPACIONAL = 'doenca_ocupacional';

    public const CLASS_LICENCA_MATERNIDADE = 'licenca_maternidade';

    public const CLASS_PRORROGACAO = 'prorrogacao_afastamento';

    public const CLASS_RETORNO = 'retorno_afastamento';

    public const CLASS_AGUARDANDO_INSS = 'aguardando_decisao_inss';

    /** Resultados possíveis na finalização do chamado. */
    public const RESULTADO_RETORNO = 'retorno_trabalho';

    public const RESULTADO_PRORROGACAO = 'prorrogacao_beneficio';

    public const RESULTADO_INDEFERIDO = 'indeferimento_inss';

    public const RESULTADO_ALTA_MEDICA = 'alta_medica';

    public const RESULTADO_NOVO_AFASTAMENTO = 'novo_afastamento';

    public const RESULTADO_ESPECIE_ACIDENTARIA = 'especie_acidentaria';

    public const RESULTADO_DESLIGAMENTO = 'desligamento_posterior';

    public const RESULTADO_AFASTAMENTO_ATIVO = 'afastamento_em_andamento';

    /** Tipos de documento para anexos (LGPD: laudos com acesso restrito). */
    public const ANEXO_ATESTADO = 'atestado_medico';

    public const ANEXO_RELATORIO = 'relatorio_medico';

    public const ANEXO_COMPARECIMENTO = 'declaracao_comparecimento';

    public const ANEXO_COMPLEMENTAR = 'documentacao_complementar';

    public const ANEXO_CAT = 'cat';

    public const ANEXO_ASO_RETORNO = 'aso_retorno';

    public const ANEXO_INSS = 'documento_inss';

    public const ANEXO_ESOCIAL = 'comprovante_esocial';

    /** @return array<string, string> */
    public static function classificacoes(): array
    {
        return [
            self::CLASS_ATESTATO_15 => 'Atestado até 15 dias',
            self::CLASS_SUPERIOR_15_INSS => 'Afastamento superior a 15 dias (encaminhamento INSS)',
            self::CLASS_ACIDENTE_TRABALHO => 'Acidente de trabalho',
            self::CLASS_ACIDENTE_TRAJETO => 'Acidente de trajeto',
            self::CLASS_DOENCA_OCUPACIONAL => 'Doença ocupacional',
            self::CLASS_LICENCA_MATERNIDADE => 'Licença maternidade',
            self::CLASS_PRORROGACAO => 'Prorrogação de afastamento',
            self::CLASS_RETORNO => 'Retorno de afastamento',
            self::CLASS_AGUARDANDO_INSS => 'Afastamento aguardando decisão do INSS',
        ];
    }

    /** @return array<string, string> */
    public static function resultadosFinais(): array
    {
        return [
            self::RESULTADO_AFASTAMENTO_ATIVO => 'Afastamento em andamento (cadastro atualizado)',
            self::RESULTADO_RETORNO => 'Retorno ao trabalho',
            self::RESULTADO_PRORROGACAO => 'Prorrogação do benefício',
            self::RESULTADO_INDEFERIDO => 'Indeferimento pelo INSS',
            self::RESULTADO_ALTA_MEDICA => 'Alta médica',
            self::RESULTADO_NOVO_AFASTAMENTO => 'Novo afastamento',
            self::RESULTADO_ESPECIE_ACIDENTARIA => 'Convertido em espécie acidentária',
            self::RESULTADO_DESLIGAMENTO => 'Desligamento posterior (validado RH/DP)',
        ];
    }

    /** @return array<string, string> */
    public static function tiposAfastamentoInicial(): array
    {
        return [
            'doenca_comum' => 'Doença comum',
            'acidente_trabalho' => 'Acidente de trabalho',
            'acidente_trajeto' => 'Acidente de trajeto',
            'doenca_ocupacional' => 'Doença ocupacional',
            'licenca_maternidade' => 'Licença maternidade',
            'outro' => 'Outro',
        ];
    }

    /** @return array<string, string> */
    public static function tiposAnexo(): array
    {
        return [
            self::ANEXO_ATESTADO => 'Atestado médico',
            self::ANEXO_RELATORIO => 'Relatório médico',
            self::ANEXO_COMPARECIMENTO => 'Declaração de comparecimento',
            self::ANEXO_COMPLEMENTAR => 'Documentação complementar',
            self::ANEXO_CAT => 'CAT',
            self::ANEXO_ASO_RETORNO => 'ASO de retorno',
            self::ANEXO_INSS => 'Documento INSS / Meu INSS',
            self::ANEXO_ESOCIAL => 'Comprovante eSocial',
        ];
    }

    /** Documentos sensíveis (CID, laudos) — perfis RH/DP/Medicina/TST. */
    public static function anexoSensivel(string $tipo): bool
    {
        return in_array($tipo, [self::ANEXO_RELATORIO, self::ANEXO_ATESTADO], true);
    }

    /**
     * @param  array<string, mixed>  $dados
     */
    public static function exigeEtapaTst(array $dados): bool
    {
        if (! empty($dados['acidente_trabalho']) || ! empty($dados['acidente_trajeto']) || ! empty($dados['doenca_ocupacional'])) {
            return true;
        }

        $class = (string) ($dados['classificacao'] ?? '');

        return in_array($class, [
            self::CLASS_ACIDENTE_TRABALHO,
            self::CLASS_ACIDENTE_TRAJETO,
            self::CLASS_DOENCA_OCUPACIONAL,
        ], true);
    }

    /**
     * @param  array<string, mixed>  $dados
     */
    public static function exigeEtapaInss(array $dados): bool
    {
        $dias = (int) ($dados['quantidade_dias'] ?? 0);
        if ($dias > 15) {
            return true;
        }

        $class = (string) ($dados['classificacao'] ?? '');

        return in_array($class, [
            self::CLASS_SUPERIOR_15_INSS,
            self::CLASS_AGUARDANDO_INSS,
            self::CLASS_PRORROGACAO,
        ], true);
    }

    /**
     * @param  array<string, mixed>  $dados
     */
    public static function exigeAsoRetorno(array $dados): bool
    {
        $dias = (int) ($dados['quantidade_dias'] ?? 0);
        if ($dias >= 30) {
            return true;
        }

        $resultado = (string) ($dados['resultado_final'] ?? '');

        return in_array($resultado, [self::RESULTADO_RETORNO, self::RESULTADO_ALTA_MEDICA], true);
    }

    /**
     * @param  array<string, mixed>  $dados
     */
    public static function mapearEspecieInss(array $dados): string
    {
        if (! empty($dados['acidente_trabalho']) || ($dados['classificacao'] ?? '') === self::CLASS_ACIDENTE_TRABALHO) {
            return 'acidente_trabalho';
        }
        if (! empty($dados['acidente_trajeto']) || ($dados['classificacao'] ?? '') === self::CLASS_ACIDENTE_TRAJETO) {
            return 'acidente_trabalho';
        }
        if (($dados['classificacao'] ?? '') === self::CLASS_LICENCA_MATERNIDADE) {
            return 'salario_maternidade';
        }

        return (string) ($dados['especie_beneficio_inss'] ?? 'auxilio_doenca');
    }
}
