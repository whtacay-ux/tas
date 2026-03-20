<?php
// Discord Clone - Davet Kodu ile Kanala Katılma
require_once '../includes/config.php';
require_once '../includes/channels.php';

$code = $_GET['code'] ?? '';

if (empty($code)) {
    redirect('../index.php');
}

// Davet kodunu doğrula
$validation = validateInviteCode($code);

if (!$validation['valid']) {
    $error = $validation['error'];
    include '../pages/invite-error.php';
    exit;
}

$invite = $validation['invite'];

// Kullanıcı giriş yapmamışsa login'e yönlendir
if (!isLoggedIn()) {
    $_SESSION['invite_code'] = $code;
    $_SESSION['redirect_after_login'] = "invite.php?code={$code}";
    redirect('../index.php?action=login&invite=' . urlencode($code));
}

$userId = $_SESSION['user_id'];

// Zaten üye mi kontrol et
$stmt = getDB()->prepare("SELECT 1 FROM server_members WHERE server_id = ? AND user_id = ?");
$stmt->execute([$invite['server_id'], $userId]);

if ($stmt->fetch()) {
    // Zaten üye, direkt kanala yönlendir
    redirect("dashboard.php?server={$invite['server_id']}&channel={$invite['channel_id']}");
}

// Daveti kullan
$result = useInviteCode($code, $userId);

if ($result['success']) {
    redirect("dashboard.php?server={$result['server_id']}&channel={$result['channel_id']}");
} else {
    $error = $result['error'];
    include '../pages/invite-error.php';
}