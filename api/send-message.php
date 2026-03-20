<?php
// Discord Clone - Mesaj Gönderme API
// DÜZELTİLDİ: Hata raporlamayı en başta kapat
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');

header('Content-Type: application/json');

// Buffer'ı temizle
while (ob_get_level()) ob_end_clean();

try {
    require_once '../includes/config.php';
    
    // Giriş kontrolü
    if (!function_exists('isLoggedIn') || !isLoggedIn()) {
        echo json_encode(['success' => false, 'error' => 'Yetkisiz erişim']);
        exit;
    }
    
    // JSON veri al
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (!$data) {
        echo json_encode(['success' => false, 'error' => 'Geçersiz JSON verisi']);
        exit;
    }
    
    $channelId = isset($data['channel_id']) ? intval($data['channel_id']) : 0;
    $message = isset($data['message']) ? trim($data['message']) : '';
    $replyTo = isset($data['reply_to']) ? intval($data['reply_to']) : null;
    
    if (!$channelId || empty($message)) {
        echo json_encode(['success' => false, 'error' => 'Kanal ve mesaj gerekli']);
        exit;
    }
    
    $userId = intval($_SESSION['user_id'] ?? 0);
    if (!$userId) {
        echo json_encode(['success' => false, 'error' => 'Kullanıcı oturumu bulunamadı']);
        exit;
    }
    
    // DÜZELTİLDİ: Direkt veritabanı işlemi - channels.php bağımlılığı yok
    $db = getDB();
    
    // Kanal var mı kontrol et
    $stmt = $db->prepare("SELECT id, server_id FROM channels WHERE id = ?");
    $stmt->execute([$channelId]);
    $channel = $stmt->fetch();
    
    if (!$channel) {
        echo json_encode(['success' => false, 'error' => 'Kanal bulunamadı']);
        exit;
    }
    
    // Kullanıcı bu sunucunun üyesi mi kontrol et
    $stmt = $db->prepare("SELECT 1 FROM server_members WHERE server_id = ? AND user_id = ?");
    $stmt->execute([$channel['server_id'], $userId]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Bu kanala mesaj gönderme yetkiniz yok']);
        exit;
    }
    
    // Mesajı kaydet
    $stmt = $db->prepare("INSERT INTO messages (user_id, channel_id, message, reply_to, created_at, is_edited) VALUES (?, ?, ?, ?, NOW(), 0)");
    $stmt->execute([$userId, $channelId, $message, $replyTo]);
    $messageId = $db->lastInsertId();
    
    // DÜZELTİLDİ: Bildirim oluştur (opsiyonel, hata olursa mesaj yine de gider)
    try {
        $stmt = $db->prepare("SELECT user_id FROM server_members WHERE server_id = ? AND user_id != ?");
        $stmt->execute([$channel['server_id'], $userId]);
        $members = $stmt->fetchAll();
        
        if (!empty($members)) {
            $notifStmt = $db->prepare("INSERT INTO notifications (user_id, sender_id, notification_type, content, related_id, is_read, created_at) VALUES (?, ?, 'message', ?, ?, 0, NOW())");
            foreach ($members as $member) {
                try {
                    $notifStmt->execute([$member['user_id'], $userId, substr($message, 0, 100), $channelId]);
                } catch (Exception $e) {
                    // Bildirim hatası mesajı engellemesin
                    continue;
                }
            }
        }
    } catch (Exception $e) {
        // Bildirim hatası kritik değil, logla ve devam et
        error_log('Bildirim hatası: ' . $e->getMessage());
    }
    
    echo json_encode(['success' => true, 'message_id' => $messageId]);
    
} catch (Exception $e) {
    error_log('Mesaj gönderme hatası: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Sunucu hatası: ' . $e->getMessage()]);
} catch (Error $e) {
    // Fatal error yakalama
    error_log('Fatal error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Kritik sunucu hatası']);
}