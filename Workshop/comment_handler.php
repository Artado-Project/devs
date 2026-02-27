<?php
session_start();
require 'config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'You must be logged in to post a comment.'
    ]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_comment') {
    $project_id = (int)($_POST['project_id'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');
    $rating = (int)($_POST['rating'] ?? 0);
    $user_id = $_SESSION['user_id'];
    
    // Validate input
    if ($project_id === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid project ID.'
        ]);
        exit();
    }
    
    if (empty($comment)) {
        echo json_encode([
            'success' => false,
            'message' => 'Comment cannot be empty.'
        ]);
        exit();
    }
    
    if (strlen($comment) > 1000) {
        echo json_encode([
            'success' => false,
            'message' => 'Comment is too long. Maximum 1000 characters.'
        ]);
        exit();
    }
    
    if ($rating < 0 || $rating > 5) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid rating.'
        ]);
        exit();
    }
    
    try {
        // Check if project exists
        $stmt = $db->prepare("SELECT id, user_id as owner_id, title FROM projects WHERE id = ?");
        $stmt->execute([$project_id]);
        $project = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$project) {
            echo json_encode([
                'success' => false,
                'message' => 'Project not found.'
            ]);
            exit();
        }
        
        // Insert comment
        $stmt = $db->prepare("
            INSERT INTO workshop_comments (project_id, user_id, comment, rating, status) 
            VALUES (?, ?, ?, ?, 'pending')
        ");
        $stmt->execute([$project_id, $user_id, $comment, $rating]);
        
        // Send email notification to project owner (if different from commenter)
        if ($project['owner_id'] != $user_id) {
            sendCommentNotification($project['owner_id'], $project['title'], $comment, $user_id);
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Comment submitted successfully! It will be visible after admin approval.'
        ]);
        
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request.'
    ]);
}

function sendCommentNotification($project_owner_id, $project_title, $comment, $commenter_id) {
    global $db;
    
    try {
        // Get project owner email
        $stmt = $db->prepare("SELECT username, email FROM users WHERE id = ?");
        $stmt->execute([$project_owner_id]);
        $owner = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get commenter username
        $stmt = $db->prepare("SELECT username FROM users WHERE id = ?");
        $stmt->execute([$commenter_id]);
        $commenter = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($owner && $commenter) {
            $to = $owner['email'];
            $subject = "New Comment on Your Project: " . $project_title;
            $message = "
            <html>
            <body>
                <h2>New Comment Notification</h2>
                <p>Hi {$owner['username']},</p>
                <p>{$commenter['username']} has posted a new comment on your project '<strong>{$project_title}</strong>'.</p>
                <div style='background-color: #f5f5f5; padding: 15px; border-left: 4px solid #007bff; margin: 20px 0;'>
                    <p><strong>Comment:</strong></p>
                    <p>" . htmlspecialchars($comment) . "</p>
                </div>
                <p>This comment is currently pending admin approval and will be visible once approved.</p>
                <p>Best regards,<br>Artado Developers Team</p>
            </body>
            </html>
            ";
            
            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $headers .= "From: noreply@artadosearch.com" . "\r\n";
            
            mail($to, $subject, $message, $headers);
        }
    } catch (Exception $e) {
        // Log error but don't fail the comment submission
        error_log("Email notification failed: " . $e->getMessage());
    }
}
?>
