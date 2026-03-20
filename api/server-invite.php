<?php
// Discord Clone - Sunucu Davet API
require_once '../includes/config.php';
require_once '../includes/channels.php';

header('Content-Type: application/json');

requireAuth();

$user = getCurrentUser();
$db = getDB();

$method = $_SERVER['REQUEST_METHOD'];

// POST - Sunucu davet linki oluştur
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $serverId = intval($input['server_id'] ?? 0);
    $maxUses = intval($input['max_uses'] ?? 0);
    $expiresAt = $input['expires_at'] ?? null;
    
    if (!$serverId) {
        jsonResponse(false, [], 'Sunucu ID gerekli');
    }
    
    // Yetki kontrolü
    if (!isServerOwner($serverId) && !isAdmin()) {
        jsonResponse(false, [], 'Davet oluşturma yetkiniz yok');
    }
    
    $result = createServerInvite($serverId, $user['id'], $maxUses, $expiresAt);
    
    if ($result['success']) {
        jsonResponse(true, ['invite_code' => $result['invite_code']]);
    } else {
        jsonResponse(false, [], $result['error']);
    }
}

// DELETE - Sunucu davet linkini sil
if ($method === 'DELETE') {
    $input = json_decode(file_get_contents('php://input'), true);
    $inviteId = intval($input['invite_id'] ?? 0);
    
    if (!$inviteId) {
        jsonResponse(false, [], 'Geçersiz davet ID');
    }
    
    $result = deleteServerInvite($inviteId);
    
    if ($result['success']) {
        jsonResponse(true, [], 'Davet linki silindi');
    } else {
        jsonResponse(false, [], $result['error']);
    }
}

jsonResponse(false, [], 'Geçersiz işlem');
