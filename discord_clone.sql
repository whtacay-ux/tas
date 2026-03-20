-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Anamakine: 127.0.0.1
-- Üretim Zamanı: 20 Mar 2026, 13:39:55
-- Sunucu sürümü: 10.4.32-MariaDB
-- PHP Sürümü: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Veritabanı: `discord_clone`
--

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `channels`
--

CREATE TABLE `channels` (
  `id` int(11) NOT NULL,
  `server_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `type` enum('text','voice','video') DEFAULT 'text',
  `position` int(11) DEFAULT 0,
  `is_private` tinyint(1) DEFAULT 0,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `channels`
--

INSERT INTO `channels` (`id`, `server_id`, `name`, `type`, `position`, `is_private`, `created_by`, `created_at`) VALUES
(1, 1, 'genel-sohbet', 'text', 0, 0, 1, '2026-03-20 11:38:50'),
(2, 1, 'muzik', 'text', 0, 0, 1, '2026-03-20 11:38:50'),
(3, 1, 'genel-ses', 'voice', 0, 0, 1, '2026-03-20 11:38:50'),
(4, 1, 'görüntülü-görüşme', 'video', 0, 0, 1, '2026-03-20 11:38:50'),
(5, 2, 'genel-sohbet', 'text', 1, 0, 2, '2026-03-20 11:39:35'),
(6, 2, 'genel-ses', 'voice', 2, 0, 2, '2026-03-20 11:39:35'),
(7, 3, 'genel-sohbet', 'text', 1, 0, 2, '2026-03-20 11:39:36'),
(8, 3, 'genel-ses', 'voice', 2, 0, 2, '2026-03-20 11:39:36'),
(9, 4, 'genel-sohbet', 'text', 1, 0, 2, '2026-03-20 11:39:36'),
(10, 4, 'genel-ses', 'voice', 2, 0, 2, '2026-03-20 11:39:36'),
(11, 5, 'genel-sohbet', 'text', 1, 0, 2, '2026-03-20 11:39:36'),
(12, 5, 'genel-ses', 'voice', 2, 0, 2, '2026-03-20 11:39:36'),
(13, 6, 'genel-sohbet', 'text', 1, 0, 2, '2026-03-20 11:39:37'),
(14, 6, 'genel-ses', 'voice', 2, 0, 2, '2026-03-20 11:39:37'),
(15, 7, 'genel-sohbet', 'text', 1, 0, 3, '2026-03-20 11:49:00'),
(16, 7, 'genel-ses', 'voice', 2, 0, 3, '2026-03-20 11:49:00'),
(17, 8, 'genel-sohbet', 'text', 1, 0, 4, '2026-03-20 11:56:19'),
(18, 8, 'genel-ses', 'voice', 2, 0, 4, '2026-03-20 11:56:19'),
(19, 9, 'genel-sohbet', 'text', 1, 0, 6, '2026-03-20 12:08:25'),
(20, 9, 'genel-ses', 'voice', 2, 0, 6, '2026-03-20 12:08:25'),
(21, 10, 'genel-sohbet', 'text', 1, 0, 7, '2026-03-20 12:17:43'),
(22, 10, 'genel-ses', 'voice', 2, 0, 7, '2026-03-20 12:17:43');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `channel_friends`
--

CREATE TABLE `channel_friends` (
  `id` int(11) NOT NULL,
  `channel_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `invited_by` int(11) NOT NULL,
  `status` enum('pending','accepted','left') DEFAULT 'pending',
  `invited_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `channel_invites`
--

CREATE TABLE `channel_invites` (
  `id` int(11) NOT NULL,
  `channel_id` int(11) NOT NULL,
  `invite_code` varchar(20) NOT NULL,
  `created_by` int(11) NOT NULL,
  `max_uses` int(11) DEFAULT 0,
  `used_count` int(11) DEFAULT 0,
  `expires_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `channel_invite_uses`
--

CREATE TABLE `channel_invite_uses` (
  `id` int(11) NOT NULL,
  `invite_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `used_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `channel_members`
--

CREATE TABLE `channel_members` (
  `id` int(11) NOT NULL,
  `channel_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role` enum('admin','member','guest') DEFAULT 'member',
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `friendships`
--

CREATE TABLE `friendships` (
  `id` int(11) NOT NULL,
  `requester_id` int(11) NOT NULL,
  `addressee_id` int(11) NOT NULL,
  `status` enum('pending','accepted','blocked') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `friendships`
--

INSERT INTO `friendships` (`id`, `requester_id`, `addressee_id`, `status`, `created_at`, `updated_at`) VALUES
(1, 5, 4, 'pending', '2026-03-20 12:01:05', NULL),
(2, 5, 6, 'pending', '2026-03-20 12:07:48', NULL),
(3, 5, 7, 'accepted', '2026-03-20 12:17:12', '2026-03-20 12:17:18');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `live_streams`
--

CREATE TABLE `live_streams` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `channel_id` int(11) NOT NULL,
  `stream_title` varchar(255) DEFAULT 'Canlı Yayın',
  `stream_key` varchar(100) NOT NULL,
  `status` enum('live','ended','paused') DEFAULT 'live',
  `viewer_count` int(11) DEFAULT 0,
  `started_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `ended_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `channel_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `media_url` varchar(255) DEFAULT NULL,
  `media_type` enum('image','video','audio','file') DEFAULT NULL,
  `reply_to` int(11) DEFAULT NULL,
  `is_edited` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `messages`
--

INSERT INTO `messages` (`id`, `user_id`, `channel_id`, `message`, `media_url`, `media_type`, `reply_to`, `is_edited`, `created_at`, `updated_at`) VALUES
(1, 4, 17, 'da', NULL, NULL, NULL, 0, '2026-03-20 11:59:44', NULL),
(2, 4, 17, 'da', NULL, NULL, NULL, 0, '2026-03-20 11:59:46', NULL),
(3, 4, 17, 'olum', NULL, NULL, NULL, 0, '2026-03-20 11:59:51', NULL),
(4, 4, 17, 'olum', NULL, NULL, NULL, 0, '2026-03-20 11:59:54', NULL),
(5, 4, 17, 'asa', NULL, NULL, NULL, 0, '2026-03-20 12:01:17', NULL),
(6, 4, 17, 'sa', NULL, NULL, NULL, 0, '2026-03-20 12:07:00', NULL),
(7, 4, 17, 'sal', NULL, NULL, NULL, 0, '2026-03-20 12:07:07', NULL),
(8, 6, 19, 'test', NULL, NULL, NULL, 0, '2026-03-20 12:08:30', NULL),
(9, 6, 19, 'test🧐🧐🧐', NULL, NULL, NULL, 0, '2026-03-20 12:08:33', NULL),
(10, 6, 19, 'olum', NULL, NULL, NULL, 0, '2026-03-20 12:16:29', NULL),
(11, 6, 19, 'sa', NULL, NULL, NULL, 0, '2026-03-20 12:16:37', NULL),
(12, 6, 19, 'sa', NULL, NULL, NULL, 0, '2026-03-20 12:16:39', NULL),
(13, 6, 19, 'sa', NULL, NULL, NULL, 0, '2026-03-20 12:16:39', NULL),
(14, 6, 19, 'sa', NULL, NULL, NULL, 0, '2026-03-20 12:16:40', NULL),
(15, 7, 21, 'test', NULL, NULL, NULL, 0, '2026-03-20 12:17:45', NULL),
(16, 7, 21, 'test', NULL, NULL, NULL, 0, '2026-03-20 12:17:47', NULL),
(17, 7, 21, 'test', NULL, NULL, NULL, 0, '2026-03-20 12:17:50', NULL),
(18, 7, 21, 'test', NULL, NULL, NULL, 0, '2026-03-20 12:21:24', NULL),
(19, 7, 21, 'sa', NULL, NULL, NULL, 0, '2026-03-20 12:25:06', NULL),
(20, 7, 21, 'aga', NULL, NULL, NULL, 0, '2026-03-20 12:25:25', NULL);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `message_reactions`
--

CREATE TABLE `message_reactions` (
  `id` int(11) NOT NULL,
  `message_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `emoji` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `sender_id` int(11) DEFAULT NULL,
  `notification_type` enum('message','mention','call','channel_join','friend_request') NOT NULL,
  `content` text NOT NULL,
  `related_id` int(11) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `sender_id`, `notification_type`, `content`, `related_id`, `is_read`, `created_at`) VALUES
(1, 7, 5, 'friend_request', 'Yeni arkadaşlık isteği', NULL, 0, '2026-03-20 12:17:12');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL,
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`permissions`)),
  `color` varchar(7) DEFAULT '#7289da',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `roles`
--

INSERT INTO `roles` (`id`, `role_name`, `permissions`, `color`, `created_at`) VALUES
(1, 'Admin', '{\"create_channel\": true, \"delete_channel\": true, \"edit_channel\": true, \"delete_message\": true, \"ban_user\": true, \"kick_user\": true, \"manage_roles\": true, \"manage_server\": true, \"voice_chat\": true, \"video_chat\": true, \"screen_share\": true}', '#e74c3c', '2026-03-20 11:38:50'),
(2, 'Moderator', '{\"create_channel\": false, \"delete_channel\": false, \"edit_channel\": false, \"delete_message\": true, \"ban_user\": false, \"kick_user\": true, \"manage_roles\": false, \"manage_server\": false, \"voice_chat\": true, \"video_chat\": true, \"screen_share\": true}', '#f39c12', '2026-03-20 11:38:50'),
(3, 'Member', '{\"create_channel\": false, \"delete_channel\": false, \"edit_channel\": false, \"delete_message\": false, \"ban_user\": false, \"kick_user\": false, \"manage_roles\": false, \"manage_server\": false, \"voice_chat\": true, \"video_chat\": true, \"screen_share\": false}', '#7289da', '2026-03-20 11:38:50'),
(4, 'Guest', '{\"create_channel\": false, \"delete_channel\": false, \"edit_channel\": false, \"delete_message\": false, \"ban_user\": false, \"kick_user\": false, \"manage_roles\": false, \"manage_server\": false, \"voice_chat\": false, \"video_chat\": false, \"screen_share\": false}', '#95a5a6', '2026-03-20 11:38:50');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `servers`
--

CREATE TABLE `servers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `owner_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `servers`
--

INSERT INTO `servers` (`id`, `name`, `icon`, `owner_id`, `created_at`) VALUES
(1, 'Genel Sunucu', NULL, 1, '2026-03-20 11:38:50'),
(2, 'TEST', NULL, 2, '2026-03-20 11:39:35'),
(3, 'TEST', NULL, 2, '2026-03-20 11:39:36'),
(4, 'TEST', NULL, 2, '2026-03-20 11:39:36'),
(5, 'TEST', NULL, 2, '2026-03-20 11:39:36'),
(6, 'TEST', NULL, 2, '2026-03-20 11:39:37'),
(7, 'arara', NULL, 3, '2026-03-20 11:49:00'),
(8, 'aga', NULL, 4, '2026-03-20 11:56:19'),
(9, 'test', NULL, 6, '2026-03-20 12:08:25'),
(10, 'terst', NULL, 7, '2026-03-20 12:17:43');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `server_members`
--

CREATE TABLE `server_members` (
  `id` int(11) NOT NULL,
  `server_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role_id` int(11) DEFAULT 3,
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `server_members`
--

INSERT INTO `server_members` (`id`, `server_id`, `user_id`, `role_id`, `joined_at`) VALUES
(1, 1, 1, 1, '2026-03-20 11:38:50'),
(2, 2, 2, 1, '2026-03-20 11:39:35'),
(3, 3, 2, 1, '2026-03-20 11:39:36'),
(4, 4, 2, 1, '2026-03-20 11:39:36'),
(5, 5, 2, 1, '2026-03-20 11:39:36'),
(6, 6, 2, 1, '2026-03-20 11:39:37'),
(7, 7, 3, 1, '2026-03-20 11:49:00'),
(8, 8, 4, 1, '2026-03-20 11:56:19'),
(9, 9, 6, 1, '2026-03-20 12:08:25'),
(10, 10, 7, 1, '2026-03-20 12:17:43');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `avatar` varchar(255) DEFAULT 'default-avatar.png',
  `status` enum('offline','online','idle','dnd') DEFAULT 'offline',
  `role_id` int(11) DEFAULT 3,
  `theme` enum('dark','light') DEFAULT 'dark',
  `last_seen` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `avatar`, `status`, `role_id`, `theme`, `last_seen`, `created_at`) VALUES
(1, 'admin', 'admin@discordclone.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'default-avatar.png', 'online', 1, 'dark', NULL, '2026-03-20 11:38:50'),
(2, 'kralbey', 'kralbey@gmail.com', '$2y$10$RDNq6eQ2pLhyxoutspbE6e2iP8xMa0uIJnqSRk65EolNkqHZYQkGe', 'default-avatar.png', 'offline', 3, 'dark', '2026-03-20 11:55:48', '2026-03-20 11:39:22'),
(3, 'ENES', 'ENES@xn--gmail-bgd.com', '$2y$10$pXQ945OyXR2NkFun13tar.RsiQp7xMOZau1TiQ0FRjpi8PKCIIABC', 'default-avatar.png', 'offline', 3, 'dark', '2026-03-20 12:00:35', '2026-03-20 11:40:00'),
(4, 'adam', 'adam@gmail.com', '$2y$10$Ww4VVoKxsD/mmU5WG.frFuy1.8X.8qv8Jdw9RglIsinYrnGJS4PBm', 'default-avatar.png', 'offline', 3, 'dark', '2026-03-20 12:07:27', '2026-03-20 11:56:02'),
(5, 'asas', 'asas@gmail.com', '$2y$10$/EQNXhfXytgwK06KneYO0uJm1Sqw9HEiZ4PdghIlxGiArAtU5K9P2', 'default-avatar.png', 'offline', 3, 'dark', '2026-03-20 12:00:50', '2026-03-20 12:00:49'),
(6, 'messi', 'messi@gmail.com', '$2y$10$zkAhvb/nVhtD0IP8AP22SuwZxRn7FFN9Enenp5/PEiYopIEKuV8j6', 'default-avatar.png', 'offline', 3, 'dark', '2026-03-20 12:16:53', '2026-03-20 12:07:35'),
(7, 'arar', 'arar@g.com', '$2y$10$q/PY1yPJF.RMLxr/ixtGtOTbbPiFpfoSKFjBhoxbmy5p6tTia6Tge', 'default-avatar.png', 'offline', 3, 'dark', '2026-03-20 12:17:06', '2026-03-20 12:17:03');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `user_settings`
--

CREATE TABLE `user_settings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `notification_sound` tinyint(1) DEFAULT 1,
  `desktop_notifications` tinyint(1) DEFAULT 1,
  `show_email` tinyint(1) DEFAULT 0,
  `language` varchar(10) DEFAULT 'tr'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `user_settings`
--

INSERT INTO `user_settings` (`id`, `user_id`, `notification_sound`, `desktop_notifications`, `show_email`, `language`) VALUES
(1, 2, 1, 1, 0, 'tr'),
(2, 3, 1, 1, 0, 'tr'),
(3, 4, 1, 1, 0, 'tr'),
(4, 5, 1, 1, 0, 'tr'),
(5, 6, 1, 1, 0, 'tr'),
(6, 7, 1, 1, 0, 'tr');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `voice_participants`
--

CREATE TABLE `voice_participants` (
  `id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `is_video_on` tinyint(1) DEFAULT 0,
  `is_audio_on` tinyint(1) DEFAULT 1,
  `is_screen_sharing` tinyint(1) DEFAULT 0,
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `voice_rooms`
--

CREATE TABLE `voice_rooms` (
  `id` int(11) NOT NULL,
  `channel_id` int(11) NOT NULL,
  `room_code` varchar(50) NOT NULL,
  `status` enum('active','ended') DEFAULT 'active',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `ended_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dökümü yapılmış tablolar için indeksler
--

--
-- Tablo için indeksler `channels`
--
ALTER TABLE `channels`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_channels_server` (`server_id`);

--
-- Tablo için indeksler `channel_friends`
--
ALTER TABLE `channel_friends`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_channel_friend` (`channel_id`,`user_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `invited_by` (`invited_by`);

--
-- Tablo için indeksler `channel_invites`
--
ALTER TABLE `channel_invites`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invite_code` (`invite_code`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_invite_code` (`invite_code`),
  ADD KEY `idx_channel` (`channel_id`);

--
-- Tablo için indeksler `channel_invite_uses`
--
ALTER TABLE `channel_invite_uses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_invite` (`invite_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Tablo için indeksler `channel_members`
--
ALTER TABLE `channel_members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_channel_member` (`channel_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Tablo için indeksler `friendships`
--
ALTER TABLE `friendships`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_friendship` (`requester_id`,`addressee_id`),
  ADD KEY `addressee_id` (`addressee_id`),
  ADD KEY `idx_friendships_status` (`requester_id`,`addressee_id`,`status`);

--
-- Tablo için indeksler `live_streams`
--
ALTER TABLE `live_streams`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `stream_key` (`stream_key`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `channel_id` (`channel_id`);

--
-- Tablo için indeksler `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reply_to` (`reply_to`),
  ADD KEY `idx_messages_channel` (`channel_id`),
  ADD KEY `idx_messages_user` (`user_id`),
  ADD KEY `idx_messages_created` (`created_at`);

--
-- Tablo için indeksler `message_reactions`
--
ALTER TABLE `message_reactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_reaction` (`message_id`,`user_id`,`emoji`),
  ADD KEY `user_id` (`user_id`);

--
-- Tablo için indeksler `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `idx_notifications_user` (`user_id`,`is_read`);

--
-- Tablo için indeksler `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Tablo için indeksler `servers`
--
ALTER TABLE `servers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `owner_id` (`owner_id`);

--
-- Tablo için indeksler `server_members`
--
ALTER TABLE `server_members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_member` (`server_id`,`user_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `role_id` (`role_id`);

--
-- Tablo için indeksler `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `role_id` (`role_id`);

--
-- Tablo için indeksler `user_settings`
--
ALTER TABLE `user_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Tablo için indeksler `voice_participants`
--
ALTER TABLE `voice_participants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_participant` (`room_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Tablo için indeksler `voice_rooms`
--
ALTER TABLE `voice_rooms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `room_code` (`room_code`),
  ADD KEY `channel_id` (`channel_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Dökümü yapılmış tablolar için AUTO_INCREMENT değeri
--

--
-- Tablo için AUTO_INCREMENT değeri `channels`
--
ALTER TABLE `channels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- Tablo için AUTO_INCREMENT değeri `channel_friends`
--
ALTER TABLE `channel_friends`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `channel_invites`
--
ALTER TABLE `channel_invites`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `channel_invite_uses`
--
ALTER TABLE `channel_invite_uses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `channel_members`
--
ALTER TABLE `channel_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `friendships`
--
ALTER TABLE `friendships`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Tablo için AUTO_INCREMENT değeri `live_streams`
--
ALTER TABLE `live_streams`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- Tablo için AUTO_INCREMENT değeri `message_reactions`
--
ALTER TABLE `message_reactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Tablo için AUTO_INCREMENT değeri `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Tablo için AUTO_INCREMENT değeri `servers`
--
ALTER TABLE `servers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Tablo için AUTO_INCREMENT değeri `server_members`
--
ALTER TABLE `server_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Tablo için AUTO_INCREMENT değeri `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Tablo için AUTO_INCREMENT değeri `user_settings`
--
ALTER TABLE `user_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Tablo için AUTO_INCREMENT değeri `voice_participants`
--
ALTER TABLE `voice_participants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `voice_rooms`
--
ALTER TABLE `voice_rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Dökümü yapılmış tablolar için kısıtlamalar
--

--
-- Tablo kısıtlamaları `channels`
--
ALTER TABLE `channels`
  ADD CONSTRAINT `channels_ibfk_1` FOREIGN KEY (`server_id`) REFERENCES `servers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `channels_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `channel_friends`
--
ALTER TABLE `channel_friends`
  ADD CONSTRAINT `channel_friends_ibfk_1` FOREIGN KEY (`channel_id`) REFERENCES `channels` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `channel_friends_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `channel_friends_ibfk_3` FOREIGN KEY (`invited_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `channel_invites`
--
ALTER TABLE `channel_invites`
  ADD CONSTRAINT `channel_invites_ibfk_1` FOREIGN KEY (`channel_id`) REFERENCES `channels` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `channel_invites_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `channel_invite_uses`
--
ALTER TABLE `channel_invite_uses`
  ADD CONSTRAINT `channel_invite_uses_ibfk_1` FOREIGN KEY (`invite_id`) REFERENCES `channel_invites` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `channel_invite_uses_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `channel_members`
--
ALTER TABLE `channel_members`
  ADD CONSTRAINT `channel_members_ibfk_1` FOREIGN KEY (`channel_id`) REFERENCES `channels` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `channel_members_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `friendships`
--
ALTER TABLE `friendships`
  ADD CONSTRAINT `friendships_ibfk_1` FOREIGN KEY (`requester_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `friendships_ibfk_2` FOREIGN KEY (`addressee_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `live_streams`
--
ALTER TABLE `live_streams`
  ADD CONSTRAINT `live_streams_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `live_streams_ibfk_2` FOREIGN KEY (`channel_id`) REFERENCES `channels` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`channel_id`) REFERENCES `channels` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_3` FOREIGN KEY (`reply_to`) REFERENCES `messages` (`id`) ON DELETE SET NULL;

--
-- Tablo kısıtlamaları `message_reactions`
--
ALTER TABLE `message_reactions`
  ADD CONSTRAINT `message_reactions_ibfk_1` FOREIGN KEY (`message_id`) REFERENCES `messages` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `message_reactions_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Tablo kısıtlamaları `servers`
--
ALTER TABLE `servers`
  ADD CONSTRAINT `servers_ibfk_1` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `server_members`
--
ALTER TABLE `server_members`
  ADD CONSTRAINT `server_members_ibfk_1` FOREIGN KEY (`server_id`) REFERENCES `servers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `server_members_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `server_members_ibfk_3` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE SET NULL;

--
-- Tablo kısıtlamaları `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE SET NULL;

--
-- Tablo kısıtlamaları `user_settings`
--
ALTER TABLE `user_settings`
  ADD CONSTRAINT `user_settings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `voice_participants`
--
ALTER TABLE `voice_participants`
  ADD CONSTRAINT `voice_participants_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `voice_rooms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `voice_participants_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `voice_rooms`
--
ALTER TABLE `voice_rooms`
  ADD CONSTRAINT `voice_rooms_ibfk_1` FOREIGN KEY (`channel_id`) REFERENCES `channels` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `voice_rooms_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
