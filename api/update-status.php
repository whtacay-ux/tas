<?php
// Discord Clone - Durum Güncelleme API
require_once '../includes/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, [], 'Geçersiz istek metodu');
}

requireAuth();

$input = json_decode(file_get_contents('php://input'), true);
$status = $input['status'] ?? 'online';

if (!in_array($status, ['online', 'offline', 'idle', 'dnd'])) {
    jsonResponse(false, [], 'Geçersiz durum');
}

$user = getCurrentUser();

if (updateUserStatus($user['id'], $status)) {
    jsonResponse(true, [], 'Durum güncellendi');
} else {
    jsonResponse(false, [], 'Durum güncellenemedi');
}
