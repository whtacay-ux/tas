<?php
// Discord Clone - Arkadaşlık İsteği Yanıt API
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

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data) {
    echo json_encode(['success' => false, 'error' => 'Geçersiz JSON verisi']);
    exit;
}

$friendshipId = $data['friendship_id'] ?? null;
$accept = $data['accept'] ?? false;

if (!$friendshipId) {
    echo json_encode(['success' => false, 'error' => 'İstek ID gerekli']);
    exit;
}

$userId = $_SESSION['user_id'];

$result = respondFriendRequest($friendshipId, $userId, $accept);

echo json_encode($result);