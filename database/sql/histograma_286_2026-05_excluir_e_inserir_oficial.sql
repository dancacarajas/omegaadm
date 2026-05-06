-- =============================================================================
-- Contrato 286 · competência 2026-05 — REMOVE o recorte errado e INSERE o oficial
-- (valores do print MÃO DE OBRA: maio/2026).
--
-- Ordem: 3 grupos + 34 itens = 37 linhas (igual à tela de histograma).
-- Células vazias no print → 0 em pgu/pos_pgu (mesmo que o "Salvar" do Laravel).
--
-- Ajuste USE para o nome do seu banco (localhost: omega286; Hostinger: veja hPanel).
-- Rode o script inteiro (transação).
-- =============================================================================

-- USE `omega286`;

SET @c = '286';
SET @d = '2026-05-01';

START TRANSACTION;

DELETE FROM contrato_histograma_linhas
WHERE contrato = @c AND competencia = @d;

DELETE FROM contrato_histograma_recortes
WHERE contrato = @c AND competencia = @d;

INSERT INTO contrato_histograma_linhas
(contrato, competencia, tipo_linha, ordem, item_codigo, descricao, unidade, mobilizacao, pre_pgu, pgu, pos_pgu, desmobilizacao, created_at, updated_at)
VALUES
(@c, @d, 'grupo', 1, '1', 'MAO DE OBRA', 'Unid.', 0, 203, 358, 62, 0, NOW(), NOW()),
(@c, @d, 'grupo', 2, '1.1', 'EQUIPE INDIRETA', 'Unid.', 0, 46.5, 65.5, 23, 0, NOW(), NOW()),
(@c, @d, 'item', 3, '1.1.1', 'Gestor', 'Unid.', 0, 1, 1, 1, 0, NOW(), NOW()),
(@c, @d, 'item', 4, '1.1.2', 'Supervisor de mecânica', 'Unid.', 0, 2, 4, 1, 0, NOW(), NOW()),
(@c, @d, 'item', 5, '1.1.3', 'Supervisor de elétrica', 'Unid.', 0, 1, 1, 1, 0, NOW(), NOW()),
(@c, @d, 'item', 6, '1.1.4', 'Engenheiro de Campo', 'Unid.', 0, 2, 2, 1, 0, NOW(), NOW()),
(@c, @d, 'item', 7, '1.1.5', 'Médico', 'Unid.', 0, 0.5, 0.5, 0, 0, NOW(), NOW()),
(@c, @d, 'item', 8, '1.1.6', 'Engenheiro de Segurança', 'Unid.', 0, 1, 1, 1, 0, NOW(), NOW()),
(@c, @d, 'item', 9, '1.1.7', 'Técnico de segurança', 'Unid.', 0, 10, 16, 2, 0, NOW(), NOW()),
(@c, @d, 'item', 10, '1.1.8', 'Técnico de planejamento', 'Unid.', 0, 2, 2, 1, 0, NOW(), NOW()),
(@c, @d, 'item', 11, '1.1.9', 'Almoxarife', 'Unid.', 0, 2, 2, 1, 0, NOW(), NOW()),
(@c, @d, 'item', 12, '1.1.10', 'Auxiliar Almoxarife', 'Unid.', 0, 2, 2, 0, 0, NOW(), NOW()),
(@c, @d, 'item', 13, '1.1.11', 'Técnico de qualidade', 'Unid.', 0, 2, 2, 1, 0, NOW(), NOW()),
(@c, @d, 'item', 14, '1.1.12', 'Encarregado Administrativo', 'Unid.', 0, 1, 1, 1, 0, NOW(), NOW()),
(@c, @d, 'item', 15, '1.1.13', 'Assistente Administrativo', 'Unid.', 0, 1, 1, 1, 0, NOW(), NOW()),
(@c, @d, 'item', 16, '1.1.14', 'Operador de caminhão Munck', 'Unid.', 0, 10, 16, 2, 0, NOW(), NOW()),
(@c, @d, 'item', 17, '1.1.15', 'Operador de Equipamentos', 'Unid.', 0, 2, 4, 0, 0, NOW(), NOW()),
(@c, @d, 'item', 18, '1.1.16', 'Técnico de materiais', 'Unid.', 0, 1, 2, 1, 0, NOW(), NOW()),
(@c, @d, 'item', 19, '1.1.17', 'Motorista leve', 'Unid.', 0, 6, 8, 8, 0, NOW(), NOW()),
(@c, @d, 'grupo', 20, '1.2', 'EQUIPE DIRETA', 'Unid.', 0, 156, 292, 39, 0, NOW(), NOW()),
(@c, @d, 'item', 21, '1.2.1', 'Encarregado Elétrica', 'Unid.', 0, 4, 4, 1, 0, NOW(), NOW()),
(@c, @d, 'item', 22, '1.2.2', 'Eletricista força controle', 'Unid.', 0, 16, 16, 2, 0, NOW(), NOW()),
(@c, @d, 'item', 23, '1.2.3', 'Eletricista Montador', 'Unid.', 0, 24, 24, 4, 0, NOW(), NOW()),
(@c, @d, 'item', 24, '1.2.4', 'Ajudante', 'Unid.', 0, 18, 18, 0, 0, NOW(), NOW()),
(@c, @d, 'item', 25, '1.2.5', 'Técnico de instrumentação', 'Unid.', 0, 2, 2, 1, 0, NOW(), NOW()),
(@c, @d, 'item', 26, '1.2.6', 'Mecânico Montador', 'Unid.', 0, 16, 0, 0, 0, NOW(), NOW()),
(@c, @d, 'item', 27, '1.2.7', 'Caldereiro', 'Unid.', 0, 8, 0, 0, 0, NOW(), NOW()),
(@c, @d, 'item', 28, '1.2.8', 'Soldador Especializado', 'Unid.', 0, 8, 0, 0, 0, NOW(), NOW()),
(@c, @d, 'item', 29, '1.2.9', 'Oficial de Civil', 'Unid.', 0, 8, 8, 1, 0, NOW(), NOW()),
(@c, @d, 'item', 30, '1.2.10', 'Encarregado Mecânica', 'Unid.', 0, 2, 12, 2, 0, NOW(), NOW()),
(@c, @d, 'item', 31, '1.2.10', 'Encarregado Andaime', 'Unid.', 0, 2, 8, 2, 0, NOW(), NOW()),
(@c, @d, 'item', 32, '1.2.11', 'Ajudante', 'Unid.', 0, 12, 48, 10, 0, NOW(), NOW()),
(@c, @d, 'item', 33, '1.2.12', 'Mecânico Ajustador', 'Unid.', 0, 2, 8, 4, 0, NOW(), NOW()),
(@c, @d, 'item', 34, '1.2.12', 'Mecânico Montador', 'Unid.', 0, 18, 80, 4, 0, NOW(), NOW()),
(@c, @d, 'item', 35, '1.2.12', 'Montador de Andaime', 'Unid.', 0, 8, 24, 4, 0, NOW(), NOW()),
(@c, @d, 'item', 36, '1.2.13', 'Caldereiro', 'Unid.', 0, 4, 16, 2, 0, NOW(), NOW()),
(@c, @d, 'item', 37, '1.2.14', 'Soldador Especializado', 'Unid.', 0, 4, 24, 2, 0, NOW(), NOW());

COMMIT;

-- Conferência: 37 linhas; totais do grupo 1 = 203 / 358 / 62
-- SELECT COUNT(*) FROM contrato_histograma_linhas WHERE contrato='286' AND competencia='2026-05-01';
-- SELECT ordem, item_codigo, descricao, pre_pgu, pgu, pos_pgu FROM contrato_histograma_linhas WHERE contrato='286' AND competencia='2026-05-01' ORDER BY ordem;
