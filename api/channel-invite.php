<?php
// Discord Clone - Kanal Davet API
require_once '../includes/config.php';

header('Content-Type: application/json');

requireAuth();

$user = getCurrentUser();
$db = getDB();

$method = $_SERVER['REQUEST_METHOD'];

// GET - Davet linklerini getir
if ($method === 'GET') {
    $channelId = $_GET['channel_id'] ?? null;
    
    if (!$channelId) {
        jsonResponse(false, [], 'Kanal ID gerekli');
    }
    
    $stmt = $db->prepare("SELECT i.*, u.username as created_by_name 
                          FROM channel_invites i 
                          JOIN users u ON i.created_by = u.id 
                          WHERE i.channel_id = ? AND i.is_active = 1 
                          ORDER BY i.created_at DESC");
    $stmt->execute([$channelId]);
    $invites = $stmt->fetchAll();
    
    jsonResponse(true, ['invites' => $invites]);
}

// POST - Davet oluştur veya arkadaş davet et
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    
    // Davet linki oluştur
    if ($action === 'create') {
        $channelId = intval($input['channel_id'] ?? 0);
        
        if (!$channelId) {
            jsonResponse(false, [], 'Kanal ID gerekli');
        }
        
        $channel = getChannelById($channelId);
        if (!$channel) {
            jsonResponse(false, [], 'Kanal bulunamadı');
        }
        
        // Üyelik kontrolü
        $stmt = $db->prepare("SELECT id FROM server_members WHERE server_id = ? AND user_id = ?");
        $stmt->execute([$channel['server_id'], $user['id']]);
        if (!$stmt->fetch()) {
            jsonResponse(false, [], 'Bu sunucunun üyesi değilsiniz');
        }
        
        $inviteCode = generateInviteCode();
        
        try {
            $stmt = $db->prepare("INSERT INTO channel_invites (channel_id, invite_code, created_by, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$channelId, $inviteCode, $user['id']]);
            
            $inviteUrl = SITE_URL . '/pages/invite.php?code=' . $inviteCode;
            
            jsonResponse(true, ['url' => $inviteUrl, 'invite_code' => $inviteCode]);
            
        } catch (Exception $e) {
            jsonResponse(false, [], 'Davet oluşturulamadı: ' . $e->getMessage());
        }
    }
    
    // Arkadaşı davet et
    if ($action === 'invite_friend') {
        $channelId = intval($input['channel_id'] ?? 0);
        $friendId = intval($input['friend_id'] ?? 0);
        
        if (!$channelId || !$friendId) {
            jsonResponse(false, [], 'Kanal ID ve arkadaş ID gerekli');
        }
        
        $channel = getChannelById($channelId);
        if (!$channel) {
            jsonResponse(false, [], 'Kanal bulunamadı');
        }
        
        // Arkadaş kontrolü
        $stmt = $db->prepare("SELECT id FROM friendships 
                              WHERE ((requester_id = ? AND addressee_id = ?) OR (requester_id = ? AND addressee_id = ?)) 
                              AND status = 'accepted'");
        $stmt->execute([$user['id'], $friendId, $friendId, $user['id']]);
        if (!$stmt->fetch()) {
            jsonResponse(false, [], 'Bu kullanıcı arkadaşınız değil');
        }
        
        // Zaten davet edilmiş mi kontrol et
        $stmt = $db->prepare("SELECT id FROM channel_friends WHERE channel_id = ? AND user_id = ? AND status = 'pending'");
        $stmt->execute([$channelId, $friendId]);
        if ($stmt->fetch()) {
            jsonResponse(false, [], 'Bu kullanıcıya zaten davet gönderilmiş');
        }
        
        try {
            $stmt = $db->prepare("INSERT INTO channel_friends (channel_id, user_id, invited_by, status, invited_at) VALUES (?, ?, ?, 'pending', NOW())");
            $stmt->execute([$channelId, $friendId, $user['id']]);
            
            // Bildirim oluştur
            $stmt = $db->prepare("INSERT INTO notifications (user_id, sender_id, notification_type, content, related_id, created_at) VALUES (?, ?, 'channel_join', ?, ?, NOW())");
            $stmt->execute([$friendId, $user['id'], "{$user['username']} sizi bir kanala davet etti", $channelId]);
            
            jsonResponse(true, [], 'Davet gönderildi');
            
        } catch (Exception $e) {
            jsonResponse(false, [], 'Davet gönderilemedi: ' . $e->getMessage());
        }
    }
    
    // Daveti yanıtla
    if ($action === 'respond_invite') {
        $inviteId = intval($input['invite_id'] ?? 0);
        $accept = filter_var($input['accept'] ?? false, FILTER_VALIDATE_BOOLEAN);
        
        if (!$inviteId) {
            jsonResponse(false, [], 'Geçersiz davet ID');
        }
        
        $stmt = $db->prepare("SELECT cf.*, c.server_id FROM channel_friends cf 
                              JOIN channels c ON cf.channel_id = c.id 
                              WHERE cf.id = ? AND cf.user_id = ? AND cf.status = 'pending'");
        $stmt->execute([$inviteId, $user['id']]);
        $invite = $stmt->fetch();
        
        if (!$invite) {
            jsonResponse(false, [], 'Davet bulunamadı');
        }
        
        try {
            if ($accept) {
                // Sunucuya üye olarak ekle
                $stmt = $db->prepare("INSERT IGNORE INTO server_members (server_id, user_id, role_id) VALUES (?, ?, 3)");
                $stmt->execute([$invite['server_id'], $user['id']]);
                
                $stmt = $db->prepare("UPDATE channel_friends SET status = 'accepted' WHERE id = ?");
                $stmt->execute([$inviteId]);
                
                jsonResponse(true, [
                    'server_id' => $invite['server_id'],
                    'channel_id' => $invite['channel_id']
                ], 'Davet kabul edildi');
            } else {
                $stmt = $db->prepare("UPDATE channel_friends SET status = 'left' WHERE id = ?");
                $stmt->execute([$inviteId]);
                
                jsonResponse(true, [], 'Davet reddedildi');
            }
            
        } catch (Exception $e) {
            jsonResponse(false, [], 'İşlem başarısız: ' . $e->getMessage());
        }
    }
}

// DELETE - Davet linkini sil
if ($method === 'DELETE') {
    $input = json_decode(file_get_contents('php://input'), true);
    $inviteId = intval($input['invite_id'] ?? 0);
    
    if (!$inviteId) {
        jsonResponse(false, [], 'Geçersiz davet ID');
    }
    
    $stmt = $db->prepare("SELECT * FROM channel_invites WHERE id = ?");
    $stmt->execute([$inviteId]);
    $invite = $stmt->fetch();
    
    if (!$invite) {
        jsonResponse(false, [], 'Davet bulunamadı');
    }
    
    // Yetki kontrolü
    if ($invite['created_by'] != $user['id'] && !isAdmin()) {
        jsonResponse(false, [], 'Bu daveti silme yetkiniz yok');
    }
    
    try {
        $stmt = $db->prepare("UPDATE channel_invites SET is_active = 0 WHERE id = ?");
        $stmt->execute([$inviteId]);
        
        jsonResponse(true, [], 'Davet silindi');
        
    } catch (Exception $e) {
        jsonResponse(false, [], 'Silme başarısız: ' . $e->getMessage());
    }
}

jsonResponse(false, [], 'Geçersiz işlem');
