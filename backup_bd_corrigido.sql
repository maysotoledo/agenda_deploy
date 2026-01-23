-- --------------------------------------------------------
-- Servidor:                     127.0.0.1
-- Versão do servidor:           8.4.7 - MySQL Community Server - GPL
-- OS do Servidor:               Win64
-- HeidiSQL Versão:              12.14.0.7165
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Copiando estrutura do banco de dados para agenda_epc
DROP DATABASE IF EXISTS `agenda_epc`;
CREATE DATABASE IF NOT EXISTS `agenda_epc` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `agenda_epc`;

-- Copiando estrutura para tabela agenda_epc.users
DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Copiando dados para a tabela agenda_epc.users: ~23 rows (aproximadamente)
DELETE FROM `users`;
INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`) VALUES
	(5, 'Mayso Toledo', 'mayso.toledo@gmail.com', NULL, '$2y$12$UWdcy34iwlSaehPJv7XcqeRHgcm.0tKkQMbeW8.GslOwhDXW8Uk0m', NULL, '2026-01-07 16:25:47', '2026-01-07 16:25:47'),
	(6, 'ANA BEATRIZ SALVIANO FERRAZ', 'anaferraz@pjc.mt.gov.br', NULL, '$2y$12$zFDvCwfBZW.2EZtrUYzOhe3ZW8.lcM8kO2s1p9RO5j8F.WjSZsfwu', NULL, '2026-01-07 16:55:10', '2026-01-07 16:55:10'),
	(7, 'ANA CLEUDY DIAS DOS SANTOS', 'anadias@pjc.mt.gov.br', NULL, '$2y$12$z76dWEAQcEpdtKRKD8ozquuGQ2sysh3rWOSEyMf37fCeKWdrdNKHq', NULL, '2026-01-07 16:57:06', '2026-01-07 16:57:06'),
	(8, 'ANA LUCIA MIRANDA MACIEL', 'anamaciel@pjc.mt.gov.br', NULL, '$2y$12$h8JjHodg2DsOCSiy62qo8erv6gF.PbvAr5TLpXaFuoGbekzbkdZWe', NULL, '2026-01-07 16:57:54', '2026-01-07 16:57:54'),
	(9, 'BRUNA TAVARES DE MESQUITA', 'brunamesquita@pjc.mt.gov.br', NULL, '$2y$12$tjO116pzqikXDnsMleclkuDJhfO72J33U39vfhEIrIXYPIuCAPQoC', NULL, '2026-01-07 16:58:29', '2026-01-07 16:58:29'),
	(10, 'CHARLES VINICIUS SIQUEIRA DE OLIVEIRA', 'charlesoliveira@pjc.mt.gov.br', NULL, '$2y$12$PSTzy551xsMbBZkNjkjGK.acJh.bB9kFOebSA8zCgKySeq9BGnyEa', NULL, '2026-01-07 16:58:58', '2026-01-07 16:58:58'),
	(11, 'GILSON DOS SANTOS E SILVA', 'gilsonsilva@pjc.mt.gov.br', NULL, '$2y$12$Q6on7xKkHM6XOB3hBGL7WeRL2hgxfidwUsVlQV34.utAQD1dpBzBq', NULL, '2026-01-07 16:59:34', '2026-01-07 16:59:34'),
	(12, 'JOSÉ CARLOS CORDEIRO GOMES', 'josecordeiro@pjc.mt.gov.br', NULL, '$2y$12$iYwcYZS1TPoMo1U.GgFCFOX.P5qpQ9Hy7dVchaOj0AcswXkKDnVvK', NULL, '2026-01-07 17:00:07', '2026-01-07 17:00:07'),
	(13, 'KARLA GREGORIO DOS SANTOS', 'karlasantos@pjc.mt.gov.br', NULL, '$2y$12$tluz1ERsGH9lg6eNQeYeYODHqTW6MxS7JiJv/0K8xRlD4QbW.kn3.', NULL, '2026-01-07 17:00:38', '2026-01-07 17:00:38'),
	(14, 'KATIANE ALVES DA SILVA', 'katianesilva@pjc.mt.gov.br', NULL, '$2y$12$vdvgDzR1yoDcJMoA.oFZV.46Z9FNs8zEC.4YiSEg7qMsj27sSYsL2', NULL, '2026-01-07 17:08:16', '2026-01-07 17:08:16'),
	(15, 'LEILA NERIS ALVES', 'leilaalves@pjc.mt.gov.br', NULL, '$2y$12$mTsUI3J3G4b.i0RvSHoYK.KUUIpxs7czp9XqAKh9a/.jOL8xoEjYi', NULL, '2026-01-07 17:10:00', '2026-01-07 17:10:00'),
	(16, 'MARIA DULCIMARIA DE SOUZA GOMES', 'mariasouza@pjc.mt.gov.br', NULL, '$2y$12$tC609UhRMJ1zhoCh5jiiiewYx2GDf5aZJ.N7nm0yrYv15T2T9HFru', NULL, '2026-01-07 17:11:46', '2026-01-07 17:11:46'),
	(17, 'MARLON VALADARES DA SILVA JÚNIOR', 'marlonjunior@pjc.mt.gov.br', NULL, '$2y$12$Sohz5uBXayxFnUE2w31BeeRnph5dhnLRHUAXddvRwuwAnfM1TEqau', NULL, '2026-01-07 17:13:45', '2026-01-07 17:13:45'),
	(18, 'MAXWELL PEREIRA XAVIER', 'maxwellxavier@pjc.mt.gov.br', NULL, '$2y$12$TyQ3YsiiYfVHKSVwyFYlre5lFSgcsl91ZSLT7.cIJLhSbgltp9.5.', NULL, '2026-01-07 17:14:14', '2026-01-07 17:14:14'),
	(19, 'ROGÉRIO DA SILVA IRLANDES', 'rogerioirlandes@pjc.mt.gov.br', NULL, '$2y$12$3pCXNzE.BtMmjtyRTHhWnuAO1QMtkRQ99QN77mzfJgH9I/WR8EH6W', NULL, '2026-01-07 17:14:45', '2026-01-07 17:14:45'),
	(20, 'ROSEMERI MARCIA MENEGAT', 'rosemerimenegat@pjc.mt.gov.br', NULL, '$2y$12$w9zzM05GqB6wktHBsjQP.uu.kwHyf8PN87ljcIZ56.PzdnFTSkHNu', NULL, '2026-01-07 17:15:18', '2026-01-07 17:15:18'),
	(21, 'SIMONNY MEDRADO DA SILVA', 'simonnysilva@pjc.mt.gov.br', NULL, '$2y$12$6OHtB1O8TVaFqDRhEFyQmOxZTf9BoySZ536TPn02LLfeZB5GJSZQO', NULL, '2026-01-07 17:15:49', '2026-01-07 17:15:49'),
	(22, 'STEFAN MORINIGO ALVES', 'stefanalves@pjc.mt.gov.br', NULL, '$2y$12$32NFguJRQzT5/rjjKg0OXezeKJhoJIeNLzjnfFR6POKuBXrKa5sMC', NULL, '2026-01-07 17:16:19', '2026-01-07 17:16:19'),
	(23, 'TIAGO BARALDI', 'tiagobaraldi@pjc.mt.gov.br', NULL, '$2y$12$nyetZy9QzGnlQQweKHylQ.hNUj9acoYbp1ByJDPAeEa.Xd.uyEWa6', NULL, '2026-01-07 17:16:48', '2026-01-07 17:16:48'),
	(24, 'ULYSSES CABRAL DE ARAUJO', 'ulyssesaraujo@pjc.mt.gov.br', NULL, '$2y$12$othj1H4F2ntG8KSc0N6RJuBiOL0010rVU8BVJ6AFTRDgV69dXE3YK', NULL, '2026-01-07 17:17:15', '2026-01-07 17:17:15'),
	(25, 'VICENTE GOMES DIAS JUNIOR', 'vicentedias@pjc.mt.gov.br', NULL, '$2y$12$XD/jokiKxAIM.uFnNexOBe7FwCsMKYJA04oPjV2rZ6h0VakzQJcP2', NULL, '2026-01-07 17:17:49', '2026-01-07 17:17:49'),
	(26, 'VICTOR MATHEUS LEAL RODRIGUES DE ALMEIDA', 'victoralmeida@pjc.mt.gov.br', NULL, '$2y$12$ALNw8Ey8/x/y9Ffmj6dHx.oUYkSvtFpp.Pm3LciNsXPMtmH61UrV6', NULL, '2026-01-07 17:18:20', '2026-01-07 17:18:20'),
	(27, 'WESLEY DE ARAUJO', 'wesleyaraujo@pjc.mt.gov.br', NULL, '$2y$12$zAbmAojUf5H6oyvEbeZgzu/nQhUgSTW./NI2niwABSwciaIJAwLr6', NULL, '2026-01-07 17:18:48', '2026-01-07 17:18:48'),
	(28, 'MARISE DA PAZ FERREIRA NETA', 'mariseneta@pjc.mt.gov.br', NULL, '$2y$12$3iiZxb7rkoJvE8y/CC2Xg.9Lai.u.NG5Ia.kWHgvt33bngjRbTZ0q', NULL, '2026-01-07 17:22:00', '2026-01-07 17:22:00');

-- Copiando estrutura para tabela agenda_epc.password_reset_tokens
DROP TABLE IF EXISTS `password_reset_tokens`;
CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Copiando dados para a tabela agenda_epc.password_reset_tokens: ~0 rows (aproximadamente)
DELETE FROM `password_reset_tokens`;

-- Copiando estrutura para tabela agenda_epc.sessions
DROP TABLE IF EXISTS `sessions`;
CREATE TABLE IF NOT EXISTS `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Copiando dados para a tabela agenda_epc.sessions: ~9 rows (aproximadamente)
DELETE FROM `sessions`;
INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
	('biJTnNoMTcKmYOZEDmOusA3t7unIKbLOfmSAaBoS', 5, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:140.0) Gecko/20100101 Firefox/140.0', 'YTo2OntzOjY6Il90b2tlbiI7czo0MDoieDVhS1U0TFg2dE53ZXJxT0hlc2o1dFVPVFphbjhOQmZLUUxaYnpEeSI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDY6Imh0dHA6Ly9hZ2VuZGFfZXBjLnRlc3QvYWRtaW4vYWdlbmRhLWNhbGVuZGFyaW8iO3M6NToicm91dGUiO3M6Mzg6ImZpbGFtZW50LmFkbWluLnBhZ2VzLmFnZW5kYS1jYWxlbmRhcmlvIjt9czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6NTtzOjE3OiJwYXNzd29yZF9oYXNoX3dlYiI7czo2MDoiJDJ5JDEyJFVXZGN5MzRpd2xTYWVoUEp2N1hjcWVSSGdjbS4wdEtrUU1iZVc4LkdzbE93aERYVzhVazBtIjtzOjE0OiJhZ2VuZGFfdXNlcl9pZCI7aTo4O30=', 1769172232);

