<?php
// Discord Clone - Bildirimler API
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

$userId = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $unreadOnly = isset($_GET['unread']) && $_GET['unread'] === '1';
    $notifications = getUserNotifications($userId, $unreadOnly);
    
    echo json_encode(['success' => true, 'notifications' => $notifications]);
    
} elseif ($method === 'POST') {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (!$data) {
        echo json_encode(['success' => false, 'error' => 'Geçersiz JSON verisi']);
        exit;
    }
    
    $notificationId = $data['notification_id'] ?? null;
    
    if ($notificationId) {
        $result = markNotificationRead($notificationId, $userId);
        echo json_encode(['success' => $result]);
    } else {
        // Tümünü okundu işaretle
        $result = markAllNotificationsRead($userId);
        echo json_encode(['success' => $result]);
    }
}