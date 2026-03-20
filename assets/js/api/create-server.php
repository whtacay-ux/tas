<?php
// Discord Clone - Sunucu Oluşturma API
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

$name = trim($data['name'] ?? '');

if (empty($name) || strlen($name) < 2 || strlen($name) > 100) {
    echo json_encode(['success' => false, 'error' => 'Geçerli bir sunucu adı girin (2-100 karakter)']);
    exit;
}

$userId = $_SESSION['user_id'];

$result = createServer($name, $userId);

echo json_encode($result);