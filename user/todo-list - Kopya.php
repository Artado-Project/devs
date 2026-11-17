<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../includes/database.php';
require_once '../includes/auth.php';

// Kullanıcının giriş yapmış olması gerekiyor
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Session mesajlarını kontrol et
$message = $_SESSION['message'] ?? '';
$message_type = $_SESSION['message_type'] ?? '';
unset($_SESSION['message'], $_SESSION['message_type']);

// Todo ekleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $priority = $_POST['priority'] ?? 'medium';
    $due_date = $_POST['due_date'] ?? '';
    $category = $_POST['category'] ?? 'general';
    $tags = $_POST['tags'] ?? '';
    $progress = $_POST['progress'] ?? 0;
    
    if (empty($title)) {
        $_SESSION['message'] = 'Todo başlığı boş bırakılamaz!';
        $_SESSION['message_type'] = 'danger';
    } else {
        try {
            $stmt = $db->prepare("INSERT INTO todo_items (user_id, title, description, priority, due_date, category, tags, progress) 
                                VALUES (:user_id, :title, :description, :priority, :due_date, :category, :tags, :progress)");
            $stmt->execute([
                ':user_id' => $user_id,
                ':title' => $title,
                ':description' => $description,
                ':priority' => $priority,
                ':due_date' => $due_date ?: null,
                ':category' => $category,
                ':tags' => $tags,
                ':progress' => $progress
            ]);
            
            $_SESSION['message'] = 'Todo başarıyla eklendi!';
            $_SESSION['message_type'] = 'success';
        } catch (PDOException $e) {
            $_SESSION['message'] = 'Hata: ' . $e->getMessage();
            $_SESSION['message_type'] = 'danger';
        }
    }
    
    // POST-Redirect-GET pattern to prevent duplicate submissions
    header("Location: todo-list.php");
    exit();
}

// Todo güncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $todo_id = $_POST['todo_id'] ?? '';
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $status = $_POST['status'] ?? 'pending';
    $priority = $_POST['priority'] ?? 'medium';
    $due_date = $_POST['due_date'] ?? '';
    $category = $_POST['category'] ?? 'general';
    $tags = $_POST['tags'] ?? '';
    $progress = $_POST['progress'] ?? 0;
    
    if (empty($title) || empty($todo_id)) {
        $_SESSION['message'] = 'Todo başlığı ve ID boş bırakılamaz!';
        $_SESSION['message_type'] = 'danger';
    } else {
        try {
            $stmt = $db->prepare("UPDATE todo_items SET title = :title, description = :description, 
                                status = :status, priority = :priority, due_date = :due_date,
                                category = :category, tags = :tags, progress = :progress
                                WHERE id = :id AND user_id = :user_id");
            $stmt->execute([
                ':title' => $title,
                ':description' => $description,
                ':status' => $status,
                ':priority' => $priority,
                ':due_date' => $due_date ?: null,
                ':category' => $category,
                ':tags' => $tags,
                ':progress' => $progress,
                ':id' => $todo_id,
                ':user_id' => $user_id
            ]);
            
            $_SESSION['message'] = 'Todo başarıyla güncellendi!';
            $_SESSION['message_type'] = 'success';
        } catch (PDOException $e) {
            $_SESSION['message'] = 'Hata: ' . $e->getMessage();
            $_SESSION['message_type'] = 'danger';
        }
    }
    
    header("Location: todo-list.php");
    exit();
}

// Todo silme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $todo_id = $_POST['todo_id'] ?? '';
    
    if (empty($todo_id)) {
        $_SESSION['message'] = 'Todo ID boş bırakılamaz!';
        $_SESSION['message_type'] = 'danger';
    } else {
        try {
            $stmt = $db->prepare("DELETE FROM todo_items WHERE id = :id AND user_id = :user_id");
            $stmt->execute([
                ':id' => $todo_id,
                ':user_id' => $user_id
            ]);
            
            $_SESSION['message'] = 'Todo başarıyla silindi!';
            $_SESSION['message_type'] = 'success';
        } catch (PDOException $e) {
            $_SESSION['message'] = 'Hata: ' . $e->getMessage();
            $_SESSION['message_type'] = 'danger';
        }
    }
    
    header("Location: todo-list.php");
    exit();
}

