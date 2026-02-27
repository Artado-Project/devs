<?php
require_once '../includes/database.php';
require_once '../includes/auth.php';

// Kullanıcının giriş yapmış olması gerekiyor
if (!isset($_SESSION['user_id'])) {
  header("Location: ../login.php");
  exit();
}

$user_id = $_SESSION['user_id'];

if (isset($_GET['id'])) {
  $project_id = $_GET['id'];

  try {
    // Önce proje resimlerini sil
    $stmt = $db->prepare("DELETE FROM project_images WHERE project_id = :project_id");
    $stmt->bindParam(':project_id', $project_id);
    $stmt->execute();

    // Sonra projeyi sil
    $stmt = $db->prepare("DELETE FROM projects WHERE id = :project_id AND user_id = :user_id");
    $stmt->bindParam(':project_id', $project_id);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();

    // Silme başarılı, projeler sayfasına yönlendir
    header("Location: projects");
    exit();
  } catch (PDOException $e) {
    $error = "Proje silinirken bir hata oluştu.";
  }
} else {
  $error = "Geçersiz proje ID.";
}

// Hata mesajını göster (eğer varsa)
if (isset($error)) {
  echo "Hata: " . $error;
}
?>