<?php
// Discord Clone - Admin Paneli
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/channels.php';

// Admin kontrolü
if (!isAdmin()) {
    redirect('../pages/dashboard.php');
}

// İstatistikleri getir
$db = getDB();

$stats = [
    'users' => $db->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'servers' => $db->query("SELECT COUNT(*) FROM servers")->fetchColumn(),
    'channels' => $db->query("SELECT COUNT(*) FROM channels")->fetchColumn(),
    'messages' => $db->query("SELECT COUNT(*) FROM messages")->fetchColumn(),
    'online_users' => $db->query("SELECT COUNT(*) FROM users WHERE status = 'online'")->fetchColumn(),
];

// Son kullanıcılar
$recentUsers = $db->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 10")->fetchAll();

// Tüm kullanıcılar
$allUsers = getAllUsers(50);

// Roller
$roles = $db->query("SELECT * FROM roles")->fetchAll();

$theme = getUserTheme();
?>
<!DOCTYPE html>
<html lang="tr" data-theme="<?php echo $theme; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Paneli - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .admin-container {
            display: flex;
            min-height: 100vh;
        }
        
        .admin-sidebar {
            width: 250px;
            background: var(--bg-tertiary);
            padding: 20px 0;
        }
        
        .admin-logo {
            padding: 0 20px 20px;
            border-bottom: 1px solid var(--border-color);
            margin-bottom: 20px;
        }
        
        .admin-logo h2 {
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .admin-logo i {
            color: var(--primary);
        }
        
        .admin-nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 20px;
            color: var(--text-secondary);
            cursor: pointer;
            transition: all var(--transition-fast);
            text-decoration: none;
        }
        
        .admin-nav-item:hover,
        .admin-nav-item.active {
            background: var(--bg-hover);
            color: var(--text-primary);
            text-decoration: none;
        }
        
        .admin-nav-item i {
            width: 20px;
            text-align: center;
        }
        
        .admin-content {
            flex: 1;
            padding: 30px;
            background: var(--bg-primary);
        }
        
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .admin-header h1 {
            font-size: 24px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: var(--bg-secondary);
            border-radius: 8px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }
        
        .stat-icon.blue { background: rgba(88, 101, 242, 0.2); color: var(--primary); }
        .stat-icon.green { background: rgba(59, 165, 93, 0.2); color: var(--success); }
        .stat-icon.orange { background: rgba(250, 168, 26, 0.2); color: var(--warning); }
        .stat-icon.red { background: rgba(237, 66, 69, 0.2); color: var(--danger); }
        
        .stat-info h3 {
            font-size: 24px;
            font-weight: 700;
        }
        
        .stat-info p {
            color: var(--text-secondary);
            font-size: 13px;
        }
        
        .admin-section {
            background: var(--bg-secondary);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .admin-section h3 {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 16px;
            text-transform: uppercase;
            color: var(--text-secondary);
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .data-table th,
        .data-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        
        .data-table th {
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            color: var(--text-secondary);
        }
        
        .data-table tr:hover {
            background: var(--bg-hover);
        }
        
        .user-cell {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .role-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .action-btns {
            display: flex;
            gap: 8px;
        }
        
        .action-btns button {
            width: 28px;
            height: 28px;
            border-radius: 4px;
            background: var(--bg-tertiary);
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .action-btns button:hover {
            background: var(--primary);
            color: white;
        }
        
        .action-btns button.danger:hover {
            background: var(--danger);
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .admin-tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 12px;
        }
        
        .admin-tab {
            padding: 8px 16px;
            border-radius: 4px;
            font-size: 14px;
            color: var(--text-secondary);
            cursor: pointer;
            transition: all var(--transition-fast);
        }
        
        .admin-tab:hover,
        .admin-tab.active {
            background: var(--bg-hover);
            color: var(--text-primary);
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <div class="admin-sidebar">
            <div class="admin-logo">
                <h2><i class="fas fa-shield-alt"></i> Admin Paneli</h2>
            </div>
            
            <a href="#dashboard" class="admin-nav-item active" onclick="showTab('dashboard')">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a href="#users" class="admin-nav-item" onclick="showTab('users')">
                <i class="fas fa-users"></i> Kullanıcılar
            </a>
            <a href="#servers" class="admin-nav-item" onclick="showTab('servers')">
                <i class="fas fa-server"></i> Sunucular
            </a>
            <a href="#channels" class="admin-nav-item" onclick="showTab('channels')">
                <i class="fas fa-hashtag"></i> Kanallar
            </a>
            <a href="#messages" class="admin-nav-item" onclick="showTab('messages')">
                <i class="fas fa-comments"></i> Mesajlar
            </a>
            
            <div style="margin-top: auto; padding-top: 20px; border-top: 1px solid var(--border-color);">
                <a href="../pages/dashboard.php" class="admin-nav-item">
                    <i class="fas fa-arrow-left"></i> Siteye Dön
                </a>
            </div>
        </div>
        
        <!-- Content -->
        <div class="admin-content">
            <!-- Dashboard -->
            <div id="tab-dashboard" class="tab-content active">
                <div class="admin-header">
                    <h1>Dashboard</h1>
                    <span style="color: var(--text-secondary);"><?php echo date('d.m.Y H:i'); ?></span>
                </div>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon blue">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $stats['users']; ?></h3>
                            <p>Toplam Kullanıcı</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon green">
                            <i class="fas fa-circle"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $stats['online_users']; ?></h3>
                            <p>Çevrimiçi</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon orange">
                            <i class="fas fa-server"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $stats['servers']; ?></h3>
                            <p>Sunucu</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon blue">
                            <i class="fas fa-hashtag"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $stats['channels']; ?></h3>
                            <p>Kanal</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon red">
                            <i class="fas fa-comments"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo number_format($stats['messages']); ?></h3>
                            <p>Mesaj</p>
                        </div>
                    </div>
                </div>
                
                <div class="admin-section">
                    <h3>Son Kayıt Olan Kullanıcılar</h3>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Kullanıcı</th>
                                <th>E-posta</th>
                                <th>Durum</th>
                                <th>Kayıt Tarihi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentUsers as $u): ?>
                                <tr>
                                    <td>
                                        <div class="user-cell">
                                            <img src="../assets/uploads/avatars/<?php echo $u['avatar']; ?>" alt="" class="avatar avatar-sm">
                                            <?php echo htmlspecialchars($u['username']); ?>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($u['email']); ?></td>
                                    <td>
                                        <span class="status-indicator status-<?php echo $u['status']; ?>"></span>
                                        <?php echo $u['status']; ?>
                                    </td>
                                    <td><?php echo date('d.m.Y H:i', strtotime($u['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Kullanıcılar -->
            <div id="tab-users" class="tab-content">
                <div class="admin-header">
                    <h1>Kullanıcı Yönetimi</h1>
                </div>
                
                <div class="admin-section">
                    <h3>Tüm Kullanıcılar</h3>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Kullanıcı</th>
                                <th>E-posta</th>
                                <th>Rol</th>
                                <th>Durum</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allUsers as $u): ?>
                                <tr>
                                    <td>#<?php echo $u['id']; ?></td>
                                    <td>
                                        <div class="user-cell">
                                            <img src="../assets/uploads/avatars/<?php echo $u['avatar']; ?>" alt="" class="avatar avatar-sm">
                                            <?php echo htmlspecialchars($u['username']); ?>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($u['email']); ?></td>
                                    <td>
                                        <span class="role-badge" style="background: <?php echo $u['color']; ?>20; color: <?php echo $u['color']; ?>">
                                            <?php echo $u['role_name']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-indicator status-<?php echo $u['status']; ?>"></span>
                                    </td>
                                    <td>
                                        <div class="action-btns">
                                            <button onclick="editUser(<?php echo $u['id']; ?>)" title="Düzenle">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button onclick="banUser(<?php echo $u['id']; ?>)" class="danger" title="Yasakla">
                                                <i class="fas fa-ban"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Diğer sekmeler -->
            <div id="tab-servers" class="tab-content">
                <div class="admin-header">
                    <h1>Sunucu Yönetimi</h1>
                </div>
                <div class="admin-section">
                    <p>Sunucu yönetimi özelliği yakında eklenecek.</p>
                </div>
            </div>
            
            <div id="tab-channels" class="tab-content">
                <div class="admin-header">
                    <h1>Kanal Yönetimi</h1>
                </div>
                <div class="admin-section">
                    <p>Kanal yönetimi özelliği yakında eklenecek.</p>
                </div>
            </div>
            
            <div id="tab-messages" class="tab-content">
                <div class="admin-header">
                    <h1>Mesaj Yönetimi</h1>
                </div>
                <div class="admin-section">
                    <p>Mesaj yönetimi özelliği yakında eklenecek.</p>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/app.js"></script>
    <script>
        function showTab(tab) {
            // Nav aktifliği
            document.querySelectorAll('.admin-nav-item').forEach(item => {
                item.classList.remove('active');
            });
            event.target.closest('.admin-nav-item').classList.add('active');
            
            // Tab içeriği
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            document.getElementById('tab-' + tab).classList.add('active');
        }
        
        function editUser(userId) {
            alert('Kullanıcı düzenleme: ' + userId);
        }
        
        function banUser(userId) {
            if (confirm('Bu kullanıcıyı yasaklamak istediğinize emin misiniz?')) {
                alert('Kullanıcı yasaklandı: ' + userId);
            }
        }
    </script>
</body>
</html>
