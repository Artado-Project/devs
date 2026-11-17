<?php
session_start();

if (file_exists('../config.php')) {
    require '../config.php';
} elseif (file_exists('config.php')) {
    require 'config.php';
}

header('Content-Type: application/json; charset=utf-8');

// Kullanıcı giriş kontrolü
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Yorum yapabilmek için lütfen giriş yapınız.'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_comment') {
    $project_id = (int)($_POST['project_id'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');
    $rating = (int)($_POST['rating'] ?? 0);
    $user_id = $_SESSION['user_id'];
    
    // Girdi doğrulama
    if ($project_id <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Geçersiz proje ID.'
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }
    
    if (empty($comment)) {
        echo json_encode([
            'success' => false,
            'message' => 'Yorum alanı boş bırakılamaz.'
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }
    
    if (mb_strlen($comment) > 1000) {
        echo json_encode([
            'success' => false,
            'message' => 'Yorum çok uzun. En fazla 1000 karakter yazabilirsiniz.'
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }
    
    if ($rating < 1 || $rating > 5) {
        $rating = 5; // Varsayılan değer
    }
    
    try {
        // Projenin var olup olmadığını kontrol et
        $stmt = $db->prepare("SELECT id, user_id as owner_id, title FROM projects WHERE id = ?");
        $stmt->execute([$project_id]);
        $project = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$project) {
            echo json_encode([
                'success' => false,
                'message' => 'İlgili proje bulunamadı.'
            ], JSON_UNESCAPED_UNICODE);
            exit();
        }
        
        // Yorum ekle
        $stmt = $db->prepare("
            INSERT INTO workshop_comments (project_id, user_id, comment, rating, status, created_at) 
            VALUES (?, ?, ?, ?, 'approved', NOW())
        ");
        $stmt->execute([$project_id, $user_id, $comment, $rating]);
        $comment_id = $db->lastInsertId();
        
        // Bildirim e-postası (varsa)
        if ($project['owner_id'] != $user_id) {
            sendCommentNotification($project['owner_id'], $project['title'], $comment, $user_id);
        }
        
        // Eklenen kullanıcı adı
        $username = $_SESSION['username'] ?? 'Kullanıcı';
        
        echo json_encode([
            'success' => true,
            'message' => 'Değerlendirmeniz başarıyla eklendi!',
            'comment' => [
                'id' => $comment_id,
                'username' => $username,
                'comment' => htmlspecialchars($comment),
                'rating' => $rating,
                'created_at' => date('d.m.Y H:i')
            ]
        ], JSON_UNESCAPED_UNICODE);
        
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Veritabanı hatası: ' . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Geçersiz istek parametresi.'
    ], JSON_UNESCAPED_UNICODE);
}

function sendCommentNotification($project_owner_id, $project_title, $comment, $commenter_id) {
    global $db;
    
    try {
        $stmt = $db->prepare("SELECT username, email FROM users WHERE id = ?");
        $stmt->execute([$project_owner_id]);
        $owner = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $stmt = $db->prepare("SELECT username FROM users WHERE id = ?");
        $stmt->execute([$commenter_id]);
        $commenter = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($owner && !empty($owner['email']) && $commenter) {
            $to = $owner['email'];
            $subject = "Artado Workshop - Yeni Proje Yorumu: " . $project_title;
            $message = "
            <html>
            <body style='font-family: sans-serif; background-color: #0f172a; color: #f8fafc; padding: 20px;'>
                <div style='max-width: 600px; margin: 0 auto; background-color: #1e293b; border-radius: 12px; padding: 24px; border: 1px solid #334155;'>
                    <h2 style='color: #38bdf8; margin-top: 0;'>Yeni Yorum Bildirimi</h2>
                    <p>Merhaba <strong>{$owner['username']}</strong>,</p>
                    <p><strong>{$commenter['username']}</strong> adlı kullanıcı '<strong>{$project_title}</strong>' başlıklı çalışmanıza yeni bir yorum yaptı:</p>
                    <div style='background-color: #0f172a; padding: 15px; border-left: 4px solid #38bdf8; margin: 20px 0; border-radius: 6px;'>
                        <p style='margin: 0; color: #cbd5e1;'>" . nl2br(htmlspecialchars($comment)) . "</p>
                    </div>
                    <p style='color: #94a3b8; font-size: 12px;'>Artado Geliştirici Ekosistemi</p>
                </div>
            </body>
            </html>
            ";
            
            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8\r\n";
            $headers .= "From: noreply@artadosearch.com\r\n";
            
            @mail($to, $subject, $message, $headers);
        }
    } catch (Exception $e) {
        error_log("Email bildirim hatası: " . $e->getMessage());
    }
}
