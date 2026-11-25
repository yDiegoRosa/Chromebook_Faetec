-- Rode este arquivo UMA VEZ no phpMyAdmin se você já tem o banco `chromebook`
-- criado com a versão antiga do chromebook.sql. Instalações novas não precisam dele.

USE `chromebook`;

-- Remove registros inválidos e duplicados antes de criar o índice único
DELETE FROM `agendamento` WHERE `horario` = 'null' OR `horario` = '';
DELETE a1 FROM `agendamento` a1
  JOIN `agendamento` a2
    ON a1.`data` = a2.`data` AND a1.`horario` = a2.`horario` AND a1.`idCor` = a2.`idCor` AND a1.`id` > a2.`id`;

-- Impede que a mesma caixa seja reservada duas vezes no mesmo horário
ALTER TABLE `agendamento` ADD UNIQUE KEY `caixa_por_horario` (`data`, `horario`, `idCor`);

-- Hash de senha precisa de mais espaço; novo campo de "trocar senha no próximo login"
ALTER TABLE `professor`
  MODIFY `senha` varchar(255) NOT NULL,
  ADD COLUMN `deve_trocar_senha` tinyint(1) NOT NULL DEFAULT 1,
  ADD UNIQUE KEY `email` (`email`);

-- Quem já tem senha criptografada não precisa trocar de novo
UPDATE `professor` SET `deve_trocar_senha` = 0 WHERE `senha` LIKE '$2y$%';