-- Copiando estrutura para tabela agenda_epc.cache
DROP TABLE IF EXISTS `cache`;
CREATE TABLE IF NOT EXISTS `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Copiando dados para a tabela agenda_epc.cache: ~10 rows (aproximadamente)
DELETE FROM `cache`;
INSERT INTO `cache` (`key`, `value`, `expiration`) VALUES
	('cartoriuscfs-cache-livewire-rate-limiter:16d36dff9abd246c67dfac3e63b993a169af77e6', 'i:1;', 1769169605),
	('cartoriuscfs-cache-livewire-rate-limiter:16d36dff9abd246c67dfac3e63b993a169af77e6:timer', 'i:1769169605;', 1769169605),
	('cartoriuscfs-cache-livewire-rate-limiter:1ed15be8470def376e35dcb604d6dac6bbb638fd', 'i:1;', 1767872558),
	('cartoriuscfs-cache-livewire-rate-limiter:1ed15be8470def376e35dcb604d6dac6bbb638fd:timer', 'i:1767872558;', 1767872558),
	('cartoriuscfs-cache-livewire-rate-limiter:79a1888b97abc34632d0ade1b7811302211a4883', 'i:1;', 1767872343),
	('cartoriuscfs-cache-livewire-rate-limiter:79a1888b97abc34632d0ade1b7811302211a4883:timer', 'i:1767872343;', 1767872343),
	('cartoriuscfs-cache-spatie.permission.cache', 'a:3:{s:5:"alias";a:4:{s:1:"a";s:2:"id";s:1:"b";s:4:"name";s:1:"c";s:10:"guard_name";s:1:"r";s:5:"roles";}s:11:"permissions";a:57:{i:0;a:4:{s:1:"a";i:1;s:1:"b";s:16:"ViewAny:Bloqueio";s:1:"c";s:3:"web";s:1:"r";a:3:{i:0;i:1;i:1;i:4;i:2;i:7;}}i:1;a:4:{s:1:"a";i:2;s:1:"b";s:13:"View:Bloqueio";s:1:"c";s:3:"web";s:1:"r";a:3:{i:0;i:1;i:1;i:4;i:2;i:7;}}i:2;a:4:{s:1:"a";i:3;s:1:"b";s:15:"Create:Bloqueio";s:1:"c";s:3:"web";s:1:"r";a:3:{i:0;i:1;i:1;i:4;i:2;i:7;}}i:3;a:4:{s:1:"a";i:4;s:1:"b";s:15:"Update:Bloqueio";s:1:"c";s:3:"web";s:1:"r";a:3:{i:0;i:1;i:1;i:4;i:2;i:7;}}i:4;a:4:{s:1:"a";i:5;s:1:"b";s:15:"Delete:Bloqueio";s:1:"c";s:3:"web";s:1:"r";a:3:{i:0;i:1;i:1;i:4;i:2;i:7;}}i:5;a:4:{s:1:"a";i:6;s:1:"b";s:16:"Restore:Bloqueio";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:6;a:4:{s:1:"a";i:7;s:1:"b";s:20:"ForceDelete:Bloqueio";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:7;a:4:{s:1:"a";i:8;s:1:"b";s:23:"ForceDeleteAny:Bloqueio";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:8;a:4:{s:1:"a";i:9;s:1:"b";s:19:"RestoreAny:Bloqueio";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:9;a:4:{s:1:"a";i:10;s:1:"b";s:18:"Replicate:Bloqueio";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:10;a:4:{s:1:"a";i:11;s:1:"b";s:16:"Reorder:Bloqueio";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:11;a:4:{s:1:"a";i:12;s:1:"b";s:14:"ViewAny:Evento";s:1:"c";s:3:"web";s:1:"r";a:3:{i:0;i:1;i:1;i:4;i:2;i:7;}}i:12;a:4:{s:1:"a";i:13;s:1:"b";s:11:"View:Evento";s:1:"c";s:3:"web";s:1:"r";a:3:{i:0;i:1;i:1;i:4;i:2;i:7;}}i:13;a:4:{s:1:"a";i:14;s:1:"b";s:13:"Create:Evento";s:1:"c";s:3:"web";s:1:"r";a:3:{i:0;i:1;i:1;i:4;i:2;i:7;}}i:14;a:4:{s:1:"a";i:15;s:1:"b";s:13:"Update:Evento";s:1:"c";s:3:"web";s:1:"r";a:5:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:4;i:4;i:7;}}i:15;a:4:{s:1:"a";i:16;s:1:"b";s:13:"Delete:Evento";s:1:"c";s:3:"web";s:1:"r";a:5:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:4;i:4;i:7;}}i:16;a:4:{s:1:"a";i:17;s:1:"b";s:14:"Restore:Evento";s:1:"c";s:3:"web";s:1:"r";a:2:{i:0;i:1;i:1;i:4;}}i:17;a:4:{s:1:"a";i:18;s:1:"b";s:18:"ForceDelete:Evento";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:18;a:4:{s:1:"a";i:19;s:1:"b";s:21:"ForceDeleteAny:Evento";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:19;a:4:{s:1:"a";i:20;s:1:"b";s:17:"RestoreAny:Evento";s:1:"c";s:3:"web";s:1:"r";a:2:{i:0;i:1;i:1;i:4;}}i:20;a:4:{s:1:"a";i:21;s:1:"b";s:16:"Replicate:Evento";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:21;a:4:{s:1:"a";i:22;s:1:"b";s:14:"Reorder:Evento";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:22;a:4:{s:1:"a";i:23;s:1:"b";s:14:"ViewAny:Ferias";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:23;a:4:{s:1:"a";i:24;s:1:"b";s:11:"View:Ferias";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:24;a:4:{s:1:"a";i:25;s:1:"b";s:13:"Create:Ferias";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:25;a:4:{s:1:"a";i:26;s:1:"b";s:13:"Update:Ferias";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:26;a:4:{s:1:"a";i:27;s:1:"b";s:13:"Delete:Ferias";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:27;a:4:{s:1:"a";i:28;s:1:"b";s:14:"Restore:Ferias";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:28;a:4:{s:1:"a";i:29;s:1:"b";s:18:"ForceDelete:Ferias";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:29;a:4:{s:1:"a";i:30;s:1:"b";s:21:"ForceDeleteAny:Ferias";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:30;a:4:{s:1:"a";i:31;s:1:"b";s:17:"RestoreAny:Ferias";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:31;a:4:{s:1:"a";i:32;s:1:"b";s:16:"Replicate:Ferias";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:32;a:4:{s:1:"a";i:33;s:1:"b";s:14:"Reorder:Ferias";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:33;a:4:{s:1:"a";i:34;s:1:"b";s:12:"ViewAny:User";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:34;a:4:{s:1:"a";i:35;s:1:"b";s:9:"View:User";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:35;a:4:{s:1:"a";i:36;s:1:"b";s:11:"Create:User";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:36;a:4:{s:1:"a";i:37;s:1:"b";s:11:"Update:User";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:37;a:4:{s:1:"a";i:38;s:1:"b";s:11:"Delete:User";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:38;a:4:{s:1:"a";i:39;s:1:"b";s:12:"Restore:User";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:39;a:4:{s:1:"a";i:40;s:1:"b";s:16:"ForceDelete:User";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:40;a:4:{s:1:"a";i:41;s:1:"b";s:19:"ForceDeleteAny:User";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:41;a:4:{s:1:"a";i:42;s:1:"b";s:15:"RestoreAny:User";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:42;a:4:{s:1:"a";i:43;s:1:"b";s:14:"Replicate:User";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:43;a:4:{s:1:"a";i:44;s:1:"b";s:12:"Reorder:User";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:44;a:4:{s:1:"a";i:45;s:1:"b";s:12:"ViewAny:Role";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:45;a:4:{s:1:"a";i:46;s:1:"b";s:9:"View:Role";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:46;a:4:{s:1:"a";i:47;s:1:"b";s:11:"Create:Role";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:47;a:4:{s:1:"a";i:48;s:1:"b";s:11:"Update:Role";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:48;a:4:{s:1:"a";i:49;s:1:"b";s:11:"Delete:Role";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:49;a:4:{s:1:"a";i:50;s:1:"b";s:12:"Restore:Role";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:50;a:4:{s:1:"a";i:51;s:1:"b";s:16:"ForceDelete:Role";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:51;a:4:{s:1:"a";i:52;s:1:"b";s:19:"ForceDeleteAny:Role";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:52;a:4:{s:1:"a";i:53;s:1:"b";s:15:"RestoreAny:Role";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:53;a:4:{s:1:"a";i:54;s:1:"b";s:14:"Replicate:Role";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:54;a:4:{s:1:"a";i:55;s:1:"b";s:12:"Reorder:Role";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:55;a:4:{s:1:"a";i:56;s:1:"b";s:21:"View:AgendaCalendario";s:1:"c";s:3:"web";s:1:"r";a:2:{i:0;i:1;i:1;i:4;}}i:56;a:4:{s:1:"a";i:57;s:1:"b";s:14:"View:Dashboard";s:1:"c";s:3:"web";s:1:"r";a:2:{i:0;i:1;i:1;i:4;}}}s:5:"roles";a:5:{i:0;a:3:{s:1:"a";i:1;s:1:"b";s:11:"super_admin";s:1:"c";s:3:"web";}i:1;a:3:{s:1:"a";i:4;s:1:"b";s:16:"cartorio_central";s:1:"c";s:3:"web";}i:2;a:3:{s:1:"a";i:7;s:1:"b";s:3:"dpc";s:1:"c";s:3:"web";}i:3;a:3:{s:1:"a";i:2;s:1:"b";s:3:"epc";s:1:"c";s:3:"web";}i:4;a:3:{s:1:"a";i:3;s:1:"b";s:3:"ipc";s:1:"c";s:3:"web";}}}', 1769255478),
	('laravel-cache-livewire-rate-limiter:16d36dff9abd246c67dfac3e63b993a169af77e6', 'i:2;', 1767796045),
	('laravel-cache-livewire-rate-limiter:16d36dff9abd246c67dfac3e63b993a169af77e6:timer', 'i:1767796045;', 1767796045),
	('laravel-cache-spatie.permission.cache', 'a:3:{s:5:"alias";a:4:{s:1:"a";s:2:"id";s:1:"b";s:4:"name";s:1:"c";s:10:"guard_name";s:1:"r";s:5:"roles";}s:11:"permissions";a:57:{i:0;a:4:{s:1:"a";i:1;s:1:"b";s:16:"ViewAny:Bloqueio";s:1:"c";s:3:"web";s:1:"r";a:3:{i:0;i:1;i:1;i:4;i:2;i:7;}}i:1;a:4:{s:1:"a";i:2;s:1:"b";s:13:"View:Bloqueio";s:1:"c";s:3:"web";s:1:"r";a:3:{i:0;i:1;i:1;i:4;i:2;i:7;}}i:2;a:4:{s:1:"a";i:3;s:1:"b";s:15:"Create:Bloqueio";s:1:"c";s:3:"web";s:1:"r";a:3:{i:0;i:1;i:1;i:4;i:2;i:7;}}i:3;a:4:{s:1:"a";i:4;s:1:"b";s:15:"Update:Bloqueio";s:1:"c";s:3:"web";s:1:"r";a:3:{i:0;i:1;i:1;i:4;i:2;i:7;}}i:4;a:4:{s:1:"a";i:5;s:1:"b";s:15:"Delete:Bloqueio";s:1:"c";s:3:"web";s:1:"r";a:3:{i:0;i:1;i:1;i:4;i:2;i:7;}}i:5;a:4:{s:1:"a";i:6;s:1:"b";s:16:"Restore:Bloqueio";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:6;a:4:{s:1:"a";i:7;s:1:"b";s:20:"ForceDelete:Bloqueio";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:7;a:4:{s:1:"a";i:8;s:1:"b";s:23:"ForceDeleteAny:Bloqueio";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:8;a:4:{s:1:"a";i:9;s:1:"b";s:19:"RestoreAny:Bloqueio";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:9;a:4:{s:1:"a";i:10;s:1:"b";s:18:"Replicate:Bloqueio";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:10;a:4:{s:1:"a";i:11;s:1:"b";s:16:"Reorder:Bloqueio";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:11;a:4:{s:1:"a";i:12;s:1:"b";s:14:"ViewAny:Evento";s:1:"c";s:3:"web";s:1:"r";a:3:{i:0;i:1;i:1;i:4;i:2;i:7;}}i:12;a:4:{s:1:"a";i:13;s:1:"b";s:11:"View:Evento";s:1:"c";s:3:"web";s:1:"r";a:3:{i:0;i:1;i:1;i:4;i:2;i:7;}}i:13;a:4:{s:1:"a";i:14;s:1:"b";s:13:"Create:Evento";s:1:"c";s:3:"web";s:1:"r";a:3:{i:0;i:1;i:1;i:4;i:2;i:7;}}i:14;a:4:{s:1:"a";i:15;s:1:"b";s:13:"Update:Evento";s:1:"c";s:3:"web";s:1:"r";a:5:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:4;i:4;i:7;}}i:15;a:4:{s:1:"a";i:16;s:1:"b";s:13:"Delete:Evento";s:1:"c";s:3:"web";s:1:"r";a:5:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:4;i:4;i:7;}}i:16;a:4:{s:1:"a";i:17;s:1:"b";s:14:"Restore:Evento";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:17;a:4:{s:1:"a";i:18;s:1:"b";s:18:"ForceDelete:Evento";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:18;a:4:{s:1:"a";i:19;s:1:"b";s:21:"ForceDeleteAny:Evento";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:19;a:4:{s:1:"a";i:20;s:1:"b";s:17:"RestoreAny:Evento";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:20;a:4:{s:1:"a";i:21;s:1:"b";s:16:"Replicate:Evento";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:21;a:4:{s:1:"a";i:22;s:1:"b";s:14:"Reorder:Evento";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:22;a:4:{s:1:"a";i:23;s:1:"b";s:14:"ViewAny:Ferias";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:23;a:4:{s:1:"a";i:24;s:1:"b";s:11:"View:Ferias";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:24;a:4:{s:1:"a";i:25;s:1:"b";s:13:"Create:Ferias";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:25;a:4:{s:1:"a";i:26;s:1:"b";s:13:"Update:Ferias";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:26;a:4:{s:1:"a";i:27;s:1:"b";s:13:"Delete:Ferias";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:27;a:4:{s:1:"a";i:28;s:1:"b";s:14:"Restore:Ferias";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:28;a:4:{s:1:"a";i:29;s:1:"b";s:18:"ForceDelete:Ferias";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:29;a:4:{s:1:"a";i:30;s:1:"b";s:21:"ForceDeleteAny:Ferias";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:30;a:4:{s:1:"a";i:31;s:1:"b";s:17:"RestoreAny:Ferias";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:31;a:4:{s:1:"a";i:32;s:1:"b";s:16:"Replicate:Ferias";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:32;a:4:{s:1:"a";i:33;s:1:"b";s:14:"Reorder:Ferias";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:33;a:4:{s:1:"a";i:34;s:1:"b";s:12:"ViewAny:User";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:34;a:4:{s:1:"a";i:35;s:1:"b";s:9:"View:User";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:35;a:4:{s:1:"a";i:36;s:1:"b";s:11:"Create:User";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:36;a:4:{s:1:"a";i:37;s:1:"b";s:11:"Update:User";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:37;a:4:{s:1:"a";i:38;s:1:"b";s:11:"Delete:User";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:38;a:4:{s:1:"a";i:39;s:1:"b";s:12:"Restore:User";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:39;a:4:{s:1:"a";i:40;s:1:"b";s:16:"ForceDelete:User";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:40;a:4:{s:1:"a";i:41;s:1:"b";s:19:"ForceDeleteAny:User";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:41;a:4:{s:1:"a";i:42;s:1:"b";s:15:"RestoreAny:User";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:42;a:4:{s:1:"a";i:43;s:1:"b";s:14:"Replicate:User";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:43;a:4:{s:1:"a";i:44;s:1:"b";s:12:"Reorder:User";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:44;a:4:{s:1:"a";i:45;s:1:"b";s:12:"ViewAny:Role";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:45;a:4:{s:1:"a";i:46;s:1:"b";s:9:"View:Role";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:46;a:4:{s:1:"a";i:47;s:1:"b";s:11:"Create:Role";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:47;a:4:{s:1:"a";i:48;s:1:"b";s:11:"Update:Role";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:48;a:4:{s:1:"a";i:49;s:1:"b";s:11:"Delete:Role";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:49;a:4:{s:1:"a";i:50;s:1:"b";s:12:"Restore:Role";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:50;a:4:{s:1:"a";i:51;s:1:"b";s:16:"ForceDelete:Role";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:51;a:4:{s:1:"a";i:52;s:1:"b";s:19:"ForceDeleteAny:Role";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:52;a:4:{s:1:"a";i:53;s:1:"b";s:15:"RestoreAny:Role";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:53;a:4:{s:1:"a";i:54;s:1:"b";s:14:"Replicate:Role";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:54;a:4:{s:1:"a";i:55;s:1:"b";s:12:"Reorder:Role";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:55;a:4:{s:1:"a";i:56;s:1:"b";s:21:"View:AgendaCalendario";s:1:"c";s:3:"web";s:1:"r";a:2:{i:0;i:1;i:1;i:4;}}i:56;a:4:{s:1:"a";i:57;s:1:"b";s:14:"View:Dashboard";s:1:"c";s:3:"web";s:1:"r";a:2:{i:0;i:1;i:1;i:4;}}}s:5:"roles";a:5:{i:0;a:3:{s:1:"a";i:1;s:1:"b";s:11:"super_admin";s:1:"c";s:3:"web";}i:1;a:3:{s:1:"a";i:4;s:1:"b";s:16:"cartorio_central";s:1:"c";s:3:"web";}i:2;a:3:{s:1:"a";i:7;s:1:"b";s:3:"dpc";s:1:"c";s:3:"web";}i:3;a:3:{s:1:"a";i:2;s:1:"b";s:3:"epc";s:1:"c";s:3:"web";}i:4;a:3:{s:1:"a";i:3;s:1:"b";s:3:"ipc";s:1:"c";s:3:"web";}}}', 1767882351);

-- Copiando estrutura para tabela agenda_epc.cache_locks
DROP TABLE IF EXISTS `cache_locks`;
CREATE TABLE IF NOT EXISTS `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Copiando dados para a tabela agenda_epc.cache_locks: ~0 rows (aproximadamente)
DELETE FROM `cache_locks`;

