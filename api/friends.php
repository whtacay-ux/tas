<?php
// Discord Clone - Arkadaş Listesi API
require_once '../includes/config.php';
require_once '../includes/channels.php';

error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Yetkisiz erişim']);
    exit;
}

$userId = $_SESSION['user_id'];
$friends = getFriends($userId);

echo json_encode(['success' => true, 'friends' => $friends]);