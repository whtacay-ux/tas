<?php
// Discord Clone - Ses Durumu Güncelleme API
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
$isScreenSharing = filter_var($input['is_screen_sharing'] ?? false, FILTER_VALIDATE_BOOLEAN);

if (!$channelId) {
    jsonResponse(false, [], 'Kanal ID gerekli');
}

$user = getCurrentUser();
$db = getDB();

// Aktif odayı bul
$stmt = $db->prepare("SELECT vr.id as room_id FROM voice_rooms vr 
                      WHERE vr.channel_id = ? AND vr.status = 'active'");
$stmt->execute([$channelId]);
$room = $stmt->fetch();

if (!$room) {
    jsonResponse(false, [], 'Oda bulunamadı');
}

$result = updateVoiceStatus($room['room_id'], $user['id'], $isAudioOn, $isVideoOn, $isScreenSharing);

if ($result['success']) {
    jsonResponse(true, [], 'Durum güncellendi');
} else {
    jsonResponse(false, [], $result['error']);
}
