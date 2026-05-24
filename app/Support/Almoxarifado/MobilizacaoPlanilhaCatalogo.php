<?php

namespace App\Support\Almoxarifado;

/** Colunas e listas da planilha CT312 (CONTROLE_GERAL). */
final class MobilizacaoPlanilhaCatalogo
{
    public const CONTRATO_NUMERO_REFERENCIA = '312 - PROJETO ATHENAS';

    public const CONTRATO_NOME_REFERENCIA = 'PGU SALOBO - 312';

    public const CONTRATO_CENTRO_CUSTO_REFERENCIA = '312';

    /** @return list<string> */
    public static function disciplinas(): array
    {
        return [
            'ELÉTRICA',
            'MECÂNICA',
            'COMUM / APOIO / SEGURANÇA',
        ];
    }

    /** @return list<string> */
    public static function categorias(): array
    {
        return [
            'Mecânica / Ferramental mecânico',
            'Material elétrico',
            'EPI / EPC / Segurança geral',
            'Canteiro / Apoio / ADM',
            'Ferramentas gerais / validar uso',
            'Içamento / Rigging',
            'Solda / Oxicorte',
            'Elétrica / Ferramental elétrico',
            'Instrumentos gerais',
            'EPI/EPC solda/caldeiraria',
            'Instrumentos elétricos de teste',
            'Consumíveis gerais',
            'EPI/EPC específico de elétrica',
        ];
    }

    /** @return list<string> */
    public static function situacoesTratativa(): array
    {
        return [
            'SEM TRATATIVA LOCALIZADA',
            'CADASTRADO NO SIGO SEM ATENDIMENTO',
            'EM ATENDIMENTO EM COMPRAS',
        ];
    }

    /** @return list<string> */
    public static function situacoesSigo(): array
    {
        return [
            '',
            'AGUARDANDO COMPRAS',
            'EM COMPRA',
            'COMPRA PARCIAL',
        ];
    }

    /** Rótulos iguais aos cabeçalhos da planilha (formulário). */
    public static function rotulos(): array
    {
        return [
            'disciplina' => 'Disciplina',
            'categoria_descricao' => 'Categoria',
            'situacao_tratativa' => 'Situação',
            'descricao_material' => 'Material',
            'unidade_medida' => 'Unid.',
            'quantidade_necessaria' => 'Qtd necessária',
            'quantidade_pedida_sigo' => 'Qtd pedida SIGO',
            'quantidade_em_compra' => 'Qtd em compra',
            'quantidade_recebida' => 'Qtd recebida',
            'saldo_a_comprar' => 'Falta comprar',
            'saldo_a_receber' => 'Falta receber',
            'situacao_sigo_descricao' => 'Situação SIGO',
        ];
    }
}
