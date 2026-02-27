<?php
require_once '../includes/database.php';
require_once '../includes/auth.php';

// Kullanıcının giriş yapmış olması gerekiyor
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $project_id = $_POST['project_id'];
    $title = $_POST['title'];
    $category = $_POST['category'];
    $description = $_POST['description'];
    $features = $_POST['features'];
    
    try {
        // Projenin bu kullanıcıya ait olduğunu kontrol et
        $stmt = $db->prepare("SELECT user_id FROM projects WHERE id = :project_id");
        $stmt->bindParam(':project_id', $project_id);
        $stmt->execute();
        $project = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($project && $project['user_id'] == $_SESSION['user_id']) {
            // Proje bilgilerini güncelle
            $stmt = $db->prepare("
                UPDATE projects 
                SET title = :title, 
                    category = :category, 
                    description = :description, 
                    features = :features,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :project_id AND user_id = :user_id
            ");
            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':category', $category);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':features', $features);
            $stmt->bindParam(':project_id', $project_id);
            $stmt->bindParam(':user_id', $_SESSION['user_id']);
            $stmt->execute();
            
            // Resim yükleme işlemi
            if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                $image = $_FILES['image'];
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $max_size = 5 * 1024 * 1024; // 5MB
                
                if (!in_array($image['type'], $allowed_types)) {
                    throw new Exception("Sadece JPEG, PNG, GIF ve WebP formatları kabul edilir.");
                }
                
                if ($image['size'] > $max_size) {
                    throw new Exception("Resim boyutu 5MB'dan küçük olmalıdır.");
                }
                
                $image_name = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $image['name']);
                $target_dir = '../public/uploads/img/';
                $image_path_server = $target_dir . $image_name;
                $image_path_db = 'public/uploads/img/' . $image_name;
                
                // Uploads klasörü yoksa oluştur ve izinleri ayarla
                if (!file_exists($target_dir)) {
                    mkdir($target_dir, 0777, true);
                    chmod($target_dir, 0777);
                }
                
                // Mevcut izinleri kontrol et ve ayarla
                if (is_dir($target_dir)) {
                    chmod($target_dir, 0777);
                }
                
                // Resmi yükle
                if (move_uploaded_file($image['tmp_name'], $image_path_server)) {
                    // Yüklenen dosyanın izinlerini ayarla
                    chmod($image_path_server, 0644);
                    
                    // Eski resmi sil
                    $stmt = $db->prepare("SELECT image_path FROM project_images WHERE project_id = :project_id");
                    $stmt->bindParam(':project_id', $project_id);
                    $stmt->execute();
                    $old_image = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($old_image && file_exists('../' . $old_image['image_path'])) {
                        unlink('../' . $old_image['image_path']);
                    }
                    
                    // Yeni resim yolunu veritabanına kaydet
                    $stmt = $db->prepare("
                        INSERT INTO project_images (project_id, image_path) 
                        VALUES (:project_id, :image_path)
                        ON DUPLICATE KEY UPDATE image_path = :image_path
                    ");
                    $stmt->bindParam(':project_id', $project_id);
                    $stmt->bindParam(':image_path', $image_path_db);
                    $stmt->execute();
                } else {
                    throw new Exception("Resim yüklenirken hata oluştu.");
                }
            }
            
            // Dosya yükleme işlemi
            if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
                $file = $_FILES['file'];
                $allowed_types = ['application/zip', 'application/x-rar-compressed', 'application/x-7z-compressed'];
                $max_size = 50 * 1024 * 1024; // 50MB
                
                if (!in_array($file['type'], $allowed_types)) {
                    throw new Exception("Sadece ZIP, RAR ve 7Z formatları kabul edilir.");
                }
                
                if ($file['size'] > $max_size) {
                    throw new Exception("Dosya boyutu 50MB'dan küçük olmalıdır.");
                }
                
                $file_name = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file['name']);
                $target_dir = '../public/uploads/files/';
                $file_path_server = $target_dir . $file_name;
                $file_path_db = 'public/uploads/files/' . $file_name;
                
                // Uploads klasörü yoksa oluştur ve izinleri ayarla
                if (!file_exists($target_dir)) {
                    mkdir($target_dir, 0777, true);
                    chmod($target_dir, 0777);
                }
                
                // Mevcut izinleri kontrol et ve ayarla
                if (is_dir($target_dir)) {
                    chmod($target_dir, 0777);
                }
                
                // Dosyayı yükle
                if (move_uploaded_file($file['tmp_name'], $file_path_server)) {
                    // Yüklenen dosyanın izinlerini ayarla
                    chmod($file_path_server, 0644);
                    
                    // Eski dosyayı sil
                    $stmt = $db->prepare("SELECT file_path FROM projects WHERE id = :project_id");
                    $stmt->bindParam(':project_id', $project_id);
                    $stmt->execute();
                    $old_file = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($old_file && $old_file['file_path'] && file_exists('../' . $old_file['file_path'])) {
                        unlink('../' . $old_file['file_path']);
                    }
                    
                    // Yeni dosya yolunu veritabanına kaydet
                    $stmt = $db->prepare("UPDATE projects SET file_path = :file_path WHERE id = :project_id");
                    $stmt->bindParam(':file_path', $file_path_db);
                    $stmt->bindParam(':project_id', $project_id);
                    $stmt->execute();
                } else {
                    throw new Exception("Dosya yüklenirken hata oluştu.");
                }
            }
            
            $_SESSION['success'] = "Proje başarıyla güncellendi!";
            header("Location: account.php");
            exit();
        } else {
            throw new Exception("Bu projeyi güncelleme yetkiniz yok.");
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Hata: " . $e->getMessage();
        header("Location: update_project.php?id=" . $project_id);
        exit();
    }
}