-- Copiando estrutura para tabela agenda_epc.failed_jobs
DROP TABLE IF EXISTS `failed_jobs`;
CREATE TABLE IF NOT EXISTS `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Copiando dados para a tabela agenda_epc.failed_jobs: ~0 rows (aproximadamente)
DELETE FROM `failed_jobs`;

-- Copiando estrutura para tabela agenda_epc.job_batches
DROP TABLE IF EXISTS `job_batches`;
CREATE TABLE IF NOT EXISTS `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Copiando dados para a tabela agenda_epc.job_batches: ~0 rows (aproximadamente)
DELETE FROM `job_batches`;

-- Copiando estrutura para tabela agenda_epc.jobs
DROP TABLE IF EXISTS `jobs`;
CREATE TABLE IF NOT EXISTS `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Copiando dados para a tabela agenda_epc.jobs: ~19 rows (aproximadamente)
DELETE FROM `jobs`;
INSERT INTO `jobs` (`id`, `queue`, `payload`, `attempts`, `reserved_at`, `available_at`, `created_at`) VALUES
	(1, 'default', '{"uuid":"b01cd70c-7764-46c6-966f-ff94264555b1","displayName":"Filament\\\\Notifications\\\\DatabaseNotification","job":"Illuminate\\\\Queue\\\\CallQueuedHandler@call","maxTries":null,"maxExceptions":null,"failOnTimeout":false,"backoff":null,"timeout":null,"retryUntil":null,"data":{"commandName":"Illuminate\\\\Notifications\\\\SendQueuedNotifications","command":"O:48:\\"Illuminate\\\\Notifications\\\\SendQueuedNotifications\\":3:{s:11:\\"notifiables\\";O:45:\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\":5:{s:5:\\"class\\";s:15:\\"App\\\\Models\\\\User\\";s:2:\\"id\\";a:1:{i:0;i:2;}s:9:\\"relations\\";a:0:{}s:10:\\"connection\\";s:5:\\"mysql\\";s:15:\\"collectionClass\\";N;}s:12:\\"notification\\";O:43:\\"Filament\\\\Notifications\\\\DatabaseNotification\\":2:{s:4:\\"data\\";a:11:{s:7:\\"actions\\";a:0:{}s:4:\\"body\\";s:75:\\"Por: admin\\nData\\/Hora: 07\\/01\\/2026 08:00\\nIntimado: teste\\nProcedimento: 234234\\";s:5:\\"color\\";N;s:8:\\"duration\\";s:10:\\"persistent\\";s:4:\\"icon\\";N;s:9:\\"iconColor\\";N;s:6:\\"status\\";N;s:5:\\"title\\";s:18:\\"Agendamento criado\\";s:4:\\"view\\";N;s:8:\\"viewData\\";a:0:{}s:6:\\"format\\";s:8:\\"filament\\";}s:2:\\"id\\";s:36:\\"314797d5-a5a0-4301-9982-805d606becfe\\";}s:8:\\"channels\\";a:1:{i:0;s:8:\\"database\\";}}"},"createdAt":1767722255,"delay":null}', 0, NULL, 1767722255, 1767722255),
	(2, 'default', '{"uuid":"97a6591c-88ff-460b-affa-e64349cd6052","displayName":"Filament\\\\Notifications\\\\DatabaseNotification","job":"Illuminate\\\\Queue\\\\CallQueuedHandler@call","maxTries":null,"maxExceptions":null,"failOnTimeout":false,"backoff":null,"timeout":null,"retryUntil":null,"data":{"commandName":"Illuminate\\\\Notifications\\\\SendQueuedNotifications","command":"O:48:\\"Illuminate\\\\Notifications\\\\SendQueuedNotifications\\":3:{s:11:\\"notifiables\\";O:45:\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\":5:{s:5:\\"class\\";s:15:\\"App\\\\Models\\\\User\\";s:2:\\"id\\";a:1:{i:0;i:2;}s:9:\\"relations\\";a:0:{}s:10:\\"connection\\";s:5:\\"mysql\\";s:15:\\"collectionClass\\";N;}s:12:\\"notification\\";O:43:\\"Filament\\\\Notifications\\\\DatabaseNotification\\":2:{s:4:\\"data\\";a:11:{s:7:\\"actions\\";a:0:{}s:4:\\"body\\";s:74:\\"Por: admin\\nData\\/Hora: 06\\/01\\/2026 08:00\\nIntimado: mayso\\nProcedimento: 66666\\";s:5:\\"color\\";N;s:8:\\"duration\\";s:10:\\"persistent\\";s:4:\\"icon\\";N;s:9:\\"iconColor\\";N;s:6:\\"status\\";N;s:5:\\"title\\";s:18:\\"Agendamento criado\\";s:4:\\"view\\";N;s:8:\\"viewData\\";a:0:{}s:6:\\"format\\";s:8:\\"filament\\";}s:2:\\"id\\";s:36:\\"90507132-8862-4b4e-9017-56bb223213f5\\";}s:8:\\"channels\\";a:1:{i:0;s:8:\\"database\\";}}"},"createdAt":1767722587,"delay":null}', 0, NULL, 1767722587, 1767722587),
	(3, 'default', '{"uuid":"93cb7471-5406-46b2-921b-799515d0e3e1","displayName":"Filament\\\\Notifications\\\\DatabaseNotification","job":"Illuminate\\\\Queue\\\\CallQueuedHandler@call","maxTries":null,"maxExceptions":null,"failOnTimeout":false,"backoff":null,"timeout":null,"retryUntil":null,"data":{"commandName":"Illuminate\\\\Notifications\\\\SendQueuedNotifications","command":"O:48:\\"Illuminate\\\\Notifications\\\\SendQueuedNotifications\\":3:{s:11:\\"notifiables\\";O:45:\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\":5:{s:5:\\"class\\";s:15:\\"App\\\\Models\\\\User\\";s:2:\\"id\\";a:1:{i:0;i:2;}s:9:\\"relations\\";a:0:{}s:10:\\"connection\\";s:5:\\"mysql\\";s:15:\\"collectionClass\\";N;}s:12:\\"notification\\";O:43:\\"Filament\\\\Notifications\\\\DatabaseNotification\\":2:{s:4:\\"data\\";a:11:{s:7:\\"actions\\";a:0:{}s:4:\\"body\\";s:74:\\"Por: admin\\nData\\/Hora: 07\\/01\\/2026 09:00\\nIntimado: mayso\\nProcedimento: 66666\\";s:5:\\"color\\";N;s:8:\\"duration\\";s:10:\\"persistent\\";s:4:\\"icon\\";N;s:9:\\"iconColor\\";N;s:6:\\"status\\";N;s:5:\\"title\\";s:22:\\"Agendamento atualizado\\";s:4:\\"view\\";N;s:8:\\"viewData\\";a:0:{}s:6:\\"format\\";s:8:\\"filament\\";}s:2:\\"id\\";s:36:\\"b73b0e88-a7c1-4b69-885a-c7d8a2a26b4e\\";}s:8:\\"channels\\";a:1:{i:0;s:8:\\"database\\";}}"},"createdAt":1767722591,"delay":null}', 0, NULL, 1767722591, 1767722591),
	(4, 'default', '{"uuid":"4ea67217-b79a-41b9-ad6e-cce14347a97d","displayName":"Filament\\\\Notifications\\\\DatabaseNotification","job":"Illuminate\\\\Queue\\\\CallQueuedHandler@call","maxTries":null,"maxExceptions":null,"failOnTimeout":false,"backoff":null,"timeout":null,"retryUntil":null,"data":{"commandName":"Illuminate\\\\Notifications\\\\SendQueuedNotifications","command":"O:48:\\"Illuminate\\\\Notifications\\\\SendQueuedNotifications\\":3:{s:11:\\"notifiables\\";O:45:\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\":5:{s:5:\\"class\\";s:15:\\"App\\\\Models\\\\User\\";s:2:\\"id\\";a:1:{i:0;i:2;}s:9:\\"relations\\";a:0:{}s:10:\\"connection\\";s:5:\\"mysql\\";s:15:\\"collectionClass\\";N;}s:12:\\"notification\\";O:43:\\"Filament\\\\Notifications\\\\DatabaseNotification\\":2:{s:4:\\"data\\";a:11:{s:7:\\"actions\\";a:0:{}s:4:\\"body\\";s:73:\\"Por: ipc\\nData\\/Hora: 06\\/01\\/2026 08:00\\nIntimado: testead\\nProcedimento: 0000\\";s:5:\\"color\\";N;s:8:\\"duration\\";s:10:\\"persistent\\";s:4:\\"icon\\";N;s:9:\\"iconColor\\";N;s:6:\\"status\\";N;s:5:\\"title\\";s:18:\\"Agendamento criado\\";s:4:\\"view\\";N;s:8:\\"viewData\\";a:0:{}s:6:\\"format\\";s:8:\\"filament\\";}s:2:\\"id\\";s:36:\\"42cd32d5-090e-447f-b5f0-495348ecad71\\";}s:8:\\"channels\\";a:1:{i:0;s:8:\\"database\\";}}"},"createdAt":1767722628,"delay":null}', 0, NULL, 1767722628, 1767722628),
	(5, 'default', '{"uuid":"8a3ddcf3-deec-4be2-b3f2-f939a2b7f8d7","displayName":"Filament\\\\Notifications\\\\DatabaseNotification","job":"Illuminate\\\\Queue\\\\CallQueuedHandler@call","maxTries":null,"maxExceptions":null,"failOnTimeout":false,"backoff":null,"timeout":null,"retryUntil":null,"data":{"commandName":"Illuminate\\\\Notifications\\\\SendQueuedNotifications","command":"O:48:\\"Illuminate\\\\Notifications\\\\SendQueuedNotifications\\":3:{s:11:\\"notifiables\\";O:45:\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\":5:{s:5:\\"class\\";s:15:\\"App\\\\Models\\\\User\\";s:2:\\"id\\";a:1:{i:0;i:2;}s:9:\\"relations\\";a:0:{}s:10:\\"connection\\";s:5:\\"mysql\\";s:15:\\"collectionClass\\";N;}s:12:\\"notification\\";O:43:\\"Filament\\\\Notifications\\\\DatabaseNotification\\":2:{s:4:\\"data\\";a:11:{s:7:\\"actions\\";a:0:{}s:4:\\"body\\";s:75:\\"Por: admin\\nData\\/Hora: 07\\/01\\/2026 10:00\\nIntimado: outro\\nProcedimento: 444333\\";s:5:\\"color\\";N;s:8:\\"duration\\";s:10:\\"persistent\\";s:4:\\"icon\\";N;s:9:\\"iconColor\\";N;s:6:\\"status\\";N;s:5:\\"title\\";s:18:\\"Agendamento criado\\";s:4:\\"view\\";N;s:8:\\"viewData\\";a:0:{}s:6:\\"format\\";s:8:\\"filament\\";}s:2:\\"id\\";s:36:\\"1a2512cf-392b-4996-a506-b9372fe28679\\";}s:8:\\"channels\\";a:1:{i:0;s:8:\\"database\\";}}"},"createdAt":1767790968,"delay":null}', 0, NULL, 1767790968, 1767790968),
	(6, 'default', '{"uuid":"c3231b52-300a-4f57-a44c-24cb40599395","displayName":"Filament\\\\Notifications\\\\DatabaseNotification","job":"Illuminate\\\\Queue\\\\CallQueuedHandler@call","maxTries":null,"maxExceptions":null,"failOnTimeout":false,"backoff":null,"timeout":null,"retryUntil":null,"data":{"commandName":"Illuminate\\\\Notifications\\\\SendQueuedNotifications","command":"O:48:\\"Illuminate\\\\Notifications\\\\SendQueuedNotifications\\":3:{s:11:\\"notifiables\\";O:45:\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\":5:{s:5:\\"class\\";s:15:\\"App\\\\Models\\\\User\\";s:2:\\"id\\";a:1:{i:0;i:2;}s:9:\\"relations\\";a:0:{}s:10:\\"connection\\";s:5:\\"mysql\\";s:15:\\"collectionClass\\";N;}s:12:\\"notification\\";O:43:\\"Filament\\\\Notifications\\\\DatabaseNotification\\":2:{s:4:\\"data\\";a:11:{s:7:\\"actions\\";a:0:{}s:4:\\"body\\";s:75:\\"Por: admin\\nData\\/Hora: 07\\/01\\/2026 11:00\\nIntimado: testead\\nProcedimento: 0000\\";s:5:\\"color\\";N;s:8:\\"duration\\";s:10:\\"persistent\\";s:4:\\"icon\\";N;s:9:\\"iconColor\\";N;s:6:\\"status\\";N;s:5:\\"title\\";s:22:\\"Agendamento atualizado\\";s:4:\\"view\\";N;s:8:\\"viewData\\";a:0:{}s:6:\\"format\\";s:8:\\"filament\\";}s:2:\\"id\\";s:36:\\"af37b4c7-14cd-4a5c-82c8-3be79685e927\\";}s:8:\\"channels\\";a:1:{i:0;s:8:\\"database\\";}}"},"createdAt":1767790976,"delay":null}', 0, NULL, 1767790976, 1767790976),
	(7, 'default', '{"uuid":"e6da5e41-be9b-417f-852f-f5eb53a8dcbb","displayName":"Filament\\\\Notifications\\\\DatabaseNotification","job":"Illuminate\\\\Queue\\\\CallQueuedHandler@call","maxTries":null,"maxExceptions":null,"failOnTimeout":false,"backoff":null,"timeout":null,"retryUntil":null,"data":{"commandName":"Illuminate\\\\Notifications\\\\SendQueuedNotifications","command":"O:48:\\"Illuminate\\\\Notifications\\\\SendQueuedNotifications\\":3:{s:11:\\"notifiables\\";O:45:\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\":5:{s:5:\\"class\\";s:15:\\"App\\\\Models\\\\User\\";s:2:\\"id\\";a:1:{i:0;i:2;}s:9:\\"relations\\";a:0:{}s:10:\\"connection\\";s:5:\\"mysql\\";s:15:\\"collectionClass\\";N;}s:12:\\"notification\\";O:43:\\"Filament\\\\Notifications\\\\DatabaseNotification\\":2:{s:4:\\"data\\";a:11:{s:7:\\"actions\\";a:0:{}s:4:\\"body\\";s:73:\\"Por: admin\\nData\\/Hora: 06\\/01\\/2026 08:00\\nIntimado: maisum\\nProcedimento: 345\\";s:5:\\"color\\";N;s:8:\\"duration\\";s:10:\\"persistent\\";s:4:\\"icon\\";N;s:9:\\"iconColor\\";N;s:6:\\"status\\";N;s:5:\\"title\\";s:18:\\"Agendamento criado\\";s:4:\\"view\\";N;s:8:\\"viewData\\";a:0:{}s:6:\\"format\\";s:8:\\"filament\\";}s:2:\\"id\\";s:36:\\"5a909b52-394b-49d9-b20d-df8c3409395c\\";}s:8:\\"channels\\";a:1:{i:0;s:8:\\"database\\";}}"},"createdAt":1767790984,"delay":null}', 0, NULL, 1767790984, 1767790984),
	(8, 'default', '{"uuid":"d3ab6b96-8026-460f-b0e8-348fb77944a0","displayName":"Filament\\\\Notifications\\\\DatabaseNotification","job":"Illuminate\\\\Queue\\\\CallQueuedHandler@call","maxTries":null,"maxExceptions":null,"failOnTimeout":false,"backoff":null,"timeout":null,"retryUntil":null,"data":{"commandName":"Illuminate\\\\Notifications\\\\SendQueuedNotifications","command":"O:48:\\"Illuminate\\\\Notifications\\\\SendQueuedNotifications\\":3:{s:11:\\"notifiables\\";O:45:\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\":5:{s:5:\\"class\\";s:15:\\"App\\\\Models\\\\User\\";s:2:\\"id\\";a:1:{i:0;i:2;}s:9:\\"relations\\";a:0:{}s:10:\\"connection\\";s:5:\\"mysql\\";s:15:\\"collectionClass\\";N;}s:12:\\"notification\\";O:43:\\"Filament\\\\Notifications\\\\DatabaseNotification\\":2:{s:4:\\"data\\";a:11:{s:7:\\"actions\\";a:0:{}s:4:\\"body\\";s:73:\\"Por: ipc\\nData\\/Hora: 06\\/01\\/2026 09:00\\nIntimado: useripc\\nProcedimento: 9888\\";s:5:\\"color\\";N;s:8:\\"duration\\";s:10:\\"persistent\\";s:4:\\"icon\\";N;s:9:\\"iconColor\\";N;s:6:\\"status\\";N;s:5:\\"title\\";s:18:\\"Agendamento criado\\";s:4:\\"view\\";N;s:8:\\"viewData\\";a:0:{}s:6:\\"format\\";s:8:\\"filament\\";}s:2:\\"id\\";s:36:\\"54e4d4c2-108f-49b5-b12a-b2b5ebad00f9\\";}s:8:\\"channels\\";a:1:{i:0;s:8:\\"database\\";}}"},"createdAt":1767791050,"delay":null}', 0, NULL, 1767791050, 1767791050),
	(9, 'default', '{"uuid":"30305fb9-d84a-49cf-88d7-8aa871c61fdb","displayName":"Filament\\\\Notifications\\\\DatabaseNotification","job":"Illuminate\\\\Queue\\\\CallQueuedHandler@call","maxTries":null,"maxExceptions":null,"failOnTimeout":false,"backoff":null,"timeout":null,"retryUntil":null,"data":{"commandName":"Illuminate\\\\Notifications\\\\SendQueuedNotifications","command":"O:48:\\"Illuminate\\\\Notifications\\\\SendQueuedNotifications\\":3:{s:11:\\"notifiables\\";O:45:\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\":5:{s:5:\\"class\\";s:15:\\"App\\\\Models\\\\User\\";s:2:\\"id\\";a:1:{i:0;i:2;}s:9:\\"relations\\";a:0:{}s:10:\\"connection\\";s:5:\\"mysql\\";s:15:\\"collectionClass\\";N;}s:12:\\"notification\\";O:43:\\"Filament\\\\Notifications\\\\DatabaseNotification\\":2:{s:4:\\"data\\";a:11:{s:7:\\"actions\\";a:0:{}s:4:\\"body\\";s:75:\\"Por: admin\\nData\\/Hora: 07\\/01\\/2026 14:00\\nIntimado: useripc\\nProcedimento: 9888\\";s:5:\\"color\\";N;s:8:\\"duration\\";s:10:\\"persistent\\";s:4:\\"icon\\";N;s:9:\\"iconColor\\";N;s:6:\\"status\\";N;s:5:\\"title\\";s:22:\\"Agendamento atualizado\\";s:4:\\"view\\";N;s:8:\\"viewData\\";a:0:{}s:6:\\"format\\";s:8:\\"filament\\";}s:2:\\"id\\";s:36:\\"be136f73-28ba-4856-a384-31fd68e5d925\\";}s:8:\\"channels\\";a:1:{i:0;s:8:\\"database\\";}}"},"createdAt":1767791064,"delay":null}', 0, NULL, 1767791064, 1767791064),
	(10, 'default', '{"uuid":"0041177b-5567-477e-b29d-cbdf0382fd91","displayName":"Filament\\\\Notifications\\\\DatabaseNotification","job":"Illuminate\\\\Queue\\\\CallQueuedHandler@call","maxTries":null,"maxExceptions":null,"failOnTimeout":false,"backoff":null,"timeout":null,"retryUntil":null,"data":{"commandName":"Illuminate\\\\Notifications\\\\SendQueuedNotifications","command":"O:48:\\"Illuminate\\\\Notifications\\\\SendQueuedNotifications\\":3:{s:11:\\"notifiables\\";O:45:\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\":5:{s:5:\\"class\\";s:15:\\"App\\\\Models\\\\User\\";s:2:\\"id\\";a:1:{i:0;i:2;}s:9:\\"relations\\";a:0:{}s:10:\\"connection\\";s:5:\\"mysql\\";s:15:\\"collectionClass\\";N;}s:12:\\"notification\\";O:43:\\"Filament\\\\Notifications\\\\DatabaseNotification\\":2:{s:4:\\"data\\";a:11:{s:7:\\"actions\\";a:0:{}s:4:\\"body\\";s:73:\\"Por: admin\\nData\\/Hora: 07\\/01\\/2026 15:00\\nIntimado: maisum\\nProcedimento: 345\\";s:5:\\"color\\";N;s:8:\\"duration\\";s:10:\\"persistent\\";s:4:\\"icon\\";N;s:9:\\"iconColor\\";N;s:6:\\"status\\";N;s:5:\\"title\\";s:22:\\"Agendamento atualizado\\";s:4:\\"view\\";N;s:8:\\"viewData\\";a:0:{}s:6:\\"format\\";s:8:\\"filament\\";}s:2:\\"id\\";s:36:\\"603fc4c0-dbd5-4aa3-99a7-4a22478c46c3\\";}s:8:\\"channels\\";a:1:{i:0;s:8:\\"database\\";}}"},"createdAt":1767791067,"delay":null}', 0, NULL, 1767791067, 1767791067),
	(11, 'default', '{"uuid":"e04fb1b4-0da9-4531-90e7-0a30670a4e4e","displayName":"Filament\\\\Notifications\\\\DatabaseNotification","job":"Illuminate\\\\Queue\\\\CallQueuedHandler@call","maxTries":null,"maxExceptions":null,"failOnTimeout":false,"backoff":null,"timeout":null,"retryUntil":null,"data":{"commandName":"Illuminate\\\\Notifications\\\\SendQueuedNotifications","command":"O:48:\\"Illuminate\\\\Notifications\\\\SendQueuedNotifications\\":3:{s:11:\\"notifiables\\";O:45:\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\":5:{s:5:\\"class\\";s:15:\\"App\\\\Models\\\\User\\";s:2:\\"id\\";a:1:{i:0;i:2;}s:9:\\"relations\\";a:0:{}s:10:\\"connection\\";s:5:\\"mysql\\";s:15:\\"collectionClass\\";N;}s:12:\\"notification\\";O:43:\\"Filament\\\\Notifications\\\\DatabaseNotification\\":2:{s:4:\\"data\\";a:11:{s:7:\\"actions\\";a:0:{}s:4:\\"body\\";s:66:\\"Por: ipc\\nData\\/Hora: 06\\/01\\/2026 08:00\\nIntimado: eu\\nProcedimento: 43\\";s:5:\\"color\\";N;s:8:\\"duration\\";s:10:\\"persistent\\";s:4:\\"icon\\";N;s:9:\\"iconColor\\";N;s:6:\\"status\\";N;s:5:\\"title\\";s:18:\\"Agendamento criado\\";s:4:\\"view\\";N;s:8:\\"viewData\\";a:0:{}s:6:\\"format\\";s:8:\\"filament\\";}s:2:\\"id\\";s:36:\\"3c210237-f9da-4e41-af51-fe6c403c878b\\";}s:8:\\"channels\\";a:1:{i:0;s:8:\\"database\\";}}"},"createdAt":1767791084,"delay":null}', 0, NULL, 1767791084, 1767791084),
	(12, 'default', '{"uuid":"48d30c66-1445-4910-b225-832d099ef155","displayName":"Filament\\\\Notifications\\\\DatabaseNotification","job":"Illuminate\\\\Queue\\\\CallQueuedHandler@call","maxTries":null,"maxExceptions":null,"failOnTimeout":false,"backoff":null,"timeout":null,"retryUntil":null,"data":{"commandName":"Illuminate\\\\Notifications\\\\SendQueuedNotifications","command":"O:48:\\"Illuminate\\\\Notifications\\\\SendQueuedNotifications\\":3:{s:11:\\"notifiables\\";O:45:\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\":5:{s:5:\\"class\\";s:15:\\"App\\\\Models\\\\User\\";s:2:\\"id\\";a:1:{i:0;i:2;}s:9:\\"relations\\";a:0:{}s:10:\\"connection\\";s:5:\\"mysql\\";s:15:\\"collectionClass\\";N;}s:12:\\"notification\\";O:43:\\"Filament\\\\Notifications\\\\DatabaseNotification\\":2:{s:4:\\"data\\";a:11:{s:7:\\"actions\\";a:0:{}s:4:\\"body\\";s:71:\\"Por: admin\\nData\\/Hora: 07\\/01\\/2026 16:00\\nIntimado: joao\\nProcedimento: 556\\";s:5:\\"color\\";N;s:8:\\"duration\\";s:10:\\"persistent\\";s:4:\\"icon\\";N;s:9:\\"iconColor\\";N;s:6:\\"status\\";N;s:5:\\"title\\";s:18:\\"Agendamento criado\\";s:4:\\"view\\";N;s:8:\\"viewData\\";a:0:{}s:6:\\"format\\";s:8:\\"filament\\";}s:2:\\"id\\";s:36:\\"07ce87a5-8308-4cd0-9b85-732f2583d833\\";}s:8:\\"channels\\";a:1:{i:0;s:8:\\"database\\";}}"},"createdAt":1767791274,"delay":null}', 0, NULL, 1767791274, 1767791274),
	(13, 'default', '{"uuid":"9ecbbdc8-dd0b-4392-bd78-3fb6d469d28a","displayName":"Filament\\\\Notifications\\\\DatabaseNotification","job":"Illuminate\\\\Queue\\\\CallQueuedHandler@call","maxTries":null,"maxExceptions":null,"failOnTimeout":false,"backoff":null,"timeout":null,"retryUntil":null,"data":{"commandName":"Illuminate\\\\Notifications\\\\SendQueuedNotifications","command":"O:48:\\"Illuminate\\\\Notifications\\\\SendQueuedNotifications\\":3:{s:11:\\"notifiables\\";O:45:\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\":5:{s:5:\\"class\\";s:15:\\"App\\\\Models\\\\User\\";s:2:\\"id\\";a:1:{i:0;i:2;}s:9:\\"relations\\";a:0:{}s:10:\\"connection\\";s:5:\\"mysql\\";s:15:\\"collectionClass\\";N;}s:12:\\"notification\\";O:43:\\"Filament\\\\Notifications\\\\DatabaseNotification\\":2:{s:4:\\"data\\";a:11:{s:7:\\"actions\\";a:0:{}s:4:\\"body\\";s:68:\\"Por: admin\\nData\\/Hora: 07\\/01\\/2026 17:00\\nIntimado: eu\\nProcedimento: 43\\";s:5:\\"color\\";N;s:8:\\"duration\\";s:10:\\"persistent\\";s:4:\\"icon\\";N;s:9:\\"iconColor\\";N;s:6:\\"status\\";N;s:5:\\"title\\";s:22:\\"Agendamento atualizado\\";s:4:\\"view\\";N;s:8:\\"viewData\\";a:0:{}s:6:\\"format\\";s:8:\\"filament\\";}s:2:\\"id\\";s:36:\\"7f87fa40-5e0d-4623-91aa-ab65b0a3558c\\";}s:8:\\"channels\\";a:1:{i:0;s:8:\\"database\\";}}"},"createdAt":1767791279,"delay":null}', 0, NULL, 1767791279, 1767791279),
	(14, 'default', '{"uuid":"a6cada97-b1dd-401c-9e13-1caa66a8d731","displayName":"Filament\\\\Notifications\\\\DatabaseNotification","job":"Illuminate\\\\Queue\\\\CallQueuedHandler@call","maxTries":null,"maxExceptions":null,"failOnTimeout":false,"backoff":null,"timeout":null,"retryUntil":null,"data":{"commandName":"Illuminate\\\\Notifications\\\\SendQueuedNotifications","command":"O:48:\\"Illuminate\\\\Notifications\\\\SendQueuedNotifications\\":3:{s:11:\\"notifiables\\";O:45:\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\":5:{s:5:\\"class\\";s:15:\\"App\\\\Models\\\\User\\";s:2:\\"id\\";a:1:{i:0;i:2;}s:9:\\"relations\\";a:0:{}s:10:\\"connection\\";s:5:\\"mysql\\";s:15:\\"collectionClass\\";N;}s:12:\\"notification\\";O:43:\\"Filament\\\\Notifications\\\\DatabaseNotification\\":2:{s:4:\\"data\\";a:11:{s:7:\\"actions\\";a:0:{}s:4:\\"body\\";s:72:\\"Por: ipc\\nData\\/Hora: 06\\/01\\/2026 08:00\\nIntimado: testipc\\nProcedimento: 345\\";s:5:\\"color\\";N;s:8:\\"duration\\";s:10:\\"persistent\\";s:4:\\"icon\\";N;s:9:\\"iconColor\\";N;s:6:\\"status\\";N;s:5:\\"title\\";s:18:\\"Agendamento criado\\";s:4:\\"view\\";N;s:8:\\"viewData\\";a:0:{}s:6:\\"format\\";s:8:\\"filament\\";}s:2:\\"id\\";s:36:\\"2c7057d1-9fe7-481f-9a9e-6ca506bceb08\\";}s:8:\\"channels\\";a:1:{i:0;s:8:\\"database\\";}}"},"createdAt":1767791730,"delay":null}', 0, NULL, 1767791730, 1767791730),
	(15, 'default', '{"uuid":"fa55a621-7d14-4993-a5ab-808dd9c95ae8","displayName":"Filament\\\\Notifications\\\\DatabaseNotification","job":"Illuminate\\\\Queue\\\\CallQueuedHandler@call","maxTries":null,"maxExceptions":null,"failOnTimeout":false,"backoff":null,"timeout":null,"retryUntil":null,"data":{"commandName":"Illuminate\\\\Notifications\\\\SendQueuedNotifications","command":"O:48:\\"Illuminate\\\\Notifications\\\\SendQueuedNotifications\\":3:{s:11:\\"notifiables\\";O:45:\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\":5:{s:5:\\"class\\";s:15:\\"App\\\\Models\\\\User\\";s:2:\\"id\\";a:1:{i:0;i:2;}s:9:\\"relations\\";a:0:{}s:10:\\"connection\\";s:5:\\"mysql\\";s:15:\\"collectionClass\\";N;}s:12:\\"notification\\";O:43:\\"Filament\\\\Notifications\\\\DatabaseNotification\\":2:{s:4:\\"data\\";a:11:{s:7:\\"actions\\";a:0:{}s:4:\\"body\\";s:75:\\"Por: admin\\nData\\/Hora: 06\\/01\\/2026 08:00\\nIntimado: testipc2\\nProcedimento: 345\\";s:5:\\"color\\";N;s:8:\\"duration\\";s:10:\\"persistent\\";s:4:\\"icon\\";N;s:9:\\"iconColor\\";N;s:6:\\"status\\";N;s:5:\\"title\\";s:22:\\"Agendamento atualizado\\";s:4:\\"view\\";N;s:8:\\"viewData\\";a:0:{}s:6:\\"format\\";s:8:\\"filament\\";}s:2:\\"id\\";s:36:\\"917ce865-138e-4a28-946a-196bc956e008\\";}s:8:\\"channels\\";a:1:{i:0;s:8:\\"database\\";}}"},"createdAt":1767792602,"delay":null}', 0, NULL, 1767792602, 1767792602),
	(16, 'default', '{"uuid":"fc1c2038-47bd-4795-a0b7-c9c1c23bdff7","displayName":"Filament\\\\Notifications\\\\DatabaseNotification","job":"Illuminate\\\\Queue\\\\CallQueuedHandler@call","maxTries":null,"maxExceptions":null,"failOnTimeout":false,"backoff":null,"timeout":null,"retryUntil":null,"data":{"commandName":"Illuminate\\\\Notifications\\\\SendQueuedNotifications","command":"O:48:\\"Illuminate\\\\Notifications\\\\SendQueuedNotifications\\":3:{s:11:\\"notifiables\\";O:45:\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\":5:{s:5:\\"class\\";s:15:\\"App\\\\Models\\\\User\\";s:2:\\"id\\";a:1:{i:0;i:8;}s:9:\\"relations\\";a:0:{}s:10:\\"connection\\";s:5:\\"mysql\\";s:15:\\"collectionClass\\";N;}s:12:\\"notification\\";O:43:\\"Filament\\\\Notifications\\\\DatabaseNotification\\":2:{s:4:\\"data\\";a:11:{s:7:\\"actions\\";a:0:{}s:4:\\"body\\";s:85:\\"Por: STEFAN MORINIGO ALVES\\nData\\/Hora: 09\\/01\\/2026 08:00\\nIntimado: aa\\nProcedimento: aaa\\";s:5:\\"color\\";N;s:8:\\"duration\\";s:10:\\"persistent\\";s:4:\\"icon\\";N;s:9:\\"iconColor\\";N;s:6:\\"status\\";N;s:5:\\"title\\";s:18:\\"Agendamento criado\\";s:4:\\"view\\";N;s:8:\\"viewData\\";a:0:{}s:6:\\"format\\";s:8:\\"filament\\";}s:2:\\"id\\";s:36:\\"41823455-1116-49fb-b198-1c6c02d5b20c\\";}s:8:\\"channels\\";a:1:{i:0;s:8:\\"database\\";}}"},"createdAt":1767872623,"delay":null}', 0, NULL, 1767872623, 1767872623),
	(17, 'default', '{"uuid":"83cd8bea-5281-469b-b1b7-4b1a6e46d016","displayName":"Filament\\\\Notifications\\\\DatabaseNotification","job":"Illuminate\\\\Queue\\\\CallQueuedHandler@call","maxTries":null,"maxExceptions":null,"failOnTimeout":false,"backoff":null,"timeout":null,"retryUntil":null,"data":{"commandName":"Illuminate\\\\Notifications\\\\SendQueuedNotifications","command":"O:48:\\"Illuminate\\\\Notifications\\\\SendQueuedNotifications\\":3:{s:11:\\"notifiables\\";O:45:\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\":5:{s:5:\\"class\\";s:15:\\"App\\\\Models\\\\User\\";s:2:\\"id\\";a:1:{i:0;i:8;}s:9:\\"relations\\";a:0:{}s:10:\\"connection\\";s:5:\\"mysql\\";s:15:\\"collectionClass\\";N;}s:12:\\"notification\\";O:43:\\"Filament\\\\Notifications\\\\DatabaseNotification\\":2:{s:4:\\"data\\";a:11:{s:7:\\"actions\\";a:0:{}s:4:\\"body\\";s:85:\\"Por: STEFAN MORINIGO ALVES\\nData\\/Hora: 09\\/01\\/2026 08:00\\nIntimado: aa\\nProcedimento: aaa\\";s:5:\\"color\\";N;s:8:\\"duration\\";s:10:\\"persistent\\";s:4:\\"icon\\";N;s:9:\\"iconColor\\";N;s:6:\\"status\\";N;s:5:\\"title\\";s:22:\\"Agendamento atualizado\\";s:4:\\"view\\";N;s:8:\\"viewData\\";a:0:{}s:6:\\"format\\";s:8:\\"filament\\";}s:2:\\"id\\";s:36:\\"9052830b-f0d9-4a9b-918a-654d733eead2\\";}s:8:\\"channels\\";a:1:{i:0;s:8:\\"database\\";}}"},"createdAt":1767872935,"delay":null}', 0, NULL, 1767872935, 1767872935),
	(18, 'default', '{"uuid":"4883af5f-8587-4530-9b1b-890bc3892499","displayName":"Filament\\\\Notifications\\\\DatabaseNotification","job":"Illuminate\\\\Queue\\\\CallQueuedHandler@call","maxTries":null,"maxExceptions":null,"failOnTimeout":false,"backoff":null,"timeout":null,"retryUntil":null,"data":{"commandName":"Illuminate\\\\Notifications\\\\SendQueuedNotifications","command":"O:48:\\"Illuminate\\\\Notifications\\\\SendQueuedNotifications\\":3:{s:11:\\"notifiables\\";O:45:\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\":5:{s:5:\\"class\\";s:15:\\"App\\\\Models\\\\User\\";s:2:\\"id\\";a:1:{i:0;i:8;}s:9:\\"relations\\";a:0:{}s:10:\\"connection\\";s:5:\\"mysql\\";s:15:\\"collectionClass\\";N;}s:12:\\"notification\\";O:43:\\"Filament\\\\Notifications\\\\DatabaseNotification\\":2:{s:4:\\"data\\";a:11:{s:7:\\"actions\\";a:0:{}s:4:\\"body\\";s:85:\\"Por: STEFAN MORINIGO ALVES\\nData\\/Hora: 09\\/01\\/2026 08:00\\nIntimado: aa\\nProcedimento: aaa\\";s:5:\\"color\\";N;s:8:\\"duration\\";s:10:\\"persistent\\";s:4:\\"icon\\";N;s:9:\\"iconColor\\";N;s:6:\\"status\\";N;s:5:\\"title\\";s:21:\\"Agendamento cancelado\\";s:4:\\"view\\";N;s:8:\\"viewData\\";a:0:{}s:6:\\"format\\";s:8:\\"filament\\";}s:2:\\"id\\";s:36:\\"a46eb422-4ec5-4d6d-a509-331fb17f61fe\\";}s:8:\\"channels\\";a:1:{i:0;s:8:\\"database\\";}}"},"createdAt":1767872935,"delay":null}', 0, NULL, 1767872935, 1767872935),
	(19, 'default', '{"uuid":"b17d68ee-f43d-4674-b12c-3540019b4503","displayName":"Filament\\\\Notifications\\\\DatabaseNotification","job":"Illuminate\\\\Queue\\\\CallQueuedHandler@call","maxTries":null,"maxExceptions":null,"failOnTimeout":false,"backoff":null,"timeout":null,"retryUntil":null,"data":{"commandName":"Illuminate\\\\Notifications\\\\SendQueuedNotifications","command":"O:48:\\"Illuminate\\\\Notifications\\\\SendQueuedNotifications\\":3:{s:11:\\"notifiables\\";O:45:\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\":5:{s:5:\\"class\\";s:15:\\"App\\\\Models\\\\User\\";s:2:\\"id\\";a:1:{i:0;i:8;}s:9:\\"relations\\";a:0:{}s:10:\\"connection\\";s:5:\\"mysql\\";s:15:\\"collectionClass\\";N;}s:12:\\"notification\\";O:43:\\"Filament\\\\Notifications\\\\DatabaseNotification\\":2:{s:4:\\"data\\";a:11:{s:7:\\"actions\\";a:0:{}s:4:\\"body\\";s:76:\\"Por: Mayso Toledo\\nData\\/Hora: 09\\/01\\/2026 08:00\\nIntimado: aa\\nProcedimento: aaa\\";s:5:\\"color\\";N;s:8:\\"duration\\";s:10:\\"persistent\\";s:4:\\"icon\\";N;s:9:\\"iconColor\\";N;s:6:\\"status\\";N;s:5:\\"title\\";s:21:\\"Agendamento cancelado\\";s:4:\\"view\\";N;s:8:\\"viewData\\";a:0:{}s:6:\\"format\\";s:8:\\"filament\\";}s:2:\\"id\\";s:36:\\"39380e95-8301-4ed0-8f43-0163631ec3cd\\";}s:8:\\"channels\\";a:1:{i:0;s:8:\\"database\\";}}"},"createdAt":1767893162,"delay":null}', 0, NULL, 1767893162, 1767893162);

-- Copiando estrutura para tabela agenda_epc.migrations
DROP TABLE IF EXISTS `migrations`;
CREATE TABLE IF NOT EXISTS `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Copiando dados para a tabela agenda_epc.migrations: ~13 rows (aproximadamente)
DELETE FROM `migrations`;
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
	(1, '0001_01_01_000000_create_users_table', 1),
	(2, '0001_01_01_000001_create_cache_table', 1),
	(3, '0001_01_01_000002_create_jobs_table', 1),
	(4, '2025_12_24_233649_create_permission_tables', 1),
	(5, '2025_12_24_233744_create_eventos_table', 1),
	(6, '2025_12_25_052721_add_user_id_to_eventos_table', 1),
	(7, '2025_12_25_175445_create_bloqueios_table', 1),
	(8, '2025_12_25_214709_add_intimado_numero_procedimento_to_eventos_table', 1),
	(9, '2025_12_25_215024_backfill_intimado_from_titulo_in_eventos_table', 1),
	(10, '2025_12_25_215101_drop_titulo_from_eventos_table', 1),
	(11, '2025_12_25_222826_create_notifications_table', 1),
	(12, '2025_12_26_011907_add_audit_and_soft_deletes_to_eventos_table', 1),
	(13, '2025_12_26_023052_create_ferias_table', 1),
	(14, '2026_01_23_120244_add_whatsapp_and_oitiva_online_to_eventos_table', 2);

