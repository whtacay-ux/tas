<?php
// Discord Clone - Kanal Oluşturma API
require_once '../includes/config.php';
require_once '../includes/channels.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, [], 'Geçersiz istek metodu');
}

requireAuth();

$input = json_decode(file_get_contents('php://input'), true);
$serverId = intval($input['server_id'] ?? 0);
$name = trim($input['name'] ?? '');
$type = $input['type'] ?? 'text';

// Debug log
error_log("Create Channel - Input: " . json_encode($input));

if (!$serverId) {
    jsonResponse(false, [], 'Sunucu ID gerekli');
}

if (empty($name)) {
    jsonResponse(false, [], 'Kanal adı gerekli');
}

if (!in_array($type, ['text', 'voice', 'video'])) {
    jsonResponse(false, [], 'Geçersiz kanal türü');
}

$user = getCurrentUser();

// Sunucu kontrolü
$server = getServerById($serverId);
if (!$server) {
    jsonResponse(false, [], 'Sunucu bulunamadı');
}

// Yetki kontrolü
if (!isServerOwner($serverId) && !isAdmin() && !isModerator()) {
    jsonResponse(false, [], 'Kanal oluşturma yetkiniz yok');
}

$result = createChannel($serverId, $name, $type, $user['id']);

if ($result['success']) {
    jsonResponse(true, ['channel_id' => $result['channel_id']]);
} else {
    jsonResponse(false, [], $result['error']);
}
