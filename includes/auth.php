<?php
// Discord Clone - Kimlik Doğrulama İşlemleri

require_once 'config.php';

// Kullanıcı kaydı
function registerUser($username, $email, $password, $confirmPassword) {
    $errors = [];
    
    // Validasyon
    if (empty($username) || strlen($username) < 3 || strlen($username) > 50) {
        $errors[] = "Kullanıcı adı 3-50 karakter arasında olmalıdır";
    }
    
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = "Kullanıcı adı sadece harf, rakam ve alt çizgi içerebilir";
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Geçerli bir e-posta adresi girin";
    }
    
    if (strlen($password) < 6) {
        $errors[] = "Şifre en az 6 karakter olmalıdır";
    }
    
    if ($password !== $confirmPassword) {
        $errors[] = "Şifreler eşleşmiyor";
    }
    
    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }
    
    $db = getDB();
    
    // Kullanıcı adı veya email kontrolü
    $stmt = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    if ($stmt->fetch()) {
        return ['success' => false, 'errors' => ["Bu kullanıcı adı veya e-posta zaten kullanılıyor"]];
    }
    
    // Şifre hashle
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
    
    // Kullanıcıyı kaydet
    $stmt = $db->prepare("INSERT INTO users (username, email, password, role_id) VALUES (?, ?, ?, 3)");
    try {
        $stmt->execute([$username, $email, $hashedPassword]);
        $userId = $db->lastInsertId();
        
        // Varsayılan kullanıcı ayarlarını oluştur
        $stmt = $db->prepare("INSERT INTO user_settings (user_id) VALUES (?)");
        $stmt->execute([$userId]);
        
        return ['success' => true, 'user_id' => $userId];
    } catch (PDOException $e) {
        return ['success' => false, 'errors' => ["Kayıt sırasında hata oluştu: " . $e->getMessage()]];
    }
}

// Kullanıcı girişi
function loginUser($usernameOrEmail, $password, $remember = false) {
    $db = getDB();
    
    // Kullanıcıyı bul
    $stmt = $db->prepare("SELECT u.*, r.role_name, r.permissions FROM users u JOIN roles r ON u.role_id = r.id WHERE u.username = ? OR u.email = ?");
    $stmt->execute([$usernameOrEmail, $usernameOrEmail]);
    $user = $stmt->fetch();
    
    if (!$user) {
        return ['success' => false, 'error' => "Kullanıcı bulunamadı"];
    }
    
    if (!password_verify($password, $user['password'])) {
        return ['success' => false, 'error' => "Şifre hatalı"];
    }
    
    // Oturum bilgilerini ayarla
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role_name'];
    
    // Durumu güncelle
    $stmt = $db->prepare("UPDATE users SET status = 'online', last_seen = NOW() WHERE id = ?");
    $stmt->execute([$user['id']]);
    
    // Beni hatırla
    if ($remember) {
        $token = bin2hex(random_bytes(32));
        setcookie('remember_token', $token, time() + 30 * 24 * 60 * 60, '/');
        // Token veritabanına kaydedilebilir
    }
    
    return ['success' => true, 'user' => $user];
}

// Kullanıcı çıkışı
function logoutUser() {
    if (isLoggedIn()) {
        $db = getDB();
        $stmt = $db->prepare("UPDATE users SET status = 'offline', last_seen = NOW() WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
    }
    
    // Oturumları temizle
    session_destroy();
    setcookie('remember_token', '', time() - 3600, '/');
    
    return true;
}

// Şifre değiştirme
function changePassword($userId, $currentPassword, $newPassword, $confirmPassword) {
    if (strlen($newPassword) < 6) {
        return ['success' => false, 'error' => "Yeni şifre en az 6 karakter olmalıdır"];
    }
    
    if ($newPassword !== $confirmPassword) {
        return ['success' => false, 'error' => "Şifreler eşleşmiyor"];
    }
    
    $db = getDB();
    
    // Mevcut şifreyi kontrol et
    $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!password_verify($currentPassword, $user['password'])) {
        return ['success' => false, 'error' => "Mevcut şifre hatalı"];
    }
    
    // Yeni şifreyi kaydet
    $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
    $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->execute([$hashedPassword, $userId]);
    
    return ['success' => true];
}

// Profil güncelleme
function updateProfile($userId, $data) {
    $allowedFields = ['username', 'email', 'avatar', 'theme'];
    $updates = [];
    $params = [];
    
    foreach ($data as $field => $value) {
        if (in_array($field, $allowedFields)) {
            $updates[] = "$field = ?";
            $params[] = $value;
        }
    }
    
    if (empty($updates)) {
        return ['success' => false, 'error' => "Güncellenecek alan yok"];
    }
    
    $params[] = $userId;
    $db = getDB();
    
    $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
    $stmt = $db->prepare($sql);
    
    try {
        $stmt->execute($params);
        return ['success' => true];
    } catch (PDOException $e) {
        return ['success' => false, 'error' => "Güncelleme hatası: " . $e->getMessage()];
    }
}

// Kullanıcı durumunu güncelle
function updateUserStatus($userId, $status) {
    $validStatuses = ['online', 'offline', 'idle', 'dnd'];
    if (!in_array($status, $validStatuses)) {
        return false;
    }
    
    $db = getDB();
    $stmt = $db->prepare("UPDATE users SET status = ? WHERE id = ?");
    return $stmt->execute([$status, $userId]);
}

// Tüm kullanıcıları getir (admin için)
function getAllUsers($limit = 50, $offset = 0) {
    $db = getDB();
    $stmt = $db->prepare("SELECT u.id, u.username, u.email, u.avatar, u.status, u.created_at, r.role_name, r.color FROM users u JOIN roles r ON u.role_id = r.id ORDER BY u.created_at DESC LIMIT ? OFFSET ?");
    $stmt->execute([$limit, $offset]);
    return $stmt->fetchAll();
}

// Kullanıcıyı ID'ye göre getir
function getUserById($userId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT u.*, r.role_name, r.color FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetch();
}

// Kullanıcı rolünü güncelle (admin için)
function updateUserRole($userId, $roleId) {
    $db = getDB();
    $stmt = $db->prepare("UPDATE users SET role_id = ? WHERE id = ?");
    return $stmt->execute([$roleId, $userId]);
}

// Kullanıcıyı yasakla (admin için)
function banUser($userId, $reason = '') {
    $db = getDB();
    // Kullanıcıyı sil veya ayrı bir banned tablosuna ekle
    $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
    return $stmt->execute([$userId]);
}