-- Copiando estrutura para tabela agenda_epc.roles
DROP TABLE IF EXISTS `roles`;
CREATE TABLE IF NOT EXISTS `roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Copiando dados para a tabela agenda_epc.roles: ~6 rows (aproximadamente)
DELETE FROM `roles`;
INSERT INTO `roles` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES
	(1, 'super_admin', 'web', '2026-01-06 18:01:12', '2026-01-06 18:01:12'),
	(2, 'epc', 'web', '2026-01-06 20:13:43', '2026-01-06 20:13:43'),
	(3, 'ipc', 'web', '2026-01-06 20:13:53', '2026-01-06 20:13:53'),
	(4, 'cartorio_central', 'web', '2026-01-07 16:52:24', '2026-01-07 16:52:24'),
	(5, 'ipc_plantao', 'web', '2026-01-07 16:55:39', '2026-01-07 16:55:39'),
	(6, 'epc_plantao', 'web', '2026-01-07 16:55:49', '2026-01-07 16:55:49'),
	(7, 'dpc', 'web', '2026-01-07 16:56:23', '2026-01-07 16:56:23');

-- Copiando estrutura para tabela agenda_epc.permissions
DROP TABLE IF EXISTS `permissions`;
CREATE TABLE IF NOT EXISTS `permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=58 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Copiando dados para a tabela agenda_epc.permissions: ~57 rows (aproximadamente)
DELETE FROM `permissions`;
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES
	(1, 'ViewAny:Bloqueio', 'web', '2026-01-06 18:01:31', '2026-01-06 18:01:31'),
	(2, 'View:Bloqueio', 'web', '2026-01-06 18:01:31', '2026-01-06 18:01:31'),
	(3, 'Create:Bloqueio', 'web', '2026-01-06 18:01:31', '2026-01-06 18:01:31'),
	(4, 'Update:Bloqueio', 'web', '2026-01-06 18:01:31', '2026-01-06 18:01:31'),
	(5, 'Delete:Bloqueio', 'web', '2026-01-06 18:01:31', '2026-01-06 18:01:31'),
	(6, 'Restore:Bloqueio', 'web', '2026-01-06 18:01:31', '2026-01-06 18:01:31'),
	(7, 'ForceDelete:Bloqueio', 'web', '2026-01-06 18:01:31', '2026-01-06 18:01:31'),
	(8, 'ForceDeleteAny:Bloqueio', 'web', '2026-01-06 18:01:31', '2026-01-06 18:01:31'),
	(9, 'RestoreAny:Bloqueio', 'web', '2026-01-06 18:01:31', '2026-01-06 18:01:31'),
	(10, 'Replicate:Bloqueio', 'web', '2026-01-06 18:01:31', '2026-01-06 18:01:31'),
	(11, 'Reorder:Bloqueio', 'web', '2026-01-06 18:01:31', '2026-01-06 18:01:31'),
	(12, 'ViewAny:Evento', 'web', '2026-01-06 18:01:31', '2026-01-06 18:01:31'),
	(13, 'View:Evento', 'web', '2026-01-06 18:01:31', '2026-01-06 18:01:31'),
	(14, 'Create:Evento', 'web', '2026-01-06 18:01:31', '2026-01-06 18:01:31'),
	(15, 'Update:Evento', 'web', '2026-01-06 18:01:31', '2026-01-06 18:01:31'),
	(16, 'Delete:Evento', 'web', '2026-01-06 18:01:31', '2026-01-06 18:01:31'),
	(17, 'Restore:Evento', 'web', '2026-01-06 18:01:31', '2026-01-06 18:01:31'),
	(18, 'ForceDelete:Evento', 'web', '2026-01-06 18:01:31', '2026-01-06 18:01:31'),
	(19, 'ForceDeleteAny:Evento', 'web', '2026-01-06 18:01:31', '2026-01-06 18:01:31'),
	(20, 'RestoreAny:Evento', 'web', '2026-01-06 18:01:31', '2026-01-06 18:01:31'),
	(21, 'Replicate:Evento', 'web', '2026-01-06 18:01:31', '2026-01-06 18:01:31'),
	(22, 'Reorder:Evento', 'web', '2026-01-06 18:01:31', '2026-01-06 18:01:31'),
	(23, 'ViewAny:Ferias', 'web', '2026-01-06 18:01:31', '2026-01-06 18:01:31'),
	(24, 'View:Ferias', 'web', '2026-01-06 18:01:31', '2026-01-06 18:01:31'),
	(25, 'Create:Ferias', 'web', '2026-01-06 18:01:31', '2026-01-06 18:01:31'),
	(26, 'Update:Ferias', 'web', '2026-01-06 18:01:31', '2026-01-06 18:01:31'),
	(27, 'Delete:Ferias', 'web', '2026-01-06 18:01:31', '2026-01-06 18:01:31'),
	(28, 'Restore:Ferias', 'web', '2026-01-06 18:01:31', '2026-01-06 18:01:31'),
	(29, 'ForceDelete:Ferias', 'web', '2026-01-06 18:01:31', '2026-01-06 18:01:31'),
	(30, 'ForceDeleteAny:Ferias', 'web', '2026-01-06 18:01:31', '2026-01-06 18:01:31'),
	(31, 'RestoreAny:Ferias', 'web', '2026-01-06 18:01:31', '2026-01-06 18:01:31'),
	(32, 'Replicate:Ferias', 'web', '2026-01-06 18:01:31', '2026-01-06 18:01:31'),
	(33, 'Reorder:Ferias', 'web', '2026-01-06 18:01:31', '2026-01-06 18:01:31'),
	(34, 'ViewAny:User', 'web', '2026-01-06 18:01:31', '2026-01-06 18:01:31'),
	(35, 'View:User', 'web', '2026-01-06 18:01:31', '2026-01-06 18:01:31'),
	(36, 'Create:User', 'web', '2026-01-06 18:01:31', '2026-01-06 18:01:31'),
	(37, 'Update:User', 'web', '2026-01-06 18:01:31', '2026-01-06 18:01:31'),
	(38, 'Delete:User', 'web', '2026-01-06 18:01:31', '2026-01-06 18:01:31'),
	(39, 'Restore:User', 'web', '2026-01-06 18:01:31', '2026-01-06 18:01:31'),
	(40, 'ForceDelete:User', 'web', '2026-01-06 18:01:31', '2026-01-06 18:01:31'),
	(41, 'ForceDeleteAny:User', 'web', '2026-01-06 18:01:31', '2026-01-06 18:01:31'),
	(42, 'RestoreAny:User', 'web', '2026-01-06 18:01:31', '2026-01-06 18:01:31'),
	(43, 'Replicate:User', 'web', '2026-01-06 18:01:31', '2026-01-06 18:01:31'),
	(44, 'Reorder:User', 'web', '2026-01-06 18:01:31', '2026-01-06 18:01:31'),
	(45, 'ViewAny:Role', 'web', '2026-01-06 18:01:31', '2026-01-06 18:01:31'),
	(46, 'View:Role', 'web', '2026-01-06 18:01:31', '2026-01-06 18:01:31'),
	(47, 'Create:Role', 'web', '2026-01-06 18:01:31', '2026-01-06 18:01:31'),
	(48, 'Update:Role', 'web', '2026-01-06 18:01:31', '2026-01-06 18:01:31'),
	(49, 'Delete:Role', 'web', '2026-01-06 18:01:31', '2026-01-06 18:01:31'),
	(50, 'Restore:Role', 'web', '2026-01-06 18:01:31', '2026-01-06 18:01:31'),
	(51, 'ForceDelete:Role', 'web', '2026-01-06 18:01:31', '2026-01-06 18:01:31'),
	(52, 'ForceDeleteAny:Role', 'web', '2026-01-06 18:01:31', '2026-01-06 18:01:31'),
	(53, 'RestoreAny:Role', 'web', '2026-01-06 18:01:31', '2026-01-06 18:01:31'),
	(54, 'Replicate:Role', 'web', '2026-01-06 18:01:31', '2026-01-06 18:01:31'),
	(55, 'Reorder:Role', 'web', '2026-01-06 18:01:31', '2026-01-06 18:01:31'),
	(56, 'View:AgendaCalendario', 'web', '2026-01-06 18:01:31', '2026-01-06 18:01:31'),
	(57, 'View:Dashboard', 'web', '2026-01-06 18:01:31', '2026-01-06 18:01:31');

