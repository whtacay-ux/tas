<?php
// Discord Clone - Arkadaşlık İsteği Yanıtlama API
require_once '../includes/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, [], 'Geçersiz istek metodu');
}

requireAuth();

$input = json_decode(file_get_contents('php://input'), true);
$friendshipId = intval($input['friendship_id'] ?? 0);
$accept = filter_var($input['accept'] ?? false, FILTER_VALIDATE_BOOLEAN);

if (!$friendshipId) {
    jsonResponse(false, [], 'Geçersiz istek ID');
}

$user = getCurrentUser();
$db = getDB();

// İsteği kontrol et
$stmt = $db->prepare("SELECT * FROM friendships WHERE id = ? AND addressee_id = ? AND status = 'pending'");
$stmt->execute([$friendshipId, $user['id']]);
$friendship = $stmt->fetch();

if (!$friendship) {
    jsonResponse(false, [], 'İstek bulunamadı');
}

try {
    if ($accept) {
        $stmt = $db->prepare("UPDATE friendships SET status = 'accepted', updated_at = NOW() WHERE id = ?");
        $stmt->execute([$friendshipId]);
        
        // Bildirim oluştur
        $stmt = $db->prepare("INSERT INTO notifications (user_id, sender_id, notification_type, content, created_at) VALUES (?, ?, 'friend_request', ?, NOW())");
        $stmt->execute([$friendship['requester_id'], $user['id'], "{$user['username']} arkadaşlık isteğinizi kabul etti"]);
        
        jsonResponse(true, [], 'Arkadaşlık isteği kabul edildi');
    } else {
        $stmt = $db->prepare("UPDATE friendships SET status = 'blocked', updated_at = NOW() WHERE id = ?");
        $stmt->execute([$friendshipId]);
        
        jsonResponse(true, [], 'Arkadaşlık isteği reddedildi');
    }
    
} catch (Exception $e) {
    jsonResponse(false, [], 'İşlem başarısız: ' . $e->getMessage());
}
