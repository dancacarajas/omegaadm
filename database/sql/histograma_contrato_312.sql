-- =============================================================================
-- Contrato 312 — histograma MÃO DE OBRA (oficial conforme planilha operacional)
--
-- Tabela: contrato_histograma_linhas
-- - 3 grupos + 34 itens = 37 linhas (mesma estrutura do contrato 286 / tela).
-- - Células vazias na planilha → 0 em pre_pgu/pgu/pos_pgu (colunas DECIMAL NOT NULL).
--   PGU vazio com Pré > 0 (ex.: 1.2.6–1.2.8) → pgu = 0 → dashboard classifica
--   como "PGU não informado" (sem_pgu_informado).
-- - Cabeçalho "MÃO DE OBRA" (203 / 358 / 62) replica o totalizador da planilha;
--   a soma apenas das linhas tipo `item` dá 202,5 / 357,5 / 62 (diferença 0,5
--   no Pré e na PGU — típico de total manual vs soma das linhas). O PGU Dashboard
--   agrega só itens, não os grupos.
--
-- Ajuste @competencia antes de rodar em produção (primeiro dia do mês).
-- Não apaga contrato_histograma_recortes (preserva data_limite_etapa_2). Para
-- zerar também o recorte, descomente o bloco opcional no final.
--
-- Rode o script inteiro em uma transação.
-- =============================================================================

-- USE `omega286`;

SET @c := '312';
SET @d := DATE '2026-05-01';

START TRANSACTION;

DELETE FROM contrato_histograma_linhas
WHERE contrato = @c AND competencia = @d;

INSERT INTO contrato_histograma_linhas
    (contrato, competencia, tipo_linha, ordem, item_codigo, descricao, unidade, mobilizacao, pre_pgu, pgu, pos_pgu, desmobilizacao, created_at, updated_at)
VALUES
    (@c, @d, 'grupo',  1,  '1',     'MÃO DE OBRA',               'Unid.', 0, 203.00, 358.00, 62.00, 0, NOW(), NOW()),
    (@c, @d, 'grupo',  2,  '1.1',   'EQUIPE INDIRETA',           'Unid.', 0,  46.50,  65.50, 23.00, 0, NOW(), NOW()),
    (@c, @d, 'item',   3,  '1.1.1', 'Gestor',                    'Unid.', 0,   1.00,   1.00,  1.00, 0, NOW(), NOW()),
    (@c, @d, 'item',   4,  '1.1.2', 'Supervisor de mecânica',    'Unid.', 0,   2.00,   4.00,  1.00, 0, NOW(), NOW()),
    (@c, @d, 'item',   5,  '1.1.3', 'Supervisor de elétrica',    'Unid.', 0,   1.00,   1.00,  1.00, 0, NOW(), NOW()),
    (@c, @d, 'item',   6,  '1.1.4', 'Engenheiro de Campo',       'Unid.', 0,   2.00,   2.00,  1.00, 0, NOW(), NOW()),
    (@c, @d, 'item',   7,  '1.1.5', 'Médico',                    'Unid.', 0,   0.50,   0.50,  0.00, 0, NOW(), NOW()),
    (@c, @d, 'item',   8,  '1.1.6', 'Engenheiro de Segurança',   'Unid.', 0,   1.00,   1.00,  1.00, 0, NOW(), NOW()),
    (@c, @d, 'item',   9,  '1.1.7', 'Técnico de segurança',      'Unid.', 0,  10.00,  16.00,  2.00, 0, NOW(), NOW()),
    (@c, @d, 'item',  10,  '1.1.8', 'Técnico de planejamento',   'Unid.', 0,   2.00,   2.00,  1.00, 0, NOW(), NOW()),
    (@c, @d, 'item',  11,  '1.1.9', 'Almoxarife',                'Unid.', 0,   2.00,   2.00,  1.00, 0, NOW(), NOW()),
    (@c, @d, 'item',  12,  '1.1.10','Auxiliar Almoxarife',       'Unid.', 0,   2.00,   2.00,  0.00, 0, NOW(), NOW()),
    (@c, @d, 'item',  13,  '1.1.11','Técnico de qualidade',      'Unid.', 0,   2.00,   2.00,  1.00, 0, NOW(), NOW()),
    (@c, @d, 'item',  14,  '1.1.12','Encarregado Administrativo','Unid.', 0,   1.00,   1.00,  1.00, 0, NOW(), NOW()),
    (@c, @d, 'item',  15,  '1.1.13','Assistente Administrativo', 'Unid.', 0,   1.00,   1.00,  1.00, 0, NOW(), NOW()),
    (@c, @d, 'item',  16,  '1.1.14','Operador de caminhão Munck','Unid.', 0,  10.00,  16.00,  2.00, 0, NOW(), NOW()),
    (@c, @d, 'item',  17,  '1.1.15','Operador de Equipamentos',  'Unid.', 0,   2.00,   4.00,  0.00, 0, NOW(), NOW()),
    (@c, @d, 'item',  18,  '1.1.16','Técnico de materiais',      'Unid.', 0,   1.00,   2.00,  1.00, 0, NOW(), NOW()),
    (@c, @d, 'item',  19,  '1.1.17','Motorista leve',            'Unid.', 0,   6.00,   8.00,  8.00, 0, NOW(), NOW()),
    (@c, @d, 'grupo', 20,  '1.2',   'EQUIPE DIRETA',             'Unid.', 0, 156.00, 292.00, 39.00, 0, NOW(), NOW()),
    (@c, @d, 'item',  21,  '1.2.1', 'Encarregado Elétrica',      'Unid.', 0,   4.00,   4.00,  1.00, 0, NOW(), NOW()),
    (@c, @d, 'item',  22,  '1.2.2', 'Eletricista força controle','Unid.', 0,  16.00,  16.00,  2.00, 0, NOW(), NOW()),
    (@c, @d, 'item',  23,  '1.2.3', 'Eletricista Montador',      'Unid.', 0,  24.00,  24.00,  4.00, 0, NOW(), NOW()),
    (@c, @d, 'item',  24,  '1.2.4', 'Ajudante',                  'Unid.', 0,  18.00,  18.00,  0.00, 0, NOW(), NOW()),
    (@c, @d, 'item',  25,  '1.2.5', 'Técnico de instrumentação', 'Unid.', 0,   2.00,   2.00,  1.00, 0, NOW(), NOW()),
    (@c, @d, 'item',  26,  '1.2.6', 'Mecânico Montador',         'Unid.', 0,  16.00,   0.00,  0.00, 0, NOW(), NOW()),
    (@c, @d, 'item',  27,  '1.2.7', 'Caldereiro',                'Unid.', 0,   8.00,   0.00,  0.00, 0, NOW(), NOW()),
    (@c, @d, 'item',  28,  '1.2.8', 'Soldador Especializado',    'Unid.', 0,   8.00,   0.00,  0.00, 0, NOW(), NOW()),
    (@c, @d, 'item',  29,  '1.2.9', 'Oficial de Civil',          'Unid.', 0,   8.00,   8.00,  1.00, 0, NOW(), NOW()),
    (@c, @d, 'item',  30,  '1.2.10','Encarregado Mecânica',     'Unid.', 0,   2.00,  12.00,  2.00, 0, NOW(), NOW()),
    (@c, @d, 'item',  31,  '1.2.10','Encarregado Andaime',      'Unid.', 0,   2.00,   8.00,  2.00, 0, NOW(), NOW()),
    (@c, @d, 'item',  32,  '1.2.11','Ajudante',                  'Unid.', 0,  12.00,  48.00, 10.00, 0, NOW(), NOW()),
    (@c, @d, 'item',  33,  '1.2.12','Mecânico Ajustador',        'Unid.', 0,   2.00,   8.00,  4.00, 0, NOW(), NOW()),
    (@c, @d, 'item',  34,  '1.2.12','Mecânico Montador',         'Unid.', 0,  18.00,  80.00,  4.00, 0, NOW(), NOW()),
    (@c, @d, 'item',  35,  '1.2.12','Montador de Andaime',       'Unid.', 0,   8.00,  24.00,  4.00, 0, NOW(), NOW()),
    (@c, @d, 'item',  36,  '1.2.13','Caldereiro',                'Unid.', 0,   4.00,  16.00,  2.00, 0, NOW(), NOW()),
    (@c, @d, 'item',  37,  '1.2.14','Soldador Especializado',    'Unid.', 0,   4.00,  24.00,  2.00, 0, NOW(), NOW());

