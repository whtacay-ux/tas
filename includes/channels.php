<?php
// Discord Clone - Kanal İşlemleri
require_once 'config.php';

// Sunucu oluştur
function createServer($name, $ownerId) {
    $db = getDB();
    
    try {
        $db->beginTransaction();
        
        // Sunucuyu oluştur
        $stmt = $db->prepare("INSERT INTO servers (name, owner_id, created_at) VALUES (?, ?, NOW())");
        $stmt->execute([$name, $ownerId]);
        $serverId = $db->lastInsertId();
        
        // Sahibi admin olarak ekle
        $stmt = $db->prepare("INSERT INTO server_members (server_id, user_id, role_id) VALUES (?, ?, 1)");
        $stmt->execute([$serverId, $ownerId]);
        
        // Varsayılan kanalları oluştur
        $defaultChannels = [
            ['genel-sohbet', 'text'],
            ['muzik', 'text'],
            ['genel-ses', 'voice'],
            ['görüntülü-görüşme', 'video']
        ];
        
        foreach ($defaultChannels as $index => $channel) {
            $stmt = $db->prepare("INSERT INTO channels (server_id, name, type, position, created_by, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$serverId, $channel[0], $channel[1], $index, $ownerId]);
        }
        
        $db->commit();
        return ['success' => true, 'server_id' => $serverId];
        
    } catch (Exception $e) {
        $db->rollBack();
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// Kanal oluştur
function createChannel($serverId, $name, $type, $createdBy) {
    $db = getDB();
    
    // Yetki kontrolü
    if (!isServerOwner($serverId) && !isModerator()) {
        return ['success' => false, 'error' => 'Yetkiniz yok'];
    }
    
    try {
        // Son pozisyonu bul
        $stmt = $db->prepare("SELECT MAX(position) as max_pos FROM channels WHERE server_id = ? AND type = ?");
        $stmt->execute([$serverId, $type]);
        $result = $stmt->fetch();
        $position = ($result['max_pos'] ?? -1) + 1;
        
        // Kanalı oluştur
        $stmt = $db->prepare("INSERT INTO channels (server_id, name, type, position, created_by, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$serverId, $name, $type, $position, $createdBy]);
        $channelId = $db->lastInsertId();
        
        return ['success' => true, 'channel_id' => $channelId];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// Kanal sil
function deleteChannel($channelId) {
    $db = getDB();
    
    $channel = getChannelById($channelId);
    if (!$channel) {
        return ['success' => false, 'error' => 'Kanal bulunamadı'];
    }
    
    // Yetki kontrolü
    if (!isServerOwner($channel['server_id']) && !isAdmin()) {
        return ['success' => false, 'error' => 'Yetkiniz yok'];
    }
    
    try {
        $stmt = $db->prepare("DELETE FROM channels WHERE id = ?");
        $stmt->execute([$channelId]);
        return ['success' => true];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// Sunucu davet linki oluştur
function createServerInvite($serverId, $createdBy, $maxUses = 0, $expiresAt = null) {
    $db = getDB();
    
    // Yetki kontrolü
    if (!isServerOwner($serverId) && !hasChannelPermission(null, 'manage_server')) {
        return ['success' => false, 'error' => 'Yetkiniz yok'];
    }
    
    try {
        $inviteCode = generateInviteCode();
        
        $stmt = $db->prepare("INSERT INTO server_invites (server_id, invite_code, created_by, max_uses, expires_at, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$serverId, $inviteCode, $createdBy, $maxUses, $expiresAt]);
        
        return ['success' => true, 'invite_code' => $inviteCode];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// Sunucu davet linkini doğrula
function validateServerInvite($inviteCode) {
    $db = getDB();
    
    $stmt = $db->prepare("SELECT * FROM server_invites WHERE invite_code = ? AND is_active = 1");
    $stmt->execute([$inviteCode]);
    $invite = $stmt->fetch();
    
    if (!$invite) {
        return ['success' => false, 'error' => 'Geçersiz davet linki'];
    }
    
    // Süre kontrolü
    if ($invite['expires_at'] && strtotime($invite['expires_at']) < time()) {
        return ['success' => false, 'error' => 'Davet linkinin süresi dolmuş'];
    }
    
    // Kullanım limiti kontrolü
    if ($invite['max_uses'] > 0 && $invite['used_count'] >= $invite['max_uses']) {
        return ['success' => false, 'error' => 'Davet linki kullanım limitine ulaşmış'];
    }
    
    return ['success' => true, 'invite' => $invite];
}

// Davet linki ile sunucuya katıl
function joinServerWithInvite($inviteCode, $userId) {
    $db = getDB();
    
    $validation = validateServerInvite($inviteCode);
    if (!$validation['success']) {
        return $validation;
    }
    
    $invite = $validation['invite'];
    
    // Zaten üye mi kontrol et
    $stmt = $db->prepare("SELECT id FROM server_members WHERE server_id = ? AND user_id = ?");
    $stmt->execute([$invite['server_id'], $userId]);
    if ($stmt->fetch()) {
        return ['success' => false, 'error' => 'Zaten bu sunucunun üyesisiniz'];
    }
    
    try {
        $db->beginTransaction();
        
        // Üye olarak ekle (Member rolü = 3)
        $stmt = $db->prepare("INSERT INTO server_members (server_id, user_id, role_id) VALUES (?, ?, 3)");
        $stmt->execute([$invite['server_id'], $userId]);
        
        // Kullanım sayısını artır
        $stmt = $db->prepare("UPDATE server_invites SET used_count = used_count + 1 WHERE id = ?");
        $stmt->execute([$invite['id']]);
        
        // Kullanım kaydı ekle
        $stmt = $db->prepare("INSERT INTO server_invite_uses (invite_id, user_id) VALUES (?, ?)");
        $stmt->execute([$invite['id'], $userId]);
        
        $db->commit();
        
        return ['success' => true, 'server_id' => $invite['server_id']];
        
    } catch (Exception $e) {
        $db->rollBack();
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// Sunucu davet linklerini getir
function getServerInvites($serverId) {
    $db = getDB();
    
    $stmt = $db->prepare("SELECT i.*, u.username as created_by_name,
                          (SELECT COUNT(*) FROM server_invite_uses WHERE invite_id = i.id) as actual_uses
                          FROM server_invites i 
                          JOIN users u ON i.created_by = u.id 
                          WHERE i.server_id = ? AND i.is_active = 1 
                          ORDER BY i.created_at DESC");
    $stmt->execute([$serverId]);
    return $stmt->fetchAll();
}

// Sunucu davet linkini sil
function deleteServerInvite($inviteId) {
    $db = getDB();
    
    $stmt = $db->prepare("SELECT server_id FROM server_invites WHERE id = ?");
    $stmt->execute([$inviteId]);
    $invite = $stmt->fetch();
    
    if (!$invite) {
        return ['success' => false, 'error' => 'Davet linki bulunamadı'];
    }
    
    // Yetki kontrolü
    if (!isServerOwner($invite['server_id']) && !isAdmin()) {
        return ['success' => false, 'error' => 'Yetkiniz yok'];
    }
    
    try {
        $stmt = $db->prepare("UPDATE server_invites SET is_active = 0 WHERE id = ?");
        $stmt->execute([$inviteId]);
        return ['success' => true];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// Kullanıcı rolünü güncelle (sunucu sahibi veya admin)
function updateUserRole($serverId, $userId, $newRoleId, $updatedBy) {
    $db = getDB();
    
    // Yetki kontrolü - sadece sunucu sahibi veya admin rol atayabilir
    $updater = getCurrentUser();
    if (!$updater) {
        return ['success' => false, 'error' => 'Giriş gerekli'];
    }
    
    $isOwner = isServerOwner($serverId);
    $isAdminUser = ($updater['role_name'] === 'Admin');
    
    if (!$isOwner && !$isAdminUser) {
        return ['success' => false, 'error' => 'Rol atama yetkiniz yok'];
    }
    
    // Kendini düşürme kontrolü
    if ($userId == $updater['id'] && $isOwner) {
        return ['success' => false, 'error' => 'Kendi rolünüzü düşüremezsiniz'];
    }
    
    // Hedef kullanıcının mevcut rolünü kontrol et
    $stmt = $db->prepare("SELECT sm.*, r.role_name FROM server_members sm 
                          JOIN roles r ON sm.role_id = r.id 
                          WHERE sm.server_id = ? AND sm.user_id = ?");
    $stmt->execute([$serverId, $userId]);
    $targetMember = $stmt->fetch();
    
    if (!$targetMember) {
        return ['success' => false, 'error' => 'Kullanıcı sunucuda bulunamadı'];
    }
    
    // Başka bir adminin rolünü değiştirme kontrolü
    if ($targetMember['role_name'] === 'Admin' && !$isOwner) {
        return ['success' => false, 'error' => 'Başka bir adminin rolünü değiştiremezsiniz'];
    }
    
    // Yeni rolü kontrol et
    $newRole = getRoleById($newRoleId);
    if (!$newRole) {
        return ['success' => false, 'error' => 'Geçersiz rol'];
    }
    
    // Admin atama yetkisi kontrolü (sadece sahibi admin atayabilir)
    if ($newRole['role_name'] === 'Admin' && !$isOwner) {
        return ['success' => false, 'error' => 'Sadece sunucu sahibi admin atayabilir'];
    }
    
    try {
        $stmt = $db->prepare("UPDATE server_members SET role_id = ? WHERE server_id = ? AND user_id = ?");
        $stmt->execute([$newRoleId, $serverId, $userId]);
        
        // Kullanıcı tablosundaki rolü de güncelle (aktif sunucu rolü)
        $stmt = $db->prepare("UPDATE users SET role_id = ? WHERE id = ?");
        $stmt->execute([$newRoleId, $userId]);
        
        return ['success' => true, 'message' => "Kullanıcının rolü '{$newRole['role_name']}' olarak güncellendi"];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// Sunucudan kullanıcı at
function kickUserFromServer($serverId, $userId, $kickedBy) {
    $db = getDB();
    
    // Yetki kontrolü
    if (!isServerOwner($serverId) && !hasChannelPermission(null, 'kick_user')) {
        return ['success' => false, 'error' => 'Yetkiniz yok'];
    }
    
    // Kendini atma kontrolü
    if ($userId == $kickedBy) {
        return ['success' => false, 'error' => 'Kendinizi atamazsınız'];
    }
    
    // Hedef kullanıcı admin mi?
    $stmt = $db->prepare("SELECT r.role_name FROM server_members sm 
                          JOIN roles r ON sm.role_id = r.id 
                          WHERE sm.server_id = ? AND sm.user_id = ?");
    $stmt->execute([$serverId, $userId]);
    $target = $stmt->fetch();
    
    if ($target && $target['role_name'] === 'Admin' && !isServerOwner($serverId)) {
        return ['success' => false, 'error' => 'Admin kullanıcıyı atamazsınız'];
    }
    
    try {
        $stmt = $db->prepare("DELETE FROM server_members WHERE server_id = ? AND user_id = ?");
        $stmt->execute([$serverId, $userId]);
        
        return ['success' => true];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// Sunucudan kullanıcı yasakla
function banUserFromServer($serverId, $userId, $bannedBy, $reason = '') {
    $db = getDB();
    
    // Yetki kontrolü
    if (!isServerOwner($serverId) && !hasChannelPermission(null, 'ban_user')) {
        return ['success' => false, 'error' => 'Yetkiniz yok'];
    }
    
    // Kendini yasaklama kontrolü
    if ($userId == $bannedBy) {
        return ['success' => false, 'error' => 'Kendinizi yasaklayamazsınız'];
    }
    
    try {
        $db->beginTransaction();
        
        // Yasaklama kaydı ekle
        $stmt = $db->prepare("INSERT INTO server_bans (server_id, user_id, banned_by, reason, banned_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$serverId, $userId, $bannedBy, $reason]);
        
        // Üyelikten çıkar
        $stmt = $db->prepare("DELETE FROM server_members WHERE server_id = ? AND user_id = ?");
        $stmt->execute([$serverId, $userId]);
        
        $db->commit();
        
        return ['success' => true];
    } catch (Exception $e) {
        $db->rollBack();
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// Sesli odada aktif kullanıcıları getir
function getVoiceChannelParticipants($channelId) {
    $db = getDB();
    
    $stmt = $db->prepare("SELECT vp.*, u.username, u.avatar, r.color as role_color
                          FROM voice_participants vp 
                          JOIN users u ON vp.user_id = u.id 
                          LEFT JOIN roles r ON u.role_id = r.id 
                          JOIN voice_rooms vr ON vp.room_id = vr.id 
                          WHERE vr.channel_id = ? AND vr.status = 'active'");
    $stmt->execute([$channelId]);
    return $stmt->fetchAll();
}

// Sesli oda oluştur veya getir
function getOrCreateVoiceRoom($channelId, $userId) {
    $db = getDB();
    
    // Mevcut aktif odayı kontrol et
    $stmt = $db->prepare("SELECT * FROM voice_rooms WHERE channel_id = ? AND status = 'active'");
    $stmt->execute([$channelId]);
    $room = $stmt->fetch();
    
    if ($room) {
        return $room;
    }
    
    // Yeni oda oluştur
    $roomCode = generateRoomCode();
    $stmt = $db->prepare("INSERT INTO voice_rooms (channel_id, room_code, created_by, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$channelId, $roomCode, $userId]);
    
    return ['id' => $db->lastInsertId(), 'room_code' => $roomCode, 'channel_id' => $channelId];
}

// Sesli odaya katıl
function joinVoiceRoom($roomId, $userId) {
    $db = getDB();
    
    // Zaten katılmış mı kontrol et
    $stmt = $db->prepare("SELECT id FROM voice_participants WHERE room_id = ? AND user_id = ?");
    $stmt->execute([$roomId, $userId]);
    if ($stmt->fetch()) {
        return ['success' => true];
    }
    
    try {
        $stmt = $db->prepare("INSERT INTO voice_participants (room_id, user_id, joined_at) VALUES (?, ?, NOW())");
        $stmt->execute([$roomId, $userId]);
        return ['success' => true];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// Sesli odadan ayrıl
function leaveVoiceRoom($roomId, $userId) {
    $db = getDB();
    
    try {
        $stmt = $db->prepare("DELETE FROM voice_participants WHERE room_id = ? AND user_id = ?");
        $stmt->execute([$roomId, $userId]);
        
        // Odada kimse kalmadıysa odayı kapat
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM voice_participants WHERE room_id = ?");
        $stmt->execute([$roomId]);
        $result = $stmt->fetch();
        
        if ($result['count'] == 0) {
            $stmt = $db->prepare("UPDATE voice_rooms SET status = 'ended', ended_at = NOW() WHERE id = ?");
            $stmt->execute([$roomId]);
        }
        
        return ['success' => true];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// Kullanıcı ses durumunu güncelle
function updateVoiceStatus($roomId, $userId, $isAudioOn, $isVideoOn, $isScreenSharing) {
    $db = getDB();
    
    try {
        $stmt = $db->prepare("UPDATE voice_participants 
                              SET is_audio_on = ?, is_video_on = ?, is_screen_sharing = ? 
                              WHERE room_id = ? AND user_id = ?");
        $stmt->execute([$isAudioOn ? 1 : 0, $isVideoOn ? 1 : 0, $isScreenSharing ? 1 : 0, $roomId, $userId]);
        return ['success' => true];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}
