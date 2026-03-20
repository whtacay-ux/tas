<?php
// Discord Clone - Ana Dashboard
require_once '../includes/config.php';
require_once '../includes/channels.php';

// Giriş kontrolü
if (!isLoggedIn()) {
    redirect('../index.php');
}

$user = getCurrentUser();
$servers = getUserServers($user['id']);

// Aktif sunucu ve kanal
$activeServerId = $_GET['server'] ?? ($servers[0]['id'] ?? null);
$activeChannelId = $_GET['channel'] ?? null;

$activeServer = $activeServerId ? getServerById($activeServerId) : null;
$channels = $activeServerId ? getServerChannels($activeServerId) : [];
$members = $activeServerId ? getServerMembers($activeServerId) : [];

// Varsayılan kanal
if (!$activeChannelId && !empty($channels)) {
    foreach ($channels as $channel) {
        if ($channel['type'] === 'text') {
            $activeChannelId = $channel['id'];
            break;
        }
    }
}

$activeChannel = $activeChannelId ? getChannelById($activeChannelId) : null;

// Arkadaşları getir
$friends = getFriends($user['id']);
$pendingRequests = getPendingRequests($user['id']);

// Bekleyen kanal davetlerini getir
$pendingChannelInvites = getPendingChannelInvites($user['id']);

