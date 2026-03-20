<?php
// Discord Clone - Ayarlar Sayfası
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Giriş kontrolü
if (!isLoggedIn()) {
    redirect('../index.php');
}

$user = getCurrentUser();
$error = '';
$success = '';

// Profil güncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    
    if (empty($username) || empty($email)) {
        $error = 'Tüm alanları doldurun';
    } else {
        $result = updateProfile($user['id'], [
            'username' => $username,
            'email' => $email
        ]);
        
        if ($result['success']) {
            $success = 'Profil başarıyla güncellendi';
            $_SESSION['username'] = $username;
            $user = getCurrentUser();
        } else {
            $error = $result['error'];
        }
    }
}

// Şifre değiştirme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    
    $result = changePassword($user['id'], $current, $new, $confirm);
    
    if ($result['success']) {
        $success = 'Şifre başarıyla değiştirildi';
    } else {
        $error = $result['error'];
    }
}

// Avatar yükleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_avatar'])) {
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $result = uploadFile($_FILES['avatar'], AVATAR_PATH, ['image/jpeg', 'image/png', 'image/gif']);
        
        if ($result['success']) {
            updateProfile($user['id'], ['avatar' => $result['file_name']]);
            $success = 'Avatar başarıyla güncellendi';
            $user = getCurrentUser();
        } else {
            $error = $result['error'];
        }
    }
}

$theme = $user['theme'];
?>
<!DOCTYPE html>
<html lang="tr" data-theme="<?php echo $theme; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ayarlar - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .settings-sidebar {
            width: 280px;
            background: var(--bg-secondary);
            padding: 20px;
            overflow-y: auto;
        }
        
        .settings-nav-group {
            margin-bottom: 24px;
        }
        
        .settings-nav-title {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--text-secondary);
            padding: 8px 16px;
            margin-bottom: 4px;
        }
        
        .settings-nav-item {
            display: block;
            padding: 10px 16px;
            border-radius: 4px;
            color: var(--text-secondary);
            font-size: 14px;
            cursor: pointer;
            transition: all var(--transition-fast);
            text-decoration: none;
        }
        
        .settings-nav-item:hover,
        .settings-nav-item.active {
            background: var(--bg-hover);
            color: var(--text-primary);
            text-decoration: none;
        }
        
        .settings-content {
            flex: 1;
            padding: 40px;
            overflow-y: auto;
        }
        
        .settings-section {
            max-width: 700px;
        }
        
        .settings-section h2 {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 8px;
            text-transform: uppercase;
        }
        
        .settings-section > p {
            color: var(--text-secondary);
            margin-bottom: 32px;
        }
        
        .settings-card {
            background: var(--bg-secondary);
            border-radius: 8px;
            padding: 24px;
            margin-bottom: 24px;
        }
        
        .settings-card h3 {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 16px;
            text-transform: uppercase;
            color: var(--text-secondary);
        }
        
        .avatar-upload {
            display: flex;
            align-items: center;
            gap: 24px;
            margin-bottom: 24px;
        }
        
        .avatar-upload img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .avatar-upload-actions {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .back-btn {
            position: fixed;
            top: 20px;
            left: 300px;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--bg-tertiary);
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 10;
        }
        
        .back-btn:hover {
            background: var(--bg-hover);
            color: var(--text-primary);
        }
        
        .danger-zone {
            border: 1px solid var(--danger);
        }
        
        .danger-zone h3 {
            color: var(--danger);
        }
    </style>
