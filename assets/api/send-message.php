<?php
// Discord Clone - Mesaj Gönderme API
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

$channelId = $data['channel_id'] ?? null;
$message = $data['message'] ?? '';
$replyTo = $data['reply_to'] ?? null;

if (!$channelId || empty(trim($message))) {
    echo json_encode(['success' => false, 'error' => 'Kanal ve mesaj gerekli']);
    exit;
}

$userId = $_SESSION['user_id'];

// DÜZELTİLDİ: Fonksiyon var mı kontrol et, yoksa manuel işlem yap
if (!function_exists('sendMessage')) {
    // Manuel mesaj gönderme
    $db = getDB();
    try {
        $stmt = $db->prepare("INSERT INTO messages (user_id, channel_id, message, reply_to, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$userId, $channelId, trim($message), $replyTo]);
        $messageId = $db->lastInsertId();
        
        echo json_encode(['success' => true, 'message_id' => $messageId]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Veritabanı hatası: ' . $e->getMessage()]);
    }
    exit;
}

$result = sendMessage($userId, $channelId, $message, null, null, $replyTo);

// DÜZELTİLDİ: Buffer temizle
if (ob_get_level()) ob_end_clean();
echo json_encode($result);