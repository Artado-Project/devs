<?php
require_once '../includes/database.php';
require_once '../includes/auth.php';
require_once '../includes/project_approval_email.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

/* =========================
   POST → UPDATE İŞLEMİ
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_POST['project_id'])) {
        header("Location: projects.php");
        exit();
    }

    $project_id  = (int)$_POST['project_id'];
    $title       = trim($_POST['title']);
    $category    = trim($_POST['category']);
    $description = trim($_POST['description']);
    $features    = trim($_POST['features']);
    $is_private  = isset($_POST['is_private']) ? 1 : 0;

    try {
        // Proje kullanıcıya ait mi?
        $stmt = $db->prepare("SELECT user_id, is_private FROM projects WHERE id = :id");
        $stmt->execute(['id' => $project_id]);
        $project = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$project || $project['user_id'] != $_SESSION['user_id']) {
            throw new Exception("Bu projeyi güncelleme yetkiniz yok.");
        }

        // Gizlilik değişti mi kontrol et
        $is_private_changed = ($project['is_private'] != $is_private);

        // Proje güncelle
        $stmt = $db->prepare("
            UPDATE projects SET
                title = :title,
                category = :category,
                description = :description,
                features = :features,
                is_private = :is_private,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id AND user_id = :user_id
        ");
        $stmt->execute([
            'title'       => $title,
            'category'    => $category,
            'description' => $description,
            'features'    => $features,
            'is_private'  => $is_private,
            'id'          => $project_id,
            'user_id'     => $_SESSION['user_id']
        ]);

        /* =========================
           RESİM YÜKLEME
        ========================= */
        if (!empty($_FILES['image']['name'])) {
            $allowed = ['image/jpeg','image/png','image/gif','image/webp'];

            if (!in_array($_FILES['image']['type'], $allowed)) {
                throw new Exception("Geçersiz resim formatı.");
            }

            if ($_FILES['image']['size'] > 5 * 1024 * 1024) {
                throw new Exception("Resim 5MB'dan büyük olamaz.");
            }

            $dir = '../public/uploads/img/';
            if (!is_dir($dir)) mkdir($dir, 0777, true);

            $name = time().'_'.preg_replace('/[^a-zA-Z0-9._-]/', '_', $_FILES['image']['name']);
            $serverPath = $dir . $name;
            $dbPath = 'public/uploads/img/' . $name;

            move_uploaded_file($_FILES['image']['tmp_name'], $serverPath);

            // Eski resmi sil
            $stmt = $db->prepare("SELECT image_path FROM project_images WHERE project_id = :id");
            $stmt->execute(['id' => $project_id]);
            $old = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($old && file_exists('../' . $old['image_path'])) {
                unlink('../' . $old['image_path']);
            }

            $stmt = $db->prepare("
                INSERT INTO project_images (project_id, image_path)
                VALUES (:id, :path)
                ON DUPLICATE KEY UPDATE image_path = :path
            ");
            $stmt->execute([
                'id'   => $project_id,
                'path' => $dbPath
            ]);
        }

        /* =========================
           DOSYA YÜKLEME
        ========================= */
        if (!empty($_FILES['file']['name'])) {
            $allowed = [
                'application/zip',
                'application/x-rar-compressed',
                'application/x-7z-compressed'
            ];

            if (!in_array($_FILES['file']['type'], $allowed)) {
                throw new Exception("Geçersiz dosya formatı.");
            }

            if ($_FILES['file']['size'] > 50 * 1024 * 1024) {
                throw new Exception("Dosya 50MB'dan büyük olamaz.");
            }

            $dir = '../public/uploads/files/';
            if (!is_dir($dir)) mkdir($dir, 0777, true);

            $name = time().'_'.preg_replace('/[^a-zA-Z0-9._-]/', '_', $_FILES['file']['name']);
            $serverPath = $dir . $name;
            $dbPath = 'public/uploads/files/' . $name;

            move_uploaded_file($_FILES['file']['tmp_name'], $serverPath);

            $stmt = $db->prepare("
                UPDATE projects SET file_path = :path WHERE id = :id
            ");
            $stmt->execute([
                'path' => $dbPath,
                'id'   => $project_id
            ]);
        }

        // 🔥 BAŞARILI → PROJECTS
        $_SESSION['success'] = "Proje başarıyla güncellendi.";
        
        // Eğer gizlilik değiştiyse onay sistemine gönder
        if ($is_private_changed) {
            $username = $_SESSION['username'] ?? 'Kullanıcı';
            sendProjectApprovalEmail($project_id, $title, $category, $username);
            $_SESSION['success'] .= " Gizlilik değişikliği admin onayına gönderildi.";
        }
        
        header("Location: projects.php?updated=1");
        exit();

    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header("Location: update_project.php?id=" . $project_id);
        exit();
    }
}

