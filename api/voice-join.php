<?php
// Discord Clone - Sesli Kanala Katılma API
require_once '../includes/config.php';
require_once '../includes/channels.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, [], 'Geçersiz istek metodu');
}

requireAuth();

$input = json_decode(file_get_contents('php://input'), true);
$channelId = intval($input['channel_id'] ?? 0);
$isAudioOn = filter_var($input['is_audio_on'] ?? true, FILTER_VALIDATE_BOOLEAN);
$isVideoOn = filter_var($input['is_video_on'] ?? false, FILTER_VALIDATE_BOOLEAN);

if (!$channelId) {
    jsonResponse(false, [], 'Kanal ID gerekli');
}

$user = getCurrentUser();
$db = getDB();

// Kanal kontrolü
$channel = getChannelById($channelId);
if (!$channel) {
    jsonResponse(false, [], 'Kanal bulunamadı');
}

if ($channel['type'] !== 'voice' && $channel['type'] !== 'video') {
    jsonResponse(false, [], 'Bu kanal sesli kanal değil');
}

// Üyelik kontrolü
$stmt = $db->prepare("SELECT id FROM server_members WHERE server_id = ? AND user_id = ?");
$stmt->execute([$channel['server_id'], $user['id']]);
if (!$stmt->fetch()) {
    jsonResponse(false, [], 'Bu sunucunun üyesi değilsiniz');
}

try {
    // Oda oluştur veya getir
    $room = getOrCreateVoiceRoom($channelId, $user['id']);
    
    // Kullanıcıyı odaya ekle
    $result = joinVoiceRoom($room['id'], $user['id']);
    
    if ($result['success']) {
        // Ses durumunu güncelle
        updateVoiceStatus($room['id'], $user['id'], $isAudioOn, $isVideoOn, false);
        
        jsonResponse(true, [
            'room_id' => $room['id'],
            'room_code' => $room['room_code']
        ], 'Sesli kanala katıldınız');
    } else {
        jsonResponse(false, [], $result['error']);
    }
    
} catch (Exception $e) {
    jsonResponse(false, [], 'Katılım başarısız: ' . $e->getMessage());
}
