<?php
// Discord Clone - Arkadaşlık İsteği Gönderme API
require_once '../includes/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, [], 'Geçersiz istek metodu');
}

requireAuth();

$input = json_decode(file_get_contents('php://input'), true);
$username = trim($input['username'] ?? '');

if (empty($username)) {
    jsonResponse(false, [], 'Kullanıcı adı gerekli');
}

$user = getCurrentUser();
$db = getDB();

// Kendine istek gönderme
if ($username === $user['username']) {
    jsonResponse(false, [], 'Kendinize istek gönderemezsiniz');
}

// Hedef kullanıcıyı bul
$stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
$stmt->execute([$username]);
$targetUser = $stmt->fetch();

if (!$targetUser) {
    jsonResponse(false, [], 'Kullanıcı bulunamadı');
}

// Zaten arkadaş mı kontrol et
$stmt = $db->prepare("SELECT id, status FROM friendships 
                      WHERE (requester_id = ? AND addressee_id = ?) 
                         OR (requester_id = ? AND addressee_id = ?)");
$stmt->execute([$user['id'], $targetUser['id'], $targetUser['id'], $user['id']]);
$existing = $stmt->fetch();

if ($existing) {
    if ($existing['status'] === 'accepted') {
        jsonResponse(false, [], 'Bu kullanıcı zaten arkadaşınız');
    } elseif ($existing['status'] === 'pending') {
        jsonResponse(false, [], 'Bekleyen bir istek zaten var');
    } elseif ($existing['status'] === 'blocked') {
        jsonResponse(false, [], 'Bu kullanıcı engellenmiş');
    }
}

try {
    $stmt = $db->prepare("INSERT INTO friendships (requester_id, addressee_id, status, created_at) VALUES (?, ?, 'pending', NOW())");
    $stmt->execute([$user['id'], $targetUser['id']]);
    
    // Bildirim oluştur
    $stmt = $db->prepare("INSERT INTO notifications (user_id, sender_id, notification_type, content, created_at) VALUES (?, ?, 'friend_request', ?, NOW())");
    $stmt->execute([$targetUser['id'], $user['id'], "{$user['username']} size arkadaşlık isteği gönderdi"]);
    
    jsonResponse(true, [], 'Arkadaşlık isteği gönderildi');
    
} catch (Exception $e) {
    jsonResponse(false, [], 'İstek gönderilemedi: ' . $e->getMessage());
}
