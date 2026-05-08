-- Limpeza em lote da etapa "Treinamentos" no RH/Recrutamento.
-- ATENÇÃO: operação DESTRUTIVA. Só execute com backup COMPLETO do banco (dump)
-- validado fora do servidor. O backup criado abaixo cobre apenas linhas que
-- ainda têm chaves *_treinamentos_* — não substitui dump integral.
-- Antes do COMMIT: confira contagens, compare com o backup e use ROLLBACK se houver dúvida.
-- MySQL 8+.

START TRANSACTION;

-- 1) Backup rápido dos estados atuais (somente linhas que têm qualquer dado de treinamentos).
DROP TABLE IF EXISTS recrutamento_vagas_backup_treinamentos_cleanup;
CREATE TABLE recrutamento_vagas_backup_treinamentos_cleanup AS
SELECT id, form_state, NOW() AS backup_em
FROM recrutamento_vagas
WHERE JSON_SEARCH(
    JSON_KEYS(form_state),
    'one',
    '%_treinamentos_%'
) IS NOT NULL;

-- 2) Procedure temporária para remover todas as chaves de treinamentos por posição.
DROP PROCEDURE IF EXISTS cleanup_treinamentos_recrutamento;
DELIMITER //
CREATE PROCEDURE cleanup_treinamentos_recrutamento()
BEGIN
    DECLARE i INT DEFAULT 1;

    WHILE i <= 50 DO
        UPDATE recrutamento_vagas
        SET form_state = JSON_REMOVE(
            form_state,
            CONCAT('$.candidato_', i, '_treinamentos_data_agendamento'),
            CONCAT('$.candidato_', i, '_treinamentos_data_inicio'),
            CONCAT('$.candidato_', i, '_treinamentos_data_fim'),
            CONCAT('$.candidato_', i, '_treinamentos_data_confirmacao'),
            CONCAT('$.candidato_', i, '_treinamentos_matriz'),
            CONCAT('$.candidato_', i, '_treinamentos_realizados'),
            CONCAT('$.candidato_', i, '_treinamentos_certificados'),
            CONCAT('$.candidato_', i, '_treinamentos_capacitacao')
        )
        WHERE JSON_EXTRACT(form_state, CONCAT('$.candidato_', i, '_treinamentos_data_agendamento')) IS NOT NULL
           OR JSON_EXTRACT(form_state, CONCAT('$.candidato_', i, '_treinamentos_data_inicio')) IS NOT NULL
           OR JSON_EXTRACT(form_state, CONCAT('$.candidato_', i, '_treinamentos_data_fim')) IS NOT NULL
           OR JSON_EXTRACT(form_state, CONCAT('$.candidato_', i, '_treinamentos_data_confirmacao')) IS NOT NULL
           OR JSON_EXTRACT(form_state, CONCAT('$.candidato_', i, '_treinamentos_matriz')) IS NOT NULL
           OR JSON_EXTRACT(form_state, CONCAT('$.candidato_', i, '_treinamentos_realizados')) IS NOT NULL
           OR JSON_EXTRACT(form_state, CONCAT('$.candidato_', i, '_treinamentos_certificados')) IS NOT NULL
           OR JSON_EXTRACT(form_state, CONCAT('$.candidato_', i, '_treinamentos_capacitacao')) IS NOT NULL;

        SET i = i + 1;
    END WHILE;
END//
DELIMITER ;

CALL cleanup_treinamentos_recrutamento();
DROP PROCEDURE cleanup_treinamentos_recrutamento;

-- 3) Validação rápida pós-limpeza: deve retornar 0.
SELECT COUNT(*) AS registros_com_dados_treinamentos
FROM recrutamento_vagas
WHERE JSON_SEARCH(
    JSON_KEYS(form_state),
    'one',
    '%_treinamentos_%'
) IS NOT NULL;

COMMIT;

-- Em caso de problema antes do COMMIT, use:
-- ROLLBACK;
