<?php
// Discord Clone - Kimlik Doğrulama İşlemleri
require_once 'config.php';

// Kullanıcı girişi
function loginUser($usernameOrEmail, $password, $remember = false) {
    $db = getDB();
    
    // Kullanıcıyı bul
    $stmt = $db->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$usernameOrEmail, $usernameOrEmail]);
    $user = $stmt->fetch();
    
    if (!$user) {
        return ['success' => false, 'error' => 'Kullanıcı adı veya şifre hatalı'];
    }
    
    // Şifreyi doğrula
    if (!password_verify($password, $user['password'])) {
        return ['success' => false, 'error' => 'Kullanıcı adı veya şifre hatalı'];
    }
    
    // Şifre yenileme gerekiyorsa (eski hash)
    if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
        $newHash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$newHash, $user['id']]);
    }
    
    // Oturum başlat
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    
    // Durumu güncelle
    updateUserStatus($user['id'], 'online');
    
    // Beni hatırla çerezi
    if ($remember) {
        $token = bin2hex(random_bytes(32));
        setcookie('remember_token', $token, time() + 30 * 24 * 60 * 60, '/', '', false, true);
        
        $stmt = $db->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
        $stmt->execute([$token, $user['id']]);
    }
    
    // Varsayılan ayarları oluştur
    createDefaultUserSettings($user['id']);
    
    return ['success' => true, 'user' => $user];
}

// Kullanıcı kaydı
function registerUser($username, $email, $password, $confirmPassword) {
    $errors = [];
    $db = getDB();
    
    // Validasyon
    if (strlen($username) < 3 || strlen($username) > 50) {
        $errors[] = 'Kullanıcı adı 3-50 karakter arasında olmalı';
    }
    
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = 'Kullanıcı adı sadece harf, rakam ve alt çizgi içerebilir';
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Geçerli bir e-posta adresi girin';
    }
    
    if (strlen($password) < 6) {
        $errors[] = 'Şifre en az 6 karakter olmalı';
    }
    
    if ($password !== $confirmPassword) {
        $errors[] = 'Şifreler eşleşmiyor';
    }
    
    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }
    
    // Benzersizlik kontrolü
    $stmt = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    if ($stmt->fetch()) {
        return ['success' => false, 'errors' => ['Bu kullanıcı adı veya e-posta zaten kullanımda']];
    }
    
    // Şifreyi hashle
    $hash = password_hash($password, PASSWORD_DEFAULT);
    
    // Varsayılan rol (Member = 3)
    $defaultRoleId = 3;
    
    // Kullanıcıyı oluştur
    $stmt = $db->prepare("INSERT INTO users (username, email, password, role_id, status, created_at) VALUES (?, ?, ?, ?, 'online', NOW())");
    $stmt->execute([$username, $email, $hash, $defaultRoleId]);
    $userId = $db->lastInsertId();
    
    // Varsayılan ayarları oluştur
    createDefaultUserSettings($userId);
    
    // Genel sunucuya otomatik ekle
    addUserToDefaultServer($userId);
    
    return ['success' => true, 'user_id' => $userId];
}

// Varsayılan kullanıcı ayarları
function createDefaultUserSettings($userId) {
    $db = getDB();
    $stmt = $db->prepare("INSERT IGNORE INTO user_settings (user_id, notification_sound, desktop_notifications, show_email, language) VALUES (?, 1, 1, 0, 'tr')");
    $stmt->execute([$userId]);
}

// Kullanıcıyı varsayılan sunucuya ekle
function addUserToDefaultServer($userId) {
    $db = getDB();
    
    // Genel Sunucu'yu bul (id = 1)
    $stmt = $db->prepare("SELECT id FROM servers WHERE id = 1");
    $stmt->execute();
    $server = $stmt->fetch();
    
    if ($server) {
        // Üye olarak ekle (rol_id = 3 - Member)
        $stmt = $db->prepare("INSERT IGNORE INTO server_members (server_id, user_id, role_id) VALUES (?, ?, 3)");
        $stmt->execute([$server['id'], $userId]);
    }
}

// Çıkış yap
function logoutUser() {
    if (isset($_SESSION['user_id'])) {
        updateUserStatus($_SESSION['user_id'], 'offline');
    }
    
    // Çerezleri temizle
    setcookie('remember_token', '', time() - 3600, '/');
    
    // Oturumu sonlandır
    session_destroy();
    
    return ['success' => true];
}

// Beni hatırla ile oturum aç
function checkRememberToken() {
    if (isLoggedIn()) return true;
    if (!isset($_COOKIE['remember_token'])) return false;
    
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE remember_token = ?");
    $stmt->execute([$_COOKIE['remember_token']]);
    $user = $stmt->fetch();
    
    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        updateUserStatus($user['id'], 'online');
        return true;
    }
    
    return false;
}
