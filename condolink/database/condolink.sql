-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 03/11/2026 às 16:06
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `condolink`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `areas_comuns`
--

CREATE TABLE `areas_comuns` (
  `id_areas` int(11) NOT NULL,
  `id_cond` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `capacidade` int(11) NOT NULL,
  `regras` text NOT NULL,
  `status_areas` enum('Ativo','Manutenção') NOT NULL DEFAULT 'Ativo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `comunicados`
--

CREATE TABLE `comunicados` (
  `id_comunicados` int(11) NOT NULL,
  `id_cond` int(11) NOT NULL,
  `id_autor` int(11) NOT NULL,
  `titulo` varchar(100) DEFAULT NULL,
  `conteudo` text DEFAULT NULL,
  `categoria` enum('Manutenção','Assembleia','Geral') DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `condominios`
--

CREATE TABLE `condominios` (
  `id_cond` int(11) NOT NULL,
  `nome_cond` varchar(100) NOT NULL,
  `endereco` varchar(255) NOT NULL,
  `create_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `reservas`
--

CREATE TABLE `reservas` (
  `id_reservas` int(11) NOT NULL,
  `id_areas` int(11) NOT NULL,
  `id_usuarios` int(11) NOT NULL,
  `data_reserva` date NOT NULL,
  `horario_inicio` time NOT NULL,
  `horario_final` time NOT NULL,
  `status_reserva` enum('pendente','cancelada','confirmada') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `unidades`
--

CREATE TABLE `unidades` (
  `id_unid` int(11) NOT NULL,
  `id_cond` int(11) NOT NULL,
  `bloco` varchar(100) NOT NULL,
  `numero` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `id_unid` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `senha_hash` varchar(255) NOT NULL,
  `tipo` enum('Sindico','Morador','Admin') NOT NULL DEFAULT 'Morador',
  `sts` enum('Ativo','Inativo','Pendente','Excluido') NOT NULL DEFAULT 'Ativo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `areas_comuns`
--
ALTER TABLE `areas_comuns`
  ADD PRIMARY KEY (`id_areas`),
  ADD KEY `fk_areasComum_condominios` (`id_cond`);

--
-- Índices de tabela `comunicados`
--
ALTER TABLE `comunicados`
  ADD PRIMARY KEY (`id_comunicados`),
  ADD KEY `fk_comunicados_condominio` (`id_cond`),
  ADD KEY `fk_comunicados_autor` (`id_autor`);

--
-- Índices de tabela `condominios`
--
ALTER TABLE `condominios`
  ADD PRIMARY KEY (`id_cond`);

--
-- Índices de tabela `reservas`
--
ALTER TABLE `reservas`
  ADD PRIMARY KEY (`id_reservas`),
  ADD KEY `fk_reservas_areas` (`id_areas`),
  ADD KEY `fk_reservas_usuarios` (`id_usuarios`);

--
-- Índices de tabela `unidades`
--
ALTER TABLE `unidades`
  ADD PRIMARY KEY (`id_unid`),
  ADD KEY `Fk_cond_id` (`id_cond`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_usuario_unidade` (`id_unid`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `areas_comuns`
--
ALTER TABLE `areas_comuns`
  MODIFY `id_areas` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `comunicados`
--
ALTER TABLE `comunicados`
  MODIFY `id_comunicados` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `condominios`
--
ALTER TABLE `condominios`
  MODIFY `id_cond` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `reservas`
--
ALTER TABLE `reservas`
  MODIFY `id_reservas` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `unidades`
--
ALTER TABLE `unidades`
  MODIFY `id_unid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `areas_comuns`
--
ALTER TABLE `areas_comuns`
  ADD CONSTRAINT `fk_areasComum_condominios` FOREIGN KEY (`id_cond`) REFERENCES `condominios` (`id_cond`) ON DELETE CASCADE;

--
-- Restrições para tabelas `comunicados`
--
ALTER TABLE `comunicados`
  ADD CONSTRAINT `fk_comunicados_autor` FOREIGN KEY (`id_autor`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_comunicados_condominio` FOREIGN KEY (`id_cond`) REFERENCES `condominios` (`id_cond`) ON DELETE CASCADE;

--
-- Restrições para tabelas `reservas`
--
ALTER TABLE `reservas`
  ADD CONSTRAINT `fk_reservas_areas` FOREIGN KEY (`id_areas`) REFERENCES `areas_comuns` (`id_areas`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_reservas_usuarios` FOREIGN KEY (`id_usuarios`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `unidades`
--
ALTER TABLE `unidades`
  ADD CONSTRAINT `Fk_cond_id` FOREIGN KEY (`id_cond`) REFERENCES `condominios` (`id_cond`) ON DELETE CASCADE;

--
-- Restrições para tabelas `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `fk_usuario_unidade` FOREIGN KEY (`id_unid`) REFERENCES `unidades` (`id_unid`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
