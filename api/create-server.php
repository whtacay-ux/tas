<?php
// Discord Clone - Sunucu Oluşturma API
require_once '../includes/config.php';
require_once '../includes/channels.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, [], 'Geçersiz istek metodu');
}

requireAuth();

$input = json_decode(file_get_contents('php://input'), true);
$name = trim($input['name'] ?? '');

if (empty($name)) {
    jsonResponse(false, [], 'Sunucu adı gerekli');
}

if (strlen($name) > 100) {
    jsonResponse(false, [], 'Sunucu adı çok uzun');
}

$user = getCurrentUser();

$result = createServer($name, $user['id']);

if ($result['success']) {
    jsonResponse(true, ['server_id' => $result['server_id']]);
} else {
    jsonResponse(false, [], $result['error']);
}
