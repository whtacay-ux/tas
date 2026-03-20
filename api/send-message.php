<?php
// Discord Clone - Mesaj Gönderme API
require_once '../includes/config.php';

header('Content-Type: application/json');

// Sadece POST isteklerine izin ver
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, [], 'Geçersiz istek metodu');
}

// Giriş kontrolü
requireAuth();

// JSON verisini al
$input = json_decode(file_get_contents('php://input'), true);
$channelId = $input['channel_id'] ?? null;
$message = trim($input['message'] ?? '');

if (!$channelId || empty($message)) {
    jsonResponse(false, [], 'Kanal ID ve mesaj gerekli');
}

$user = getCurrentUser();
$db = getDB();

// Kanal kontrolü
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

try {
    // Mesajı kaydet
    $stmt = $db->prepare("INSERT INTO messages (user_id, channel_id, message, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$user['id'], $channelId, $message]);
    $messageId = $db->lastInsertId();
    
    // Bildirim oluştur (kanaldaki diğer kullanıcılara)
    $stmt = $db->prepare("SELECT user_id FROM server_members WHERE server_id = ? AND user_id != ?");
    $stmt->execute([$channel['server_id'], $user['id']]);
    $members = $stmt->fetchAll();
    
    foreach ($members as $member) {
        $stmt = $db->prepare("INSERT INTO notifications (user_id, sender_id, notification_type, content, related_id, created_at) 
                              VALUES (?, ?, 'message', ?, ?, NOW())");
        $stmt->execute([$member['user_id'], $user['id'], "{$user['username']} yeni bir mesaj gönderdi", $channelId]);
    }
    
    jsonResponse(true, ['message_id' => $messageId, 'message' => 'Mesaj gönderildi']);
    
} catch (Exception $e) {
    jsonResponse(false, [], 'Mesaj gönderilemedi: ' . $e->getMessage());
}
