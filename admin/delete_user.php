<?php
require_once '../includes/database.php';
require_once '../includes/auth.php';

// Kullanıcının admin olup olmadığını kontrol et
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login");
    exit();
}

// Kullanıcı ID'si URL'den alınır
if (isset($_GET['id'])) {
    $user_id = $_GET['id'];

    // Kullanıcıyı veritabanından sil
    $stmt = $db->prepare("DELETE FROM users WHERE id = :id");
    $stmt->bindParam(':id', $user_id, PDO::PARAM_INT);

    if ($stmt->execute()) {
        // Silme başarılı ise yönetici sayfasına yönlendir
        header("Location: users.php?message=Kullanıcı başarıyla silindi.");
        exit();
    } else {
        // Silme başarısız olduysa hata mesajı
        echo "Kullanıcı silinemedi, lütfen tekrar deneyin.";
    }
} else {
    // ID parametresi geçerli değilse hata mesajı
    echo "Geçersiz kullanıcı ID'si.";
}
?>