// Proje bilgilerini getir
if (isset($_GET['id'])) {
    $project_id = $_GET['id'];
    $stmt = $db->prepare("
        SELECT p.*, pi.image_path 
        FROM projects p 
        LEFT JOIN project_images pi ON p.id = pi.project_id 
        WHERE p.id = :project_id AND p.user_id = :user_id
    ");
    $stmt->bindParam(':project_id', $project_id);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    $project = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$project) {
        $_SESSION['error'] = "Proje bulunamadı veya erişim yetkiniz yok.";
        header("Location: account.php");
        exit();
    }
} else {
    $_SESSION['error'] = "Proje ID belirtilmedi.";
    header("Location: account.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proje Düzenle - Artado Devs</title>
    <link rel="stylesheet" crossorigin href="user/assets/compiled/css/app.css">
    <link rel="stylesheet" crossorigin href="user/assets/compiled/css/app-dark.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Proje Düzenle</h3>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger">
                                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="post" enctype="multipart/form-data">
                            <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                            
                            <div class="mb-3">
                                <label for="title" class="form-label">Proje Başlığı</label>
                                <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($project['title']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="category" class="form-label">Kategori</label>
                                <select class="form-control" id="category" name="category" required>
                                    <option value="web" <?php echo $project['category'] == 'web' ? 'selected' : ''; ?>>Web</option>
                                    <option value="mobil" <?php echo $project['category'] == 'mobil' ? 'selected' : ''; ?>>Mobil</option>
                                    <option value="desktop" <?php echo $project['category'] == 'desktop' ? 'selected' : ''; ?>>Desktop</option>
                                    <option value="game" <?php echo $project['category'] == 'game' ? 'selected' : ''; ?>>Oyun</option>
                                    <option value="other" <?php echo $project['category'] == 'other' ? 'selected' : ''; ?>>Diğer</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Açıklama</label>
                                <textarea class="form-control" id="description" name="description" rows="4" required><?php echo htmlspecialchars($project['description']); ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="features" class="form-label">Özellikler</label>
                                <textarea class="form-control" id="features" name="features" rows="3"><?php echo htmlspecialchars($project['features']); ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="image" class="form-label">Proje Resmi</label>
                                <input type="file" class="form-control" id="image" name="image" accept="image/*">
                                <?php if ($project['image_path']): ?>
                                    <div class="mt-2">
                                        <img src="../<?php echo $project['image_path']; ?>" alt="Proje Resmi" style="max-width: 200px; height: auto;">
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="mb-3">
                                <label for="file" class="form-label">Proje Dosyası</label>
                                <input type="file" class="form-control" id="file" name="file" accept=".zip,.rar,.7z">
                                <?php if ($project['file_path']): ?>
                                    <div class="mt-2">
                                        <small class="text-muted">Mevcut dosya: <?php echo basename($project['file_path']); ?></small>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <a href="account.php" class="btn btn-secondary">İptal</a>
                                <button type="submit" class="btn btn-primary">Güncelle</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
