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
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            --primary: #5865f2;
            --primary-hover: #4752c4;
            --bg-primary: #36393f;
            --bg-secondary: #2f3136;
            --bg-tertiary: #202225;
            --text-primary: #dcddde;
            --text-secondary: #96989d;
            --text-muted: #72767d;
            --success: #43b581;
            --danger: #f04747;
            --warning: #faa61a;
            --border: #202225;
            --input-bg: #40444b;
        }
        
        [data-theme="light"] {
            --bg-primary: #ffffff;
            --bg-secondary: #f2f3f5;
            --bg-tertiary: #e3e5e8;
            --text-primary: #2e3338;
            --text-secondary: #4f5660;
            --text-muted: #747f8d;
            --border: #e3e5e8;
            --input-bg: #e3e5e8;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--primary) 0%, #7289da 50%, #99aab5 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .auth-container {
            width: 100%;
            max-width: 480px;
            padding: 20px;
        }
        
        .auth-box {
            background: var(--bg-secondary);
            border-radius: 8px;
            padding: 32px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
        }
        
        .logo {
            text-align: center;
            margin-bottom: 24px;
        }
        
        .logo i {
            font-size: 64px;
            color: var(--primary);
            margin-bottom: 16px;
        }
        
        .logo h1 {
            font-size: 24px;
            color: var(--text-primary);
            font-weight: 700;
        }
        
        .logo p {
            color: var(--text-secondary);
            font-size: 14px;
            margin-top: 8px;
        }
        
        .auth-tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 24px;
            border-bottom: 1px solid var(--border);
        }
        
        .auth-tab {
            flex: 1;
            padding: 12px;
            background: transparent;
            color: var(--text-secondary);
            font-size: 14px;
            font-weight: 600;
            border: none;
            border-bottom: 2px solid transparent;
            cursor: pointer;
            transition: all 0.2s;
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
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            color: var(--text-secondary);
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 8px;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 16px;
            background: var(--input-bg);
            border: 1px solid transparent;
            border-radius: 4px;
            color: var(--text-primary);
            font-size: 14px;
            transition: all 0.2s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
        }
        
        .form-hint {
            color: var(--text-muted);
            font-size: 12px;
            margin-top: 6px;
        }
        
        .forgot-password {
            text-align: right;
            margin-top: -12px;
            margin-bottom: 16px;
        }
        
        .forgot-password a {
            font-size: 12px;
            color: var(--primary);
            text-decoration: none;
        }
        
        .forgot-password a:hover {
            text-decoration: underline;
        }
        
        .checkbox-wrapper {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
        }
        
        .checkbox-wrapper input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: var(--primary);
        }
        
        .checkbox-wrapper label {
            font-size: 13px;
            color: var(--text-secondary);
            cursor: pointer;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
            width: 100%;
        }
        
        .btn-primary:hover {
            background: var(--primary-hover);
        }
        
        .btn-block {
            width: 100%;
        }
        
        .btn-lg {
            padding: 14px 24px;
            font-size: 15px;
        }
        
        .alert {
            padding: 12px 16px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-error {
            background: rgba(240, 71, 71, 0.1);
            color: var(--danger);
            border: 1px solid rgba(240, 71, 71, 0.3);
        }
        
        .alert-success {
            background: rgba(67, 181, 129, 0.1);
            color: var(--success);
            border: 1px solid rgba(67, 181, 129, 0.3);
        }
        
        .theme-toggle {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: var(--bg-secondary);
            border: none;
            color: var(--text-primary);
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            transition: all 0.2s;
        }
        
        .theme-toggle:hover {
            transform: scale(1.1);
        }
        
        .features {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px solid var(--border);
        }
        
        .feature {
            text-align: center;
        }
        
        .feature i {
            font-size: 24px;
            color: var(--primary);
            margin-bottom: 8px;
        }
        
        .feature span {
            display: block;
            font-size: 12px;
            color: var(--text-secondary);
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <div class="logo">
                <i class="fas fa-comments"></i>
                <h1><?php echo SITE_NAME; ?></h1>
                <p>Sesli, görüntülü ve metin tabanlı sohbet platformu</p>
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
                    <a href="#">Şifremi unuttum</a>
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
            
            <div class="features">
                <div class="feature">
                    <i class="fas fa-microphone"></i>
                    <span>Sesli Sohbet</span>
                </div>
                <div class="feature">
                    <i class="fas fa-video"></i>
                    <span>Görüntülü Arama</span>
                </div>
                <div class="feature">
                    <i class="fas fa-desktop"></i>
                    <span>Ekran Paylaşımı</span>
                </div>
            </div>
        </div>
        
        <p style="text-align: center; margin-top: 16px; color: rgba(255,255,255,0.7); font-size: 12px;">
            &copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. Tüm hakları saklıdır.
        </p>
    </div>
    
    <button class="theme-toggle" onclick="toggleTheme()" title="Temayı Değiştir">
        <i class="fas fa-sun"></i>
    </button>
    
    <script>
        function switchTab(tab) {
            document.querySelectorAll('.auth-tab').forEach(t => t.classList.remove('active'));
            event.target.classList.add('active');
            
            document.querySelectorAll('.auth-form').forEach(f => f.classList.remove('active'));
            document.getElementById(tab + '-form').classList.add('active');
        }
        
        function toggleTheme() {
            const html = document.documentElement;
            const current = html.getAttribute('data-theme');
            const newTheme = current === 'dark' ? 'light' : 'dark';
            html.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
        }
        
        // Kayıtlı temayı yükle
        const savedTheme = localStorage.getItem('theme') || 'dark';
        document.documentElement.setAttribute('data-theme', savedTheme);
    </script>
</body>
</html>
