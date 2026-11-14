-- ==============================================================================
-- SCRIPT: Limpeza Automática do Projeto Mais Antigo ao Atingir o Limite (10 Projetos)
-- Banco de Dados: mvc_creator
-- Descrição:
--   Ao atingir o limite de 10 projetos por usuário, este script / procedure
--   remove automaticamente o projeto mais antigo (menor data_criacao / menor id_projeto),
--   liberando espaço para a criação do novo projeto.
-- ==============================================================================

USE `mvc_creator`;

-- ------------------------------------------------------------------------------
-- 1. STORED PROCEDURE: sp_apagar_projeto_mais_antigo_se_limite
-- Parâmetros:
--   p_id_usuario: ID do usuário proprietário dos projetos
--   p_limite: Limite máximo tolerado (padrão 10)
-- ------------------------------------------------------------------------------
DELIMITER $$

DROP PROCEDURE IF EXISTS `sp_apagar_projeto_mais_antigo_se_limite`$$

CREATE PROCEDURE `sp_apagar_projeto_mais_antigo_se_limite`(
    IN p_id_usuario INT,
    IN p_limite INT
)
BEGIN
    DECLARE v_total INT;
    DECLARE v_id_antigo INT;

    -- Define limite padrão caso seja passado nulo ou inválido
    IF p_limite IS NULL OR p_limite <= 0 THEN
        SET p_limite = 10;
    END IF;

    -- Conta total de projetos atuais do usuário
    SELECT COUNT(*) INTO v_total 
    FROM `projeto` 
    WHERE `id_usuario` = p_id_usuario;

    -- Enquanto a quantidade for igual ou superior ao limite, apaga o mais antigo
    WHILE v_total >= p_limite DO
        SELECT `id_projeto` INTO v_id_antigo 
        FROM `projeto` 
        WHERE `id_usuario` = p_id_usuario 
        ORDER BY `data_criacao` ASC, `id_projeto` ASC 
        LIMIT 1;

        IF v_id_antigo IS NOT NULL THEN
            DELETE FROM `projeto` WHERE `id_projeto` = v_id_antigo;
            SET v_total = v_total - 1;
        ELSE
            SET v_total = 0;
        END IF;
    END WHILE;
END$$

DELIMITER ;


-- ------------------------------------------------------------------------------
-- 2. QUERY DIRETA EM LOTE (Para execução via cron, agendamento ou rotina SQL)
-- Apaga o projeto mais antigo de todos os usuários que possuírem 10 ou mais projetos:
-- ------------------------------------------------------------------------------
-- DELETE FROM `projeto` 
-- WHERE `id_projeto` IN (
--     SELECT id_projeto FROM (
--         SELECT p.id_projeto
--         FROM `projeto` p
--         INNER JOIN (
--             SELECT id_usuario, COUNT(*) AS total
--             FROM `projeto`
--             GROUP BY id_usuario
--             HAVING total >= 10
--         ) u ON p.id_usuario = u.id_usuario
--         WHERE p.id_projeto = (
--             SELECT p_sub.id_projeto
--             FROM `projeto` p_sub
--             WHERE p_sub.id_usuario = p.id_usuario
--             ORDER BY p_sub.data_criacao ASC, p_sub.id_projeto ASC
--             LIMIT 1
--         )
--     ) AS mais_antigos
-- );
