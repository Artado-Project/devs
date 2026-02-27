<?php
// Bildirim fonksiyonları

function createNotification($user_id, $type, $title, $message, $link = null) {
    global $db;
    
    try {
        $stmt = $db->prepare("
            INSERT INTO notifications (user_id, type, title, message, link, created_at) 
            VALUES (:user_id, :type, :title, :message, :link, NOW())
        ");
        
        $stmt->execute([
            ':user_id' => $user_id,
            ':type' => $type,
            ':title' => $title,
            ':message' => $message,
            ':link' => $link
        ]);
        
        return true;
    } catch (PDOException $e) {
        error_log("Bildirim oluşturma hatası: " . $e->getMessage());
        return false;
    }
}

function getUnreadNotifications($user_id) {
    global $db;
    
    try {
        $stmt = $db->prepare("
            SELECT * FROM notifications 
            WHERE user_id = :user_id AND is_read = 0 
            ORDER BY created_at DESC 
            LIMIT 10
        ");
        
        $stmt->execute([':user_id' => $user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Bildirimleri getirme hatası: " . $e->getMessage());
        return [];
    }
}

function markNotificationAsRead($notification_id, $user_id) {
    global $db;
    
    try {
        $stmt = $db->prepare("
            UPDATE notifications 
            SET is_read = 1, read_at = NOW() 
            WHERE id = :id AND user_id = :user_id
        ");
        
        return $stmt->execute([':id' => $notification_id, ':user_id' => $user_id]);
    } catch (PDOException $e) {
        error_log("Bildirim okundu işaretleme hatası: " . $e->getMessage());
        return false;
    }
}

function getUnreadNotificationCount($user_id) {
    global $db;
    
    try {
        $stmt = $db->prepare("
            SELECT COUNT(*) as count FROM notifications 
            WHERE user_id = :user_id AND is_read = 0
        ");
        
        $stmt->execute([':user_id' => $user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] ?? 0;
    } catch (PDOException $e) {
        error_log("Bildirim sayısı alma hatası: " . $e->getMessage());
        return 0;
    }
}
?>
