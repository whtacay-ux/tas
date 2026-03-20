<?php
// Discord Clone - Bildirimleri Getirme API
require_once '../includes/config.php';

header('Content-Type: application/json');

requireAuth();

$user = getCurrentUser();
$db = getDB();

// Okunmamış bildirimleri getir
$stmt = $db->prepare("SELECT n.*, u.username as sender_name, u.avatar as sender_avatar 
                      FROM notifications n 
                      LEFT JOIN users u ON n.sender_id = u.id 
                      WHERE n.user_id = ? 
                      ORDER BY n.created_at DESC LIMIT 50");
$stmt->execute([$user['id']]);
$notifications = $stmt->fetchAll();

// Okunmamış sayısını getir
$stmt = $db->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
$stmt->execute([$user['id']]);
$unreadCount = $stmt->fetch()['count'];

jsonResponse(true, [
    'notifications' => $notifications,
    'unread_count' => $unreadCount
]);
