-- Discord Clone - Veritabanı Şeması
-- Versiyon: 3.0 - WebRTC ve Rol Yönetimi Destekli

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Tabloları temizle
DROP TABLE IF EXISTS `voice_participants`;
DROP TABLE IF EXISTS `voice_rooms`;
DROP TABLE IF EXISTS `server_invite_uses`;
DROP TABLE IF EXISTS `server_invites`;
DROP TABLE IF EXISTS `server_bans`;
DROP TABLE IF EXISTS `channel_invite_uses`;
DROP TABLE IF EXISTS `channel_invites`;
DROP TABLE IF EXISTS `channel_friends`;
DROP TABLE IF EXISTS `channel_members`;
DROP TABLE IF EXISTS `message_reactions`;
DROP TABLE IF EXISTS `messages`;
DROP TABLE IF EXISTS `channels`;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `friendships`;
DROP TABLE IF EXISTS `server_members`;
DROP TABLE IF EXISTS `servers`;
DROP TABLE IF EXISTS `user_settings`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `roles`;
DROP TABLE IF EXISTS `live_streams`;

SET FOREIGN_KEY_CHECKS = 1;

-- Roller Tablosu
CREATE TABLE `roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_name` varchar(50) NOT NULL,
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`permissions`)),
  `color` varchar(7) DEFAULT '#7289da',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_name` (`role_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Varsayılan Rolleri Ekle
INSERT INTO `roles` (`id`, `role_name`, `permissions`, `color`) VALUES
(1, 'Admin', '{"create_channel": true, "delete_channel": true, "edit_channel": true, "delete_message": true, "ban_user": true, "kick_user": true, "manage_roles": true, "manage_server": true, "voice_chat": true, "video_chat": true, "screen_share": true}', '#e74c3c'),
(2, 'Moderator', '{"create_channel": false, "delete_channel": false, "edit_channel": false, "delete_message": true, "ban_user": false, "kick_user": true, "manage_roles": false, "manage_server": false, "voice_chat": true, "video_chat": true, "screen_share": true}', '#f39c12'),
(3, 'Member', '{"create_channel": false, "delete_channel": false, "edit_channel": false, "delete_message": false, "ban_user": false, "kick_user": false, "manage_roles": false, "manage_server": false, "voice_chat": true, "video_chat": true, "screen_share": false}', '#7289da'),
(4, 'Guest', '{"create_channel": false, "delete_channel": false, "edit_channel": false, "delete_message": false, "ban_user": false, "kick_user": false, "manage_roles": false, "manage_server": false, "voice_chat": false, "video_chat": false, "screen_share": false}', '#95a5a6');

