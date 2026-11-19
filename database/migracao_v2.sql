-- CondoLink: migração v2 (rode no phpMyAdmin, aba SQL, com o banco `condolink` selecionado).
-- Pode ser executada uma única vez sobre o dump original (condolink.sql).

SET NAMES utf8mb4;

-- 1) Cadastro direto: o usuário se cadastra sem escolher unidade (vínculo é feito depois)
ALTER TABLE `usuarios`
  MODIFY `id_unid` int(11) NULL,
  ADD COLUMN `aceite_privacidade_em` datetime NULL,
  ADD COLUMN `aceite_cookies_em` datetime NULL,
  ADD COLUMN `criado_em` timestamp NOT NULL DEFAULT current_timestamp();

-- 2) Limite de tentativas de login
CREATE TABLE `login_tentativas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL,
  `ip` varchar(45) NOT NULL,
  `sucesso` tinyint(1) NOT NULL DEFAULT 0,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_login_email` (`email`, `criado_em`),
  KEY `idx_login_ip` (`ip`, `criado_em`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 3) Redefinição de senha (guarda só o hash do token)
CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) NOT NULL,
  `token_hash` char(64) NOT NULL,
  `expira_em` datetime NOT NULL,
  `usado_em` datetime NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_reset_token` (`token_hash`),
  KEY `idx_reset_usuario` (`id_usuario`, `criado_em`),
  CONSTRAINT `fk_reset_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 4) Planos
CREATE TABLE `planos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `slug` varchar(30) NOT NULL,
  `nome` varchar(50) NOT NULL,
  `preco_mensal` decimal(10,2) NOT NULL,
  `descricao` varchar(255) NOT NULL,
  `max_unidades` int(11) NULL COMMENT 'NULL = ilimitado',
  `max_areas` int(11) NULL COMMENT 'NULL = ilimitado',
  `recursos` text NOT NULL COMMENT 'um recurso por linha',
  `destaque` tinyint(1) NOT NULL DEFAULT 0,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_planos_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `planos` (`slug`,`nome`,`preco_mensal`,`descricao`,`max_unidades`,`max_areas`,`recursos`,`destaque`) VALUES
('essencial','Essencial',99.00,'Para condomínios pequenos que estão começando a organizar o uso das áreas comuns.',50,3,
 'Reservas de áreas comuns pelo site\nMural de comunicados\nGestão de horários e regras de cada área\nSuporte por e-mail',0),
('premium','Premium',199.00,'Para condomínios médios que querem mais controle, histórico e comunicação segmentada.',200,8,
 'Tudo do plano Essencial\nComunicados por bloco ou categoria\nControle de convidados\nRelatórios de uso das áreas\nSuporte prioritário por WhatsApp',1),
('pro','Pro',399.00,'Para grandes complexos residenciais e administradoras com vários condomínios.',NULL,NULL,
 'Tudo do plano Premium\nVários condomínios na mesma conta\nSuporte prioritário 24h\nGerente de conta dedicado',0);

-- 5) Clientes (dados de cobrança do usuário)
CREATE TABLE `clientes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `telefone` varchar(20) NOT NULL,
  `cpf` char(11) NOT NULL,
  `cep` char(8) NOT NULL,
  `endereco` varchar(255) NOT NULL,
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_clientes_usuario` (`id_usuario`),
  CONSTRAINT `fk_clientes_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 6) Assinaturas
CREATE TABLE `assinaturas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_cliente` int(11) NOT NULL,
  `id_plano` int(11) NOT NULL,
  `status` enum('pendente','ativa','cancelada','expirada') NOT NULL DEFAULT 'pendente',
  `ciclo` enum('mensal','anual') NOT NULL DEFAULT 'mensal',
  `valor_mensal` decimal(10,2) NOT NULL COMMENT 'preço mensal do plano na data da contratação',
  `inicio` date NULL,
  `fim` date NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_assin_cliente` (`id_cliente`, `status`),
  KEY `idx_assin_plano` (`id_plano`),
  CONSTRAINT `fk_assin_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_assin_plano` FOREIGN KEY (`id_plano`) REFERENCES `planos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 7) Transações (nenhum dado de cartão é guardado aqui)
CREATE TABLE `transacoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_assinatura` int(11) NOT NULL,
  `metodo` enum('cartao','pix') NOT NULL,
  `valor_bruto` decimal(10,2) NOT NULL,
  `desconto` decimal(10,2) NOT NULL DEFAULT 0.00,
  `valor_total` decimal(10,2) NOT NULL,
  `status` enum('pendente','aprovada','recusada','estornada') NOT NULL DEFAULT 'pendente',
  `referencia` varchar(100) NULL COMMENT 'ID da cobrança no provedor de pagamento',
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_transacoes_assin` (`id_assinatura`),
  KEY `idx_transacoes_status` (`status`, `criado_em`),
  CONSTRAINT `fk_transacoes_assin` FOREIGN KEY (`id_assinatura`) REFERENCES `assinaturas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 8) Primeiro administrador: cadastre-se normalmente no site e depois rode (troque o e-mail):
-- UPDATE `usuarios` SET `tipo` = 'Admin' WHERE `email` = 'seu@email.com';