-- Copiando estrutura para tabela agenda_epc.role_has_permissions
DROP TABLE IF EXISTS `role_has_permissions`;
CREATE TABLE IF NOT EXISTS `role_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `role_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`),
  CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Copiando dados para a tabela agenda_epc.role_has_permissions: ~85 rows (aproximadamente)
DELETE FROM `role_has_permissions`;
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES
	(1, 1),
	(1, 4),
	(1, 7),
	(2, 1),
	(2, 4),
	(2, 7),
	(3, 1),
	(3, 4),
	(3, 7),
	(4, 1),
	(4, 4),
	(4, 7),
	(5, 1),
	(5, 4),
	(5, 7),
	(6, 1),
	(7, 1),
	(8, 1),
	(9, 1),
	(10, 1),
	(11, 1),
	(12, 1),
	(12, 4),
	(12, 7),
	(13, 1),
	(13, 4),
	(13, 7),
	(14, 1),
	(14, 4),
	(14, 7),
	(15, 1),
	(15, 2),
	(15, 3),
	(15, 4),
	(15, 7),
	(16, 1),
	(16, 2),
	(16, 3),
	(16, 4),
	(16, 7),
	(17, 1),
	(17, 4),
	(18, 1),
	(19, 1),
	(20, 1),
	(20, 4),
	(21, 1),
	(22, 1),
	(23, 1),
	(24, 1),
	(25, 1),
	(26, 1),
	(27, 1),
	(28, 1),
	(29, 1),
	(30, 1),
	(31, 1),
	(32, 1),
	(33, 1),
	(34, 1),
	(35, 1),
	(36, 1),
	(37, 1),
	(38, 1),
	(39, 1),
	(40, 1),
	(41, 1),
	(42, 1),
	(43, 1),
	(44, 1),
	(45, 1),
	(46, 1),
	(47, 1),
	(48, 1),
	(49, 1),
	(50, 1),
	(51, 1),
	(52, 1),
	(53, 1),
	(54, 1),
	(55, 1),
	(56, 1),
	(56, 4),
	(57, 1),
	(57, 4);

-- Copiando estrutura para tabela agenda_epc.model_has_roles
DROP TABLE IF EXISTS `model_has_roles`;
CREATE TABLE IF NOT EXISTS `model_has_roles` (
  `role_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Copiando dados para a tabela agenda_epc.model_has_roles: ~23 rows (aproximadamente)
