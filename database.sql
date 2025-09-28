-- Script SQL para criar tabela de controle de entrega de pulseiras
-- Sistema de Entrega de Pulseiras - Salão do Turismo

-- Tabela para registrar as entregas de pulseiras
CREATE TABLE IF NOT EXISTS `entregas_pulseiras` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `inscricao_id` int(11) NOT NULL COMMENT 'ID da inscrição (extraído do QRCode após o ponto)',
  `qrcode_lido` text NOT NULL COMMENT 'QRCode completo lido (ex: 10.3731452383)',
  `tipo_qr` int(2) NOT NULL COMMENT 'Tipo do QRCode: 10=código inscrição, 20=id participante',
  `data_entrega` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `data_entrega_dia` date GENERATED ALWAYS AS (DATE(`data_entrega`)) STORED,
  `status` enum('entregue','pendente') DEFAULT 'entregue',
  `observacoes` text NULL,
  `ip_dispositivo` varchar(45) NULL,
  `user_agent` text NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_inscricao_id` (`inscricao_id`),
  INDEX `idx_data_entrega` (`data_entrega`),
  INDEX `idx_status` (`status`),
  INDEX `idx_tipo_qr` (`tipo_qr`),
  INDEX `idx_data_entrega_dia` (`data_entrega_dia`),
  UNIQUE KEY `unique_inscricao_dia` (`inscricao_id`, `data_entrega_dia`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Registra todas as entregas de pulseiras do evento';

-- Tabela para configurações do sistema de pulseiras
CREATE TABLE IF NOT EXISTS `pulseiras_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sistema_ativo` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=sistema ativo, 0=inativo',
  `uma_pulseira_por_dia` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=apenas uma pulseira por dia por pessoa',
  `tipos_permitidos` varchar(50) NOT NULL DEFAULT '10,20' COMMENT 'Tipos de QR permitidos (separados por vírgula)',
  `data_atualizacao` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `usuario_responsavel` varchar(100) NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Configurações do sistema de entrega de pulseiras';

-- Inserir registro inicial de configuração
INSERT IGNORE INTO `pulseiras_config` (`id`, `sistema_ativo`, `uma_pulseira_por_dia`, `tipos_permitidos`) 
VALUES (1, 1, 1, '10,20');
