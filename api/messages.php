<?php
// Discord Clone - Mesaj API
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

$method = $_SERVER['REQUEST_METHOD'];
$userId = $_SESSION['user_id'];

if ($method === 'GET') {
    $channelId = $_GET['channel_id'] ?? null;
    $beforeId = $_GET['before'] ?? null;
    $afterId = $_GET['after'] ?? null;
    $limit = min($_GET['limit'] ?? 50, 100);
    
    if (!$channelId) {
        echo json_encode(['success' => false, 'error' => 'Kanal ID gerekli']);
        exit;
    }
    
    // DÜZELTİLDİ: Daha hızlı sorgu - sadece gerekli alanlar
    $messages = getChannelMessages($channelId, $limit, $beforeId);
    
    // DÜZELTİLDİ: Hızlı yanıt için buffer temizleme
    if (ob_get_level()) ob_end_clean();
    
    echo json_encode(['success' => true, 'messages' => $messages]);
    
} elseif ($method === 'POST') {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (!$data) {
        echo json_encode(['success' => false, 'error' => 'Geçersiz JSON verisi']);
        exit;
    }
    
    $channelId = $data['channel_id'] ?? null;
    $message = $data['message'] ?? '';
    $replyTo = $data['reply_to'] ?? null;
    
    if (!$channelId) {
        echo json_encode(['success' => false, 'error' => 'Kanal ID gerekli']);
        exit;
    }
    
    // DÜZELTİLDİ: Hızlı yanıt
    $result = sendMessage($userId, $channelId, $message, null, null, $replyTo);
    
    if (ob_get_level()) ob_end_clean();
    echo json_encode($result);
    
} elseif ($method === 'DELETE') {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (!$data) {
        echo json_encode(['success' => false, 'error' => 'Geçersiz JSON verisi']);
        exit;
    }
    
    $messageId = $data['message_id'] ?? null;
    
    if (!$messageId) {
        echo json_encode(['success' => false, 'error' => 'Mesaj ID gerekli']);
        exit;
    }
    
    $result = deleteMessage($messageId, $userId);
    
    if (ob_get_level()) ob_end_clean();
    echo json_encode($result);
    
} elseif ($method === 'PUT') {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (!$data) {
        echo json_encode(['success' => false, 'error' => 'Geçersiz JSON verisi']);
        exit;
    }
    
    $messageId = $data['message_id'] ?? null;
    $newMessage = $data['message'] ?? '';
    
    if (!$messageId) {
        echo json_encode(['success' => false, 'error' => 'Mesaj ID gerekli']);
        exit;
    }
    
    $result = editMessage($messageId, $userId, $newMessage);
    
    if (ob_get_level()) ob_end_clean();
    echo json_encode($result);
}