// Todo'ları getir
try {
    $stmt = $db->prepare("SELECT * FROM todo_items WHERE user_id = :user_id ORDER BY 
                        CASE priority 
                            WHEN 'high' THEN 1 
                            WHEN 'medium' THEN 2 
                            WHEN 'low' THEN 3 
                        END, created_at DESC");
    $stmt->execute([':user_id' => $user_id]);
    $todos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $todos = [];
    $message = 'Todo\'lar yüklenirken hata oluştu: ' . $e->getMessage();
    $message_type = 'danger';
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yapılacaklar Listesi - Artado Developers</title>
    <link rel="shortcut icon" href="https://raw.githubusercontent.com/Artado-Project/devs/refs/heads/main/ArtadoDevs/images/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="assets/compiled/css/app.css">
    <link rel="stylesheet" href="assets/compiled/css/app-dark.css">
    <link rel="stylesheet" href="assets/compiled/css/iconly.css">
</head>
<body>
    <script src="assets/static/js/initTheme.js"></script>
    <div id="app">
        <div id="sidebar">
            <div class="sidebar-wrapper active">
                <div class="sidebar-header position-relative">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="logo">
                            <a href="index.php"><img src="https://raw.githubusercontent.com/Artado-Project/devs/refs/heads/main/ArtadoDevs/images/favicon.ico" alt="Logo"></a>
                        </div>
                        <div class="theme-toggle d-flex gap-2 align-items-center mt-2">
                            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" aria-hidden="true" role="img" class="iconify iconify--system-uicons" width="20" height="20" preserveAspectRatio="xMidYMid meet" viewBox="0 0 21 21">
                                <g fill="none" fill-rule="evenodd" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M10.5 14.5c2.219 0 4-1.763 4-3.982a4.003 4.003 0 0 0-4-4.018c-2.219 0-4 1.781-4 4c0 2.219 1.781 4 4 4zM4.136 4.136L5.55 5.55m9.9 9.9l1.414 1.414M1.5 10.5h2m14 0h2M4.135 16.863L5.55 15.45m9.899-9.9l1.414-1.415M10.5 19.5v-2m0-14v-2" opacity=".3"></path>
                                    <g transform="translate(-210 -1)">
                                        <path d="M220.5 2.5v2m6.5.5l-1.5 1.5"></path>
                                        <circle cx="220.5" cy="11.5" r="4"></circle>
                                        <path d="m214 5l1.5 1.5m5 14v-2m6.5-.5l-1.5-1.5M214 18l1.5-1.5m-4-5h2m14 0h2"></path>
                                    </g>
                                </g>
                            </svg>
                            <div class="form-check form-switch fs-6">
                                <input class="form-check-input me-0" type="checkbox" id="toggle-dark" style="cursor: pointer">
                                <label class="form-check-label"></label>
                            </div>
                            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" aria-hidden="true" role="img" class="iconify iconify--mdi" width="20" height="20" preserveAspectRatio="xMidYMid meet" viewBox="0 0 24 24">
                                <path fill="currentColor" d="m17.75 4.09l-2.53 1.94l.91 3.06l-2.63-1.81l-2.63 1.81l.91-3.06l-2.53-1.94L12.44 4l1.06-3l1.06 3l3.19.09m3.5 6.91l-1.64 1.25l.59 1.98l-1.7-1.17l-1.7 1.17l.59-1.98L15.75 11l2.06-.05L18.5 9l.69 1.95l2.06.05m-2.28 4.95c.83-.08 1.72 1.1 1.19 1.85c-.32.45-.66.87-1.08 1.27C15.17 23 8.84 23 4.94 19.07c-3.91-3.9-3.91-10.24 0-14.14c.4-.4.82-.76 1.27-1.08c.75-.53 1.93.36 1.85 1.19c-.27 2.86.69 5.83 2.89 8.02a9.96 9.96 0 0 0 8.02 2.89m-1.64 2.02a12.08 12.08 0 0 1-7.8-3.47c-2.17-2.19-3.33-5-3.49-7.82c-2.81 3.14-2.7 7.96.31 10.98c3.02 3.01 7.84 3.12 10.98.31Z"></path>
                            </svg>
                        </div>
                        <div class="sidebar-toggler x">
                            <a href="#" class="sidebar-hide d-xl-none d-block"><i class="bi bi-x bi-middle"></i></a>
                        </div>
                    </div>
                </div>

                <div class="sidebar-menu">
                    <ul class="menu">
                        <li class="sidebar-title">Konsol</li>

                        <li class="sidebar-item">
                            <a href="index.php" class='sidebar-link'>
                                <i class="bi bi-grid-fill"></i>
                                <span>Ana Sayfa</span>
                            </a>
                        </li>

                        <li class="sidebar-item">
                            <a href="../Workshop/" class='sidebar-link'>
                                <i class="bi bi-tools"></i>
                                <span>Workshop</span>
                            </a>
                        </li>

                        <li class="sidebar-item">
                            <a href="projects.php" class='sidebar-link'>
                                <i class="bi bi-stack"></i>
                                <span>Projelerim</span>
                            </a>
                        </li>

                        <li class="sidebar-item">
                            <a href="announcements.php" class='sidebar-link'>
                                <i class="bi bi-journal-check"></i>
                                <span>Duyurular</span>
                            </a>
                        </li>
                        <li class="sidebar-item has-sub">
                            <a href="#" class='sidebar-link'>
                                <i class="bi bi-collection-fill"></i>
                                <span>Sosyal Medya</span>
                            </a>
                            <ul class="submenu">
                                <li class="submenu-item">
                                    <a href="https://artadosearch.com" class="submenu-link">Artado Search</a>
                                </li>
                                <li class="submenu-item">
                                    <a href="https://forum.artado.xyz" class="submenu-link">Forum</a>
                                </li>
                                <li class="submenu-item">
                                    <a href="https://matrix.to/#/#artadoproject:matrix.org" class="submenu-link">Matrix</a>
                                </li>
                                <li class="submenu-item">
                                    <a href="https://x.com/ArtadoL" class="submenu-link">Twitter</a>
                                </li>
                            </ul>
                        </li>

                        <li class="sidebar-item has-sub">
                            <a href="#" class='sidebar-link'>
                                <i class="bi bi-grid-1x2-fill"></i>
                                <span>Destekte Bulunun</span>
                            </a>
                            <ul class="submenu">
                                <li class="submenu-item">
                                    <a href="https://kreosus.com/artadosoft?hl=tr" target="_blank" class="submenu-link">Kreosus</a>
                                </li>
                            </ul>
                        </li>

                        <li class="sidebar-title">Çalışma Panelim</li>

                        <li class="sidebar-item has-sub ">
                            <a href="#" class='sidebar-link'>
                                <i class="bi bi-hexagon-fill"></i>
                                <span>Proje Oluştur</span>
                            </a>
                            <ul class="submenu">
                                <li class="submenu-item">
                                    <a href="create-eklenti.php" class="submenu-link">Eklenti</a>
                                </li>
                                <li class="submenu-item">
                                    <a href="create-tema.php" class="submenu-link">Tema</a>
                                </li>
                                <li class="submenu-item">
                                    <a href="create-logo.php" class="submenu-link">Logo</a>
                                </li>
                                <li class="submenu-item">
                                    <a href="create-uyg-pc.php" class="submenu-link">PC Uygulaması</a>
                                </li>
                                <li class="submenu-item">
                                    <a href="create-uyg-mobil.php" class="submenu-link">Mobil Uygulama</a>
                                </li>
                                <li class="submenu-item">
                                    <a href="create-game-pc.php" class="submenu-link">PC Oyunu</a>
                                </li>
                                <li class="submenu-item">
                                    <a href="create-game-mobil.php" class="submenu-link">Mobil Oyun</a>
                                </li>
                            </ul>
                        </li>

                        <li class="sidebar-item active">
                            <a href="todo-list.php" class='sidebar-link'>
                                <i class="bi bi-file-earmark-medical-fill"></i>
                                <span>Yapılacaklar Listesi</span>
                            </a>
                        </li>

                        <li class="sidebar-item">
                            <a href="announcements.php" class='sidebar-link'>
                                <i class="bi bi-journal-check"></i>
                                <span>Duyurular</span>
                            </a>
                        </li>

                        <li class="sidebar-title">Ayarlar</li>

                        <li class="sidebar-item has-sub">
                            <a href="#" class='sidebar-link'>
                                <i class="bi bi-person-circle"></i>
                                <span>Hesap</span>
                            </a>
                            <ul class="submenu">
                                <li class="submenu-item">
                                    <a href="account-profile.php" class="submenu-link">Profil</a>
                                </li>
                                <li class="submenu-item">
                                    <a href="account-security.php" class="submenu-link">Güvenlik</a>
                                </li>
                            </ul>
                        </li>

                        <li class="sidebar-item">
                            <a href="auth-login.php?logout=true" class='sidebar-link'>
                                <i class="bi bi-box-arrow-right"></i>
                                <span>Çıkış Yap</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div id="main">
            <header class="mb-3">
                <a href="#" class="burger-btn d-block d-xl-none">
                    <i class="bi bi-justify fs-3"></i>
                </a>
            </header>

            <div class="page-heading">
                <div class="page-title">
                    <div class="row">
                        <div class="col-12 col-md-6 order-md-1 order-last">
                            <h3>Yapılacaklar Listesi</h3>
                            <p class="text-subtitle text-muted">Görevlerinizi yönetin ve organize edin.</p>
                        </div>
                        <div class="col-12 col-md-6 order-md-2 order-first">
                            <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="index.php">Ana Sayfa</a></li>
                                    <li class="breadcrumb-item active" aria-current="page">Yapılacaklar Listesi</li>
                                </ol>
                            </nav>
                        </div>
                    </div>
                </div>

                <section class="section">
                    <?php if ($message): ?>
                        <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
                            <?= $message ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-12 col-lg-4">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Yeni Todo Ekle</h4>
                                </div>
                                <div class="card-body">
                                    <form method="post">
                                        <input type="hidden" name="action" value="add">
                                        
                                        <div class="form-group mb-3">
                                            <label for="title" class="form-label">Başlık *</label>
                                            <input type="text" class="form-control" id="title" name="title" required>
                                        </div>

                                        <div class="form-group mb-3">
                                            <label for="description" class="form-label">Açıklama</label>
                                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                                        </div>

                                        <div class="form-group mb-3">
                                            <label for="category" class="form-label">Kategori</label>
                                            <select class="form-control" id="category" name="category">
                                                <option value="general">Genel</option>
                                                <option value="work">İş</option>
                                                <option value="personal">Kişisel</option>
                                                <option value="urgent">Acil</option>
                                                <option value="study">Çalışma</option>
                                                <option value="health">Sağlık</option>
                                                <option value="shopping">Alışveriş</option>
                                            </select>
                                        </div>

                                        <div class="form-group mb-3">
                                            <label for="tags" class="form-label">Etiketler (virgülle ayırın)</label>
                                            <input type="text" class="form-control" id="tags" name="tags" placeholder="örn: önemli, proje, tasarım">
                                        </div>

                                        <div class="form-group mb-3">
                                            <label for="progress" class="form-label">İlerleme (%)</label>
                                            <div class="d-flex align-items-center gap-2">
                                                <input type="range" class="form-range flex-grow-1" id="progress" name="progress" min="0" max="100" value="0" oninput="updateProgressLabel(this.value)">
                                                <span id="progressLabel" class="badge bg-primary">0%</span>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group mb-3">
                                                    <label for="priority" class="form-label">Öncelik</label>
                                                    <select class="form-control" id="priority" name="priority">
                                                        <option value="low">Düşük</option>
                                                        <option value="medium" selected>Orta</option>
                                                        <option value="high">Yüksek</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group mb-3">
                                                    <label for="due_date" class="form-label">Bitiş Tarihi</label>
                                                    <input type="datetime-local" class="form-control" id="due_date" name="due_date">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group mb-3">
                                                    <label for="estimated_hours" class="form-label">Tahmini Süre (saat)</label>
                                                    <input type="number" class="form-control" id="estimated_hours" name="estimated_hours" step="0.5" min="0" placeholder="2.5">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group mb-3">
                                                    <label for="actual_hours" class="form-label">Gerçekleşen Süre (saat)</label>
                                                    <input type="number" class="form-control" id="actual_hours" name="actual_hours" step="0.5" min="0" placeholder="1.5">
                                                </div>
                                            </div>
                                        </div>

                                        <button type="submit" class="btn btn-primary w-100">
                                            <i class="bi bi-plus-circle me-2"></i>Todo Ekle
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-lg-8">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h4 class="card-title">Todo Listem</h4>
                                    <span class="badge bg-info"><?= count($todos) ?> toplam</span>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($todos)): ?>
                                        <div class="text-center py-5">
                                            <i class="bi bi-clipboard-check fs-1 text-muted"></i>
                                            <p class="text-muted mt-3">Henüz todo eklenmemiş.</p>
                                        </div>
                                    <?php else: ?>
                                        <div class="todo-list">
                                            <?php foreach ($todos as $todo): ?>
                                                <div class="todo-item border rounded p-3 mb-3 <?= $todo['status'] === 'completed' ? 'bg-light' : '' ?>">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <div class="flex-grow-1">
                                                            <div class="d-flex align-items-center mb-2 flex-wrap gap-1">
                                                                <span class="badge bg-<?= 
                                                                    $todo['priority'] === 'high' ? 'danger' : 
                                                                    ($todo['priority'] === 'medium' ? 'warning' : 'info') 
                                                                ?>">
                                                                    <?= 
                                                                        $todo['priority'] === 'high' ? 'Yüksek' : 
                                                                        ($todo['priority'] === 'medium' ? 'Orta' : 'Düşük') 
                                                                    ?>
                                                                </span>
                                                                <span class="badge bg-<?= 
                                                                    $todo['status'] === 'completed' ? 'success' : 
                                                                    ($todo['status'] === 'in_progress' ? 'primary' : 'secondary') 
                                                                ?>">
                                                                    <?= 
                                                                        $todo['status'] === 'completed' ? 'Tamamlandı' : 
                                                                        ($todo['status'] === 'in_progress' ? 'Devam Ediyor' : 'Beklemede') 
                                                                    ?>
                                                                </span>
                                                                <span class="badge bg-info">
                                                                    <?= 
                                                                        $todo['category'] === 'work' ? 'İş' :
                                                                        ($todo['category'] === 'personal' ? 'Kişisel' :
                                                                        ($todo['category'] === 'urgent' ? 'Acil' :
                                                                        ($todo['category'] === 'study' ? 'Çalışma' :
                                                                        ($todo['category'] === 'health' ? 'Sağlık' :
                                                                        ($todo['category'] === 'shopping' ? 'Alışveriş' : 'Genel')))))
                                                                    ?>
                                                                </span>
                                                                <?php if ($todo['tags']): ?>
                                                                    <?php foreach (explode(',', $todo['tags']) as $tag): ?>
                                                                        <span class="badge bg-secondary"><?= htmlspecialchars(trim($tag)) ?></span>
                                                                    <?php endforeach; ?>
                                                                <?php endif; ?>
                                                            </div>
                                                            <h6 class="mb-1 <?= $todo['status'] === 'completed' ? 'text-decoration-line-through text-muted' : '' ?>">
                                                                <?= htmlspecialchars($todo['title']) ?>
                                                            </h6>
                                                            <?php if ($todo['description']): ?>
                                                                <p class="text-muted small mb-2"><?= htmlspecialchars($todo['description']) ?></p>
                                                            <?php endif; ?>
                                                            
                                                            <?php if ($todo['progress'] > 0): ?>
                                                                <div class="mb-2">
                                                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                                                        <small class="text-muted">İlerleme</small>
                                                                        <small class="text-muted"><?= $todo['progress'] ?>%</small>
                                                                    </div>
                                                                    <div class="progress" style="height: 6px;">
                                                                        <div class="progress-bar <?= $todo['progress'] === 100 ? 'bg-success' : ($todo['progress'] >= 50 ? 'bg-warning' : 'bg-info') ?>" 
                                                                             role="progressbar" style="width: <?= $todo['progress'] ?>%"></div>
                                                                    </div>
                                                                </div>
                                                            <?php endif; ?>
                                                            
                                                            <div class="row small text-muted">
                                                                <?php if ($todo['due_date']): ?>
                                                                    <div class="col-md-4 mb-2">
                                                                        <i class="bi bi-calendar me-1"></i>
                                                                        <?= date('d.m.Y H:i', strtotime($todo['due_date'])) ?>
                                                                    </div>
                                                                <?php endif; ?>
                                                                <?php if ($todo['estimated_hours']): ?>
                                                                    <div class="col-md-4 mb-2">
                                                                        <i class="bi bi-clock me-1"></i>
                                                                        Tahmini: <?= $todo['estimated_hours'] ?>s
                                                                    </div>
                                                                <?php endif; ?>
                                                                <?php if ($todo['actual_hours']): ?>
                                                                    <div class="col-md-4 mb-2">
                                                                        <i class="bi bi-stopwatch me-1"></i>
                                                                        Gerçekleşen: <?= $todo['actual_hours'] ?>s
                                                                    </div>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                        <div class="d-flex gap-2">
                                                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                                                    onclick="editTodo(<?= $todo['id'] ?>, '<?= htmlspecialchars($todo['title']) ?>', '<?= htmlspecialchars($todo['description'] ?? '') ?>', '<?= $todo['status'] ?>', '<?= $todo['priority'] ?>', '<?= $todo['due_date'] ?? '' ?>', '<?= $todo['category'] ?? 'general' ?>', '<?= htmlspecialchars($todo['tags'] ?? '') ?>', '<?= $todo['progress'] ?? 0 ?>', '<?= $todo['estimated_hours'] ?? '' ?>', '<?= $todo['actual_hours'] ?? '' ?>')">
                                                                <i class="bi bi-pencil"></i>
                                                            </button>
                                                            <form method="post" style="display: inline;">
                                                                <input type="hidden" name="action" value="delete">
                                                                <input type="hidden" name="todo_id" value="<?= $todo['id'] ?>">
                                                                <button type="submit" class="btn btn-sm btn-outline-danger" 
                                                                        onclick="return confirm('Bu todo\'yu silmek istediğinizden emin misiniz?')">
                                                                    <i class="bi bi-trash"></i>
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            <footer>
                <div class="footer clearfix mb-0 text-muted">
                    <div class="float-start">
                        <p>2025 &copy; Artado Software</p>
                    </div>
                    <div class="float-end">
                        <p>Sxinar Tarafından <span class="text-danger"><i class="bi bi-heart-fill icon-mid"></i></span>
                            ile <a href="https://sxi.is-a.dev">Sxinar</a></p>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Todo Düzenle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" id="edit_todo_id" name="todo_id">
                        
                        <div class="form-group mb-3">
                            <label for="edit_title" class="form-label">Başlık *</label>
                            <input type="text" class="form-control" id="edit_title" name="title" required>
                        </div>

                        <div class="form-group mb-3">
                            <label for="edit_description" class="form-label">Açıklama</label>
                            <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                        </div>

                        <div class="form-group mb-3">
                            <label for="edit_category" class="form-label">Kategori</label>
                            <select class="form-control" id="edit_category" name="category">
                                <option value="general">Genel</option>
                                <option value="work">İş</option>
                                <option value="personal">Kişisel</option>
                                <option value="urgent">Acil</option>
                                <option value="study">Çalışma</option>
                                <option value="health">Sağlık</option>
                                <option value="shopping">Alışveriş</option>
                            </select>
                        </div>

                        <div class="form-group mb-3">
                            <label for="edit_tags" class="form-label">Etiketler (virgülle ayırın)</label>
                            <input type="text" class="form-control" id="edit_tags" name="tags" placeholder="örn: önemli, proje, tasarım">
                        </div>

                        <div class="form-group mb-3">
                            <label for="edit_progress" class="form-label">İlerleme (%)</label>
                            <div class="d-flex align-items-center gap-2">
                                <input type="range" class="form-range flex-grow-1" id="edit_progress" name="progress" min="0" max="100" value="0" oninput="updateEditProgressLabel(this.value)">
                                <span id="editProgressLabel" class="badge bg-primary">0%</span>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="edit_status" class="form-label">Durum</label>
                                    <select class="form-control" id="edit_status" name="status">
                                        <option value="pending">Beklemede</option>
                                        <option value="in_progress">Devam Ediyor</option>
                                        <option value="completed">Tamamlandı</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="edit_priority" class="form-label">Öncelik</label>
                                    <select class="form-control" id="edit_priority" name="priority">
                                        <option value="low">Düşük</option>
                                        <option value="medium">Orta</option>
                                        <option value="high">Yüksek</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="edit_due_date" class="form-label">Bitiş Tarihi</label>
                                    <input type="datetime-local" class="form-control" id="edit_due_date" name="due_date">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="edit_estimated_hours" class="form-label">Tahmini Süre (saat)</label>
                                    <input type="number" class="form-control" id="edit_estimated_hours" name="estimated_hours" step="0.5" min="0">
                                </div>
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label for="edit_actual_hours" class="form-label">Gerçekleşen Süre (saat)</label>
                            <input type="number" class="form-control" id="edit_actual_hours" name="actual_hours" step="0.5" min="0">
                        </div>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label for="edit_due_date" class="form-label">Bitiş Tarihi</label>
                            <input type="datetime-local" class="form-control" id="edit_due_date" name="due_date">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" class="btn btn-primary">Güncelle</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="assets/static/js/components/dark.js"></script>
    <script src="assets/extensions/perfect-scrollbar/perfect-scrollbar.min.js"></script>
    <script src="assets/compiled/js/app.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function updateProgressLabel(value) {
            document.getElementById('progressLabel').textContent = value + '%';
        }
        
        function updateEditProgressLabel(value) {
            document.getElementById('editProgressLabel').textContent = value + '%';
        }
        
        function editTodo(id, title, description, status, priority, dueDate, category, tags, progress, estimatedHours, actualHours) {
            document.getElementById('edit_todo_id').value = id;
            document.getElementById('edit_title').value = title;
            document.getElementById('edit_description').value = description;
            document.getElementById('edit_status').value = status;
            document.getElementById('edit_priority').value = priority;
            document.getElementById('edit_due_date').value = dueDate;
            document.getElementById('edit_category').value = category || 'general';
            document.getElementById('edit_tags').value = tags || '';
            document.getElementById('edit_progress').value = progress || 0;
            document.getElementById('edit_estimated_hours').value = estimatedHours || '';
            document.getElementById('edit_actual_hours').value = actualHours || '';
            
            updateEditProgressLabel(progress || 0);
            
            new bootstrap.Modal(document.getElementById('editModal')).show();
        }
        
        // Form submit engelleme (çift gönderim önleme)
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function() {
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>İşleniyor...';
                }
            });
        });
    </script>
</body>
</html>
