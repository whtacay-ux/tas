<?php
// Discord Clone - Ayarlar Sayfası
require_once '../includes/config.php';

if (!isLoggedIn()) {
    redirect('../index.php');
}

$user = getCurrentUser();
$db = getDB();
$error = '';
$success = '';

// Profil güncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    
    if (empty($username) || empty($email)) {
        $error = 'Kullanıcı adı ve e-posta gerekli';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Geçerli bir e-posta adresi girin';
    } else {
        try {
            $stmt = $db->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
            $stmt->execute([$username, $email, $user['id']]);
            $success = 'Profil güncellendi';
        } catch (Exception $e) {
            $error = 'Bu kullanıcı adı veya e-posta zaten kullanımda';
        }
    }
}

// Şifre değiştirme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($currentPassword) || empty($newPassword)) {
        $error = 'Tüm şifre alanları gerekli';
    } elseif (strlen($newPassword) < 6) {
        $error = 'Yeni şifre en az 6 karakter olmalı';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Şifreler eşleşmiyor';
    } else {
        // Mevcut şifreyi kontrol et
        $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user['id']]);
        $currentHash = $stmt->fetch()['password'];
        
        if (!password_verify($currentPassword, $currentHash)) {
            $error = 'Mevcut şifre hatalı';
        } else {
            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$newHash, $user['id']]);
            $success = 'Şifre değiştirildi';
        }
    }
}

// Avatar yükleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar'])) {
    $file = $_FILES['avatar'];
    
    if ($file['error'] === 0) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        
        if (in_array($file['type'], $allowedTypes)) {
            if ($file['size'] <= 2 * 1024 * 1024) { // 2MB
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = 'avatar_' . $user['id'] . '_' . time() . '.' . $extension;
                $uploadPath = AVATAR_PATH . $filename;
                
                if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                    // Eski avatarı sil
                    if ($user['avatar'] && $user['avatar'] !== 'default-avatar.png') {
                        @unlink(AVATAR_PATH . $user['avatar']);
                    }
                    
                    $stmt = $db->prepare("UPDATE users SET avatar = ? WHERE id = ?");
                    $stmt->execute([$filename, $user['id']]);
                    $success = 'Avatar güncellendi';
                } else {
                    $error = 'Avatar yüklenemedi';
                }
            } else {
                $error = 'Avatar boyutu 2MB\'dan küçük olmalı';
            }
        } else {
            $error = 'Sadece JPG, PNG ve GIF dosyaları yüklenebilir';
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #5865f2;
            --bg-primary: #36393f;
            --bg-secondary: #2f3136;
            --bg-tertiary: #202225;
            --text-primary: #dcddde;
            --text-secondary: #96989d;
            --success: #43b581;
            --danger: #f04747;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
        }
        
        .header {
            background: var(--bg-secondary);
            padding: 16px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--bg-tertiary);
        }
        
        .header h1 {
            font-size: 20px;
        }
        
        .header a {
            color: var(--text-secondary);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .header a:hover {
            color: var(--text-primary);
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 24px;
        }
        
        .section {
            background: var(--bg-secondary);
            border-radius: 8px;
            padding: 24px;
            margin-bottom: 24px;
        }
        
        .section h2 {
            font-size: 18px;
            margin-bottom: 20px;
            color: var(--text-primary);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            color: var(--text-secondary);
            text-transform: uppercase;
            margin-bottom: 8px;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            background: var(--bg-tertiary);
            border: 1px solid transparent;
            border-radius: 4px;
            color: var(--text-primary);
            font-size: 14px;
            outline: none;
        }
        
        .form-group input:focus {
            border-color: var(--primary);
        }
        
        .avatar-section {
            display: flex;
            align-items: center;
            gap: 24px;
            margin-bottom: 20px;
        }
        
        .avatar-preview {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .avatar-upload {
            flex: 1;
        }
        
        .avatar-upload input[type="file"] {
            display: none;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background: #4752c4;
        }
        
        .btn-secondary {
            background: var(--bg-tertiary);
            color: var(--text-primary);
        }
        
        .btn-secondary:hover {
            background: #4f545c;
        }
        
        .alert {
            padding: 12px 16px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .alert-error {
            background: rgba(240, 71, 71, 0.1);
            color: var(--danger);
        }
        
        .alert-success {
            background: rgba(67, 181, 129, 0.1);
            color: var(--success);
        }
    </style>
</head>
<body>
    <div class="header">
        <a href="dashboard.php">
            <i class="fas fa-arrow-left"></i> Geri Dön
        </a>
        <h1>Hesap Ayarları</h1>
        <div></div>
    </div>
    
    <div class="container">
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo e($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo e($success); ?></div>
        <?php endif; ?>
        
        <!-- Avatar -->
        <div class="section">
            <h2><i class="fas fa-user-circle"></i> Profil Fotoğrafı</h2>
            <form method="POST" enctype="multipart/form-data">
                <div class="avatar-section">
                    <img src="../assets/uploads/avatars/<?php echo e($user['avatar'] ?: 'default-avatar.png'); ?>" 
                         alt="Avatar" class="avatar-preview"
                         onerror="this.src='../assets/uploads/avatars/default-avatar.png'">
                    <div class="avatar-upload">
                        <label for="avatar-input" class="btn btn-secondary">
                            <i class="fas fa-upload"></i> Yeni Fotoğraf Yükle
                        </label>
                        <input type="file" id="avatar-input" name="avatar" accept="image/*" onchange="this.form.submit()">
                        <p style="color: var(--text-secondary); font-size: 12px; margin-top: 8px;">
                            JPG, PNG veya GIF. Maksimum 2MB.
                        </p>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Profil Bilgileri -->
        <div class="section">
            <h2><i class="fas fa-user"></i> Profil Bilgileri</h2>
            <form method="POST">
                <div class="form-group">
                    <label>Kullanıcı Adı</label>
                    <input type="text" name="username" value="<?php echo e($user['username']); ?>" required>
                </div>
                <div class="form-group">
                    <label>E-posta</label>
                    <input type="email" name="email" value="<?php echo e($user['email']); ?>" required>
                </div>
                <button type="submit" name="update_profile" class="btn btn-primary">
                    <i class="fas fa-save"></i> Kaydet
                </button>
            </form>
        </div>
        
        <!-- Şifre Değiştirme -->
        <div class="section">
            <h2><i class="fas fa-lock"></i> Şifre Değiştir</h2>
            <form method="POST">
                <div class="form-group">
                    <label>Mevcut Şifre</label>
                    <input type="password" name="current_password" required>
                </div>
                <div class="form-group">
                    <label>Yeni Şifre</label>
                    <input type="password" name="new_password" required minlength="6">
                </div>
                <div class="form-group">
                    <label>Yeni Şifre Tekrar</label>
                    <input type="password" name="confirm_password" required>
                </div>
                <button type="submit" name="change_password" class="btn btn-primary">
                    <i class="fas fa-key"></i> Şifreyi Değiştir
                </button>
            </form>
        </div>
    </div>
</body>
</html>
