<?php
// Discord Clone - Kanal ve Mesaj İşlemleri
require_once 'config.php';

// ========== SUNUCU İŞLEMLERİ ==========

function createServer($name, $ownerId, $icon = null) {
    $db = getDB();
    
    try {
        $stmt = $db->prepare("INSERT INTO servers (name, owner_id, icon) VALUES (?, ?, ?)");
        $stmt->execute([$name, $ownerId, $icon]);
        $serverId = $db->lastInsertId();
        
        $stmt = $db->prepare("INSERT INTO server_members (server_id, user_id, role_id) VALUES (?, ?, 1)");
        $stmt->execute([$serverId, $ownerId]);
        
        createChannel($serverId, 'genel-sohbet', $ownerId, 'text');
        createChannel($serverId, 'genel-ses', $ownerId, 'voice');
        
        return ['success' => true, 'server_id' => $serverId];
    } catch (PDOException $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function getUserServers($userId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT s.*, sm.role_id FROM servers s JOIN server_members sm ON s.id = sm.server_id WHERE sm.user_id = ? ORDER BY s.created_at DESC");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function getServerById($serverId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT s.*, u.username as owner_name FROM servers s JOIN users u ON s.owner_id = u.id WHERE s.id = ?");
    $stmt->execute([$serverId]);
    return $stmt->fetch();
}

function getServerMembers($serverId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT u.id, u.username, u.avatar, u.status, r.role_name, r.color FROM server_members sm JOIN users u ON sm.user_id = u.id JOIN roles r ON sm.role_id = r.id WHERE sm.server_id = ? ORDER BY r.id ASC, u.username ASC");
    $stmt->execute([$serverId]);
    return $stmt->fetchAll();
}

// ========== KANAL İŞLEMLERİ ==========

function createChannel($serverId, $name, $createdBy, $type = 'text', $isPrivate = false) {
    $db = getDB();
    
    // Sunucu var mı kontrol et
    $stmt = $db->prepare("SELECT id FROM servers WHERE id = ?");
    $stmt->execute([$serverId]);
    if (!$stmt->fetch()) {
        return ['success' => false, 'error' => 'Sunucu bulunamadı'];
    }
    
    // Kullanıcı var mı kontrol et
    $stmt = $db->prepare("SELECT id FROM users WHERE id = ?");
    $stmt->execute([$createdBy]);
    if (!$stmt->fetch()) {
        return ['success' => false, 'error' => 'Kullanıcı bulunamadı'];
    }
    
    $stmt = $db->prepare("SELECT MAX(position) as max_pos FROM channels WHERE server_id = ?");
    $stmt->execute([$serverId]);
    $result = $stmt->fetch();
    $position = ($result['max_pos'] ?? 0) + 1;
    
    try {
        $stmt = $db->prepare("INSERT INTO channels (server_id, name, type, position, is_private, created_by) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$serverId, $name, $type, $position, $isPrivate ? 1 : 0, $createdBy]);
        $channelId = $db->lastInsertId();
        
        // Varsayılan davet kodu oluştur
        createChannelInvite($channelId, $createdBy);
        
        return ['success' => true, 'channel_id' => $channelId];
    } catch (PDOException $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function getServerChannels($serverId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT c.*, u.username as creator_name FROM channels c JOIN users u ON c.created_by = u.id WHERE c.server_id = ? ORDER BY c.position ASC");
    $stmt->execute([$serverId]);
    return $stmt->fetchAll();
}

function getChannelById($channelId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT c.*, s.name as server_name, u.username as creator_name FROM channels c JOIN servers s ON c.server_id = s.id JOIN users u ON c.created_by = u.id WHERE c.id = ?");
    $stmt->execute([$channelId]);
    return $stmt->fetch();
}

// ========== DAVET SİSTEMİ ==========

function createChannelInvite($channelId, $userId, $maxUses = 0, $expiresHours = null) {
    $db = getDB();
    
    $code = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789'), 0, 8);
    
    $expiresAt = null;
    if ($expiresHours) {
        $expiresAt = date('Y-m-d H:i:s', strtotime("+{$expiresHours} hours"));
    }
    
    try {
        $stmt = $db->prepare("INSERT INTO channel_invites (channel_id, invite_code, created_by, max_uses, expires_at) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$channelId, $code, $userId, $maxUses, $expiresAt]);
        
        return ['success' => true, 'code' => $code, 'invite_id' => $db->lastInsertId()];
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            return createChannelInvite($channelId, $userId, $maxUses, $expiresHours);
        }
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function validateInviteCode($code) {
    $db = getDB();
    
    $stmt = $db->prepare("SELECT i.*, c.name as channel_name, c.server_id, s.name as server_name 
                          FROM channel_invites i 
                          JOIN channels c ON i.channel_id = c.id 
                          JOIN servers s ON c.server_id = s.id 
                          WHERE i.invite_code = ? AND i.is_active = TRUE");
    $stmt->execute([$code]);
    $invite = $stmt->fetch();
    
    if (!$invite) {
        return ['valid' => false, 'error' => 'Geçersiz davet kodu'];
    }
    
    if ($invite['expires_at'] && strtotime($invite['expires_at']) < time()) {
        return ['valid' => false, 'error' => 'Davet kodunun süresi doldu'];
    }
    
    if ($invite['max_uses'] > 0 && $invite['used_count'] >= $invite['max_uses']) {
        return ['valid' => false, 'error' => 'Davet kodu kullanım limitine ulaştı'];
    }
    
    return ['valid' => true, 'invite' => $invite];
}

function useInviteCode($code, $userId) {
    $db = getDB();
    
    $validation = validateInviteCode($code);
    if (!$validation['valid']) {
        return $validation;
    }
    
    $invite = $validation['invite'];
    
    try {
        $stmt = $db->prepare("UPDATE channel_invites SET used_count = used_count + 1 WHERE id = ?");
        $stmt->execute([$invite['id']]);
        
        $stmt = $db->prepare("INSERT INTO channel_invite_uses (invite_id, user_id) VALUES (?, ?)");
        $stmt->execute([$invite['id'], $userId]);
        
        $stmt = $db->prepare("SELECT 1 FROM server_members WHERE server_id = ? AND user_id = ?");
        $stmt->execute([$invite['server_id'], $userId]);
        
        if (!$stmt->fetch()) {
            $stmt = $db->prepare("INSERT INTO server_members (server_id, user_id, role_id) VALUES (?, ?, 3)");
            $stmt->execute([$invite['server_id'], $userId]);
        }
        
        return ['success' => true, 'channel_id' => $invite['channel_id'], 'server_id' => $invite['server_id']];
        
    } catch (PDOException $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function getChannelInvites($channelId, $userId) {
    $db = getDB();
    
    $channel = getChannelById($channelId);
    if (!$channel) return [];
    
    $server = getServerById($channel['server_id']);
    $isAdmin = ($server['owner_id'] == $userId || isModerator());
    
    if (!$isAdmin) {
        $stmt = $db->prepare("SELECT * FROM channel_invites WHERE channel_id = ? AND created_by = ? AND is_active = TRUE ORDER BY created_at DESC");
        $stmt->execute([$channelId, $userId]);
    } else {
        $stmt = $db->prepare("SELECT i.*, u.username as creator_name FROM channel_invites i JOIN users u ON i.created_by = u.id WHERE i.channel_id = ? AND i.is_active = TRUE ORDER BY i.created_at DESC");
        $stmt->execute([$channelId]);
    }
    
    return $stmt->fetchAll();
}

function deleteInviteCode($inviteId, $userId) {
    $db = getDB();
    
    $stmt = $db->prepare("SELECT i.*, c.server_id FROM channel_invites i JOIN channels c ON i.channel_id = c.id WHERE i.id = ?");
    $stmt->execute([$inviteId]);
    $invite = $stmt->fetch();
    
    if (!$invite) return ['success' => false, 'error' => 'Davet bulunamadı'];
    
    $server = getServerById($invite['server_id']);
    $isAdmin = ($server['owner_id'] == $userId || isModerator());
    
    if (!$isAdmin && $invite['created_by'] != $userId) {
        return ['success' => false, 'error' => 'Yetkiniz yok'];
    }
    
    $stmt = $db->prepare("UPDATE channel_invites SET is_active = FALSE WHERE id = ?");
    $stmt->execute([$inviteId]);
    
    return ['success' => true];
}

// ========== KANAL ARKADAŞLARI ==========

function inviteFriendToChannel($channelId, $friendId, $invitedBy) {
    $db = getDB();
    
    $stmt = $db->prepare("SELECT 1 FROM friendships WHERE 
        ((requester_id = ? AND addressee_id = ?) OR (requester_id = ? AND addressee_id = ?)) 
        AND status = 'accepted'");
    $stmt->execute([$invitedBy, $friendId, $friendId, $invitedBy]);
    
    if (!$stmt->fetch()) {
        return ['success' => false, 'error' => 'Bu kullanıcı arkadaşınız değil'];
    }
    
    $channel = getChannelById($channelId);
    $stmt = $db->prepare("SELECT 1 FROM server_members WHERE server_id = ? AND user_id = ?");
    $stmt->execute([$channel['server_id'], $friendId]);
    
    if ($stmt->fetch()) {
        return ['success' => false, 'error' => 'Kullanıcı zaten sunucuda'];
    }
    
    $stmt = $db->prepare("SELECT status FROM channel_friends WHERE channel_id = ? AND user_id = ?");
    $stmt->execute([$channelId, $friendId]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        if ($existing['status'] == 'pending') {
            return ['success' => false, 'error' => 'Zaten davet edilmiş'];
        }
        $stmt = $db->prepare("UPDATE channel_friends SET status = 'pending', invited_by = ?, invited_at = NOW() WHERE channel_id = ? AND user_id = ?");
        $stmt->execute([$invitedBy, $channelId, $friendId]);
    } else {
        $stmt = $db->prepare("INSERT INTO channel_friends (channel_id, user_id, invited_by) VALUES (?, ?, ?)");
        $stmt->execute([$channelId, $friendId, $invitedBy]);
    }
    
    $stmt = $db->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->execute([$invitedBy]);
    $inviter = $stmt->fetch();
    
    $stmt = $db->prepare("INSERT INTO notifications (user_id, sender_id, notification_type, content, related_id) VALUES (?, ?, 'channel_join', ?, ?)");
    $stmt->execute([$friendId, $invitedBy, "{$inviter['username']} sizi bir kanala davet etti", $channelId]);
    
    return ['success' => true];
}

function getPendingChannelInvites($userId) {
    $db = getDB();
    
    $stmt = $db->prepare("SELECT cf.*, c.name as channel_name, s.name as server_name, s.id as server_id, u.username as inviter_name 
                          FROM channel_friends cf 
                          JOIN channels c ON cf.channel_id = c.id 
                          JOIN servers s ON c.server_id = s.id 
                          JOIN users u ON cf.invited_by = u.id 
                          WHERE cf.user_id = ? AND cf.status = 'pending'");
    $stmt->execute([$userId]);
    
    return $stmt->fetchAll();
}

function respondChannelInvite($channelFriendId, $userId, $accept) {
    $db = getDB();
    
    $stmt = $db->prepare("SELECT cf.*, c.server_id FROM channel_friends cf JOIN channels c ON cf.channel_id = c.id WHERE cf.id = ? AND cf.user_id = ?");
    $stmt->execute([$channelFriendId, $userId]);
    $invite = $stmt->fetch();
    
    if (!$invite) {
        return ['success' => false, 'error' => 'Davet bulunamadı'];
    }
    
    if ($accept) {
        $stmt = $db->prepare("INSERT IGNORE INTO server_members (server_id, user_id, role_id) VALUES (?, ?, 3)");
        $stmt->execute([$invite['server_id'], $userId]);
        
        $stmt = $db->prepare("UPDATE channel_friends SET status = 'accepted' WHERE id = ?");
        $stmt->execute([$channelFriendId]);
        
        return ['success' => true, 'action' => 'accepted', 'server_id' => $invite['server_id'], 'channel_id' => $invite['channel_id']];
    } else {
        $stmt = $db->prepare("UPDATE channel_friends SET status = 'left' WHERE id = ?");
        $stmt->execute([$channelFriendId]);
        
        return ['success' => true, 'action' => 'rejected'];
    }
}

// ========== MESAJ İŞLEMLERİ ==========

function sendMessage($userId, $channelId, $message, $mediaUrl = null, $mediaType = null, $replyTo = null) {
    if (empty(trim($message)) && !$mediaUrl) {
        return ['success' => false, 'error' => 'Mesaj boş olamaz'];
    }
    
    $db = getDB();
    
    try {
        $stmt = $db->prepare("INSERT INTO messages (user_id, channel_id, message, media_url, media_type, reply_to) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $channelId, $message, $mediaUrl, $mediaType, $replyTo]);
        $messageId = $db->lastInsertId();
        
        sendChannelNotifications($userId, $channelId, $message);
        
        return ['success' => true, 'message_id' => $messageId];
    } catch (PDOException $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function getChannelMessages($channelId, $limit = 50, $beforeId = null) {
    $db = getDB();
    
    $sql = "SELECT m.*, u.username, u.avatar, u.status, r.color as user_color,
            (SELECT COUNT(*) FROM message_reactions WHERE message_id = m.id) as reaction_count
            FROM messages m 
            JOIN users u ON m.user_id = u.id 
            JOIN roles r ON u.role_id = r.id 
            WHERE m.channel_id = ?";
    
    $params = [$channelId];
    
    if ($beforeId) {
        $sql .= " AND m.id < ?";
        $params[] = $beforeId;
    }
    
    $sql .= " ORDER BY m.created_at DESC LIMIT ?";
    $params[] = $limit;
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $messages = $stmt->fetchAll();
    
    foreach ($messages as &$message) {
        $message['reactions'] = getMessageReactions($message['id']);
    }
    
    return array_reverse($messages);
}

function deleteMessage($messageId, $userId) {
    $db = getDB();
    
    $stmt = $db->prepare("SELECT user_id FROM messages WHERE id = ?");
    $stmt->execute([$messageId]);
    $message = $stmt->fetch();
    
    if (!$message) {
        return ['success' => false, 'error' => 'Mesaj bulunamadı'];
    }
    
    if ($message['user_id'] != $userId && !isModerator()) {
        return ['success' => false, 'error' => 'Yetkiniz yok'];
    }
    
    $stmt = $db->prepare("DELETE FROM messages WHERE id = ?");
    $stmt->execute([$messageId]);
    
    return ['success' => true];
}

function editMessage($messageId, $userId, $newMessage) {
    $db = getDB();
    
    $stmt = $db->prepare("SELECT user_id FROM messages WHERE id = ?");
    $stmt->execute([$messageId]);
    $message = $stmt->fetch();
    
    if (!$message || $message['user_id'] != $userId) {
        return ['success' => false, 'error' => 'Yetkiniz yok'];
    }
    
    $stmt = $db->prepare("UPDATE messages SET message = ?, is_edited = TRUE, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$newMessage, $messageId]);
    
    return ['success' => true];
}

// ========== TEPKİ İŞLEMLERİ ==========

function toggleReaction($messageId, $userId, $emoji) {
    $db = getDB();
    
    $stmt = $db->prepare("SELECT id FROM message_reactions WHERE message_id = ? AND user_id = ? AND emoji = ?");
    $stmt->execute([$messageId, $userId, $emoji]);
    
    if ($stmt->fetch()) {
        $stmt = $db->prepare("DELETE FROM message_reactions WHERE message_id = ? AND user_id = ? AND emoji = ?");
        $stmt->execute([$messageId, $userId, $emoji]);
        return ['success' => true, 'action' => 'removed'];
    } else {
        $stmt = $db->prepare("INSERT INTO message_reactions (message_id, user_id, emoji) VALUES (?, ?, ?)");
        $stmt->execute([$messageId, $userId, $emoji]);
        return ['success' => true, 'action' => 'added'];
    }
}

function getMessageReactions($messageId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT emoji, COUNT(*) as count FROM message_reactions WHERE message_id = ? GROUP BY emoji");
    $stmt->execute([$messageId]);
    return $stmt->fetchAll();
}

// ========== BİLDİRİM İŞLEMLERİ ==========

function sendNotification($userId, $senderId, $type, $content, $relatedId = null) {
    $db = getDB();
    
    $stmt = $db->prepare("INSERT INTO notifications (user_id, sender_id, notification_type, content, related_id) VALUES (?, ?, ?, ?, ?)");
    return $stmt->execute([$userId, $senderId, $type, $content, $relatedId]);
}

function sendChannelNotifications($senderId, $channelId, $message) {
    $db = getDB();
    
    $channel = getChannelById($channelId);
    if (!$channel) return;
    
    $stmt = $db->prepare("SELECT user_id FROM server_members WHERE server_id = ? AND user_id != ?");
    $stmt->execute([$channel['server_id'], $senderId]);
    $members = $stmt->fetchAll();
    
    $sender = getUserById($senderId);
    $content = substr($message, 0, 100) . (strlen($message) > 100 ? '...' : '');
    
    foreach ($members as $member) {
        sendNotification($member['user_id'], $senderId, 'message', "{$sender['username']}: $content", $channelId);
    }
}

function getUserNotifications($userId, $unreadOnly = false) {
    $db = getDB();
    
    $sql = "SELECT n.*, u.username as sender_name, u.avatar as sender_avatar 
            FROM notifications n 
            LEFT JOIN users u ON n.sender_id = u.id 
            WHERE n.user_id = ?";
    
    if ($unreadOnly) {
        $sql .= " AND n.is_read = FALSE";
    }
    
    $sql .= " ORDER BY n.created_at DESC LIMIT 50";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function markNotificationRead($notificationId, $userId) {
    $db = getDB();
    $stmt = $db->prepare("UPDATE notifications SET is_read = TRUE WHERE id = ? AND user_id = ?");
    return $stmt->execute([$notificationId, $userId]);
}

function markAllNotificationsRead($userId) {
    $db = getDB();
    $stmt = $db->prepare("UPDATE notifications SET is_read = TRUE WHERE user_id = ? AND is_read = FALSE");
    return $stmt->execute([$userId]);
}

// ========== ARKADAŞLIK İŞLEMLERİ ==========

function sendFriendRequest($requesterId, $addresseeId) {
    if ($requesterId == $addresseeId) {
        return ['success' => false, 'error' => 'Kendinize istek gönderemezsiniz'];
    }
    
    $db = getDB();
    
    $stmt = $db->prepare("SELECT status FROM friendships WHERE (requester_id = ? AND addressee_id = ?) OR (requester_id = ? AND addressee_id = ?)");
    $stmt->execute([$requesterId, $addresseeId, $addresseeId, $requesterId]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        return ['success' => false, 'error' => 'Zaten bir istek mevcut veya arkadaşsınız'];
    }
    
    try {
        $stmt = $db->prepare("INSERT INTO friendships (requester_id, addressee_id) VALUES (?, ?)");
        $stmt->execute([$requesterId, $addresseeId]);
        
        $requester = getUserById($requesterId);
        sendNotification($addresseeId, $requesterId, 'friend_request', "{$requester['username']} size arkadaşlık isteği gönderdi");
        
        return ['success' => true];
    } catch (PDOException $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function respondFriendRequest($friendshipId, $userId, $accept) {
    $db = getDB();
    
    $stmt = $db->prepare("SELECT * FROM friendships WHERE id = ? AND addressee_id = ? AND status = 'pending'");
    $stmt->execute([$friendshipId, $userId]);
    $friendship = $stmt->fetch();
    
    if (!$friendship) {
        return ['success' => false, 'error' => 'İstek bulunamadı'];
    }
    
    if ($accept) {
        $stmt = $db->prepare("UPDATE friendships SET status = 'accepted' WHERE id = ?");
        $stmt->execute([$friendshipId]);
        return ['success' => true, 'action' => 'accepted'];
    } else {
        $stmt = $db->prepare("DELETE FROM friendships WHERE id = ?");
        $stmt->execute([$friendshipId]);
        return ['success' => true, 'action' => 'rejected'];
    }
}

function getFriends($userId) {
    $db = getDB();
    
    $stmt = $db->prepare("SELECT f.*, u.username, u.avatar, u.status, 
                          CASE WHEN f.requester_id = ? THEN f.addressee_id ELSE f.requester_id END as friend_id
                          FROM friendships f 
                          JOIN users u ON (CASE WHEN f.requester_id = ? THEN f.addressee_id ELSE f.requester_id END) = u.id
                          WHERE (f.requester_id = ? OR f.addressee_id = ?) AND f.status = 'accepted'");
    $stmt->execute([$userId, $userId, $userId, $userId]);
    return $stmt->fetchAll();
}

function getPendingRequests($userId) {
    $db = getDB();
    
    $stmt = $db->prepare("SELECT f.*, u.username, u.avatar FROM friendships f JOIN users u ON f.requester_id = u.id WHERE f.addressee_id = ? AND f.status = 'pending'");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function getUserById($userId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT u.*, r.role_name, r.color FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetch();
}