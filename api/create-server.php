<?php
// Discord Clone - Kanal Oluşturma API (Davet Sistemi ile)
require_once '../includes/config.php';
require_once '../includes/channels.php';

error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Yetkisiz erişim']);
    exit;
}

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data) {
    echo json_encode(['success' => false, 'error' => 'Geçersiz JSON verisi']);
    exit;
}

$serverId = $data['server_id'] ?? null;
$name = trim($data['name'] ?? '');
$type = $data['type'] ?? 'text';
$friends = $data['friends'] ?? []; // Davet edilecek arkadaşlar
$createInvite = $data['create_invite'] ?? true; // Varsayılan davet oluştur

if (!$serverId) {
    echo json_encode(['success' => false, 'error' => 'Sunucu ID gerekli']);
    exit;
}

if (empty($name) || strlen($name) < 2 || strlen($name) > 100) {
    echo json_encode(['success' => false, 'error' => 'Geçerli bir kanal adı girin (2-100 karakter)']);
    exit;
}

if (!in_array($type, ['text', 'voice', 'video'])) {
    echo json_encode(['success' => false, 'error' => 'Geçersiz kanal türü']);
    exit;
}

$userId = $_SESSION['user_id'];

// Yetki kontrolü
$db = getDB();
$stmt = $db->prepare("SELECT role_id FROM server_members WHERE server_id = ? AND user_id = ?");
$stmt->execute([$serverId, $userId]);
$member = $stmt->fetch();

if (!$member || $member['role_id'] > 2) {
    echo json_encode(['success' => false, 'error' => 'Bu işlem için yetkiniz yok']);
    exit;
}

// Kanalı oluştur
$result = createChannel($serverId, $name, $userId, $type);

if (!$result['success']) {
    echo json_encode($result);
    exit;
}

$channelId = $result['channel_id'];

// Arkadaşları davet et
$invitedFriends = [];
foreach ($friends as $friendId) {
    $inviteResult = inviteFriendToChannel($channelId, $friendId, $userId);
    if ($inviteResult['success']) {
        $invitedFriends[] = $friendId;
    }
}

// Davet kodu oluştur (eğer istenirse)
$inviteCode = null;
if ($createInvite) {
    $inviteResult = createChannelInvite($channelId, $userId, 0, null); // Sınırsız, süresiz
    if ($inviteResult['success']) {
        $inviteCode = $inviteResult['code'];
    }
}

echo json_encode([
    'success' => true,
    'channel_id' => $channelId,
    'invite_code' => $inviteCode,
    'invited_friends' => $invitedFriends,
    'invite_url' => $inviteCode ? "http://localhost:7272/discord-clone/invite/{$inviteCode}" : null
]);