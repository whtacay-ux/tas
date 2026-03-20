<?php
// Discord Clone - Rol Atama API
require_once '../includes/config.php';
require_once '../includes/channels.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, [], 'Geçersiz istek metodu');
}

requireAuth();

$input = json_decode(file_get_contents('php://input'), true);
$serverId = intval($input['server_id'] ?? 0);
$userId = intval($input['user_id'] ?? 0);
$roleId = intval($input['role_id'] ?? 0);

if (!$serverId || !$userId || !$roleId) {
    jsonResponse(false, [], 'Sunucu ID, kullanıcı ID ve rol ID gerekli');
}

$currentUser = getCurrentUser();

$result = updateUserRole($serverId, $userId, $roleId, $currentUser['id']);

if ($result['success']) {
    jsonResponse(true, ['message' => $result['message']]);
} else {
    jsonResponse(false, [], $result['error']);
}
