-- Discord Clone Veritabanı Şeması
-- XAMPP/MySQL için

CREATE DATABASE IF NOT EXISTS discord_clone CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE discord_clone;

-- Roller Tablosu
CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(50) NOT NULL UNIQUE,
    permissions JSON NOT NULL,
    color VARCHAR(7) DEFAULT '#7289da',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Kullanıcılar Tablosu
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    avatar VARCHAR(255) DEFAULT 'default-avatar.png',
    status ENUM('offline', 'online', 'idle', 'dnd') DEFAULT 'offline',
    role_id INT DEFAULT 3,
    theme ENUM('dark', 'light') DEFAULT 'dark',
    last_seen TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE SET NULL
);

-- Sunucular Tablosu
CREATE TABLE servers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    icon VARCHAR(255) DEFAULT NULL,
    owner_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Sunucu Üyeleri Tablosu
CREATE TABLE server_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    server_id INT NOT NULL,
    user_id INT NOT NULL,
    role_id INT DEFAULT 3,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (server_id) REFERENCES servers(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE SET NULL,
    UNIQUE KEY unique_member (server_id, user_id)
);

-- Kanallar Tablosu
CREATE TABLE channels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    server_id INT DEFAULT NULL,
    name VARCHAR(100) NOT NULL,
    type ENUM('text', 'voice', 'video') DEFAULT 'text',
    position INT DEFAULT 0,
    is_private BOOLEAN DEFAULT FALSE,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (server_id) REFERENCES servers(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Kanal Üyeleri Tablosu (Özel Kanallar için)
CREATE TABLE channel_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    channel_id INT NOT NULL,
    user_id INT NOT NULL,
    role ENUM('admin', 'member', 'guest') DEFAULT 'member',
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (channel_id) REFERENCES channels(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_channel_member (channel_id, user_id)
);

-- Mesajlar Tablosu
CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    channel_id INT NOT NULL,
    message TEXT NOT NULL,
    media_url VARCHAR(255) DEFAULT NULL,
    media_type ENUM('image', 'video', 'audio', 'file') DEFAULT NULL,
    reply_to INT DEFAULT NULL,
    is_edited BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (channel_id) REFERENCES channels(id) ON DELETE CASCADE,
    FOREIGN KEY (reply_to) REFERENCES messages(id) ON DELETE SET NULL
);

-- Mesaj Tepkileri Tablosu
CREATE TABLE message_reactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    message_id INT NOT NULL,
    user_id INT NOT NULL,
    emoji VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_reaction (message_id, user_id, emoji)
);

-- Bildirimler Tablosu
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    sender_id INT DEFAULT NULL,
    notification_type ENUM('message', 'mention', 'call', 'channel_join', 'friend_request') NOT NULL,
    content TEXT NOT NULL,
    related_id INT DEFAULT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Arkadaşlık Sistemi
CREATE TABLE friendships (
    id INT AUTO_INCREMENT PRIMARY KEY,
    requester_id INT NOT NULL,
    addressee_id INT NOT NULL,
    status ENUM('pending', 'accepted', 'blocked') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (requester_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (addressee_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_friendship (requester_id, addressee_id)
);

-- Sesli/Görüntülü Odaları
CREATE TABLE voice_rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    channel_id INT NOT NULL,
    room_code VARCHAR(50) NOT NULL UNIQUE,
    status ENUM('active', 'ended') DEFAULT 'active',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ended_at TIMESTAMP NULL,
    FOREIGN KEY (channel_id) REFERENCES channels(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Odadaki Katılımcılar
CREATE TABLE voice_participants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    user_id INT NOT NULL,
    is_video_on BOOLEAN DEFAULT FALSE,
    is_audio_on BOOLEAN DEFAULT TRUE,
    is_screen_sharing BOOLEAN DEFAULT FALSE,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES voice_rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_participant (room_id, user_id)
);

-- Yayınlar (Live Streams)
CREATE TABLE live_streams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    channel_id INT NOT NULL,
    stream_title VARCHAR(255) DEFAULT 'Canlı Yayın',
    stream_key VARCHAR(100) NOT NULL UNIQUE,
    status ENUM('live', 'ended', 'paused') DEFAULT 'live',
    viewer_count INT DEFAULT 0,
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ended_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (channel_id) REFERENCES channels(id) ON DELETE CASCADE
);

-- Kullanıcı Ayarları
CREATE TABLE user_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    notification_sound BOOLEAN DEFAULT TRUE,
    desktop_notifications BOOLEAN DEFAULT TRUE,
    show_email BOOLEAN DEFAULT FALSE,
    language VARCHAR(10) DEFAULT 'tr',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Varsayılan Rolleri Ekle
INSERT INTO roles (role_name, permissions, color) VALUES
('Admin', '{"create_channel": true, "delete_channel": true, "edit_channel": true, "delete_message": true, "ban_user": true, "kick_user": true, "manage_roles": true, "manage_server": true, "voice_chat": true, "video_chat": true, "screen_share": true}', '#e74c3c'),
('Moderator', '{"create_channel": false, "delete_channel": false, "edit_channel": false, "delete_message": true, "ban_user": false, "kick_user": true, "manage_roles": false, "manage_server": false, "voice_chat": true, "video_chat": true, "screen_share": true}', '#f39c12'),
('Member', '{"create_channel": false, "delete_channel": false, "edit_channel": false, "delete_message": false, "ban_user": false, "kick_user": false, "manage_roles": false, "manage_server": false, "voice_chat": true, "video_chat": true, "screen_share": false}', '#7289da'),
('Guest', '{"create_channel": false, "delete_channel": false, "edit_channel": false, "delete_message": false, "ban_user": false, "kick_user": false, "manage_roles": false, "manage_server": false, "voice_chat": false, "video_chat": false, "screen_share": false}', '#95a5a6');

-- Varsayılan Admin Kullanıcısı (şifre: admin123)
INSERT INTO users (username, email, password, role_id, status) VALUES
('admin', 'admin@discordclone.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 'online');

-- Örnek Sunucu
INSERT INTO servers (name, owner_id) VALUES
('Genel Sunucu', 1);

-- Örnek Kanallar
INSERT INTO channels (server_id, name, type, created_by) VALUES
(1, 'genel-sohbet', 'text', 1),
(1, 'muzik', 'text', 1),
(1, 'genel-ses', 'voice', 1),
(1, 'görüntülü-görüşme', 'video', 1);

-- Sunucu üyesi olarak admin ekle
INSERT INTO server_members (server_id, user_id, role_id) VALUES
(1, 1, 1);

-- İndeksler
CREATE INDEX idx_messages_channel ON messages(channel_id);
CREATE INDEX idx_messages_user ON messages(user_id);
CREATE INDEX idx_messages_created ON messages(created_at);
CREATE INDEX idx_channels_server ON channels(server_id);
CREATE INDEX idx_notifications_user ON notifications(user_id, is_read);
CREATE INDEX idx_friendships_status ON friendships(requester_id, addressee_id, status);
