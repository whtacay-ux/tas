<?php
// Discord Clone - Arkadaşları Getirme API
require_once '../includes/config.php';

header('Content-Type: application/json');

requireAuth();

$user = getCurrentUser();
$friends = getFriends($user['id']);

jsonResponse(true, ['friends' => $friends]);
