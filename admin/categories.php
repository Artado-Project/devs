<?php
require_once '../includes/database.php';
require_once '../includes/auth.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Kategori ekleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $category_name = trim($_POST['category_name']);
    $category_slug = strtolower(str_replace(' ', '_', $category_name));
    
    if (!empty($category_name)) {
        try {
            $stmt = $db->prepare("INSERT INTO categories (name, slug) VALUES (:name, :slug)");
            $stmt->execute([':name' => $category_name, ':slug' => $category_slug]);
            $_SESSION['success'] = "Kategori başarıyla eklendi!";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Hata: " . $e->getMessage();
        }
    }
    header("Location: categories.php");
    exit();
}

// Kategori silme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $category_id = (int)$_POST['category_id'];
    
    try {
        $stmt = $db->prepare("DELETE FROM categories WHERE id = :id");
        $stmt->execute([':id' => $category_id]);
        $_SESSION['success'] = "Kategori başarıyla silindi!";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Hata: " . $e->getMessage();
    }
    header("Location: categories.php");
    exit();
}

// Kategorileri getir
try {
    $stmt = $db->query("SELECT * FROM categories ORDER BY name ASC");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $categories = [];
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kategori Yönetimi - Artado Admin</title>
    <link rel="stylesheet" href="../assets/compiled/css/app.css">
    <link rel="stylesheet" href="../assets/compiled/css/app-dark.css">
</head>
<body>
    <div class="d-flex">
        <?php include 'header.php'; ?>
        
        <div class="main-content w-100">
            <div class="container-fluid p-4">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h4 class="card-title">Kategori Yönetimi</h4>
                            </div>
                            <div class="card-body">
                                <!-- Kategori Ekleme Formu -->
                                <div class="mb-4">
                                    <h5 class="mb-3">Yeni Kategori Ekle</h5>
                                    <form method="POST" class="d-flex gap-2">
                                        <input type="hidden" name="action" value="add">
                                        <input type="text" name="category_name" class="form-control" placeholder="Kategori adı" required>
                                        <button type="submit" class="btn btn-primary">Ekle</button>
                                    </form>
                                </div>
                                
                                <!-- Kategori Listesi -->
                                <div>
                                    <h5 class="mb-3">Mevcut Kategoriler</h5>
                                    <?php if (empty($categories)): ?>
                                        <div class="alert alert-info">Henüz kategori eklenmemiş.</div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>ID</th>
                                                        <th>Kategori Adı</th>
                                                        <th>Slug</th>
                                                        <th>İşlemler</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($categories as $category): ?>
                                                        <tr>
                                                            <td><?= $category['id'] ?></td>
                                                            <td><?= htmlspecialchars($category['name']) ?></td>
                                                            <td><?= htmlspecialchars($category['slug']) ?></td>
                                                            <td>
                                                                <form method="POST" style="display: inline;">
                                                                    <input type="hidden" name="action" value="delete">
                                                                    <input type="hidden" name="category_id" value="<?= $category['id'] ?>">
                                                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Bu kategoriyi silmek istediğinize emin misiniz?')">
                                                                        <i class="fas fa-trash"></i> Sil
                                                                    </button>
                                                                </form>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/static/js/components/dark-light.js"></script>
    <script src="../assets/static/js/initTheme.js"></script>
</body>
</html>