DELETE FROM `model_has_roles`;
INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES
	(1, 'App\\Models\\User', 5),
	(2, 'App\\Models\\User', 8),
	(2, 'App\\Models\\User', 15),
	(2, 'App\\Models\\User', 27),
	(3, 'App\\Models\\User', 6),
	(3, 'App\\Models\\User', 10),
	(3, 'App\\Models\\User', 13),
	(3, 'App\\Models\\User', 14),
	(3, 'App\\Models\\User', 17),
	(3, 'App\\Models\\User', 18),
	(3, 'App\\Models\\User', 26),
	(4, 'App\\Models\\User', 22),
	(5, 'App\\Models\\User', 7),
	(5, 'App\\Models\\User', 9),
	(5, 'App\\Models\\User', 12),
	(5, 'App\\Models\\User', 20),
	(5, 'App\\Models\\User', 23),
	(5, 'App\\Models\\User', 24),
	(5, 'App\\Models\\User', 25),
	(6, 'App\\Models\\User', 11),
	(6, 'App\\Models\\User', 16),
	(6, 'App\\Models\\User', 21),
	(6, 'App\\Models\\User', 28),
	(7, 'App\\Models\\User', 19);

-- Copiando estrutura para tabela agenda_epc.model_has_permissions
DROP TABLE IF EXISTS `model_has_permissions`;
CREATE TABLE IF NOT EXISTS `model_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Copiando dados para a tabela agenda_epc.model_has_permissions: ~0 rows (aproximadamente)
DELETE FROM `model_has_permissions`;

-- Copiando estrutura para tabela agenda_epc.notifications
DROP TABLE IF EXISTS `notifications`;
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notifiable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notifiable_id` bigint unsigned NOT NULL,
  `data` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Copiando dados para a tabela agenda_epc.notifications: ~0 rows (aproximadamente)
