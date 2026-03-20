<?php
// Discord Clone - Ana Sayfa / Giriş
require_once 'includes/config.php';

// Zaten giriş yapmışsa ana uygulamaya yönlendir
if (isLoggedIn()) {
    redirect('pages/dashboard.php');
}

$error = '';
$success = '';

// Giriş formu gönderildi mi?
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    require_once 'includes/auth.php';
    
    $usernameOrEmail = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    $result = loginUser($usernameOrEmail, $password, $remember);
    
    if ($result['success']) {
        redirect('pages/dashboard.php');
    } else {
        $error = $result['error'];
    }
}

// Kayıt formu gönderildi mi?
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    require_once 'includes/auth.php';
    
    $username = $_POST['reg_username'] ?? '';
    $email = $_POST['reg_email'] ?? '';
    $password = $_POST['reg_password'] ?? '';
    $confirmPassword = $_POST['reg_confirm'] ?? '';
    
    $result = registerUser($username, $email, $password, $confirmPassword);
    
    if ($result['success']) {
        $success = 'Hesabınız başarıyla oluşturuldu! Şimdi giriş yapabilirsiniz.';
    } else {
        $error = implode('<br>', $result['errors']);
    }
}

$theme = getUserTheme();
?>
<!DOCTYPE html>
<html lang="tr" data-theme="<?php echo $theme; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo generateCSRFToken(); ?>">
    <title><?php echo SITE_NAME; ?> - Giriş</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .auth-tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 24px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .auth-tab {
            flex: 1;
            padding: 12px;
            background: transparent;
            color: var(--text-secondary);
            font-size: 14px;
            font-weight: 600;
            border-bottom: 2px solid transparent;
            transition: all var(--transition-fast);
        }
        
        .auth-tab.active {
            color: var(--primary);
            border-bottom-color: var(--primary);
        }
        
        .auth-tab:hover:not(.active) {
            color: var(--text-primary);
        }
        
        .auth-form {
            display: none;
        }
        
        .auth-form.active {
            display: block;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 24px;
        }
        
        .logo i {
            font-size: 64px;
            color: var(--primary);
        }
        
        .logo h1 {
            font-size: 28px;
            margin-top: 12px;
            color: var(--text-primary);
        }
        
        .forgot-password {
            text-align: right;
            margin-top: -8px;
            margin-bottom: 16px;
        }
        
        .forgot-password a {
            font-size: 12px;
            color: var(--text-link);
        }
        
        .checkbox-wrapper {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 16px;
        }
        
        .checkbox-wrapper input[type="checkbox"] {
            width: auto;
        }
        
        .checkbox-wrapper label {
            font-size: 13px;
            color: var(--text-secondary);
        }
    </style>
</head>
<body>
    <div class="auth-page">
        <div class="auth-container">
            <div class="auth-box">
                <div class="logo">
                    <i class="fab fa-discord"></i>
                    <h1><?php echo SITE_NAME; ?></h1>
                </div>
                
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                    </div>
                <?php endif; ?>
                
                <div class="auth-tabs">
                    <button class="auth-tab active" onclick="switchTab('login')">Giriş Yap</button>
                    <button class="auth-tab" onclick="switchTab('register')">Hesap Oluştur</button>
                </div>
                
                <!-- Giriş Formu -->
                <form id="login-form" class="auth-form active" method="POST" action="">
                    <div class="form-group">
                        <label class="form-label">E-posta veya Kullanıcı Adı</label>
                        <input type="text" name="username" required placeholder="ornek@email.com" autocomplete="username">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Şifre</label>
                        <input type="password" name="password" required placeholder="••••••••" autocomplete="current-password">
                    </div>
                    
                    <div class="forgot-password">
                        <a href="pages/forgot-password.php">Şifremi unuttum</a>
                    </div>
                    
                    <div class="checkbox-wrapper">
                        <input type="checkbox" name="remember" id="remember">
                        <label for="remember">Beni hatırla</label>
                    </div>
                    
                    <button type="submit" name="login" class="btn btn-primary btn-block btn-lg">
                        <i class="fas fa-sign-in-alt"></i> Giriş Yap
                    </button>
                </form>
                
                <!-- Kayıt Formu -->
                <form id="register-form" class="auth-form" method="POST" action="">
                    <div class="form-group">
                        <label class="form-label">Kullanıcı Adı</label>
                        <input type="text" name="reg_username" required placeholder="kullaniciadi" 
                               pattern="[a-zA-Z0-9_]+" minlength="3" maxlength="50"
                               title="Sadece harf, rakam ve alt çizgi kullanabilirsiniz">
                        <p class="form-hint">3-50 karakter, sadece harf, rakam ve alt çizgi</p>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">E-posta</label>
                        <input type="email" name="reg_email" required placeholder="ornek@email.com">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Şifre</label>
                        <input type="password" name="reg_password" required placeholder="••••••••" minlength="6">
                        <p class="form-hint">En az 6 karakter</p>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Şifre Tekrar</label>
                        <input type="password" name="reg_confirm" required placeholder="••••••••">
                    </div>
                    
                    <button type="submit" name="register" class="btn btn-primary btn-block btn-lg">
                        <i class="fas fa-user-plus"></i> Hesap Oluştur
                    </button>
                </form>
            </div>
            
            <p style="text-align: center; margin-top: 16px; color: var(--text-muted); font-size: 12px;">
                &copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. Tüm hakları saklıdır.
            </p>
        </div>
    </div>
    
    <button class="theme-toggle" title="Temayı Değiştir">
        <i class="fas fa-sun"></i>
    </button>
    
    <script src="assets/js/app.js"></script>
    <script>
        function switchTab(tab) {
            // Tab butonları
            document.querySelectorAll('.auth-tab').forEach(t => t.classList.remove('active'));
            event.target.classList.add('active');
            
            // Formlar
            document.querySelectorAll('.auth-form').forEach(f => f.classList.remove('active'));
            document.getElementById(tab + '-form').classList.add('active');
        }
    </script>
</body>
</html>
