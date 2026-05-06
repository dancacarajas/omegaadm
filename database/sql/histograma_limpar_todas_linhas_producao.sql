-- =============================================================================
-- APAGAR TODO O HISTOGRAMA (produção / qualquer ambiente)
--
-- Remove TODAS as linhas de `contrato_histograma_linhas`.
-- Opcional: remove também `contrato_histograma_recortes` (datas limite por mês).
--
-- ANTES: faça backup (phpMyAdmin → Exportar → só essas tabelas) ou snapshot do BD.
-- Rode pelo banco na esquerda > aba SQL > Ctrl+A > Executar (script inteiro).
-- Ajuste o USE para o nome real do banco no hPanel, se for diferente.
-- =============================================================================

USE `u482227589_omegaadm`;

-- Pré-visualização (quantos registros serão apagados)
SELECT
    (SELECT COUNT(*) FROM contrato_histograma_linhas) AS linhas_antes,
    (SELECT COUNT(*) FROM contrato_histograma_recortes) AS recortes_antes;

START TRANSACTION;

DELETE FROM contrato_histograma_linhas;

-- Descomente a linha abaixo se quiser apagar também metadados de recorte (contrato + competência)
-- DELETE FROM contrato_histograma_recortes;

COMMIT;

-- Conferência (deve ser 0 em linhas; recortes só zera se você descomentou o DELETE acima)
SELECT
    (SELECT COUNT(*) FROM contrato_histograma_linhas) AS linhas_depois,
    (SELECT COUNT(*) FROM contrato_histograma_recortes) AS recortes_depois;
