<?php
require_once '../includes/database.php';
require_once '../includes/auth.php';

// Kullanıcının giriş yapmış olması gerekiyor
if (!isset($_SESSION['user_id'])) {
  header("Location: ../login");
  exit();
}

// Admin kontrolü
if ($_SESSION['role'] !== 'admin') {
  header("Location: ../user/index.php");
  exit();
}

if (isset($_GET['id'])) {
  $project_id = $_GET['id'];

  try {
    // Önce proje resimlerini sil
    $stmt = $db->prepare("DELETE FROM project_images WHERE project_id = :project_id");
    $stmt->bindParam(':project_id', $project_id);
    $stmt->execute();

    // Sonra projeyi sil (admin tüm projeleri silebilir)
    $stmt = $db->prepare("DELETE FROM projects WHERE id = :project_id");
    $stmt->bindParam(':project_id', $project_id);
    $stmt->execute();

    // Silme başarılı, projeler sayfasına yönlendir
    header("Location: projects.php");
    exit();
  } catch (PDOException $e) {
    $error = "Proje silinirken bir hata oluştu: " . $e->getMessage();
  }
} else {
  $error = "Geçersiz proje ID.";
}

// Hata mesajını göster (eğer varsa)
if (isset($error)) {
  echo "Hata: " . $error;
}
?>