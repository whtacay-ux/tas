<?php
// Discord Clone - Kanal Oluşturma API
require_once '../includes/config.php';
require_once '../includes/channels.php';

// Hata gösterme kapalı (JSON yanıt bozulmasın)
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

// Giriş kontrolü
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Yetkisiz erişim']);
    exit;
}

// JSON veri al
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data) {
    echo json_encode(['success' => false, 'error' => 'Geçersiz JSON verisi']);
    exit;
}

$serverId = $data['server_id'] ?? null;
$name = trim($data['name'] ?? '');
$type = $data['type'] ?? 'text';

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

if (!$member || $member['role_id'] > 2) { // Admin veya Moderator değilse
    echo json_encode(['success' => false, 'error' => 'Bu işlem için yetkiniz yok']);
    exit;
}

// DÜZELTİLDİ: Parametre sırası ($createdBy önce geliyor)
$result = createChannel($serverId, $name, $userId, $type);

echo json_encode($result);