$theme = $user['theme'];
?>
<!DOCTYPE html>
<html lang="tr" data-theme="<?php echo $theme; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo generateCSRFToken(); ?>">
    <title><?php echo $activeChannel ? $activeChannel['name'] : 'Discord Clone'; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .welcome-screen {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px;
            text-align: center;
        }
        
        .welcome-screen i {
            font-size: 80px;
            color: var(--primary);
            margin-bottom: 24px;
        }
        
        .welcome-screen h2 {
            font-size: 24px;
            margin-bottom: 12px;
        }
        
        .welcome-screen p {
            color: var(--text-secondary);
            max-width: 500px;
        }
        
        .friends-view {
            display: none;
        }
        
        .friends-view.active {
            display: flex;
            flex-direction: column;
            flex: 1;
        }
        
        .add-friend-form {
            display: flex;
            gap: 12px;
            padding: 16px;
            background: var(--bg-secondary);
            border-radius: 8px;
            margin: 16px;
        }
        
        .add-friend-form input {
            flex: 1;
        }
        
        .pending-badge {
            background: var(--danger);
            color: white;
            font-size: 11px;
            padding: 2px 6px;
            border-radius: 10px;
            margin-left: 8px;
        }
        
        .request-actions {
            display: flex;
            gap: 8px;
        }
        
        .request-actions button {
            width: 32px;
            height: 32px;
            border-radius: 50%;
        }
        
        .request-actions .accept {
            background: var(--success);
            color: white;
        }
        
        .request-actions .reject {
            background: var(--danger);
            color: white;
        }
        
        .back-to-home {
            background: none;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            padding: 8px;
            border-radius: 4px;
            transition: all 0.2s;
        }
        
        .back-to-home:hover {
            color: var(--text-primary);
            background: var(--bg-hover);
        }
        
        .message-pending {
            opacity: 0.7;
        }
        
        .message-pending .message-content {
            position: relative;
        }
        
        .message-pending .pending {
            margin-left: 8px;
            font-size: 12px;
            animation: pulse 1.5s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 0.5; }
            50% { opacity: 1; }
        }
        
        .server-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px;
            border-bottom: 1px solid var(--bg-tertiary);
        }
        
        .server-header h3 {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
        }
        
        .server-header-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        /* Kanal Davet Modal Stilleri */
        .invite-modal-content {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .invite-link-box {
            display: flex;
            gap: 8px;
            margin-bottom: 16px;
        }
        
        .invite-link-box input {
            flex: 1;
            padding: 10px;
            background: var(--bg-tertiary);
            border: 1px solid var(--border);
            border-radius: 4px;
            color: var(--text-primary);
        }
        
        .friend-invite-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px;
            background: var(--bg-tertiary);
            border-radius: 8px;
            margin-bottom: 8px;
        }
        
        .friend-invite-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .friend-invite-info img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
        }
        
        .invite-list-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px;
            background: var(--bg-tertiary);
            border-radius: 8px;
            margin-bottom: 8px;
        }
        
        .invite-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        
        .invite-code {
            font-family: monospace;
            background: var(--bg-secondary);
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .channel-invite-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px;
            background: var(--bg-secondary);
            border-radius: 8px;
            margin-bottom: 12px;
        }
        
        .channel-invite-info h4 {
            margin-bottom: 4px;
        }
        
        .channel-invite-info p {
            color: var(--text-secondary);
            font-size: 14px;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            color: var(--text-secondary);
        }
        
        .empty-state i {
            font-size: 48px;
            margin-bottom: 16px;
            opacity: 0.5;
        }
        
        /* Kanal item hover menüsü */
        .channel-item {
            position: relative;
        }
        
        .channel-item-actions {
            display: none;
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
        }
        
        .channel-item:hover .channel-item-actions {
            display: flex;
            gap: 4px;
        }
        
        .channel-item-btn {
            background: none;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            padding: 4px;
            border-radius: 4px;
        }
        
        .channel-item-btn:hover {
            color: var(--text-primary);
            background: var(--bg-hover);
        }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Sunucu Listesi -->
        <div class="server-sidebar">
            <!-- Ana Sayfa / Arkadaşlar -->
            <div class="server-icon <?php echo !$activeServerId ? 'active' : ''; ?>" 
                 onclick="goToHome()" 
                 data-tooltip="Arkadaşlar">
                <i class="fas fa-user-friends"></i>
            </div>
            
            <div class="server-divider"></div>
            
            <!-- Sunucular -->
            <?php foreach ($servers as $server): ?>
                <div class="server-icon <?php echo $activeServerId == $server['id'] ? 'active' : ''; ?>" 
                     onclick="location.href='?server=<?php echo $server['id']; ?>'"
                     data-tooltip="<?php echo htmlspecialchars($server['name']); ?>">
                    <?php if ($server['icon']): ?>
                        <img src="../assets/uploads/<?php echo $server['icon']; ?>" alt="">
                    <?php else: ?>
                        <span><?php echo strtoupper(substr($server['name'], 0, 2)); ?></span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            
            <!-- Sunucu Ekle -->
            <div class="server-icon server-add" onclick="openCreateServerModal()" data-tooltip="Sunucu Ekle">
                <i class="fas fa-plus"></i>
            </div>
        </div>
        
        <?php if ($activeServerId): ?>
            <!-- Kanal Listesi -->
            <div class="channel-sidebar">
                <div class="server-header">
                    <h3><?php echo htmlspecialchars($activeServer['name']); ?></h3>
                    <div class="server-header-actions">
                        <button class="back-to-home" onclick="goToHome()" title="Ana Sayfaya Dön">
                            <i class="fas fa-home"></i>
                        </button>
                        <i class="fas fa-chevron-down" style="color: var(--text-secondary); cursor: pointer;"></i>
                    </div>
                </div>
                
                <div class="channel-list">
                    <!-- Metin Kanalları -->
                    <div class="channel-category">
                        <div class="channel-category-header">
                            <i class="fas fa-chevron-down"></i>
                            METİN KANALLARI
                        </div>
                        <?php foreach ($channels as $channel): ?>
                            <?php if ($channel['type'] === 'text'): ?>
                                <div class="channel-item <?php echo $activeChannelId == $channel['id'] ? 'active' : ''; ?>"
                                     data-channel-id="<?php echo $channel['id']; ?>"
                                     data-channel-type="text"
                                     onclick="location.href='?server=<?php echo $activeServerId; ?>&channel=<?php echo $channel['id']; ?>'">
                                    <i class="fas fa-hashtag"></i>
                                    <span class="channel-name"><?php echo htmlspecialchars($channel['name']); ?></span>
                                    <div class="channel-item-actions" onclick="event.stopPropagation()">
                                        <button class="channel-item-btn" onclick="openInviteModal(<?php echo $channel['id']; ?>)" title="Davet Et">
                                            <i class="fas fa-user-plus"></i>
                                        </button>
                                        <button class="channel-item-btn" onclick="showInviteLinks(<?php echo $channel['id']; ?>)" title="Davet Linkleri">
                                            <i class="fas fa-link"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        
                        <?php if (isModerator()): ?>
                            <div class="channel-item" onclick="openCreateChannelModal()">
                                <i class="fas fa-plus"></i>
                                <span class="channel-name" style="opacity: 0.7;">Kanal Ekle</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Sesli Kanalları -->
                    <div class="channel-category">
                        <div class="channel-category-header">
                            <i class="fas fa-chevron-down"></i>
                            SESLİ KANALLAR
                        </div>
                        <?php foreach ($channels as $channel): ?>
                            <?php if ($channel['type'] === 'voice' || $channel['type'] === 'video'): ?>
                                <div class="channel-item"
                                     data-channel-id="<?php echo $channel['id']; ?>"
                                     data-channel-type="<?php echo $channel['type']; ?>"
                                     onclick="joinVoiceChannel(<?php echo $channel['id']; ?>)">
                                    <i class="fas <?php echo $channel['type'] === 'video' ? 'fa-video' : 'fa-volume-up'; ?>"></i>
                                    <span class="channel-name"><?php echo htmlspecialchars($channel['name']); ?></span>
                                    <div class="channel-item-actions" onclick="event.stopPropagation()">
                                        <button class="channel-item-btn" onclick="openInviteModal(<?php echo $channel['id']; ?>)" title="Davet Et">
                                            <i class="fas fa-user-plus"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Kullanıcı Paneli -->
                <div class="user-panel">
                    <div class="avatar-wrapper">
                        <img src="../assets/uploads/avatars/<?php echo !empty($user['avatar']) ? $user['avatar'] : 'default-avatar.png'; ?>" 
                             alt="" 
                             class="avatar"
                             onerror="this.src='../assets/uploads/avatars/default-avatar.png'">
                        <span class="avatar-status status-<?php echo $user['status']; ?>"></span>
                    </div>
                    <div class="user-info">
                        <div class="user-name"><?php echo htmlspecialchars($user['username']); ?></div>
                        <div class="user-status-text">#<?php echo $user['id']; ?></div>
                    </div>
                    <div class="user-actions">
                        <button onclick="location.href='settings.php'" data-tooltip="Ayarlar">
                            <i class="fas fa-cog"></i>
                        </button>
                        <button onclick="location.href='logout.php'" data-tooltip="Çıkış">
                            <i class="fas fa-sign-out-alt"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Ana İçerik -->
            <div class="main-content" id="main-content">
                <?php if ($activeChannel && $activeChannel['type'] === 'text'): ?>
                    <!-- Chat Header -->
                    <div class="chat-header">
                        <div class="chat-header-left">
                            <i class="fas fa-hashtag" style="color: var(--text-muted);"></i>
                            <h2><?php echo htmlspecialchars($activeChannel['name']); ?></h2>
                            <span>|</span>
                            <span><?php echo htmlspecialchars($activeChannel['server_name']); ?></span>
                        </div>
                        <div class="chat-header-right">
                            <div class="search-box">
                                <i class="fas fa-search"></i>
                                <input type="text" placeholder="Ara...">
                            </div>
                            <div class="header-actions">
                                <button data-tooltip="Bildirimler" onclick="showNotifications()">
                                    <i class="fas fa-bell"></i>
                                    <span class="badge" id="notif-badge" style="display: none;">0</span>
                                </button>
                                <button data-tooltip="Pinlenenler">
                                    <i class="fas fa-thumbtack"></i>
                                </button>
                                <button data-tooltip="Davet Et" onclick="openInviteModal(<?php echo $activeChannelId; ?>)">
                                    <i class="fas fa-user-plus"></i>
                                </button>
                                <button data-tooltip="Üye Listesi" onclick="toggleMembers()">
                                    <i class="fas fa-users"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Mesajlar -->
                    <div class="chat-messages" id="messages-container">
                        <div class="loading">
                            <div class="spinner"></div>
                        </div>
                    </div>
                    
                    <!-- Mesaj Girişi -->
                    <div class="chat-input-container">
                        <form id="message-form" class="chat-input-wrapper">
                            <div class="chat-input-actions">
                                <button type="button" onclick="document.getElementById('file-input').click()">
                                    <i class="fas fa-plus-circle"></i>
                                </button>
                            </div>
                            <textarea id="message-input" placeholder="#<?php echo htmlspecialchars($activeChannel['name']); ?> kanalına mesaj gönder" rows="1"></textarea>
                            <div class="chat-input-actions">
                                <button type="button" onclick="toggleEmojiPanel()">
                                    <i class="fas fa-smile"></i>
                                </button>
                                <button type="submit">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </div>
                            <input type="file" id="file-input" style="display: none;" onchange="handleFileUpload(this)">
                        </form>
                    </div>
                    
                    <!-- Emoji Paneli -->
                    <div class="emoji-panel" id="emoji-panel">
                        <div class="emoji-header">
                            <input type="text" class="emoji-search" placeholder="Emoji ara...">
                        </div>
                        <div class="emoji-categories">
                            <span class="emoji-category active">😀</span>
                            <span class="emoji-category">🐱</span>
                            <span class="emoji-category">🍎</span>
                            <span class="emoji-category">⚽</span>
                            <span class="emoji-category">🚗</span>
                            <span class="emoji-category">💡</span>
                        </div>
                        <div class="emoji-list" id="emoji-list">
                            <!-- Emojiler JS ile yüklenecek -->
                        </div>
                    </div>
                    
                <?php else: ?>
                    <div class="welcome-screen">
                        <i class="fas fa-comments"></i>
                        <h2>Bir kanal seçin</h2>
                        <p>Sohbete başlamak için sol taraftan bir metin kanalı seçin veya sesli sohbete katılın.</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Üyeler Paneli -->
            <div class="members-sidebar" id="members-sidebar">
                <div class="members-header">ÇEVRİMİÇİ — <?php echo count(array_filter($members, fn($m) => $m['status'] === 'online')); ?></div>
                
                <?php 
                $roles = ['Admin' => [], 'Moderator' => [], 'Member' => [], 'Guest' => []];
                foreach ($members as $member) {
                    $roles[$member['role_name']][] = $member;
                }
                ?>
                
                <?php foreach ($roles as $roleName => $roleMembers): ?>
                    <?php if (!empty($roleMembers)): ?>
                        <div class="members-header" style="margin-top: 16px;"><?php echo $roleName; ?> — <?php echo count($roleMembers); ?></div>
                        <?php foreach ($roleMembers as $member): ?>
                            <div class="member-item">
                                <div class="avatar-wrapper">
                                    <img src="../assets/uploads/avatars/<?php echo !empty($member['avatar']) ? $member['avatar'] : 'default-avatar.png'; ?>" 
                                         alt="" 
                                         class="avatar avatar-sm"
                                         onerror="this.src='../assets/uploads/avatars/default-avatar.png'">
                                    <span class="avatar-status status-<?php echo $member['status']; ?>"></span>
                                </div>
                                <div class="member-info">
                                    <div class="member-name" style="color: <?php echo $member['color']; ?>">
                                        <?php echo htmlspecialchars($member['username']); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            
        <?php else: ?>
            <!-- Arkadaşlar Görünümü -->
            <div class="friends-view active" id="friends-view" style="flex: 1;">
                <div class="chat-header">
                    <div class="chat-header-left">
                        <i class="fas fa-user-friends" style="color: var(--text-muted);"></i>
                        <h2>Arkadaşlar</h2>
                    </div>
                </div>
                
                <div class="friends-header">
                    <div class="friends-tabs">
                        <div class="friends-tab active" onclick="switchFriendsTab('all')">Tümü</div>
                        <div class="friends-tab" onclick="switchFriendsTab('pending')">
                            Bekleyen
                            <?php if (count($pendingRequests) > 0): ?>
                                <span class="pending-badge"><?php echo count($pendingRequests); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="friends-tab" onclick="switchFriendsTab('channel-invites')">
                            Kanal Davetleri
                            <?php if (count($pendingChannelInvites) > 0): ?>
                                <span class="pending-badge"><?php echo count($pendingChannelInvites); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="friends-tab" onclick="switchFriendsTab('add')">Arkadaş Ekle</div>
                    </div>
                </div>
                
                <!-- Tüm Arkadaşlar -->
                <div class="friends-list" id="tab-all">
                    <?php if (empty($friends)): ?>
                        <div class="empty-state">
                            <i class="fas fa-user-friends"></i>
                            <h3>Henüz arkadaşınız yok</h3>
                            <p>Arkadaş eklemek için "Arkadaş Ekle" sekmesini kullanın.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($friends as $friend): ?>
                            <div class="friend-item">
                                <div class="avatar-wrapper">
                                    <img src="../assets/uploads/avatars/<?php echo !empty($friend['avatar']) ? $friend['avatar'] : 'default-avatar.png'; ?>" 
                                         alt="" 
                                         class="avatar"
                                         onerror="this.src='../assets/uploads/avatars/default-avatar.png'">
                                    <span class="avatar-status status-<?php echo $friend['status']; ?>"></span>
                                </div>
                                <div class="member-info">
                                    <div class="member-name"><?php echo htmlspecialchars($friend['username']); ?></div>
                                    <div class="member-status"><?php echo $friend['status'] === 'online' ? 'Çevrimiçi' : 'Çevrimdışı'; ?></div>
                                </div>
                                <div class="friend-actions">
                                    <button data-tooltip="Mesaj Gönder">
                                        <i class="fas fa-comment"></i>
                                    </button>
                                    <button data-tooltip="More" onclick="showFriendMenu(<?php echo $friend['friend_id']; ?>)">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <!-- Bekleyen İstekler -->
                <div class="friends-list" id="tab-pending" style="display: none;">
                    <?php if (empty($pendingRequests)): ?>
                        <div class="empty-state">
                            <i class="fas fa-check-circle"></i>
                            <h3>Bekleyen istek yok</h3>
                        </div>
                    <?php else: ?>
                        <?php foreach ($pendingRequests as $request): ?>
                            <div class="friend-item">
                                <img src="../assets/uploads/avatars/<?php echo !empty($request['avatar']) ? $request['avatar'] : 'default-avatar.png'; ?>" 
                                     alt="" 
                                     class="avatar"
                                     onerror="this.src='../assets/uploads/avatars/default-avatar.png'">
                                <div class="member-info">
                                    <div class="member-name"><?php echo htmlspecialchars($request['username']); ?></div>
                                    <div class="member-status">Arkadaşlık isteği gönderdi</div>
                                </div>
                                <div class="request-actions">
                                    <button class="accept" onclick="respondRequest(<?php echo $request['id']; ?>, true)">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button class="reject" onclick="respondRequest(<?php echo $request['id']; ?>, false)">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <!-- Kanal Davetleri -->
                <div class="friends-list" id="tab-channel-invites" style="display: none;">
                    <?php if (empty($pendingChannelInvites)): ?>
                        <div class="empty-state">
                            <i class="fas fa-envelope-open"></i>
                            <h3>Bekleyen kanal daveti yok</h3>
                        </div>
                    <?php else: ?>
                        <?php foreach ($pendingChannelInvites as $invite): ?>
                            <div class="channel-invite-item">
                                <div class="channel-invite-info">
                                    <h4><?php echo htmlspecialchars($invite['inviter_name']); ?> davet etti</h4>
                                    <p><?php echo htmlspecialchars($invite['server_name']); ?> / <?php echo htmlspecialchars($invite['channel_name']); ?></p>
                                </div>
                                <div class="request-actions">
                                    <button class="accept" onclick="respondChannelInvite(<?php echo $invite['id']; ?>, true)">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button class="reject" onclick="respondChannelInvite(<?php echo $invite['id']; ?>, false)">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <!-- Arkadaş Ekle -->
                <div class="friends-list" id="tab-add" style="display: none;">
                    <div class="add-friend-form">
                        <input type="text" id="friend-username" placeholder="Kullanıcı adı girin...">
                        <button class="btn btn-primary" onclick="sendFriendRequest()">
                            <i class="fas fa-user-plus"></i> Arkadaşlık İsteği Gönder
                        </button>
                    </div>
                    <div class="empty-state">
                        <i class="fas fa-search"></i>
                        <h3>Kullanıcı adı ile arama yapın</h3>
                        <p>Arkadaşınızın kullanıcı adını girerek onları bulun.</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Sunucu Oluşturma Modalı -->
    <div class="modal-overlay" id="create-server-modal">
        <div class="modal">
            <div class="modal-header">
                <h3>Sunucu Oluştur</h3>
                <button class="modal-close" onclick="ModalManager.close('create-server-modal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Sunucu Adı</label>
                    <input type="text" id="server-name" placeholder="Örn: Oyun Grubum">
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary modal-cancel">İptal</button>
                <button class="btn btn-primary" onclick="createServer()">Oluştur</button>
            </div>
        </div>
    </div>
    
    <!-- Kanal Oluşturma Modalı -->
    <div class="modal-overlay" id="create-channel-modal">
        <div class="modal">
            <div class="modal-header">
                <h3>Kanal Oluştur</h3>
                <button class="modal-close" onclick="ModalManager.close('create-channel-modal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Kanal Adı</label>
                    <input type="text" id="channel-name" placeholder="Örn: genel-sohbet">
                </div>
                <div class="form-group">
                    <label class="form-label">Kanal Türü</label>
                    <select id="channel-type" style="width: 100%; padding: 10px; background: var(--bg-tertiary); color: var(--text-primary); border: none; border-radius: 4px;">
                        <option value="text">Metin Kanalı</option>
                        <option value="voice">Sesli Kanal</option>
                        <option value="video">Görüntülü Kanal</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary modal-cancel">İptal</button>
                <button class="btn btn-primary" onclick="createChannel()">Oluştur</button>
            </div>
        </div>
    </div>
    
    <!-- Kanal Davet Modalı -->
    <div class="modal-overlay" id="invite-modal">
        <div class="modal" style="max-width: 500px;">
            <div class="modal-header">
                <h3>Kanala Davet Et</h3>
                <button class="modal-close" onclick="ModalManager.close('invite-modal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body invite-modal-content">
                <!-- Davet Linki Oluştur -->
                <div class="form-group">
                    <label class="form-label">Davet Linki</label>
                    <div class="invite-link-box">
                        <input type="text" id="invite-link-input" readonly placeholder="Link oluşturmak için tıklayın">
                        <button class="btn btn-primary" onclick="createInviteLink()">
                            <i class="fas fa-plus"></i> Oluştur
                        </button>
                    </div>
                </div>
                
                <hr style="border: none; border-top: 1px solid var(--border); margin: 16px 0;">
                
                <!-- Arkadaşları Davet Et -->
                <div class="form-group">
                    <label class="form-label">Arkadaşlarını Davet Et</label>
                    <div id="invite-friends-list">
                        <!-- Arkadaşlar JS ile yüklenecek -->
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary modal-cancel">Kapat</button>
            </div>
        </div>
    </div>
    
    <!-- Davet Linkleri Modalı -->
    <div class="modal-overlay" id="invite-links-modal">
        <div class="modal" style="max-width: 500px;">
            <div class="modal-header">
                <h3>Aktif Davet Linkleri</h3>
                <button class="modal-close" onclick="ModalManager.close('invite-links-modal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body invite-modal-content">
                <div id="invite-links-list">
                    <!-- Linkler JS ile yüklenecek -->
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary modal-cancel">Kapat</button>
            </div>
        </div>
    </div>
    
    <button class="theme-toggle" title="Temayı Değiştir">
        <i class="fas fa-sun"></i>
    </button>
    
    <script src="../assets/js/app.js"></script>
    <script>
        // Aktif kanal bilgisi
        window.currentChannelId = <?php echo $activeChannelId ?: 'null'; ?>;
        window.currentUserId = <?php echo $user['id']; ?>;
        window.currentUsername = '<?php echo htmlspecialchars($user['username']); ?>';
        window.currentAvatar = '../assets/uploads/avatars/<?php echo !empty($user['avatar']) ? $user['avatar'] : 'default-avatar.png'; ?>';
        window.activeServerId = <?php echo $activeServerId ? intval($activeServerId) :
        'null'; ?>;

        // Aktif davet kanalı
        let activeInviteChannelId = null;
        
        // Sayfa yüklendiğinde
        document.addEventListener('DOMContentLoaded', () => {
            <?php if ($activeChannelId && $activeChannel['type'] === 'text'): ?>
                ChatManager.init(<?php echo $activeChannelId; ?>);
            <?php endif; ?>
            
            // Emoji panelini doldur
            const emojiList = document.getElementById('emoji-list');
            if (emojiList) {
                fillEmojiPanel();
            }
        });
        
        // Ana sayfaya dönüş
        function goToHome() {
            window.location.href = 'dashboard.php';
        }
        
        function switchFriendsTab(tab) {
            document.querySelectorAll('.friends-tab').forEach(t => t.classList.remove('active'));
            event.target.classList.add('active');
            
            document.querySelectorAll('[id^="tab-"]').forEach(t => t.style.display = 'none');
            document.getElementById('tab-' + tab).style.display = 'block';
        }
        
        // Arkadaşlık isteği
        function sendFriendRequest() {
            const username = document.getElementById('friend-username').value.trim();
            if (username) {
                FriendManager.sendRequest(username);
            }
        }
        
        function respondRequest(friendshipId, accept) {
            FriendManager.respondRequest(friendshipId, accept);
        }
        
        // Üye listesi toggle
        function toggleMembers() {
            const sidebar = document.getElementById('members-sidebar');
            sidebar.style.display = sidebar.style.display === 'none' ? 'block' : 'none';
        }
        
        // Modal fonksiyonları
        function openCreateServerModal() {
            ModalManager.open('create-server-modal');
        }
        
        function openCreateChannelModal() {
            ModalManager.open('create-channel-modal');
        }
        
        // Kanal davet modalını aç
        function openInviteModal(channelId) {
            activeInviteChannelId = channelId;
            ModalManager.open('invite-modal');
            loadFriendsForInvite();
        }
        
        // Davet linklerini göster
        function showInviteLinks(channelId) {
            activeInviteChannelId = channelId;
            ModalManager.open('invite-links-modal');
            loadInviteLinks(channelId);
        }
        
        // Arkadaşları davet listesi için yükle
        async function loadFriendsForInvite() {
            const container = document.getElementById('invite-friends-list');
            container.innerHTML = '<div class="loading"><div class="spinner"></div></div>';
            
            try {
                const response = await fetch('../api/friends.php');
                const data = await response.json();
                
                if (data.success && data.friends.length > 0) {
                    container.innerHTML = data.friends.map(friend => `
                        <div class="friend-invite-item">
                            <div class="friend-invite-info">
                                <img src="../assets/uploads/avatars/${friend.avatar || 'default-avatar.png'}" 
                                     onerror="this.src='../assets/uploads/avatars/default-avatar.png'">
                                <div>
                                    <div style="font-weight: 600;">${escapeHtml(friend.username)}</div>
                                    <div style="font-size: 12px; color: var(--text-secondary);">${friend.status}</div>
                                </div>
                            </div>
                            <button class="btn btn-primary btn-sm" onclick="inviteFriendToChannel(${friend.friend_id})">
                                Davet Et
                            </button>
                        </div>
                    `).join('');
                } else {
                    container.innerHTML = `
                        <div class="empty-state">
                            <i class="fas fa-user-friends"></i>
                            <p>Davet edebileceğiniz arkadaşınız yok</p>
                        </div>
                    `;
                }
            } catch (error) {
                container.innerHTML = '<p style="color: var(--danger);">Arkadaşlar yüklenemedi</p>';
            }
        }
        
        // Arkadaşı kanala davet et
        async function inviteFriendToChannel(friendId) {
            if (!activeInviteChannelId) return;
            
            try {
                const response = await fetch('../api/channel-invite.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'invite_friend',
                        channel_id: activeInviteChannelId,
                        friend_id: friendId
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showToast('Davet gönderildi!', 'success');
                    loadFriendsForInvite(); // Listeyi güncelle
                } else {
                    showToast(data.error || 'Davet gönderilemedi', 'error');
                }
            } catch (error) {
                showToast('Davet gönderilirken hata oluştu', 'error');
            }
        }
        
        // Davet linki oluştur
        async function createInviteLink() {
            if (!activeInviteChannelId) return;
            
            try {
                const response = await fetch('../api/channel-invite.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'create',
                        channel_id: activeInviteChannelId
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    const input = document.getElementById('invite-link-input');
                    input.value = data.url;
                    input.select();
                    document.execCommand('copy');
                    showToast('Link kopyalandı!', 'success');
                } else {
                    showToast(data.error || 'Link oluşturulamadı', 'error');
                }
            } catch (error) {
                showToast('Link oluşturulurken hata oluştu', 'error');
            }
        }
        
        // Davet linklerini yükle
        async function loadInviteLinks(channelId) {
            const container = document.getElementById('invite-links-list');
            container.innerHTML = '<div class="loading"><div class="spinner"></div></div>';
            
            try {
                const response = await fetch(`../api/channel-invite.php?channel_id=${channelId}`);
                const data = await response.json();
                
                if (data.success && data.invites.length > 0) {
                    container.innerHTML = data.invites.map(invite => `
                        <div class="invite-list-item">
                            <div class="invite-info">
                                <span class="invite-code">${escapeHtml(invite.invite_code)}</span>
                                <span style="font-size: 12px; color: var(--text-secondary);">
                                    Kullanım: ${invite.used_count}/${invite.max_uses || '∞'}
                                    ${invite.expires_at ? '• Süre: ' + new Date(invite.expires_at).toLocaleDateString() : ''}
                                </span>
                            </div>
                            <button class="btn btn-danger btn-sm" onclick="deleteInviteLink(${invite.id})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    `).join('');
                } else {
                    container.innerHTML = `
                        <div class="empty-state">
                            <i class="fas fa-link"></i>
                            <p>Aktif davet linki yok</p>
                        </div>
                    `;
                }
            } catch (error) {
                container.innerHTML = '<p style="color: var(--danger);">Linkler yüklenemedi</p>';
            }
        }
        
        // Davet linkini sil
        async function deleteInviteLink(inviteId) {
            if (!confirm('Bu davet linkini silmek istediğinize emin misiniz?')) return;
            
            try {
                const response = await fetch('../api/channel-invite.php', {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ invite_id: inviteId })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showToast('Link silindi', 'success');
                    loadInviteLinks(activeInviteChannelId);
                } else {
                    showToast(data.error || 'Link silinemedi', 'error');
                }
            } catch (error) {
                showToast('Link silinirken hata oluştu', 'error');
            }
        }
        
        // Kanal davetini yanıtla (kabul/red)
        async function respondChannelInvite(inviteId, accept) {
            try {
                const response = await fetch('../api/channel-invite.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'respond_invite',
                        invite_id: inviteId,
                        accept: accept
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    if (accept && data.server_id) {
                        window.location.href = `?server=${data.server_id}&channel=${data.channel_id}`;
                    } else {
                        location.reload();
                    }
                } else {
                    showToast(data.error || 'İşlem başarısız', 'error');
                }
            } catch (error) {
                showToast('İşlem sırasında hata oluştu', 'error');
            }
        }
        
        // Sunucu oluşturma
        async function createServer() {
            const name = document.getElementById('server-name').value.trim();
            if (!name) return;
            
            try {
                const response = await fetch('../api/create-server.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ name })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.error || 'Sunucu oluşturulamadı');
                }
            } catch (error) {
                alert('Sunucu oluşturulurken hata: ' + error.message);
            }
        }
        