-- Kullanıcılar Tablosu
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `avatar` varchar(255) DEFAULT 'default-avatar.png',
  `status` enum('offline','online','idle','dnd') DEFAULT 'offline',
  `role_id` int(11) DEFAULT 3,
  `theme` enum('dark','light') DEFAULT 'dark',
  `remember_token` varchar(255) DEFAULT NULL,
  `last_seen` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `role_id` (`role_id`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Kullanıcı Ayarları Tablosu
CREATE TABLE `user_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `notification_sound` tinyint(1) DEFAULT 1,
  `desktop_notifications` tinyint(1) DEFAULT 1,
  `show_email` tinyint(1) DEFAULT 0,
  `language` varchar(10) DEFAULT 'tr',
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  CONSTRAINT `user_settings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Sunucular Tablosu
CREATE TABLE `servers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `owner_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `owner_id` (`owner_id`),
  CONSTRAINT `servers_ibfk_1` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Sunucu Üyeleri Tablosu
CREATE TABLE `server_members` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `server_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role_id` int(11) DEFAULT 3,
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_member` (`server_id`,`user_id`),
  KEY `user_id` (`user_id`),
  KEY `role_id` (`role_id`),
  CONSTRAINT `server_members_ibfk_1` FOREIGN KEY (`server_id`) REFERENCES `servers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `server_members_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `server_members_ibfk_3` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Sunucu Davetleri Tablosu
CREATE TABLE `server_invites` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `server_id` int(11) NOT NULL,
  `invite_code` varchar(20) NOT NULL,
  `created_by` int(11) NOT NULL,
  `max_uses` int(11) DEFAULT 0,
  `used_count` int(11) DEFAULT 0,
  `expires_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `invite_code` (`invite_code`),
  KEY `server_id` (`server_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `server_invites_ibfk_1` FOREIGN KEY (`server_id`) REFERENCES `servers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `server_invites_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Sunucu Davet Kullanımları Tablosu
CREATE TABLE `server_invite_uses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `invite_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `used_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_invite` (`invite_id`,`user_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `server_invite_uses_ibfk_1` FOREIGN KEY (`invite_id`) REFERENCES `server_invites` (`id`) ON DELETE CASCADE,
  CONSTRAINT `server_invite_uses_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Sunucu Yasaklamaları Tablosu
CREATE TABLE `server_bans` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `server_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `banned_by` int(11) NOT NULL,
  `reason` text DEFAULT NULL,
  `banned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `server_id` (`server_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `server_bans_ibfk_1` FOREIGN KEY (`server_id`) REFERENCES `servers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `server_bans_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Kanallar Tablosu
CREATE TABLE `channels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `server_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `type` enum('text','voice','video') DEFAULT 'text',
  `position` int(11) DEFAULT 0,
  `is_private` tinyint(1) DEFAULT 0,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `server_id` (`server_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `channels_ibfk_1` FOREIGN KEY (`server_id`) REFERENCES `servers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `channels_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Kanal Üyeleri Tablosu
CREATE TABLE `channel_members` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `channel_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role` enum('admin','member','guest') DEFAULT 'member',
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_channel_member` (`channel_id`,`user_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `channel_members_ibfk_1` FOREIGN KEY (`channel_id`) REFERENCES `channels` (`id`) ON DELETE CASCADE,
  CONSTRAINT `channel_members_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Kanal Davetleri Tablosu
CREATE TABLE `channel_invites` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `channel_id` int(11) NOT NULL,
  `invite_code` varchar(20) NOT NULL,
  `created_by` int(11) NOT NULL,
  `max_uses` int(11) DEFAULT 0,
  `used_count` int(11) DEFAULT 0,
  `expires_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `invite_code` (`invite_code`),
  KEY `channel_id` (`channel_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `channel_invites_ibfk_1` FOREIGN KEY (`channel_id`) REFERENCES `channels` (`id`) ON DELETE CASCADE,
  CONSTRAINT `channel_invites_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Kanal Davet Kullanımları Tablosu
CREATE TABLE `channel_invite_uses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `invite_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `used_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_invite` (`invite_id`,`user_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `channel_invite_uses_ibfk_1` FOREIGN KEY (`invite_id`) REFERENCES `channel_invites` (`id`) ON DELETE CASCADE,
  CONSTRAINT `channel_invite_uses_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Kanal Arkadaşları Tablosu
CREATE TABLE `channel_friends` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `channel_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `invited_by` int(11) NOT NULL,
  `status` enum('pending','accepted','left') DEFAULT 'pending',
  `invited_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_channel_friend` (`channel_id`,`user_id`),
  KEY `user_id` (`user_id`),
  KEY `invited_by` (`invited_by`),
  CONSTRAINT `channel_friends_ibfk_1` FOREIGN KEY (`channel_id`) REFERENCES `channels` (`id`) ON DELETE CASCADE,
  CONSTRAINT `channel_friends_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `channel_friends_ibfk_3` FOREIGN KEY (`invited_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Mesajlar Tablosu
CREATE TABLE `messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `channel_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `media_url` varchar(255) DEFAULT NULL,
  `media_type` enum('image','video','audio','file') DEFAULT NULL,
  `reply_to` int(11) DEFAULT NULL,
  `is_edited` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `reply_to` (`reply_to`),
  KEY `channel_id` (`channel_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`channel_id`) REFERENCES `channels` (`id`) ON DELETE CASCADE,
  CONSTRAINT `messages_ibfk_3` FOREIGN KEY (`reply_to`) REFERENCES `messages` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Mesaj Tepkileri Tablosu
CREATE TABLE `message_reactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `message_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `emoji` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_reaction` (`message_id`,`user_id`,`emoji`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `message_reactions_ibfk_1` FOREIGN KEY (`message_id`) REFERENCES `messages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `message_reactions_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Arkadaşlıklar Tablosu
CREATE TABLE `friendships` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `requester_id` int(11) NOT NULL,
  `addressee_id` int(11) NOT NULL,
  `status` enum('pending','accepted','blocked') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_friendship` (`requester_id`,`addressee_id`),
  KEY `addressee_id` (`addressee_id`),
  CONSTRAINT `friendships_ibfk_1` FOREIGN KEY (`requester_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `friendships_ibfk_2` FOREIGN KEY (`addressee_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Bildirimler Tablosu
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `sender_id` int(11) DEFAULT NULL,
  `notification_type` enum('message','mention','call','channel_join','friend_request') NOT NULL,
  `content` text NOT NULL,
  `related_id` int(11) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `sender_id` (`sender_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Sesli Odalar Tablosu
CREATE TABLE `voice_rooms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `channel_id` int(11) NOT NULL,
  `room_code` varchar(50) NOT NULL,
  `status` enum('active','ended') DEFAULT 'active',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `ended_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `room_code` (`room_code`),
  KEY `channel_id` (`channel_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `voice_rooms_ibfk_1` FOREIGN KEY (`channel_id`) REFERENCES `channels` (`id`) ON DELETE CASCADE,
  CONSTRAINT `voice_rooms_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Sesli Oda Katılımcıları Tablosu
CREATE TABLE `voice_participants` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `room_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `is_video_on` tinyint(1) DEFAULT 0,
  `is_audio_on` tinyint(1) DEFAULT 1,
  `is_screen_sharing` tinyint(1) DEFAULT 0,
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_participant` (`room_id`,`user_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `voice_participants_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `voice_rooms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `voice_participants_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Canlı Yayınlar Tablosu
CREATE TABLE `live_streams` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `channel_id` int(11) NOT NULL,
  `stream_title` varchar(255) DEFAULT 'Canlı Yayın',
  `stream_key` varchar(100) NOT NULL,
  `status` enum('live','ended','paused') DEFAULT 'live',
  `viewer_count` int(11) DEFAULT 0,
  `started_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `ended_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `stream_key` (`stream_key`),
  KEY `user_id` (`user_id`),
  KEY `channel_id` (`channel_id`),
  CONSTRAINT `live_streams_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `live_streams_ibfk_2` FOREIGN KEY (`channel_id`) REFERENCES `channels` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Varsayılan Admin Kullanıcısı
INSERT INTO `users` (`id`, `username`, `email`, `password`, `avatar`, `status`, `role_id`, `theme`, `created_at`) VALUES
(1, 'admin', 'admin@discordclone.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'default-avatar.png', 'online', 1, 'dark', NOW());

-- Varsayılan Sunucu
INSERT INTO `servers` (`id`, `name`, `icon`, `owner_id`, `created_at`) VALUES
(1, 'Genel Sunucu', NULL, 1, NOW());

-- Sunucu Sahibini Ekle
INSERT INTO `server_members` (`server_id`, `user_id`, `role_id`, `joined_at`) VALUES
(1, 1, 1, NOW());

-- Varsayılan Kanallar
INSERT INTO `channels` (`id`, `server_id`, `name`, `type`, `position`, `created_by`, `created_at`) VALUES
(1, 1, 'genel-sohbet', 'text', 0, 1, NOW()),
(2, 1, 'muzik', 'text', 1, 1, NOW()),
(3, 1, 'genel-ses', 'voice', 0, 1, NOW()),
(4, 1, 'görüntülü-görüşme', 'video', 1, 1, NOW());

-- Admin Kullanıcı Ayarları
INSERT INTO `user_settings` (`user_id`, `notification_sound`, `desktop_notifications`, `show_email`, `language`) VALUES
(1, 1, 1, 0, 'tr');
 PRIMARY KEY (`id`),
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
