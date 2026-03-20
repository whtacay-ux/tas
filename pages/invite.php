<?php
// Discord Clone - Davet Sayfası
require_once '../includes/config.php';

$code = $_GET['code'] ?? '';
$error = '';
$success = '';

if (empty($code)) {
    redirect('../index.php');
}

$db = getDB();

// Davet kodunu kontrol et
// Önce sunucu davetini kontrol et
$stmt = $db->prepare("SELECT * FROM server_invites WHERE invite_code = ? AND is_active = 1");
$stmt->execute([$code]);
$serverInvite = $stmt->fetch();

if ($serverInvite) {
    // Süre kontrolü
    if ($serverInvite['expires_at'] && strtotime($serverInvite['expires_at']) < time()) {
        $error = 'Davet linkinin süresi dolmuş';
    }
    // Kullanım limiti kontrolü
    elseif ($serverInvite['max_uses'] > 0 && $serverInvite['used_count'] >= $serverInvite['max_uses']) {
        $error = 'Davet linki kullanım limitine ulaşmış';
    } else {
        // Sunucu bilgilerini al
        $stmt = $db->prepare("SELECT s.*, u.username as owner_name FROM servers s JOIN users u ON s.owner_id = u.id WHERE s.id = ?");
        $stmt->execute([$serverInvite['server_id']]);
        $server = $stmt->fetch();
        
        if (!$server) {
            $error = 'Sunucu bulunamadı';
        }
    }
} else {
    // Kanal davetini kontrol et
    $stmt = $db->prepare("SELECT * FROM channel_invites WHERE invite_code = ? AND is_active = 1");
    $stmt->execute([$code]);
    $channelInvite = $stmt->fetch();
    
    if (!$channelInvite) {
        $error = 'Geçersiz davet linki';
    } else {
        // Süre kontrolü
        if ($channelInvite['expires_at'] && strtotime($channelInvite['expires_at']) < time()) {
            $error = 'Davet linkinin süresi dolmuş';
        }
        // Kullanım limiti kontrolü
        elseif ($channelInvite['max_uses'] > 0 && $channelInvite['used_count'] >= $channelInvite['max_uses']) {
            $error = 'Davet linki kullanım limitine ulaşmış';
        } else {
            // Kanal ve sunucu bilgilerini al
            $stmt = $db->prepare("SELECT c.*, s.name as server_name, s.id as server_id FROM channels c JOIN servers s ON c.server_id = s.id WHERE c.id = ?");
            $stmt->execute([$channelInvite['channel_id']]);
            $channel = $stmt->fetch();
            
            if (!$channel) {
                $error = 'Kanal bulunamadı';
            }
        }
    }
}

// Daveti kabul et
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accept'])) {
    if (!isLoggedIn()) {
        redirect('../index.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    }
    
    $user = getCurrentUser();
    
    if ($serverInvite) {
        require_once '../includes/channels.php';
        $result = joinServerWithInvite($code, $user['id']);
        
        if ($result['success']) {
            redirect('dashboard.php?server=' . $result['server_id']);
        } else {
            $error = $result['error'];
        }
    } elseif ($channelInvite) {
        // Kanal davetini kabul et
        $stmt = $db->prepare("SELECT server_id FROM channels WHERE id = ?");
        $stmt->execute([$channelInvite['channel_id']]);
        $channelData = $stmt->fetch();
        
        // Sunucuya üye olarak ekle
        $stmt = $db->prepare("INSERT IGNORE INTO server_members (server_id, user_id, role_id) VALUES (?, ?, 3)");
        $stmt->execute([$channelData['server_id'], $user['id']]);
        
        // Davet kullanımını artır
        $stmt = $db->prepare("UPDATE channel_invites SET used_count = used_count + 1 WHERE id = ?");
        $stmt->execute([$channelInvite['id']]);
        
        redirect('dashboard.php?server=' . $channelData['server_id'] . '&channel=' . $channelInvite['channel_id']);
    }
}

$theme = getUserTheme();
?>
<!DOCTYPE html>
<html lang="tr" data-theme="<?php echo $theme; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Davet - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #5865f2;
            --bg-primary: #36393f;
            --bg-secondary: #2f3136;
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
            background: linear-gradient(135deg, var(--primary) 0%, #7289da 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .invite-container {
            width: 100%;
            max-width: 480px;
            padding: 20px;
        }
        
        .invite-box {
            background: var(--bg-secondary);
            border-radius: 8px;
            padding: 40px;
            text-align: center;
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
        }
        
        .invite-box i {
            font-size: 64px;
            color: var(--primary);
            margin-bottom: 24px;
        }
        
        .invite-box.error i {
            color: var(--danger);
        }
        
        .invite-box h1 {
            font-size: 24px;
            color: var(--text-primary);
            margin-bottom: 12px;
        }
        
        .invite-box p {
            color: var(--text-secondary);
            margin-bottom: 24px;
        }
        
        .server-info {
            background: rgba(0,0,0,0.2);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 24px;
        }
        
        .server-info h2 {
            color: var(--text-primary);
            font-size: 20px;
            margin-bottom: 8px;
        }
        
        .server-info span {
            color: var(--text-secondary);
            font-size: 14px;
        }
        
        .btn {
            padding: 14px 32px;
            border: none;
            border-radius: 4px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-primary {
            background: var(--success);
            color: white;
        }
        
        .btn-primary:hover {
            background: #3ca374;
        }
        
        .btn-secondary {
            background: var(--bg-primary);
            color: var(--text-primary);
            text-decoration: none;
            display: inline-block;
        }
        
        .error-message {
            color: var(--danger);
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="invite-container">
        <div class="invite-box <?php echo $error ? 'error' : ''; ?>">
            <?php if ($error): ?>
                <i class="fas fa-times-circle"></i>
                <h1>Davet Geçersiz</h1>
                <p class="error-message"><?php echo e($error); ?></p>
                <a href="../index.php" class="btn btn-secondary">Ana Sayfaya Dön</a>
            <?php elseif ($serverInvite): ?>
                <i class="fas fa-envelope-open-text"></i>
                <h1>Sunucu Daveti</h1>
                <p>Bir sunucuya davet edildiniz</p>
                
                <div class="server-info">
                    <h2><?php echo e($server['name']); ?></h2>
                    <span>Sahibi: <?php echo e($server['owner_name']); ?></span>
                </div>
                
                <?php if (isLoggedIn()): ?>
                    <form method="POST">
                        <button type="submit" name="accept" class="btn btn-primary">
                            <i class="fas fa-check"></i> Katıl
                        </button>
                    </form>
                <?php else: ?>
                    <a href="../index.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="btn btn-primary">
                        Giriş Yap ve Katıl
                    </a>
                <?php endif; ?>
            <?php elseif ($channelInvite): ?>
                <i class="fas fa-envelope-open-text"></i>
                <h1>Kanal Daveti</h1>
                <p>Bir kanala davet edildiniz</p>
                
                <div class="server-info">
                    <h2><?php echo e($channel['server_name']); ?></h2>
                    <span>Kanal: <?php echo e($channel['name']); ?></span>
                </div>
                
                <?php if (isLoggedIn()): ?>
                    <form method="POST">
                        <button type="submit" name="accept" class="btn btn-primary">
                            <i class="fas fa-check"></i> Katıl
                        </button>
                    </form>
                <?php else: ?>
                    <a href="../index.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="btn btn-primary">
                        Giriş Yap ve Katıl
                    </a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
