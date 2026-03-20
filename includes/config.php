<?php
// Discord Clone - Yapılandırma Dosyası

// DÜZELTİLDİ: Tüm hata çıktılarını en başta kapat
@ini_set('display_errors', 0);
@ini_set('display_startup_errors', 0);
error_reporting(0);

// Zaman dilimi
date_default_timezone_set('Europe/Istanbul');

// Oturum başlat
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// DÜZELTİLDİ: Hata loglama
ini_set('log_errors', 1);
$logDir = __DIR__ . '/../logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0755, true);
}
ini_set('error_log', $logDir . '/php_errors.log');

// Veritabanı yapılandırması (XAMPP)
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'discord_clone');

// Site yapılandırması
define('SITE_URL', 'http://localhost:7272/discord-clone');
define('SITE_NAME', 'Discord Clone');
define('SITE_VERSION', '1.0.0');

// Dosya yükleme yapılandırması
define('UPLOAD_PATH', __DIR__ . '/../assets/uploads/');
define('AVATAR_PATH', UPLOAD_PATH . 'avatars/');
define('MEDIA_PATH', UPLOAD_PATH . 'channel_media/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024);

// WebSocket yapılandırması
define('WS_HOST', 'localhost');
define('WS_PORT', 8080);

// WebRTC yapılandırması
define('STUN_SERVER', 'stun:stun.l.google.com:19302');
define('TURN_SERVER', '');
define('TURN_USER', '');
define('TURN_PASS', '');

// Güvenlik
define('CSRF_TOKEN_NAME', 'csrf_token');
define('SESSION_LIFETIME', 7200);

// Veritabanı bağlantısı
function getDB() {
    static $db = null;
    if ($db === null) {
        try {
            $db = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            error_log('DB Connection Error: ' . $e->getMessage());
            die(json_encode(['success' => false, 'error' => 'Veritabanı bağlantı hatası']));
        }
    }
    return $db;
}

// CSRF Token oluştur
function generateCSRFToken() {
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

// CSRF Token doğrula
function validateCSRFToken($token) {
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

// Kullanıcı giriş kontrolü
function isLoggedIn() {
    return isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0;
}

// Admin kontrolü
function isAdmin() {
    if (!isLoggedIn()) return false;
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT r.role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $role = $stmt->fetchColumn();
        return $role === 'Admin';
    } catch (Exception $e) {
        return false;
    }
}

// Moderatör kontrolü
function isModerator() {
    if (!isLoggedIn()) return false;
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT r.role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $role = $stmt->fetchColumn();
        return in_array($role, ['Admin', 'Moderator']);
    } catch (Exception $e) {
        return false;
    }
}

// Mevcut kullanıcı bilgilerini al
function getCurrentUser() {
    if (!isLoggedIn()) return null;
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT u.*, r.role_name, r.permissions, r.color FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
    } catch (Exception $e) {
        return null;
    }
}

// Kullanıcı temasını al
function getUserTheme() {
    if (isLoggedIn()) {
        $user = getCurrentUser();
        return $user['theme'] ?? 'dark';
    }
    return $_COOKIE['theme'] ?? 'dark';
}

// Güvenli çıkış
function redirect($url) {
    header("Location: " . $url);
    exit;
}

// Mesaj göster
function setFlashMessage($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlashMessage() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// Zaman formatı
function timeAgo($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) return 'az önce';
    if ($diff < 3600) return floor($diff / 60) . ' dk önce';
    if ($diff < 86400) return floor($diff / 3600) . ' saat önce';
    if ($diff < 604800) return floor($diff / 86400) . ' gün önce';
    return date('d.m.Y', $time);
}

// Dosya yükleme fonksiyonu
function uploadFile($file, $destination, $allowedTypes = []) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Dosya yüklenirken hata oluştu'];
    }
    
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'error' => 'Dosya boyutu çok büyük (max 10MB)'];
    }
    
    if (!empty($allowedTypes)) {
        $fileType = mime_content_type($file['tmp_name']);
        if (!in_array($fileType, $allowedTypes)) {
            return ['success' => false, 'error' => 'Geçersiz dosya türü'];
        }
    }
    
    $fileName = uniqid() . '_' . basename($file['name']);
    $targetPath = $destination . $fileName;
    
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => true, 'file_name' => $fileName];
    }
    
    return ['success' => false, 'error' => 'Dosya kaydedilemedi'];
}