<?php
// Discord Clone - Durum Güncelleme API
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Hata gösterme kapalı (JSON yanıt bozulmasın)
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

// Giriş kontrolü
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

$status = $data['status'] ?? 'online';

if (!in_array($status, ['online', 'offline', 'idle', 'dnd'])) {
    echo json_encode(['success' => false, 'error' => 'Geçersiz durum']);
    exit;
}

$userId = $_SESSION['user_id'];

$result = updateUserStatus($userId, $status);

echo json_encode(['success' => $result]);