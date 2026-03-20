<?php
// Discord Clone - Tema Güncelleme API
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

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data) {
    echo json_encode(['success' => false, 'error' => 'Geçersiz JSON verisi']);
    exit;
}

$theme = $data['theme'] ?? 'dark';

if (!in_array($theme, ['dark', 'light'])) {
    echo json_encode(['success' => false, 'error' => 'Geçersiz tema']);
    exit;
}

$userId = $_SESSION['user_id'];

$result = updateProfile($userId, ['theme' => $theme]);

echo json_encode($result);