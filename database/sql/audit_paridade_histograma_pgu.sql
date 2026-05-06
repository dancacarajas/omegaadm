-- =============================================================================
-- AUDITORIA PGU × histograma (paridade no BD)
--
-- IMPORTANTE (phpMyAdmin):
--   • Abra SQL pelo BANCO na esquerda (clique no nome do banco) > aba "SQL".
--     Não abra pela tabela "contrato_histograma_linhas" se ela ainda não existir
--     — isso gera erros estranhos (#1109 / SQL cortado).
--   • Sempre Ctrl+A no editor e UM clique em "Executar" (script inteiro).
--
-- Localhost 127.0.0.1: o banco `u482227589_omegaadm` só funciona se for uma
-- cópia real do servidor OU se você rodou `php artisan migrate` nesse banco.
-- Se a tabela não existir: na pasta do projeto → `php artisan migrate`
--
-- Hostinger: USE com o nome do banco do hPanel.
--
-- ATENÇÃO: rode o PASSO 1 primeiro e copie o valor da coluna "contrato" para @c.
-- Se no banco só existir "312" e você deixar @c = '286', o PASSO 2 dá ZERO linhas
-- (não é bug — simplesmente não há histograma 286 nesse recorte).
-- =============================================================================

USE `u482227589_omegaadm`;

-- Edite SEMPRE após olhar o PASSO 1 (contrato + competencia exatos da tabela)
SET @c = '312', @d = '2026-05-01';

-- PASSO 0 — a tabela existe neste banco? (deve retornar 1 linha com o nome da tabela)
SHOW TABLES LIKE 'contrato_histograma_linhas';

-- PASSO 1 — quais recortes existem (contrato + mês salvo)
SELECT
    contrato,
    competencia,
    COUNT(*) AS total_linhas_inclui_grupo,
    SUM(CASE WHEN tipo_linha = 'grupo' THEN 1 ELSE 0 END) AS qtd_grupo,
    SUM(CASE WHEN tipo_linha = 'item' OR tipo_linha IS NULL OR tipo_linha = '' THEN 1 ELSE 0 END) AS qtd_item_ou_sem_tipo
FROM contrato_histograma_linhas
GROUP BY contrato, competencia
ORDER BY contrato, competencia;

-- PASSO 2 — métricas PGU (mesmo filtro itensParaMetricasPgu)
-- Semântica: pre_pgu = mobilizado · pgu = necessidade · pendência = max(0, pgu - pre_pgu)
SELECT
    @c AS contrato_filtro,
    @d AS competencia_filtro,
    COUNT(*) AS total_itens_no_ranking_pgu,
    SUM(CASE WHEN pgu > 0 AND pre_pgu >= pgu THEN 1 ELSE 0 END) AS linhas_cobertura_100pct,
    SUM(CASE WHEN pgu > pre_pgu THEN 1 ELSE 0 END) AS linhas_com_pendencia_mobilizacao,
    SUM(CASE WHEN IFNULL(pre_pgu, 0) > 0 AND IFNULL(pgu, 0) <= 0 THEN 1 ELSE 0 END) AS linhas_pre_sem_pgu_informado,
    ROUND(SUM(GREATEST(IFNULL(pgu, 0) - IFNULL(pre_pgu, 0), 0)), 1) AS soma_pendencias_por_funcao,
    ROUND(SUM(LEAST(IFNULL(pre_pgu, 0), IFNULL(pgu, 0))), 1) AS soma_coberto_min_pre_pgu
FROM contrato_histograma_linhas
WHERE contrato = @c
  AND competencia = @d
  AND (tipo_linha = 'item' OR tipo_linha IS NULL OR tipo_linha = '');

SELECT MD5(
    GROUP_CONCAT(
        CONCAT_WS('|', ordem, IFNULL(item_codigo, ''), pre_pgu, pgu)
        ORDER BY ordem SEPARATOR ';;'
    )
) AS fingerprint_pre_pgu_pgu
FROM contrato_histograma_linhas
WHERE contrato = @c
  AND competencia = @d
  AND (tipo_linha = 'item' OR tipo_linha IS NULL OR tipo_linha = '');

SELECT ordem, item_codigo, descricao, tipo_linha, pre_pgu, pgu, pos_pgu
FROM contrato_histograma_linhas
WHERE contrato = @c
  AND competencia = @d
  AND (tipo_linha = 'item' OR tipo_linha IS NULL OR tipo_linha = '')
  AND pgu > pre_pgu
ORDER BY ordem;
