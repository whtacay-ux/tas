<?php
// Discord Clone - Sesli Kanaldan Ayrılma API
require_once '../includes/config.php';
require_once '../includes/channels.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, [], 'Geçersiz istek metodu');
}

requireAuth();

$input = json_decode(file_get_contents('php://input'), true);
$channelId = intval($input['channel_id'] ?? 0);

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
    jsonResponse(true, [], 'Oda bulunamadı');
}

$result = leaveVoiceRoom($room['room_id'], $user['id']);

if ($result['success']) {
    jsonResponse(true, [], 'Sesli kanaldan ayrıldınız');
} else {
    jsonResponse(false, [], $result['error']);
}
