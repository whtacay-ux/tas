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

// Sunucu davet linklerini getir (eğer sunucu sahibiyse)
$serverInvites = [];
if ($activeServerId && (isServerOwner($activeServerId) || isAdmin())) {
    $serverInvites = getServerInvites($activeServerId);
}

// Tüm rolleri getir (rol atama için)
$allRoles = getAllRoles();

$theme = $user['theme'];
?>
<!DOCTYPE html>
<html lang="tr" data-theme="<?php echo $theme; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo generateCSRFToken(); ?>">
    <title><?php echo $activeChannel ? e($activeChannel['name']) : 'Discord Clone'; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #5865f2;
            --primary-hover: #4752c4;
            --bg-primary: #36393f;
            --bg-secondary: #2f3136;
            --bg-tertiary: #202225;
            --bg-hover: rgba(79, 84, 92, 0.4);
            --text-primary: #dcddde;
            --text-secondary: #96989d;
            --text-muted: #72767d;
            --success: #43b581;
            --danger: #f04747;
            --warning: #faa61a;
            --border: #202225;
            --input-bg: #40444b;
            --font: 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
        }
        
        [data-theme="light"] {
            --bg-primary: #ffffff;
            --bg-secondary: #f2f3f5;
            --bg-tertiary: #e3e5e8;
            --bg-hover: rgba(116, 127, 141, 0.2);
            --text-primary: #2e3338;
            --text-secondary: #4f5660;
            --text-muted: #747f8d;
            --border: #e3e5e8;
            --input-bg: #e3e5e8;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: var(--font);
            background: var(--bg-primary);
            color: var(--text-primary);
            overflow: hidden;
        }
        
        .app-container {
            display: flex;
            height: 100vh;
        }
        
        /* Sunucu Sidebar */
        .server-sidebar {
            width: 72px;
            background: var(--bg-tertiary);
            padding: 12px 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            overflow-y: auto;
        }
        
        .server-icon {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: var(--bg-secondary);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
            position: relative;
            overflow: hidden;
        }
        
        .server-icon:hover {
            border-radius: 16px;
            background: var(--primary);
        }
        
        .server-icon.active {
            border-radius: 16px;
            background: var(--primary);
        }
        
        .server-icon.active::before {
            content: '';
            position: absolute;
            left: -16px;
            width: 8px;
            height: 40px;
            background: var(--text-primary);
            border-radius: 0 4px 4px 0;
        }
        
        .server-icon img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .server-icon span {
            font-size: 14px;
            font-weight: 700;
            color: var(--text-primary);
        }
        
        .server-icon i {
            font-size: 20px;
            color: var(--text-primary);
        }
        
        .server-divider {
            width: 32px;
            height: 2px;
            background: var(--border);
            margin: 4px 0;
        }
        
        .server-add {
            background: var(--bg-secondary);
            color: var(--success);
        }
        
        .server-add:hover {
            background: var(--success);
            color: white;
        }
        
        /* Kanal Sidebar */
        .channel-sidebar {
            width: 240px;
            background: var(--bg-secondary);
            display: flex;
            flex-direction: column;
        }
        
        .server-header {
            padding: 16px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .server-header h3 {
            font-size: 15px;
            font-weight: 600;
            color: var(--text-primary);
        }
        
        .server-header-actions {
            display: flex;
            gap: 8px;
        }
        
        .server-header-actions button {
            background: none;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            padding: 4px;
            border-radius: 4px;
            transition: all 0.2s;
        }
        
        .server-header-actions button:hover {
            color: var(--text-primary);
            background: var(--bg-hover);
        }
        
        .channel-list {
            flex: 1;
            overflow-y: auto;
            padding: 16px 8px;
        }
        
        .channel-category {
            margin-bottom: 16px;
        }
        
        .channel-category-header {
            padding: 8px;
            font-size: 11px;
            font-weight: 700;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 4px;
            cursor: pointer;
        }
        
        .channel-category-header i {
            font-size: 10px;
        }
        
        .channel-item {
            padding: 8px 12px;
            margin: 2px 0;
            border-radius: 4px;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.2s;
            position: relative;
        }
        
        .channel-item:hover {
            background: var(--bg-hover);
        }
        
        .channel-item.active {
            background: var(--bg-hover);
        }
        
        .channel-item i {
            font-size: 18px;
            color: var(--text-muted);
        }
        
        .channel-item.active i {
            color: var(--text-primary);
        }
        
        .channel-name {
            font-size: 14px;
            color: var(--text-secondary);
            flex: 1;
        }
        
        .channel-item.active .channel-name {
            color: var(--text-primary);
            font-weight: 500;
        }
        
        .channel-item-actions {
            display: none;
            gap: 4px;
        }
        
        .channel-item:hover .channel-item-actions {
            display: flex;
        }
        
        .channel-item-btn {
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            padding: 4px;
            border-radius: 4px;
            font-size: 12px;
        }
        
        .channel-item-btn:hover {
            color: var(--text-primary);
            background: rgba(255,255,255,0.1);
        }
        
        /* Kullanıcı Paneli */
        .user-panel {
            padding: 12px;
            background: var(--bg-tertiary);
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .avatar-wrapper {
            position: relative;
        }
        
        .avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .avatar-sm {
            width: 24px;
            height: 24px;
        }
        
        .avatar-lg {
            width: 80px;
            height: 80px;
        }
        
        .avatar-status {
            position: absolute;
            bottom: -2px;
            right: -2px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            border: 2px solid var(--bg-tertiary);
        }
        
        .status-online { background: var(--success); }
        .status-offline { background: var(--text-muted); }
        .status-idle { background: var(--warning); }
        .status-dnd { background: var(--danger); }
        
        .user-info {
            flex: 1;
        }
        
        .user-name {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-primary);
        }
        
        .user-status-text {
            font-size: 11px;
            color: var(--text-muted);
        }
        
        .user-actions {
            display: flex;
            gap: 4px;
        }
        
        .user-actions button {
            background: none;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            padding: 6px;
            border-radius: 4px;
            transition: all 0.2s;
        }
        
        .user-actions button:hover {
            color: var(--text-primary);
            background: var(--bg-hover);
        }
        
        /* Ana İçerik */
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: var(--bg-primary);
        }
        
        .chat-header {
            padding: 16px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .chat-header-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .chat-header-left h2 {
            font-size: 16px;
            font-weight: 600;
        }
        
        .chat-header-left span {
            color: var(--text-muted);
            font-size: 14px;
        }
        
        .chat-header-right {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .search-box {
            display: flex;
            align-items: center;
            background: var(--bg-tertiary);
            border-radius: 4px;
            padding: 6px 12px;
            gap: 8px;
        }
        
        .search-box input {
            background: none;
            border: none;
            color: var(--text-primary);
            font-size: 13px;
            width: 150px;
            outline: none;
        }
        
        .search-box input::placeholder {
            color: var(--text-muted);
        }
        
        .header-actions {
            display: flex;
            gap: 8px;
        }
        
        .header-actions button {
            background: none;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            padding: 8px;
            border-radius: 4px;
            position: relative;
            transition: all 0.2s;
        }
        
        .header-actions button:hover {
            color: var(--text-primary);
            background: var(--bg-hover);
        }
        
        .badge {
            position: absolute;
            top: 2px;
            right: 2px;
            background: var(--danger);
            color: white;
            font-size: 10px;
            padding: 2px 5px;
            border-radius: 10px;
            font-weight: 700;
        }
        
        /* Mesajlar */
        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 16px;
        }
        
        .message {
            display: flex;
            gap: 12px;
            padding: 8px 16px;
            margin: 0 -16px;
            transition: background 0.1s;
        }
        
        .message:hover {
            background: var(--bg-hover);
        }
        
        .message-body {
            flex: 1;
        }
        
        .message-header {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 4px;
        }
        
        .author {
            font-size: 15px;
            font-weight: 500;
        }
        
        .time {
            font-size: 11px;
            color: var(--text-muted);
        }
        
        .message .text {
            font-size: 14px;
            color: var(--text-primary);
            line-height: 1.4;
            word-wrap: break-word;
        }
        
        /* Mesaj Girişi */
        .chat-input-container {
            padding: 16px;
        }
        
        .chat-input-wrapper {
            display: flex;
            align-items: center;
            background: var(--bg-tertiary);
            border-radius: 8px;
            padding: 12px;
            gap: 12px;
        }
        
        .chat-input-actions button {
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            padding: 8px;
            border-radius: 4px;
            font-size: 20px;
            transition: all 0.2s;
        }
        
        .chat-input-actions button:hover {
            color: var(--text-primary);
        }
        
        .chat-input-wrapper textarea {
            flex: 1;
            background: none;
            border: none;
            color: var(--text-primary);
            font-size: 14px;
            resize: none;
            outline: none;
            max-height: 200px;
            font-family: inherit;
        }
        
        .chat-input-wrapper textarea::placeholder {
            color: var(--text-muted);
        }
        
        /* Üyeler Paneli */
        .members-sidebar {
            width: 240px;
            background: var(--bg-secondary);
            padding: 16px;
            overflow-y: auto;
        }
        
        .members-header {
            font-size: 11px;
            font-weight: 700;
            color: var(--text-secondary);
            padding: 16px 8px 8px;
            text-transform: uppercase;
        }
        
        .member-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 6px 8px;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .member-item:hover {
            background: var(--bg-hover);
        }
        
        .member-info {
            flex: 1;
        }
        
        .member-name {
            font-size: 14px;
            font-weight: 500;
        }
        
        /* Arkadaşlar Görünümü */
        .friends-view {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .friends-header {
            padding: 16px;
            border-bottom: 1px solid var(--border);
        }
        
        .friends-tabs {
            display: flex;
            gap: 24px;
        }
        
        .friends-tab {
            padding: 8px 16px;
            font-size: 15px;
            font-weight: 500;
            color: var(--text-secondary);
            cursor: pointer;
            border-radius: 4px;
            transition: all 0.2s;
            position: relative;
        }
        
        .friends-tab:hover {
            color: var(--text-primary);
            background: var(--bg-hover);
        }
        
        .friends-tab.active {
            color: var(--text-primary);
            background: var(--bg-hover);
        }
        
        .pending-badge {
            position: absolute;
            top: -4px;
            right: -4px;
            background: var(--danger);
            color: white;
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 10px;
            font-weight: 700;
        }
        
        .friends-list {
            flex: 1;
            overflow-y: auto;
            padding: 16px;
        }
        
        .friend-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .friend-item:hover {
            background: var(--bg-hover);
        }
        
        .friend-actions {
            display: flex;
            gap: 8px;
            margin-left: auto;
        }
        
        .friend-actions button {
            background: var(--bg-tertiary);
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            padding: 8px;
            border-radius: 50%;
            width: 36px;
            height: 36px;
            transition: all 0.2s;
        }
        
        .friend-actions button:hover {
            background: var(--primary);
            color: white;
        }
        
        .request-actions {
            display: flex;
            gap: 8px;
            margin-left: auto;
        }
        
        .request-actions button {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .request-actions .accept {
            background: var(--success);
            color: white;
        }
        
        .request-actions .accept:hover {
            background: #3ca374;
        }
        
        .request-actions .reject {
            background: var(--danger);
            color: white;
        }
        
        .request-actions .reject:hover {
            background: #d84040;
        }
        
        .add-friend-form {
            display: flex;
            gap: 12px;
            padding: 16px;
            background: var(--bg-secondary);
            border-radius: 8px;
            margin-bottom: 16px;
        }
        
        .add-friend-form input {
            flex: 1;
            padding: 12px 16px;
            background: var(--bg-tertiary);
            border: 1px solid transparent;
            border-radius: 4px;
            color: var(--text-primary);
            font-size: 14px;
            outline: none;
        }
        
        .add-friend-form input:focus {
            border-color: var(--primary);
        }
        
        /* Boş Durum */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-muted);
        }
        
        .empty-state i {
            font-size: 64px;
            margin-bottom: 16px;
            opacity: 0.5;
        }
        
        .empty-state h3 {
            font-size: 18px;
            color: var(--text-primary);
            margin-bottom: 8px;
        }
        
        /* Modal */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.8);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }
        
        .modal-overlay.active {
            display: flex;
        }
        
        .modal {
            background: var(--bg-secondary);
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
            max-height: 80vh;
            overflow: hidden;
            animation: modalSlide 0.2s ease;
        }
        
        @keyframes modalSlide {
            from {
                opacity: 0;
                transform: scale(0.95);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }
        
        .modal-header {
            padding: 16px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .modal-header h3 {
            font-size: 18px;
            font-weight: 700;
        }
        
        .modal-close {
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            padding: 8px;
            font-size: 18px;
            transition: all 0.2s;
        }
        
        .modal-close:hover {
            color: var(--text-primary);
        }
        
        .modal-body {
            padding: 16px;
            overflow-y: auto;
            max-height: 50vh;
        }
        
        .modal-footer {
            padding: 16px;
            border-top: 1px solid var(--border);
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }
        
        .form-group {
            margin-bottom: 16px;
        }
        
        .form-group label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            color: var(--text-secondary);
            text-transform: uppercase;
            margin-bottom: 8px;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            background: var(--bg-tertiary);
            border: 1px solid transparent;
            border-radius: 4px;
            color: var(--text-primary);
            font-size: 14px;
            outline: none;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            border-color: var(--primary);
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--primary-hover);
        }
        
        .btn-secondary {
            background: var(--bg-tertiary);
            color: var(--text-primary);
        }
        
        .btn-secondary:hover {
            background: var(--bg-hover);
        }
        
        .btn-danger {
            background: var(--danger);
            color: white;
        }
        
        .btn-danger:hover {
            background: #d84040;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }
        
        /* Davet Linki */
        .invite-link-box {
            display: flex;
            gap: 8px;
            margin-bottom: 16px;
        }
        
        .invite-link-box input {
            flex: 1;
            padding: 12px;
            background: var(--bg-tertiary);
            border: 1px solid var(--border);
            border-radius: 4px;
            color: var(--text-primary);
            font-family: monospace;
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
        
        .invite-code {
            font-family: monospace;
            font-size: 14px;
            color: var(--primary);
        }
        
        .invite-info {
            font-size: 12px;
            color: var(--text-muted);
        }
        
        /* Rol Atama */
        .role-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px;
            background: var(--bg-tertiary);
            border-radius: 8px;
            margin-bottom: 8px;
        }
        
        .role-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .role-color {
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }
        
        .role-name {
            font-weight: 500;
        }
        
        /* Sesli/Görüntülü Sohbet */
        .voice-chat-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: var(--bg-primary);
        }
        
        .voice-chat-header {
            padding: 16px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .voice-chat-grid {
            flex: 1;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 16px;
            padding: 16px;
            overflow-y: auto;
        }
        
        .voice-participant {
            aspect-ratio: 16/9;
            background: var(--bg-secondary);
            border-radius: 8px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        
        .voice-participant video {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .voice-participant-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            margin-bottom: 12px;
        }
        
        .voice-participant-name {
            font-weight: 500;
            z-index: 1;
        }
        
        .voice-participant-status {
            position: absolute;
            bottom: 8px;
            right: 8px;
            display: flex;
            gap: 8px;
        }
        
        .voice-participant-status i {
            padding: 6px;
            background: rgba(0,0,0,0.6);
            border-radius: 50%;
            font-size: 12px;
        }
        
        .voice-controls {
            padding: 16px;
            background: var(--bg-secondary);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
        }
        
        .voice-control-btn {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            transition: all 0.2s;
        }
        
        .voice-control-btn.primary {
            background: var(--primary);
            color: white;
        }
        
        .voice-control-btn.primary:hover {
            background: var(--primary-hover);
        }
        
        .voice-control-btn.danger {
            background: var(--danger);
            color: white;
        }
        
        .voice-control-btn.danger:hover {
            background: #d84040;
        }
        
        .voice-control-btn.secondary {
            background: var(--bg-tertiary);
            color: var(--text-primary);
        }
        
        .voice-control-btn.secondary:hover {
            background: var(--bg-hover);
        }
        
        .voice-control-btn.muted {
            background: var(--danger);
        }
        
        /* Toast */
        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 20px;
            border-radius: 8px;
            color: white;
            font-weight: 500;
            z-index: 9999;
            animation: slideIn 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        .toast-success { background: var(--success); }
        .toast-error { background: var(--danger); }
        .toast-info { background: var(--primary); }
        
        /* Yükleme */
        .loading {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
        }
        
        .spinner {
            width: 40px;
            height: 40px;
            border: 3px solid var(--bg-tertiary);
            border-top-color: var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Karşılama Ekranı */
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
        
        /* Emoji Paneli */
        .emoji-panel {
            position: absolute;
            bottom: 80px;
            right: 20px;
            width: 350px;
            background: var(--bg-secondary);
            border-radius: 8px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.4);
            display: none;
            z-index: 100;
        }
        
        .emoji-panel.active {
            display: block;
        }
        
        .emoji-header {
            padding: 12px;
            border-bottom: 1px solid var(--border);
        }
        
        .emoji-search {
            width: 100%;
            padding: 8px 12px;
            background: var(--bg-tertiary);
            border: none;
            border-radius: 4px;
            color: var(--text-primary);
            font-size: 14px;
        }
        
        .emoji-categories {
            display: flex;
            padding: 8px;
            gap: 8px;
            border-bottom: 1px solid var(--border);
        }
        
        .emoji-category {
            padding: 8px;
            cursor: pointer;
            border-radius: 4px;
            transition: all 0.2s;
        }
        
        .emoji-category:hover,
        .emoji-category.active {
            background: var(--bg-hover);
        }
        
        .emoji-list {
            display: grid;
            grid-template-columns: repeat(8, 1fr);
            gap: 4px;
            padding: 12px;
            max-height: 250px;
            overflow-y: auto;
        }
        
        .emoji-item {
            font-size: 24px;
            cursor: pointer;
            padding: 4px;
            text-align: center;
            border-radius: 4px;
            transition: all 0.2s;
        }
        
        .emoji-item:hover {
            background: var(--bg-hover);
            transform: scale(1.2);
        }
        
        /* Tema Değiştirici */
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
            z-index: 100;
        }
        
        .theme-toggle:hover {
            transform: scale(1.1);
        }
        
        /* Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: transparent;
        }
        
        ::-webkit-scrollbar-thumb {
            background: var(--bg-tertiary);
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #4f545c;
        }
        
        /* Ekran Paylaşımı */
        .screen-share-preview {
            position: fixed;
            bottom: 100px;
            right: 20px;
            width: 320px;
            height: 180px;
            background: var(--bg-secondary);
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(0,0,0,0.4);
            z-index: 50;
            display: none;
        }
        
        .screen-share-preview.active {
            display: block;
        }
        
        .screen-share-preview video {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        
        .screen-share-preview-header {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            padding: 8px;
            background: linear-gradient(to bottom, rgba(0,0,0,0.6), transparent);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .screen-share-preview-header span {
            font-size: 12px;
            color: white;
        }
        
        .screen-share-preview-header button {
            background: var(--danger);
            border: none;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            cursor: pointer;
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
                 title="Arkadaşlar">
                <i class="fas fa-user-friends"></i>
            </div>
            
            <div class="server-divider"></div>
            
            <!-- Sunucular -->
            <?php foreach ($servers as $server): ?>
                <div class="server-icon <?php echo $activeServerId == $server['id'] ? 'active' : ''; ?>" 
                     onclick="location.href='?server=<?php echo $server['id']; ?>'"
                     title="<?php echo e($server['name']); ?>">
                    <?php if ($server['icon']): ?>
                        <img src="../assets/uploads/<?php echo $server['icon']; ?>" alt="">
                    <?php else: ?>
                        <span><?php echo strtoupper(substr($server['name'], 0, 2)); ?></span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            
            <!-- Sunucu Ekle -->
            <div class="server-icon server-add" onclick="openCreateServerModal()" title="Sunucu Ekle">
                <i class="fas fa-plus"></i>
            </div>
        </div>
        
        <?php if ($activeServerId): ?>
            <!-- Kanal Listesi -->
            <div class="channel-sidebar">
                <div class="server-header">
                    <h3><?php echo e($activeServer['name']); ?></h3>
                    <div class="server-header-actions">
                        <?php if (isServerOwner($activeServerId) || isAdmin()): ?>
                            <button onclick="openServerSettingsModal()" title="Sunucu Ayarları">
                                <i class="fas fa-cog"></i>
                            </button>
                        <?php endif; ?>
                        <button onclick="goToHome()" title="Ana Sayfaya Dön">
                            <i class="fas fa-home"></i>
                        </button>
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
                                    <span class="channel-name"><?php echo e($channel['name']); ?></span>
                                    <div class="channel-item-actions" onclick="event.stopPropagation()">
                                        <button class="channel-item-btn" onclick="openInviteModal(<?php echo $channel['id']; ?>)" title="Davet Et">
                                            <i class="fas fa-user-plus"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        
                        <?php if (isServerOwner($activeServerId) || isAdmin()): ?>
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
                                <div class="channel-item <?php echo $activeChannelId == $channel['id'] ? 'active' : ''; ?>"
                                     data-channel-id="<?php echo $channel['id']; ?>"
                                     data-channel-type="<?php echo $channel['type']; ?>"
                                     onclick="joinVoiceChannel(<?php echo $channel['id']; ?>, '<?php echo $channel['type']; ?>')">
                                    <i class="fas <?php echo $channel['type'] === 'video' ? 'fa-video' : 'fa-volume-up'; ?>"></i>
                                    <span class="channel-name"><?php echo e($channel['name']); ?></span>
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
                        <div class="user-name"><?php echo e($user['username']); ?></div>
                        <div class="user-status-text">#<?php echo $user['id']; ?></div>
                    </div>
                    <div class="user-actions">
                        <button onclick="location.href='settings.php'" title="Ayarlar">
                            <i class="fas fa-cog"></i>
                        </button>
                        <button onclick="location.href='logout.php'" title="Çıkış">
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
                            <h2><?php echo e($activeChannel['name']); ?></h2>
                            <span>|</span>
                            <span><?php echo e($activeChannel['server_name']); ?></span>
                        </div>
                        <div class="chat-header-right">
                            <div class="search-box">
                                <i class="fas fa-search"></i>
                                <input type="text" placeholder="Ara...">
                            </div>
                            <div class="header-actions">
                                <button title="Bildirimler" onclick="showNotifications()">
                                    <i class="fas fa-bell"></i>
                                    <span class="badge" id="notif-badge" style="display: none;">0</span>
                                </button>
                                <button title="Pinlenenler">
                                    <i class="fas fa-thumbtack"></i>
                                </button>
                                <button title="Davet Et" onclick="openInviteModal(<?php echo $activeChannelId; ?>)">
                                    <i class="fas fa-user-plus"></i>
                                </button>
                                <button title="Üye Listesi" onclick="toggleMembers()">
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
                            <textarea id="message-input" placeholder="#<?php echo e($activeChannel['name']); ?> kanalına mesaj gönder" rows="1"></textarea>
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
                        <div class="emoji-list" id="emoji-list"></div>
                    </div>
                    
                <?php elseif ($activeChannel && ($activeChannel['type'] === 'voice' || $activeChannel['type'] === 'video')): ?>
                    <!-- Sesli/Görüntülü Sohbet -->
                    <div class="voice-chat-container" id="voice-chat-container">
                        <div class="voice-chat-header">
                            <div class="chat-header-left">
                                <i class="fas <?php echo $activeChannel['type'] === 'video' ? 'fa-video' : 'fa-volume-up'; ?>" style="color: var(--text-muted);"></i>
                                <h2><?php echo e($activeChannel['name']); ?></h2>
                                <span>|</span>
                                <span><?php echo e($activeChannel['server_name']); ?></span>
                            </div>
                            <div class="header-actions">
                                <button onclick="leaveVoiceChannel()" class="btn btn-danger btn-sm">
                                    <i class="fas fa-phone-slash"></i> Ayrıl
                                </button>
                            </div>
                        </div>
                        
                        <div class="voice-chat-grid" id="voice-participants">
                            <!-- Katılımcılar JS ile yüklenecek -->
                        </div>
                        
                        <div class="voice-controls">
                            <button class="voice-control-btn secondary" id="mic-btn" onclick="toggleMic()">
                                <i class="fas fa-microphone"></i>
                            </button>
                            <button class="voice-control-btn secondary" id="video-btn" onclick="toggleVideo()">
                                <i class="fas fa-video"></i>
                            </button>
                            <button class="voice-control-btn secondary" id="screen-btn" onclick="toggleScreenShare()">
                                <i class="fas fa-desktop"></i>
                            </button>
                            <button class="voice-control-btn danger" onclick="leaveVoiceChannel()">
                                <i class="fas fa-phone-slash"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Ekran Paylaşımı Önizleme -->
                    <div class="screen-share-preview" id="screen-share-preview">
                        <div class="screen-share-preview-header">
                            <span><i class="fas fa-desktop"></i> Ekran Paylaşımı</span>
                            <button onclick="stopScreenShare()">Durdur</button>
                        </div>
                        <video id="screen-share-video" autoplay muted></video>
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
                    $roles[$member['role_name'] ?? 'Member'][] = $member;
                }
                ?>
                
                <?php foreach ($roles as $roleName => $roleMembers): ?>
                    <?php if (!empty($roleMembers)): ?>
                        <div class="members-header" style="margin-top: 16px;"><?php echo $roleName; ?> — <?php echo count($roleMembers); ?></div>
                        <?php foreach ($roleMembers as $member): ?>
                            <div class="member-item" onclick="showMemberMenu(<?php echo $member['id']; ?>, '<?php echo e($member['username']); ?>', <?php echo $member['role_name'] === 'Admin' ? 1 : ($member['role_name'] === 'Moderator' ? 2 : 3); ?>)">
                                <div class="avatar-wrapper">
                                    <img src="../assets/uploads/avatars/<?php echo !empty($member['avatar']) ? $member['avatar'] : 'default-avatar.png'; ?>" 
                                         alt="" 
                                         class="avatar avatar-sm"
                                         onerror="this.src='../assets/uploads/avatars/default-avatar.png'">
                                    <span class="avatar-status status-<?php echo $member['status']; ?>"></span>
                                </div>
                                <div class="member-info">
                                    <div class="member-name" style="color: <?php echo $member['color'] ?? '#7289da'; ?>">
                                        <?php echo e($member['username']); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            
        <?php else: ?>
            <!-- Arkadaşlar Görünümü -->
            <div class="friends-view" id="friends-view">
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
                                    <div class="member-name"><?php echo e($friend['username']); ?></div>
                                    <div class="member-status"><?php echo $friend['status'] === 'online' ? 'Çevrimiçi' : 'Çevrimdışı'; ?></div>
                                </div>
                                <div class="friend-actions">
                                    <button title="Mesaj Gönder">
                                        <i class="fas fa-comment"></i>
                                    </button>
                                    <button title="Sesli Ara">
                                        <i class="fas fa-phone"></i>
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
                                    <div class="member-name"><?php echo e($request['username']); ?></div>
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
                                    <h4><?php echo e($invite['inviter_name']); ?> davet etti</h4>
                                    <p><?php echo e($invite['server_name']); ?> / <?php echo e($invite['channel_name']); ?></p>
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
                <button class="modal-close" onclick="closeModal('create-server-modal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Sunucu Adı</label>
                    <input type="text" id="server-name" placeholder="Örn: Oyun Grubum">
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('create-server-modal')">İptal</button>
                <button class="btn btn-primary" onclick="createServer()">Oluştur</button>
            </div>
        </div>
    </div>
    
    <!-- Kanal Oluşturma Modalı -->
    <div class="modal-overlay" id="create-channel-modal">
        <div class="modal">
            <div class="modal-header">
                <h3>Kanal Oluştur</h3>
                <button class="modal-close" onclick="closeModal('create-channel-modal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Kanal Adı</label>
                    <input type="text" id="channel-name" placeholder="Örn: genel-sohbet">
                </div>
                <div class="form-group">
                    <label>Kanal Türü</label>
                    <select id="channel-type">
                        <option value="text">Metin Kanalı</option>
                        <option value="voice">Sesli Kanal</option>
                        <option value="video">Görüntülü Kanal</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('create-channel-modal')">İptal</button>
                <button class="btn btn-primary" onclick="createChannel()">Oluştur</button>
            </div>
        </div>
    </div>
    
    <!-- Kanal Davet Modalı -->
    <div class="modal-overlay" id="invite-modal">
        <div class="modal">
            <div class="modal-header">
                <h3>Kanala Davet Et</h3>
                <button class="modal-close" onclick="closeModal('invite-modal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Davet Linki</label>
                    <div class="invite-link-box">
                        <input type="text" id="invite-link-input" readonly>
                        <button class="btn btn-primary" onclick="createInviteLink()">
                            <i class="fas fa-copy"></i> Kopyala
                        </button>
                    </div>
                </div>
                <hr style="border: none; border-top: 1px solid var(--border); margin: 16px 0;">
                <div class="form-group">
                    <label>Arkadaşlarını Davet Et</label>
                    <div id="invite-friends-list"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('invite-modal')">Kapat</button>
            </div>
        </div>
    </div>
    
    <!-- Sunucu Ayarları Modalı -->
    <div class="modal-overlay" id="server-settings-modal">
        <div class="modal" style="max-width: 600px;">
            <div class="modal-header">
                <h3>Sunucu Ayarları</h3>
                <button class="modal-close" onclick="closeModal('server-settings-modal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <!-- Davet Linkleri -->
                <div class="form-group">
                    <label>Davet Linkleri</label>
                    <button class="btn btn-primary btn-sm" onclick="createServerInvite()" style="margin-bottom: 12px;">
                        <i class="fas fa-plus"></i> Yeni Davet Linki
                    </button>
                    <div id="server-invites-list">
                        <?php foreach ($serverInvites as $invite): ?>
                            <div class="invite-list-item">
                                <div>
                                    <span class="invite-code"><?php echo e($invite['invite_code']); ?></span>
                                    <div class="invite-info">
                                        Kullanım: <?php echo $invite['used_count']; ?>/<?php echo $invite['max_uses'] ?: '∞'; ?> • 
                                        Oluşturan: <?php echo e($invite['created_by_name']); ?>
                                    </div>
                                </div>
                                <button class="btn btn-danger btn-sm" onclick="deleteServerInvite(<?php echo $invite['id']; ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <hr style="border: none; border-top: 1px solid var(--border); margin: 16px 0;">
                
                <!-- Rol Yönetimi -->
                <div class="form-group">
                    <label>Rol Yönetimi</label>
                    <p style="color: var(--text-muted); font-size: 13px; margin-bottom: 12px;">
                        Üye üzerine tıklayarak rol atayabilirsiniz.
                    </p>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('server-settings-modal')">Kapat</button>
            </div>
        </div>
    </div>
    
    <!-- Rol Atama Modalı -->
    <div class="modal-overlay" id="role-assign-modal">
        <div class="modal">
            <div class="modal-header">
                <h3>Rol Atama</h3>
                <button class="modal-close" onclick="closeModal('role-assign-modal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <p style="margin-bottom: 16px;">
                    <strong id="role-assign-username"></strong> kullanıcısına rol ata:
                </p>
                <div id="role-list">
                    <?php foreach ($allRoles as $role): ?>
                        <div class="role-item" onclick="assignRole(<?php echo $role['id']; ?>)">
                            <div class="role-info">
                                <span class="role-color" style="background: <?php echo e($role['color']); ?>"></span>
                                <span class="role-name"><?php echo e($role['role_name']); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('role-assign-modal')">İptal</button>
            </div>
        </div>
    </div>
    
    <button class="theme-toggle" onclick="toggleTheme()" title="Temayı Değiştir">
        <i class="fas fa-sun"></i>
    </button>
    
    <script src="../assets/js/app.js"></script>
    <script>
        // Global değişkenler
        window.currentChannelId = <?php echo $activeChannelId ?: 'null'; ?>;
        window.currentUserId = <?php echo $user['id']; ?>;
        window.currentUsername = '<?php echo e($user['username']); ?>';
        window.currentAvatar = '../assets/uploads/avatars/<?php echo !empty($user['avatar']) ? $user['avatar'] : 'default-avatar.png'; ?>';
        window.activeServerId = <?php echo $activeServerId ? intval($activeServerId) : 'null'; ?>;
        window.activeChannelType = '<?php echo $activeChannel['type'] ?? 'text'; ?>';
        
        // Aktif davet kanalı
        let activeInviteChannelId = null;
        let selectedUserId = null;
        
        // WebRTC değişkenleri
        let localStream = null;
        let screenStream = null;
        let peerConnections = {};
        let isMicOn = true;
        let isVideoOn = true;
        let isScreenSharing = false;
        
        // Sayfa yüklendiğinde
        document.addEventListener('DOMContentLoaded', () => {
            <?php if ($activeChannelId && $activeChannel['type'] === 'text'): ?>
                if (typeof ChatManager !== 'undefined') {
                    ChatManager.init(<?php echo $activeChannelId; ?>);
                }
            <?php elseif ($activeChannelId && ($activeChannel['type'] === 'voice' || $activeChannel['type'] === 'video')): ?>
                initWebRTC();
            <?php endif; ?>
            
            // Emoji panelini doldur
            fillEmojiPanel();
            
            // Modal kapatma olayları
            document.querySelectorAll('.modal-overlay').forEach(modal => {
                modal.addEventListener('click', (e) => {
                    if (e.target === modal) modal.classList.remove('active');
                });
            });
        });
        
        // Modal fonksiyonları
        function openModal(modalId) {
            document.getElementById(modalId).classList.add('active');
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }
        
        function openCreateServerModal() {
            openModal('create-server-modal');
        }
        
        function openCreateChannelModal() {
            openModal('create-channel-modal');
        }
        
        function openServerSettingsModal() {
            openModal('server-settings-modal');
        }
        
        function openInviteModal(channelId) {
            activeInviteChannelId = channelId;
            openModal('invite-modal');
            loadFriendsForInvite();
            
            // Davet linkini oluştur
            fetch('../api/channel-invite.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'create',
                    channel_id: channelId
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('invite-link-input').value = data.url;
                }
            });
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
                        <div class="friend-item">
                            <div class="avatar-wrapper">
                                <img src="../assets/uploads/avatars/${friend.avatar || 'default-avatar.png'}" 
                                     class="avatar avatar-sm"
                                     onerror="this.src='../assets/uploads/avatars/default-avatar.png'">
                            </div>
                            <div class="member-info">
                                <div class="member-name">${escapeHtml(friend.username)}</div>
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
                    loadFriendsForInvite();
                } else {
                    showToast(data.error || 'Davet gönderilemedi', 'error');
                }
            } catch (error) {
                showToast('Davet gönderilirken hata oluştu', 'error');
            }
        }
        
        // Davet linki oluştur
        async function createInviteLink() {
            const input = document.getElementById('invite-link-input');
            input.select();
            document.execCommand('copy');
            showToast('Link kopyalandı!', 'success');
        }
        
        // Sunucu davet linki oluştur
        async function createServerInvite() {
            if (!window.activeServerId) return;
            
            try {
                const response = await fetch('../api/server-invite.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        server_id: window.activeServerId
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showToast('Davet linki oluşturuldu!', 'success');
                    location.reload();
                } else {
                    showToast(data.error || 'Link oluşturulamadı', 'error');
                }
            } catch (error) {
                showToast('Link oluşturulurken hata oluştu', 'error');
            }
        }
        
        // Sunucu davet linkini sil
        async function deleteServerInvite(inviteId) {
            if (!confirm('Bu davet linkini silmek istediğinize emin misiniz?')) return;
            
            try {
                const response = await fetch('../api/server-invite.php', {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ invite_id: inviteId })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showToast('Link silindi', 'success');
                    location.reload();
                } else {
                    showToast(data.error || 'Link silinemedi', 'error');
                }
            } catch (error) {
                showToast('Link silinirken hata oluştu', 'error');
            }
        }
        
        // Üye menüsü göster (rol atama)
        function showMemberMenu(userId, username, currentRoleId) {
            <?php if (!isServerOwner($activeServerId) && !isAdmin()): ?>
                return;
            <?php endif; ?>
            
            // Kendini değiştirme
            if (userId == window.currentUserId) {
                showToast('Kendi rolünüzü değiştiremezsiniz', 'error');
                return;
            }
            
            selectedUserId = userId;
            document.getElementById('role-assign-username').textContent = username;
            openModal('role-assign-modal');
        }
        
        // Rol ata
        async function assignRole(roleId) {
            if (!selectedUserId || !window.activeServerId) return;
            
            try {
                const response = await fetch('../api/assign-role.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        server_id: window.activeServerId,
                        user_id: selectedUserId,
                        role_id: roleId
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showToast(data.message || 'Rol atandı!', 'success');
                    closeModal('role-assign-modal');
                    location.reload();
                } else {
                    showToast(data.error || 'Rol atanamadı', 'error');
                }
            } catch (error) {
                showToast('Rol atanırken hata oluştu', 'error');
            }
        }
        
        // Sunucu oluşturma
        async function createServer() {
            const name = document.getElementById('server-name').value.trim();
            if (!name) {
                showToast('Sunucu adı gerekli', 'error');
                return;
            }
            
            try {
                const response = await fetch('../api/create-server.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ name })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    location.href = '?server=' + data.server_id;
                } else {
                    showToast(data.error || 'Sunucu oluşturulamadı', 'error');
                }
            } catch (error) {
                showToast('Sunucu oluşturulurken hata', 'error');
            }
        }
        
        // Kanal oluşturma
        async function createChannel() {
            const name = document.getElementById('channel-name').value.trim();
            const type = document.getElementById('channel-type').value;
            
            if (!name) {
                showToast('Kanal adı gerekli', 'error');
                return;
            }
            
            if (!window.activeServerId) {
                showToast('Sunucu bulunamadı', 'error');
                return;
            }
            
            try {
                const response = await fetch('../api/create-channel.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        server_id: window.activeServerId,
                        name: name,
                        type: type
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    location.reload();
                } else {
                    showToast(data.error || 'Kanal oluşturulamadı', 'error');
                }
            } catch (error) {
                showToast('Kanal oluşturulurken hata', 'error');
            }
        }
        
        // Ana sayfaya dönüş
        function goToHome() {
            window.location.href = 'dashboard.php';
        }
        
        // Arkadaşlar sekmesi değiştir
        function switchFriendsTab(tab) {
            document.querySelectorAll('.friends-tab').forEach(t => t.classList.remove('active'));
            event.target.classList.add('active');
            
            document.querySelectorAll('[id^="tab-"]').forEach(t => t.style.display = 'none');
            document.getElementById('tab-' + tab).style.display = 'block';
        }
        
        // Arkadaşlık isteği gönder
        async function sendFriendRequest() {
            const username = document.getElementById('friend-username').value.trim();
            if (!username) return;
            
            try {
                const response = await fetch('../api/friend-request.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ username })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showToast('Arkadaşlık isteği gönderildi!', 'success');
                    document.getElementById('friend-username').value = '';
                } else {
                    showToast(data.error || 'İstek gönderilemedi', 'error');
                }
            } catch (error) {
                showToast('İstek gönderildi', 'success');
            }
        }
        
        // Arkadaşlık isteğini yanıtla
        async function respondRequest(friendshipId, accept) {
            try {
                const response = await fetch('../api/respond-friend.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ friendship_id: friendshipId, accept })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    location.reload();
                } else {
                    showToast(data.error || 'İşlem başarısız', 'error');
                }
            } catch (error) {
                location.reload();
            }
        }
        
        // Kanal davetini yanıtla
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
        
        // Üye listesi toggle
        function toggleMembers() {
            const sidebar = document.getElementById('members-sidebar');
            sidebar.style.display = sidebar.style.display === 'none' ? 'block' : 'none';
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
                const formData = new FormData();
                formData.append('file', input.files[0]);
                
                try {
                    const response = await fetch('../api/upload.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        showToast('Dosya yüklendi!', 'success');
                    } else {
                        showToast(data.error || 'Yükleme başarısız', 'error');
                    }
                } catch (error) {
                    showToast('Yükleme hatası', 'error');
                }
            }
        }
        
        // ========== WEBRTC SESLİ/GÖRÜNTÜLÜ SOHBET ==========
        
        // Sesli kanala katıl
        async function joinVoiceChannel(channelId, type) {
            window.location.href = `?server=${window.activeServerId}&channel=${channelId}`;
        }
        
        // WebRTC başlat
        async function initWebRTC() {
            if (!window.currentChannelId) return;
            
            try {
                // Kamera ve mikrofon erişimi iste
                const constraints = {
                    audio: true,
                    video: window.activeChannelType === 'video'
                };
                
                localStream = await navigator.mediaDevices.getUserMedia(constraints);
                
                // Kendi görüntünü ekle
                addParticipantToGrid(window.currentUserId, window.currentUsername, window.currentAvatar, localStream, true);
                
                // Sunucuya katılım bildir
                await fetch('../api/voice-join.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        channel_id: window.currentChannelId,
                        is_audio_on: isMicOn,
                        is_video_on: isVideoOn
                    })
                });
                
                showToast('Sesli kanala katıldınız', 'success');
                
            } catch (error) {
                console.error('WebRTC hatası:', error);
                showToast('Kamera/Mikrofon erişimi reddedildi', 'error');
            }
        }
        
        // Katılımcıyı grid'e ekle
        function addParticipantToGrid(userId, username, avatar, stream, isLocal = false) {
            const grid = document.getElementById('voice-participants');
            if (!grid) return;
            
            const existing = document.getElementById(`participant-${userId}`);
            if (existing) existing.remove();
            
            const div = document.createElement('div');
            div.className = 'voice-participant';
            div.id = `participant-${userId}`;
            
            if (stream && stream.getVideoTracks().length > 0 && stream.getVideoTracks()[0].enabled) {
                const video = document.createElement('video');
                video.srcObject = stream;
                video.autoplay = true;
                video.muted = isLocal;
                div.appendChild(video);
            } else {
                const img = document.createElement('img');
                img.src = avatar;
                img.className = 'voice-participant-avatar';
                img.onerror = () => { img.src = '../assets/uploads/avatars/default-avatar.png'; };
                div.appendChild(img);
            }
            
            const name = document.createElement('div');
            name.className = 'voice-participant-name';
            name.textContent = username + (isLocal ? ' (Sen)' : '');
            div.appendChild(name);
            
            const status = document.createElement('div');
            status.className = 'voice-participant-status';
            status.innerHTML = `
                <i class="fas fa-microphone${isMicOn ? '' : '-slash'}"></i>
                ${stream && stream.getVideoTracks().length > 0 ? `<i class="fas fa-video${isVideoOn ? '' : '-slash'}"></i>` : ''}
            `;
            div.appendChild(status);
            
            grid.appendChild(div);
        }
        
        // Mikrofon aç/kapat
        function toggleMic() {
            if (!localStream) return;
            
            const audioTrack = localStream.getAudioTracks()[0];
            if (audioTrack) {
                audioTrack.enabled = !audioTrack.enabled;
                isMicOn = audioTrack.enabled;
                
                const btn = document.getElementById('mic-btn');
                btn.innerHTML = `<i class="fas fa-microphone${isMicOn ? '' : '-slash'}"></i>`;
                btn.classList.toggle('muted', !isMicOn);
                
                // Sunucuya bildir
                updateVoiceStatus();
            }
        }
        
        // Kamera aç/kapat
        function toggleVideo() {
            if (!localStream) return;
            
            const videoTrack = localStream.getVideoTracks()[0];
            if (videoTrack) {
                videoTrack.enabled = !videoTrack.enabled;
                isVideoOn = videoTrack.enabled;
                
                const btn = document.getElementById('video-btn');
                btn.innerHTML = `<i class="fas fa-video${isVideoOn ? '' : '-slash'}"></i>`;
                btn.classList.toggle('muted', !isVideoOn);
                
                // Grid'i güncelle
                addParticipantToGrid(window.currentUserId, window.currentUsername, window.currentAvatar, localStream, true);
                
                // Sunucuya bildir
                updateVoiceStatus();
            }
        }
        
        // Ekran paylaşma
        async function toggleScreenShare() {
            if (isScreenSharing) {
                stopScreenShare();
                return;
            }
            
            try {
                screenStream = await navigator.mediaDevices.getDisplayMedia({
                    video: true,
                    audio: true
                });
                
                isScreenSharing = true;
                
                const preview = document.getElementById('screen-share-preview');
                const video = document.getElementById('screen-share-video');
                
                video.srcObject = screenStream;
                preview.classList.add('active');
                
                const btn = document.getElementById('screen-btn');
                btn.classList.add('muted');
                
                showToast('Ekran paylaşımı başladı', 'success');
                
                // Ekran paylaşımı durduğunda
                screenStream.getVideoTracks()[0].onended = () => {
                    stopScreenShare();
                };
                
            } catch (error) {
                console.error('Ekran paylaşım hatası:', error);
                showToast('Ekran paylaşımı başlatılamadı', 'error');
            }
        }
        
        // Ekran paylaşımını durdur
        function stopScreenShare() {
            if (screenStream) {
                screenStream.getTracks().forEach(track => track.stop());
                screenStream = null;
            }
            
            isScreenSharing = false;
            
            const preview = document.getElementById('screen-share-preview');
            preview.classList.remove('active');
            
            const btn = document.getElementById('screen-btn');
            btn.classList.remove('muted');
            
            showToast('Ekran paylaşımı durduruldu', 'info');
        }
        
        // Sesli odadan ayrıl
        async function leaveVoiceChannel() {
            // Stream'leri durdur
            if (localStream) {
                localStream.getTracks().forEach(track => track.stop());
                localStream = null;
            }
            
            stopScreenShare();
            
            // Sunucuya ayrılma bildir
            if (window.currentChannelId) {
                await fetch('../api/voice-leave.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ channel_id: window.currentChannelId })
                });
            }
            
            // Metin kanalına yönlendir
            window.location.href = `?server=${window.activeServerId}`;
        }
        
        // Ses durumunu güncelle
        async function updateVoiceStatus() {
            if (!window.currentChannelId) return;
            
            await fetch('../api/voice-status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    channel_id: window.currentChannelId,
                    is_audio_on: isMicOn,
                    is_video_on: isVideoOn,
                    is_screen_sharing: isScreenSharing
                })
            });
        }
        
        // Toast bildirim
        function showToast(message, type = 'info') {
            const existing = document.querySelector('.toast');
            if (existing) existing.remove();
            
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            
            const icon = type === 'success' ? 'check-circle' : 
                         type === 'error' ? 'exclamation-circle' : 'info-circle';
            
            toast.innerHTML = `<i class="fas fa-${icon}"></i> ${message}`;
            document.body.appendChild(toast);
            
            setTimeout(() => toast.remove(), 3000);
        }
        
        // HTML escape
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Tema değiştir
        function toggleTheme() {
            const html = document.documentElement;
            const current = html.getAttribute('data-theme');
            const newTheme = current === 'dark' ? 'light' : 'dark';
            html.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            
            // Sunucuya kaydet
            fetch('../api/update-theme.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ theme: newTheme })
            }).catch(() => {});
        }
        
        // Kayıtlı temayı yükle
        const savedTheme = localStorage.getItem('theme') || 'dark';
        document.documentElement.setAttribute('data-theme', savedTheme);
        
        // Sayfa kapatılırken
        window.addEventListener('beforeunload', () => {
            if (window.currentChannelId && (window.activeChannelType === 'voice' || window.activeChannelType === 'video')) {
                fetch('../api/voice-leave.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ channel_id: window.currentChannelId })
                });
            }
        });
    </script>
</body>
</html>
