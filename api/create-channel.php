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

$serverId = isset($data['server_id']) ? intval($data['server_id']) : 0;
$name = trim($data['name'] ?? '');
$type = $data['type'] ?? 'text';

if (!$serverId || $serverId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Sunucu ID gerekli veya geçersiz (ID: ' . $serverId . ')']);
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

// Yetki kontrolü - Sunucu sahibi veya moderatör mü?
$db = getDB();

// Önce sunucu sahibi mi kontrol et
$stmt = $db->prepare("SELECT owner_id FROM servers WHERE id = ?");
$stmt->execute([$serverId]);
$server = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$server) {
    echo json_encode(['success' => false, 'error' => 'Sunucu bulunamadı (ID: ' . $serverId . ')']);
    exit;
}

$isOwner = ($server['owner_id'] == $userId);
$isModerator = false;

if (!$isOwner) {
    // Moderatör veya admin mi kontrol et
    $stmt = $db->prepare("SELECT sm.role_id, r.role_name 
                          FROM server_members sm 
                          JOIN roles r ON sm.role_id = r.id 
                          WHERE sm.server_id = ? AND sm.user_id = ?");
    $stmt->execute([$serverId, $userId]);
    $member = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($member && in_array($member['role_name'], ['Admin', 'Moderator'])) {
        $isModerator = true;
    }
}

if (!$isOwner && !$isModerator) {
    echo json_encode(['success' => false, 'error' => 'Bu işlem için yetkiniz yok']);
    exit;
}

// Kanalı oluştur - channels.php'deki fonksiyonu kullan
$result = createChannel($serverId, $name, $userId, $type);

echo json_encode($result);