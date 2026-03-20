<?php
// Discord Clone - Dosya Yükleme API
require_once '../includes/config.php';

header('Content-Type: application/json');

requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, [], 'Geçersiz istek metodu');
}

if (!isset($_FILES['file'])) {
    jsonResponse(false, [], 'Dosya bulunamadı');
}

$file = $_FILES['file'];
$user = getCurrentUser();

// Dosya boyutu kontrolü
if ($file['size'] > MAX_FILE_SIZE) {
    jsonResponse(false, [], 'Dosya boyutu çok büyük (max 10MB)');
}

// Dosya uzantısı kontrolü
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'video/mp4', 'audio/mpeg', 'audio/wav'];
if (!in_array($file['type'], $allowedTypes)) {
    jsonResponse(false, [], 'Desteklenmeyen dosya türü');
}

// Dosya türünü belirle
$type = 'file';
if (strpos($file['type'], 'image/') === 0) {
    $type = 'image';
} elseif (strpos($file['type'], 'video/') === 0) {
    $type = 'video';
} elseif (strpos($file['type'], 'audio/') === 0) {
    $type = 'audio';
}

// Benzersiz dosya adı oluştur
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = uniqid() . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
$uploadPath = UPLOAD_PATH . $filename;

if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
    jsonResponse(true, [
        'url' => '../assets/uploads/' . $filename,
        'filename' => $filename,
        'type' => $type
    ]);
} else {
    jsonResponse(false, [], 'Dosya yüklenemedi');
}