COMMIT;

-- -----------------------------------------------------------------------------
-- Conferência rápida (execute após o COMMIT)
-- -----------------------------------------------------------------------------
-- Linhas: 37
-- SELECT COUNT(*) AS n FROM contrato_histograma_linhas WHERE contrato = '312' AND competencia = '2026-05-01';
--
-- Somente itens (como o dashboard PGU): esperado sum_pre 202.5, sum_pgu 357.5, sum_pos 62
-- SELECT
--   SUM(pre_pgu) AS sum_pre,
--   SUM(pgu)     AS sum_pgu,
--   SUM(pos_pgu) AS sum_pos
-- FROM contrato_histograma_linhas
-- WHERE contrato = '312' AND competencia = '2026-05-01' AND tipo_linha = 'item';
--
-- PGU não informado (Pré > 0 e pgu = 0): 3 linhas — 1.2.6, 1.2.7, 1.2.8
-- SELECT item_codigo, descricao, pre_pgu, pgu, pos_pgu
-- FROM contrato_histograma_linhas
-- WHERE contrato = '312' AND competencia = '2026-05-01' AND tipo_linha = 'item' AND pre_pgu > 0 AND pgu = 0
-- ORDER BY ordem;
--
-- Listagem completa:
-- SELECT ordem, tipo_linha, item_codigo, descricao, pre_pgu, pgu, pos_pgu
-- FROM contrato_histograma_linhas
-- WHERE contrato = '312' AND competencia = '2026-05-01'
-- ORDER BY ordem;

-- -----------------------------------------------------------------------------
-- Opcional: remover também o recorte (data limite Fase 2) desta competência
-- -----------------------------------------------------------------------------
-- START TRANSACTION;
-- DELETE FROM contrato_histograma_recortes WHERE contrato = '312' AND competencia = '2026-05-01';
-- INSERT INTO contrato_histograma_recortes (contrato, competencia, data_limite_etapa_2, created_at, updated_at)
-- VALUES ('312', '2026-05-01', DATE '2026-06-22', NOW(), NOW());
-- COMMIT;
