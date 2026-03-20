<?php
// Discord Clone - Ana Yapılandırma
session_start();

// Hata raporlama
define('DEBUG', true);
if (DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Zaman dilimi
date_default_timezone_set('Europe/Istanbul');

// Veritabanı bağlantı bilgileri
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'discord_clone');

// Site ayarları
define('SITE_NAME', 'VoiceChat');
define('SITE_URL', 'http://localhost:7272/discord-clone');
define('UPLOAD_PATH', __DIR__ . '/../assets/uploads/');
define('AVATAR_PATH', UPLOAD_PATH . 'avatars/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB

// WebRTC Sunucu ayarları
define('WEBRTC_STUN_SERVER', 'stun:stun.l.google.com:19302');
define('WEBRTC_TURN_SERVER', '');
define('WEBRTC_TURN_USER', '');
define('WEBRTC_TURN_PASS', '');

// CSRF Token
define('CSRF_TOKEN_NAME', 'csrf_token');

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
            die("Veritabanı bağlantı hatası: " . $e->getMessage());
        }
    }
    return $db;
}

// CSRF Token oluştur
function generateCSRFToken() {
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

// CSRF Token doğrula
function validateCSRFToken($token) {
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

// Giriş kontrolü
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Mevcut kullanıcıyı getir
function getCurrentUser() {
    if (!isLoggedIn()) return null;
    
    $db = getDB();
    $stmt = $db->prepare("SELECT u.*, r.role_name, r.color as role_color, r.permissions 
                          FROM users u 
                          LEFT JOIN roles r ON u.role_id = r.id 
                          WHERE u.id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

// Kullanıcı temasını getir
function getUserTheme() {
    if (isLoggedIn()) {
        $user = getCurrentUser();
        return $user['theme'] ?? 'dark';
    }
    return 'dark';
}

// Yönlendirme
function redirect($url) {
    header("Location: $url");
    exit;
}

// Güvenli çıktı
function e($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

// JSON yanıtı gönder
function jsonResponse($success, $data = [], $error = null) {
    header('Content-Type: application/json; charset=utf-8');
    $response = ['success' => $success];
    if ($error) $response['error'] = $error;
    echo json_encode(array_merge($response, $data));
    exit;
}

// API yetkilendirme kontrolü
function requireAuth() {
    if (!isLoggedIn()) {
        jsonResponse(false, [], 'Giriş gerekli');
    }
}

// Moderatör kontrolü
function isModerator() {
    $user = getCurrentUser();
    if (!$user) return false;
    return in_array($user['role_name'], ['Admin', 'Moderator']);
}

// Admin kontrolü
function isAdmin() {
    $user = getCurrentUser();
    if (!$user) return false;
    return $user['role_name'] === 'Admin';
}

// Sunucu sahibi kontrolü
function isServerOwner($serverId) {
    $user = getCurrentUser();
    if (!$user) return false;
    
    $db = getDB();
    $stmt = $db->prepare("SELECT owner_id FROM servers WHERE id = ?");
    $stmt->execute([$serverId]);
    $server = $stmt->fetch();
    
    return $server && $server['owner_id'] == $user['id'];
}

// Kanal yetkisi kontrolü
function hasChannelPermission($channelId, $permission) {
    $user = getCurrentUser();
    if (!$user) return false;
    
    $db = getDB();
    
    // Kanal bilgilerini al
    $stmt = $db->prepare("SELECT c.*, s.owner_id FROM channels c JOIN servers s ON c.server_id = s.id WHERE c.id = ?");
    $stmt->execute([$channelId]);
    $channel = $stmt->fetch();
    
    if (!$channel) return false;
    
    // Sunucu sahibi tüm yetkilere sahip
    if ($channel['owner_id'] == $user['id']) return true;
    
    // Admin tüm yetkilere sahip
    if ($user['role_name'] === 'Admin') return true;
    
    // Rol izinlerini kontrol et
    $permissions = json_decode($user['permissions'] ?? '{}', true);
    return $permissions[$permission] ?? false;
}

// Kullanıcının sunucularını getir
function getUserServers($userId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT s.* FROM servers s 
                          JOIN server_members sm ON s.id = sm.server_id 
                          WHERE sm.user_id = ? 
                          ORDER BY s.created_at DESC");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

// Sunucu bilgilerini getir
function getServerById($serverId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT s.*, u.username as owner_name 
                          FROM servers s 
                          JOIN users u ON s.owner_id = u.id 
                          WHERE s.id = ?");
    $stmt->execute([$serverId]);
    return $stmt->fetch();
}

// Sunucu kanallarını getir
function getServerChannels($serverId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT c.*, s.name as server_name 
                          FROM channels c 
                          JOIN servers s ON c.server_id = s.id 
                          WHERE c.server_id = ? 
                          ORDER BY c.position ASC, c.created_at ASC");
    $stmt->execute([$serverId]);
    return $stmt->fetchAll();
}

// Kanal bilgilerini getir
function getChannelById($channelId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT c.*, s.name as server_name, s.owner_id 
                          FROM channels c 
                          JOIN servers s ON c.server_id = s.id 
                          WHERE c.id = ?");
    $stmt->execute([$channelId]);
    return $stmt->fetch();
}

// Sunucu üyelerini getir
function getServerMembers($serverId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT u.id, u.username, u.avatar, u.status, 
                          r.role_name, r.color 
                          FROM server_members sm 
                          JOIN users u ON sm.user_id = u.id 
                          LEFT JOIN roles r ON sm.role_id = r.id 
                          WHERE sm.server_id = ? 
                          ORDER BY FIELD(r.role_name, 'Admin', 'Moderator', 'Member', 'Guest'), u.username");
    $stmt->execute([$serverId]);
    return $stmt->fetchAll();
}

// Arkadaşları getir
function getFriends($userId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT u.id, u.username, u.avatar, u.status, f.id as friend_id
                          FROM friendships f 
                          JOIN users u ON (f.requester_id = u.id AND f.addressee_id = ?) 
                             OR (f.addressee_id = u.id AND f.requester_id = ?) 
                          WHERE f.status = 'accepted' AND u.id != ?");
    $stmt->execute([$userId, $userId, $userId]);
    return $stmt->fetchAll();
}

// Bekleyen arkadaşlık isteklerini getir
function getPendingRequests($userId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT f.id, u.username, u.avatar 
                          FROM friendships f 
                          JOIN users u ON f.requester_id = u.id 
                          WHERE f.addressee_id = ? AND f.status = 'pending'");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

// Bekleyen kanal davetlerini getir
function getPendingChannelInvites($userId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT cf.id, u.username as inviter_name, c.name as channel_name, s.name as server_name, cf.channel_id
                          FROM channel_friends cf 
                          JOIN users u ON cf.invited_by = u.id 
                          JOIN channels c ON cf.channel_id = c.id 
                          JOIN servers s ON c.server_id = s.id 
                          WHERE cf.user_id = ? AND cf.status = 'pending'");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

// Tüm rolleri getir
function getAllRoles() {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM roles ORDER BY id ASC");
    return $stmt->fetchAll();
}

// Rol bilgilerini getir
function getRoleById($roleId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM roles WHERE id = ?");
    $stmt->execute([$roleId]);
    return $stmt->fetch();
}

// Kullanıcı durumunu güncelle
function updateUserStatus($userId, $status) {
    $db = getDB();
    $stmt = $db->prepare("UPDATE users SET status = ?, last_seen = NOW() WHERE id = ?");
    return $stmt->execute([$status, $userId]);
}

// Benzersiz kod oluştur
function generateUniqueCode($length = 10) {
    return substr(str_shuffle(str_repeat('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', $length)), 0, $length);
}

// Davet linki oluştur
function generateInviteCode() {
    return generateUniqueCode(8);
}

// WebRTC odası kodu oluştur
function generateRoomCode() {
    return 'room_' . generateUniqueCode(12);
}
