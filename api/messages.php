<?php
// Discord Clone - Mesajları Getirme API
require_once '../includes/config.php';

header('Content-Type: application/json');

// Giriş kontrolü
requireAuth();

$channelId = $_GET['channel_id'] ?? null;
$before = $_GET['before'] ?? null;
$after = $_GET['after'] ?? null;

if (!$channelId) {
    jsonResponse(false, [], 'Kanal ID gerekli');
}

$user = getCurrentUser();
$db = getDB();

// Kanal kontrolü
$channel = getChannelById($channelId);
if (!$channel) {
    jsonResponse(false, [], 'Kanal bulunamadı');
}

// Üyelik kontrolü
$stmt = $db->prepare("SELECT id FROM server_members WHERE server_id = ? AND user_id = ?");
$stmt->execute([$channel['server_id'], $user['id']]);
if (!$stmt->fetch()) {
    jsonResponse(false, [], 'Bu sunucunun üyesi değilsiniz');
}

try {
    $sql = "SELECT m.*, u.username, u.avatar, r.color as user_color 
            FROM messages m 
            JOIN users u ON m.user_id = u.id 
            LEFT JOIN roles r ON u.role_id = r.id 
            WHERE m.channel_id = ?";
    $params = [$channelId];
    
    if ($before) {
        $sql .= " AND m.id < ?";
        $params[] = $before;
    }
    
    if ($after) {
        $sql .= " AND m.id > ?";
        $params[] = $after;
    }
    
    $sql .= " ORDER BY m.created_at DESC LIMIT 50";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $messages = $stmt->fetchAll();
    
    // Ters çevir (en eski mesaj önce)
    $messages = array_reverse($messages);
    
    jsonResponse(true, ['messages' => $messages]);
    
} catch (Exception $e) {
    jsonResponse(false, [], 'Mesajlar alınamadı: ' . $e->getMessage());
}
