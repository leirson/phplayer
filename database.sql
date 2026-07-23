-- SQL para criação do banco de dados do Player Subsonic PHP
-- Importe este arquivo no phpMyAdmin da Hostinger

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "-03:00";

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `role` varchar(20) DEFAULT 'ouvinte',
  `theme` varchar(30) DEFAULT 'default',
  `can_share` tinyint(1) DEFAULT 1,
  `can_download` tinyint(1) DEFAULT 1,
  `dashboardLimit` int(11) DEFAULT 100,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `songs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `artist` varchar(255) DEFAULT 'Artista Desconhecido',
  `album` varchar(255) DEFAULT 'Álbum Desconhecido',
  `genre` varchar(100) DEFAULT 'Desconhecido',
  `file_name` varchar(255) NOT NULL,
  `file_size` int(11) NOT NULL,
  `duration` int(11) DEFAULT 180,
  `cover_url` varchar(500) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `playlists` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `playlist_songs` (
  `playlist_id` int(11) NOT NULL,
  `song_id` int(11) NOT NULL,
  `position` int(11) NOT NULL,
  PRIMARY KEY (`playlist_id`,`song_id`),
  FOREIGN KEY (`playlist_id`) REFERENCES `playlists`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`song_id`) REFERENCES `songs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `favorites` (
  `username` varchar(50) NOT NULL,
  `song_id` int(11) NOT NULL,
  PRIMARY KEY (`username`,`song_id`),
  FOREIGN KEY (`song_id`) REFERENCES `songs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `videos` (
  `id` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_size` bigint(20) NOT NULL,
  `cover_url` varchar(500) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `settings` (
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `artist_metadata` (
  `artist` varchar(255) NOT NULL,
  `artist_photo` varchar(1000) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  PRIMARY KEY (`artist`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `radios` (
  `id` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `url` varchar(500) NOT NULL,
  `resolved_url` varchar(500) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `shares` (
  `share_hash` varchar(100) NOT NULL,
  `target_type` varchar(50) NOT NULL,
  `target_id` varchar(500) NOT NULL,
  `target_name` varchar(255) NOT NULL,
  `created_by` varchar(50) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `expires_at` datetime DEFAULT NULL,
  PRIMARY KEY (`share_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Admin: admin  Ouvinte: ouvinte
INSERT INTO `users` (`id`, `username`, `password`, `role`) VALUES
(1, 'admin', '$2y$10$m.1eNSRiMtmn.9RvSqJL/.sRfFdcFlgv36RrpGkNfzR5F7LaA1C42', 'admin'),
(2, 'ouvinte', '$2y$10$X10Q4Ac4vmEgRpyWM2ok1./0gKGk6d3QXpeHE4c0YcD1rZ/VxGEKe', 'ouvinte')
ON DUPLICATE KEY UPDATE `password` = VALUES(`password`), `role` = VALUES(`role`);
COMMIT;