// Kanal oluşturma
async function createChannel() {
    const nameInput = document.getElementById('channel-name');
    const typeInput = document.getElementById('channel-type');
    
    const name = nameInput.value.trim();
    const type = typeInput ? typeInput.value : 'text';
    
    // Sunucu ID'yi URL'den al (güvenilir yöntem)
    const urlParams = new URLSearchParams(window.location.search);
    const serverId = urlParams.get('server');
    
    console.log('Kanal oluşturma:', { name, type, serverId, windowActiveServerId: window.activeServerId });
    
    if (!name) {
        alert('Kanal adı gerekli');
        return;
    }
    
    if (!serverId) {
        alert('Sunucu bulunamadı! Lütfen bir sunucu seçin. (URL\'de server parametresi yok)');
        return;
    }
    
    // serverId'nin sayı olduğundan emin ol
    const numericServerId = parseInt(serverId);
    if (isNaN(numericServerId) || numericServerId <= 0) {
        alert('Geçersiz sunucu ID: ' + serverId);
        return;
    }
    
    try {
        const requestData = {
            server_id: numericServerId,
            name: name,
            type: type
        };
        
        console.log('Gönderilen veri:', requestData);
        
        const response = await fetch('../api/create-channel.php', {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(requestData)
        });
        
        const responseText = await response.text();
        console.log('Ham yanıt:', responseText);
        
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (e) {
            console.error('JSON parse hatası:', e);
            alert('Sunucu yanıtı geçersiz. Yanıt: ' + responseText.substring(0, 500));
            return;
        }
        
        if (data.success) {
            // Modal'ı kapat ve sayfayı yenile
            const modal = document.getElementById('create-channel-modal');
            if (modal) modal.classList.remove('active');
            location.reload();
        } else {
            alert('Hata: ' + (data.error || 'Bilinmeyen hata'));
        }
    } catch (error) {
        console.error('Fetch hatası:', error);
        alert('Kanal oluşturulurken hata: ' + error.message);
    }
}
        
        // Emoji paneli
        function toggleEmojiPanel() {
            document.getElementById('emoji-panel').classList.toggle('active');
        }
        
        function fillEmojiPanel() {
            const emojis = ['😀','😃','😄','😁','😅','😂','🤣','😊','😇','🙂','🙃','😉','😌','😍','🥰','😘','😗','😙','😚','😋','😛','😝','😜','🤪','🤨','🧐','🤓','😎','🥸','🤩','🥳','😏','😒','😞','😔','😟','😕','🙁','☹️','😣','😖','😫','😩','🥺','😢','😭','😤','😠','😡','🤬','🤯','😳','🥵','🥶','😱','😨','😰','😥','😓','🤗','🤔','🤭','🤫','🤥','😶','😐','😑','😬','🙄','😯','😦','😧','😮','😲','🥱','😴','🤤','😪','😵','🤐','🥴','🤢','🤮','🤧','😷','🤒','🤕','🤑','🤠','😈','👿','👹','👺','🤡','💩','👻','💀','☠️','👽','👾','🤖','🎃','😺','😸','😹','😻','😼','😽','🙀','😿','😾'];
            
            const container = document.getElementById('emoji-list');
            if (container) {
                container.innerHTML = emojis.map(e => `<span class="emoji-item" onclick="insertEmoji('${e}')">${e}</span>`).join('');
            }
        }
        
        function insertEmoji(emoji) {
            const input = document.getElementById('message-input');
            if (input) {
                input.value += emoji;
                input.focus();
            }
        }
        
        // Dosya yükleme
        async function handleFileUpload(input) {
            if (input.files && input.files[0]) {
                const result = await FileUploader.upload(input.files[0]);
                if (result.success) {
                    console.log('Dosya yüklendi:', result.url);
                }
            }
        }
        
        // Sesli kanala katıl
        function joinVoiceChannel(channelId) {
            alert('Sesli sohbet özelliği yapım aşamasında!');
        }
        
        // Toast bildirim
        function showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.style.cssText = `
                position: fixed;
                bottom: 20px;
                right: 20px;
                padding: 12px 24px;
                border-radius: 8px;
                color: white;
                font-weight: 500;
                z-index: 9999;
                animation: slideIn 0.3s ease;
                background: ${type === 'success' ? 'var(--success)' : type === 'error' ? 'var(--danger)' : 'var(--primary)'};
            `;
            toast.textContent = message;
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.remove();
            }, 3000);
        }
        
        // HTML escape
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Sayfa kapatılırken durumu güncelle
        window.addEventListener('beforeunload', () => {
            fetch('../api/update-status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ status: 'offline' })
            });
        });
    </script>
</body>
</html>