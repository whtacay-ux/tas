<?php
// Discord Clone - Arkadaşlık İsteği API
require_once '../includes/config.php';

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

$username = trim($data['username'] ?? '');

if (empty($username)) {
    echo json_encode(['success' => false, 'error' => 'Kullanıcı adı gerekli']);
    exit;
}

$userId = $_SESSION['user_id'];

// Kullanıcıyı bul
$db = getDB();
$stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
$stmt->execute([$username]);
$targetUser = $stmt->fetch();

if (!$targetUser) {
    echo json_encode(['success' => false, 'error' => 'Kullanıcı bulunamadı']);
    exit;
}

if ($targetUser['id'] == $userId) {
    echo json_encode(['success' => false, 'error' => 'Kendinize istek gönderemezsiniz']);
    exit;
}

// DÜZELTİLDİ: Direkt veritabanı işlemi (channels.php bağımlılığını kaldır)
try {
    // Mevcut isteği kontrol et
    $stmt = $db->prepare("SELECT status FROM friendships WHERE (requester_id = ? AND addressee_id = ?) OR (requester_id = ? AND addressee_id = ?)");
    $stmt->execute([$userId, $targetUser['id'], $targetUser['id'], $userId]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        echo json_encode(['success' => false, 'error' => 'Zaten bir istek mevcut veya arkadaşsınız']);
        exit;
    }
    
    // Yeni istek oluştur
    $stmt = $db->prepare("INSERT INTO friendships (requester_id, addressee_id, status, created_at) VALUES (?, ?, 'pending', NOW())");
    $stmt->execute([$userId, $targetUser['id']]);
    
    // Bildirim oluştur
    $stmt = $db->prepare("INSERT INTO notifications (user_id, sender_id, notification_type, content, is_read, created_at) VALUES (?, ?, 'friend_request', ?, 0, NOW())");
    $stmt->execute([$targetUser['id'], $userId, 'Yeni arkadaşlık isteği']);
    
    if (ob_get_level()) ob_end_clean();
    echo json_encode(['success' => true]);
    
} catch (PDOException $e) {
    if (ob_get_level()) ob_end_clean();
    echo json_encode(['success' => false, 'error' => 'Veritabanı hatası: ' . $e->getMessage()]);
}