</head>
<body>
    <div class="settings-container" style="display: flex; height: 100vh;">
        <!-- Sol Menü -->
        <div class="settings-sidebar">
            <div class="settings-nav-group">
                <div class="settings-nav-title">Kullanıcı Ayarları</div>
                <a href="#profile" class="settings-nav-item active">Hesabım</a>
                <a href="#privacy" class="settings-nav-item">Gizlilik ve Güvenlik</a>
                <a href="#notifications" class="settings-nav-item">Bildirimler</a>
            </div>
            
            <div class="settings-nav-group">
                <div class="settings-nav-title">Uygulama Ayarları</div>
                <a href="#appearance" class="settings-nav-item">Görünüm</a>
                <a href="#language" class="settings-nav-item">Dil</a>
            </div>
            
            <div style="margin-top: auto; padding-top: 20px;">
                <a href="dashboard.php" class="settings-nav-item" style="color: var(--danger);">
                    <i class="fas fa-arrow-left"></i> Geri Dön
                </a>
            </div>
        </div>
        
        <!-- İçerik -->
        <div class="settings-content">
            <a href="dashboard.php" class="back-btn">
                <i class="fas fa-times"></i>
            </a>
            
            <div class="settings-section">
                <h2>Hesabım</h2>
                <p>Hesap bilgilerinizi buradan yönetebilirsiniz.</p>
                
                <?php if ($error): ?>
                    <div class="alert alert-error" style="margin-bottom: 20px;">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success" style="margin-bottom: 20px;">
                        <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Avatar -->
                <div class="settings-card">
                    <h3>Profil Fotoğrafı</h3>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="avatar-upload">
                            <img src="../assets/uploads/avatars/<?php echo $user['avatar']; ?>" alt="Avatar">
                            <div class="avatar-upload-actions">
                                <input type="file" name="avatar" id="avatar-input" accept="image/*" style="display: none;">
                                <button type="button" class="btn btn-secondary" onclick="document.getElementById('avatar-input').click()">
                                    <i class="fas fa-upload"></i> Yeni Fotoğraf Yükle
                                </button>
                                <button type="submit" name="upload_avatar" class="btn btn-primary" id="avatar-submit" style="display: none;">
                                    Kaydet
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- Profil Bilgileri -->
                <div class="settings-card">
                    <h3>Kullanıcı Bilgileri</h3>
                    <form method="POST">
                        <div class="form-group">
                            <label class="form-label">Kullanıcı Adı</label>
                            <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">E-posta</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Rol</label>
                            <input type="text" value="<?php echo $user['role_name']; ?>" disabled style="opacity: 0.7;">
                        </div>
                        
                        <button type="submit" name="update_profile" class="btn btn-primary">
                            <i class="fas fa-save"></i> Değişiklikleri Kaydet
                        </button>
                    </form>
                </div>
                
                <!-- Şifre Değiştirme -->
                <div class="settings-card">
                    <h3>Şifre Değiştir</h3>
                    <form method="POST">
                        <div class="form-group">
                            <label class="form-label">Mevcut Şifre</label>
                            <input type="password" name="current_password" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Yeni Şifre</label>
                            <input type="password" name="new_password" required minlength="6">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Yeni Şifre Tekrar</label>
                            <input type="password" name="confirm_password" required>
                        </div>
                        
                        <button type="submit" name="change_password" class="btn btn-primary">
                            <i class="fas fa-key"></i> Şifreyi Değiştir
                        </button>
                    </form>
                </div>
                
                <!-- Tema Ayarları -->
                <div class="settings-card">
                    <h3>Görünüm</h3>
                    <div class="settings-item">
                        <div class="settings-item-info">
                            <h4>Koyu Tema</h4>
                            <p>Daha karanlık bir görünüm tercih edin</p>
                        </div>
                        <label class="toggle">
                            <input type="checkbox" id="theme-toggle" <?php echo $theme === 'dark' ? 'checked' : ''; ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>
                
                <!-- Tehlikeli Bölge -->
                <div class="settings-card danger-zone">
                    <h3>Hesabı Sil</h3>
                    <p style="margin-bottom: 16px; color: var(--text-secondary);">
                        Hesabınızı sildiğinizde tüm verileriniz kalıcı olarak silinecektir. Bu işlem geri alınamaz.
                    </p>
                    <button class="btn btn-danger" onclick="confirmDeleteAccount()">
                        <i class="fas fa-trash"></i> Hesabımı Sil
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/app.js"></script>
    <script>
        // Avatar seçildiğinde otomatik gönder
        document.getElementById('avatar-input').addEventListener('change', function() {
            if (this.files && this.files[0]) {
                document.getElementById('avatar-submit').style.display = 'inline-flex';
            }
        });
        
        // Tema toggle
        document.getElementById('theme-toggle').addEventListener('change', function() {
            ThemeManager.toggle();
        });
        
        function confirmDeleteAccount() {
            if (confirm('Hesabınızı silmek istediğinize emin misiniz? Bu işlem geri alınamaz!')) {
                if (prompt('Silmeyi onaylamak için şifrenizi girin:')) {
                    // Hesap silme işlemi
                    alert('Hesap silme özelliği yönetici tarafından yapılandırılmalıdır.');
                }
            }
        }
    </script>
</body>
</html>
