<?php
// Discord Clone - Dosya Yükleme API
require_once '../includes/config.php';

// Hata gösterme kapalı (JSON yanıt bozulmasın)
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

// Giriş kontrolü
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Yetkisiz erişim']);
    exit;
}

if (!isset($_FILES['file'])) {
    echo json_encode(['success' => false, 'error' => 'Dosya bulunamadı']);
    exit;
}

$file = $_FILES['file'];
$type = $_POST['type'] ?? 'media';

// İzin verilen dosya türleri
$allowedTypes = [
    'image/jpeg', 'image/png', 'image/gif', 'image/webp',
    'video/mp4', 'video/webm',
    'audio/mp3', 'audio/ogg', 'audio/wav',
    'application/pdf',
    'text/plain'
];

// Hedef dizin
$uploadDir = $type === 'avatar' ? AVATAR_PATH : MEDIA_PATH;

if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$result = uploadFile($file, $uploadDir, $allowedTypes);

if ($result['success']) {
    $fileUrl = '../assets/uploads/' . ($type === 'avatar' ? 'avatars/' : 'channel_media/') . $result['file_name'];
    echo json_encode([
        'success' => true,
        'url' => $fileUrl,
        'file_name' => $result['file_name']
    ]);
} else {
    echo json_encode(['success' => false, 'error' => $result['error']]);
}