DELETE FROM `notifications`;

-- Copiando estrutura para tabela agenda_epc.eventos
DROP TABLE IF EXISTS `eventos`;
CREATE TABLE IF NOT EXISTS `eventos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `intimado` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `whatsapp` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `oitiva_online` tinyint(1) NOT NULL DEFAULT '0',
  `numero_procedimento` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `starts_at` datetime NOT NULL,
  `ends_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `deleted_by` bigint unsigned DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `eventos_user_id_foreign` (`user_id`),
  KEY `eventos_updated_by_foreign` (`updated_by`),
  KEY `eventos_deleted_by_foreign` (`deleted_by`),
  KEY `eventos_created_by_updated_by_deleted_by_index` (`created_by`,`updated_by`,`deleted_by`),
  CONSTRAINT `eventos_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `eventos_deleted_by_foreign` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `eventos_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `eventos_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Copiando dados para a tabela agenda_epc.eventos: ~1 rows (aproximadamente)
DELETE FROM `eventos`;

-- Copiando estrutura para tabela agenda_epc.bloqueios
DROP TABLE IF EXISTS `bloqueios`;
CREATE TABLE IF NOT EXISTS `bloqueios` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `dia` date NOT NULL,
  `motivo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `bloqueios_user_id_dia_unique` (`user_id`,`dia`),
  KEY `bloqueios_created_by_foreign` (`created_by`),
  KEY `bloqueios_user_id_dia_index` (`user_id`,`dia`),
  CONSTRAINT `bloqueios_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `bloqueios_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Copiando dados para a tabela agenda_epc.bloqueios: ~0 rows (aproximadamente)
DELETE FROM `bloqueios`;

-- Copiando estrutura para tabela agenda_epc.ferias
DROP TABLE IF EXISTS `ferias`;
CREATE TABLE IF NOT EXISTS `ferias` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `inicio` date NOT NULL,
  `fim` date NOT NULL,
  `ano` smallint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ferias_user_id_ano_index` (`user_id`,`ano`),
  KEY `ferias_inicio_fim_index` (`inicio`,`fim`),
  CONSTRAINT `ferias_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Copiando dados para a tabela agenda_epc.ferias: ~0 rows (aproximadamente)
DELETE FROM `ferias`;

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
