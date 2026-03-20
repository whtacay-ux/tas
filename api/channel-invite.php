<?php
// Discord Clone - Kanal Davet İşlemleri API
require_once '../includes/config.php';
require_once '../includes/channels.php';

error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Yetkisiz erişim']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$userId = $_SESSION['user_id'];

if ($method === 'POST') {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    $action = $data['action'] ?? '';
    
    switch ($action) {
        case 'create':
            $channelId = $data['channel_id'] ?? null;
            $maxUses = $data['max_uses'] ?? 0;
            $expiresHours = $data['expires_hours'] ?? null;
            
            if (!$channelId) {
                echo json_encode(['success' => false, 'error' => 'Kanal ID gerekli']);
                exit;
            }
            
            $result = createChannelInvite($channelId, $userId, $maxUses, $expiresHours);
            
            if ($result['success']) {
                echo json_encode([
                    'success' => true,
                    'code' => $result['code'],
                    'url' => "http://localhost:7272/discord-clone/invite/{$result['code']}"
                ]);
            } else {
                echo json_encode($result);
            }
            break;
            
        case 'invite_friend':
            $channelId = $data['channel_id'] ?? null;
            $friendId = $data['friend_id'] ?? null;
            
            if (!$channelId || !$friendId) {
                echo json_encode(['success' => false, 'error' => 'Kanal ve arkadaş ID gerekli']);
                exit;
            }
            
            $result = inviteFriendToChannel($channelId, $friendId, $userId);
            echo json_encode($result);
            break;
            
        case 'respond_invite':
            $inviteId = $data['invite_id'] ?? null;
            $accept = $data['accept'] ?? false;
            
            if (!$inviteId) {
                echo json_encode(['success' => false, 'error' => 'Davet ID gerekli']);
                exit;
            }
            
            $result = respondChannelInvite($inviteId, $userId, $accept);
            echo json_encode($result);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Geçersiz işlem']);
    }
    
} elseif ($method === 'GET') {
    $channelId = $_GET['channel_id'] ?? null;
    
    if ($channelId) {
        // Kanalın davet kodlarını getir
        $invites = getChannelInvites($channelId, $userId);
        echo json_encode(['success' => true, 'invites' => $invites]);
    } else {
        // Kullanıcının bekleyen davetlerini getir
        $pending = getPendingChannelInvites($userId);
        echo json_encode(['success' => true, 'pending_invites' => $pending]);
    }
    
} elseif ($method === 'DELETE') {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    $inviteId = $data['invite_id'] ?? null;
    
    if (!$inviteId) {
        echo json_encode(['success' => false, 'error' => 'Davet ID gerekli']);
        exit;
    }
    
    $result = deleteInviteCode($inviteId, $userId);
    echo json_encode($result);
}