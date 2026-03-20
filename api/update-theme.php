<?php
// Discord Clone - Tema Güncelleme API
require_once '../includes/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, [], 'Geçersiz istek metodu');
}

requireAuth();

$input = json_decode(file_get_contents('php://input'), true);
$theme = $input['theme'] ?? 'dark';

if (!in_array($theme, ['dark', 'light'])) {
    jsonResponse(false, [], 'Geçersiz tema');
}

$user = getCurrentUser();
$db = getDB();

$stmt = $db->prepare("UPDATE users SET theme = ? WHERE id = ?");
if ($stmt->execute([$theme, $user['id']])) {
    jsonResponse(true, [], 'Tema güncellendi');
} else {
    jsonResponse(false, [], 'Tema güncellenemedi');
}