/* =========================
   GET → YETKİSİZ ERİŞİM
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'GET' && !isset($_GET['id'])) {
    header("Location: projects.php");
    exit();
}

// Proje bilgilerini çek
$project_id = (int)$_GET['id'];
$stmt = $db->prepare("SELECT * FROM projects WHERE id = :id AND user_id = :user_id");
$stmt->execute([':id' => $project_id, ':user_id' => $_SESSION['user_id']]);
$project = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$project) {
    header("Location: projects.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proje Güncelle - Artado Developers</title>
    <link rel="stylesheet" href="assets/compiled/css/app.css">
    <link rel="stylesheet" href="assets/compiled/css/app-dark.css">
</head>
<body>
    <div id="app">
        <div id="sidebar">
            <div class="sidebar-wrapper active">
                <div class="sidebar-header position-relative">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="logo">
                            <a href="index.php"><img src="../public/logo.png" alt="Logo"></a>
                        </div>
                    </div>
                </div>
                <div class="sidebar-menu">
                    <ul class="menu">
                        <li class="sidebar-item">
                            <a href="index.php" class='sidebar-link'>
                                <i class="bi bi-grid-fill"></i>
                                <span>Ana Sayfa</span>
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a href="projects.php" class='sidebar-link'>
                                <i class="bi bi-stack"></i>
                                <span>Projelerim</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div id="main">
            <header class="mb-4">
                <nav class="navbar navbar-expand-lg navbar-light">
                    <div class="container-fluid">
                        <h4 class="page-title mb-0">Proje Güncelle</h4>
                    </div>
                </nav>
            </header>

            <div class="main-content">
                <div class="container-fluid">
                    <div class="row justify-content-center">
                        <div class="col-12 col-lg-8">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Proje Bilgilerini Güncelle</h5>
                                </div>
                                <div class="card-body">
                                    <?php if (isset($_SESSION['error'])): ?>
                                        <div class="alert alert-danger">
                                            <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (isset($_SESSION['success'])): ?>
                                        <div class="alert alert-success">
                                            <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                                        </div>
                                    <?php endif; ?>

                                    <form method="POST" enctype="multipart/form-data">
                                        <input type="hidden" name="project_id" value="<?= $project['id'] ?>">
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Proje Başlığı</label>
                                            <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($project['title']) ?>" required>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Kategori</label>
                                            <select name="category" class="form-control" required>
                                                <option value="artado_eklenti" <?= $project['category'] == 'artado_eklenti' ? 'selected' : '' ?>>Artado Eklenti</option>
                                                <option value="artado_tema" <?= $project['category'] == 'artado_tema' ? 'selected' : '' ?>>Artado Tema</option>
                                                <option value="pc_uygulama" <?= $project['category'] == 'pc_uygulama' ? 'selected' : '' ?>>PC Uygulaması</option>
                                                <option value="mobil_uygulama" <?= $project['category'] == 'mobil_uygulama' ? 'selected' : '' ?>>Mobil Uygulaması</option>
                                                <option value="pc_oyun" <?= $project['category'] == 'pc_oyun' ? 'selected' : '' ?>>PC Oyunu</option>
                                                <option value="mobil_oyun" <?= $project['category'] == 'mobil_oyun' ? 'selected' : '' ?>>Mobil Oyunu</option>
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Açıklama</label>
                                            <textarea name="description" class="form-control" rows="4" required><?= htmlspecialchars($project['description']) ?></textarea>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Özellikler</label>
                                            <textarea name="features" class="form-control" rows="3"><?= htmlspecialchars($project['features']) ?></textarea>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Dosya</label>
                                            <input type="file" name="file" class="form-control">
                                            <small class="text-muted">Mevcut dosya: <?= htmlspecialchars($project['file_path']) ?></small>
                                        </div>

                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input type="checkbox" name="is_private" class="form-check-input" id="is_private" <?= $project['is_private'] ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="is_private">
                                                    <i class="fas fa-lock"></i> Projeyi Gizli Yap
                                                </label>
                                                <small class="d-block text-muted">Gizli projeler sadece admin onayıyla görünür hale gelir.</small>
                                            </div>
                                        </div>

                                        <div class="d-flex gap-2">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save"></i> Güncelle
                                            </button>
                                            <a href="projects.php" class="btn btn-secondary">
                                                <i class="fas fa-arrow-left"></i> Geri Dön
                                            </a>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
