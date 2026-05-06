-- =============================================================================
-- Histograma — contrato 312 (linhas conforme planilha / imagem de referência)
-- Tabela: contrato_histograma_linhas
-- Ajuste a competência (primeiro dia do mês) antes de rodar em produção:
-- =============================================================================

SET @contrato     := '312';
SET @competencia  := DATE '2026-05-01';  -- altere se o mês do histograma for outro
SET @agora        := NOW();

-- Remove recorte existente do mesmo contrato + competência (evita duplicar)
DELETE FROM contrato_histograma_linhas
WHERE contrato = @contrato AND competencia = @competencia;

INSERT INTO contrato_histograma_linhas
    (contrato, competencia, tipo_linha, ordem, item_codigo, descricao, unidade, mobilizacao, pre_pgu, pgu, pos_pgu, desmobilizacao, created_at, updated_at)
VALUES
    (@contrato, @competencia, 'grupo',  1,  '1',     'MÃO DE OBRA',              'Unid.', 0, 203.00, 358.00, 62.00, 0, @agora, @agora),
    (@contrato, @competencia, 'grupo',  2,  '1.1',   'EQUIPE INDIRETA',          'Unid.', 0,  46.50,  65.50, 23.00, 0, @agora, @agora),
    (@contrato, @competencia, 'item',   3,  '1.1.1', 'Gestor',                   'Unid.', 0,   1.00,   1.00,  1.00, 0, @agora, @agora),
    (@contrato, @competencia, 'item',   4,  '1.1.2', 'Supervisor de mecânica',   'Unid.', 0,   2.00,   4.00,  1.00, 0, @agora, @agora),
    (@contrato, @competencia, 'item',   5,  '1.1.3', 'Supervisor de elétrica',   'Unid.', 0,   1.00,   1.00,  1.00, 0, @agora, @agora),
    (@contrato, @competencia, 'item',   6,  '1.1.4', 'Engenheiro de Campo',      'Unid.', 0,   2.00,   2.00,  1.00, 0, @agora, @agora),
    (@contrato, @competencia, 'item',   7,  '1.1.5', 'Médico',                   'Unid.', 0,   0.50,   0.50,  0.00, 0, @agora, @agora),
    (@contrato, @competencia, 'item',   8,  '1.1.6', 'Engenheiro de Segurança',  'Unid.', 0,   1.00,   1.00,  1.00, 0, @agora, @agora),
    (@contrato, @competencia, 'item',   9,  '1.1.7', 'Técnico de segurança',     'Unid.', 0,  10.00,  16.00,  2.00, 0, @agora, @agora),
    (@contrato, @competencia, 'item',  10,  '1.1.8', 'Técnico de planejamento',  'Unid.', 0,   2.00,   2.00,  1.00, 0, @agora, @agora),
    (@contrato, @competencia, 'item',  11,  '1.1.9', 'Almoxarife',               'Unid.', 0,   2.00,   2.00,  1.00, 0, @agora, @agora),
    (@contrato, @competencia, 'item',  12,  '1.1.10','Auxiliar Almoxarife',      'Unid.', 0,   2.00,   2.00,  0.00, 0, @agora, @agora),
    (@contrato, @competencia, 'item',  13,  '1.1.11','Técnico de qualidade',     'Unid.', 0,   2.00,   2.00,  1.00, 0, @agora, @agora),
    (@contrato, @competencia, 'item',  14,  '1.1.12','Encarregado Administrativo','Unid.', 0,   1.00,   1.00,  1.00, 0, @agora, @agora),
    (@contrato, @competencia, 'item',  15,  '1.1.13','Assistente Administrativo','Unid.', 0,   1.00,   1.00,  1.00, 0, @agora, @agora),
    (@contrato, @competencia, 'item',  16,  '1.1.14','Operador de caminhão Munck','Unid.', 0,  10.00,  16.00,  2.00, 0, @agora, @agora),
    (@contrato, @competencia, 'item',  17,  '1.1.15','Operador de Equipamentos', 'Unid.', 0,   2.00,   4.00,  0.00, 0, @agora, @agora),
    (@contrato, @competencia, 'item',  18,  '1.1.16','Técnico de materiais',     'Unid.', 0,   1.00,   2.00,  1.00, 0, @agora, @agora),
    (@contrato, @competencia, 'item',  19,  '1.1.17','Motorista leve',           'Unid.', 0,   6.00,   8.00,  8.00, 0, @agora, @agora),
    (@contrato, @competencia, 'grupo', 20,  '1.2',   'EQUIPE DIRETA',            'Unid.', 0, 156.00, 292.00, 39.00, 0, @agora, @agora),
    (@contrato, @competencia, 'item',  21,  '1.2.1', 'Encarregado Elétrica',     'Unid.', 0,   4.00,   4.00,  1.00, 0, @agora, @agora),
    (@contrato, @competencia, 'item',  22,  '1.2.2', 'Eletricista força controle','Unid.', 0,  16.00,  16.00,  2.00, 0, @agora, @agora),
    (@contrato, @competencia, 'item',  23,  '1.2.3', 'Eletricista Montador',     'Unid.', 0,  24.00,  24.00,  4.00, 0, @agora, @agora),
    (@contrato, @competencia, 'item',  24,  '1.2.4', 'Ajudante',                 'Unid.', 0,  18.00,  18.00,  0.00, 0, @agora, @agora),
    (@contrato, @competencia, 'item',  25,  '1.2.5', 'Técnico de instrumentação','Unid.', 0,   2.00,   2.00,  1.00, 0, @agora, @agora),
    (@contrato, @competencia, 'item',  26,  '1.2.6', 'Mecânico Montador',        'Unid.', 0,  16.00,   0.00,  0.00, 0, @agora, @agora),
    (@contrato, @competencia, 'item',  27,  '1.2.7', 'Caldereiro',               'Unid.', 0,   8.00,   0.00,  0.00, 0, @agora, @agora),
    (@contrato, @competencia, 'item',  28,  '1.2.8', 'Soldador Especializado',   'Unid.', 0,   8.00,   0.00,  0.00, 0, @agora, @agora),
    (@contrato, @competencia, 'item',  29,  '1.2.9', 'Oficial de Civil',         'Unid.', 0,   8.00,   8.00,  1.00, 0, @agora, @agora),
    (@contrato, @competencia, 'item',  30,  '1.2.10','Encarregado Mecânica',     'Unid.', 0,   2.00,  12.00,  2.00, 0, @agora, @agora),
    (@contrato, @competencia, 'item',  31,  '1.2.10','Encarregado Andaime',      'Unid.', 0,   2.00,   8.00,  2.00, 0, @agora, @agora),
    (@contrato, @competencia, 'item',  32,  '1.2.11','Ajudante',                 'Unid.', 0,  12.00,  48.00, 10.00, 0, @agora, @agora),
    (@contrato, @competencia, 'item',  33,  '1.2.12','Mecânico Ajustador',       'Unid.', 0,   2.00,   8.00,  4.00, 0, @agora, @agora),
    (@contrato, @competencia, 'item',  34,  '1.2.12','Mecânico Montador',        'Unid.', 0,  18.00,  80.00,  4.00, 0, @agora, @agora),
    (@contrato, @competencia, 'item',  35,  '1.2.12','Montador de Andaime',      'Unid.', 0,   8.00,  24.00,  4.00, 0, @agora, @agora),
    (@contrato, @competencia, 'item',  36,  '1.2.13','Caldereiro',               'Unid.', 0,   4.00,  16.00,  2.00, 0, @agora, @agora),
    (@contrato, @competencia, 'item',  37,  '1.2.14','Soldador Especializado',   'Unid.', 0,   4.00,  24.00,  2.00, 0, @agora, @agora);

-- Opcional: data limite Fase 1 → Fase 2 (ajuste ou remova o INSERT)
-- DELETE FROM contrato_histograma_recortes WHERE contrato = @contrato AND competencia = @competencia;
-- INSERT INTO contrato_histograma_recortes (contrato, competencia, data_limite_etapa_2, created_at, updated_at)
-- VALUES (@contrato, @competencia, DATE '2026-06-22', @agora, @agora);
