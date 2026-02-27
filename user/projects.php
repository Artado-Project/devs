<?php
require_once '../includes/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Varsayılan PROJE resmi (internetten)
$profile_photo = get_user_avatar($project['profile_photo'] ?? null, true);

// Kullanıcının giriş yapmış olması gerekiyor
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Kategori filtresi
$category_filter = '';
if (isset($_GET['category'])) {
    $category = $_GET['category'];
    $category_filter = "AND p.category = :category";
}

// Kullanıcının projeleri ve kullanıcı bilgilerini çek
$stmt = $db->prepare("
    SELECT p.*, u.username, u.profile_photo, pi.image_path
    FROM projects p
    LEFT JOIN users u ON p.user_id = u.id
    LEFT JOIN project_images pi ON p.id = pi.project_id
    WHERE p.user_id = :user_id
    $category_filter
    ORDER BY p.upload_date DESC
");
$stmt->bindParam(':user_id', $user_id);
if (isset($_GET['category'])) {
    $stmt->bindParam(':category', $category);
}
$stmt->execute();
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Kategori adlarını Türkçe'ye çeviren fonksiyon
function getCategoryName($category) {
    $categories = [
        'pc_oyun' => 'PC Oyunu',
        'mobil_uygulama' => 'Mobil Uygulama',
        'pc_uygulama' => 'PC Uygulaması',
        'artado_eklenti' => 'Artado Eklentisi',
        'artado_tema' => 'Artado Teması',
        'artado_logo' => 'Artado Logo',
        'ana_sayfa' => 'Ana Sayfa Teması'
    ];
    return isset($categories[$category]) ? $categories[$category] : $category;
}
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Projelerim - Artado Developers</title>
    <link rel="shortcut icon" href="https://raw.githubusercontent.com/Artado-Project/devs/refs/heads/main/ArtadoDevs/images/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="https://raw.githubusercontent.com/Artado-Project/devs/refs/heads/main/ArtadoDevs/images/favicon.ico" type="image/png">
    <link rel="stylesheet" crossorigin href="./assets/compiled/css/app.css">
    <link rel="stylesheet" crossorigin href="./assets/compiled/css/app-dark.css">
    <link rel="stylesheet" crossorigin href="./assets/compiled/css/iconly.css">
    <style>
        .modal-header-primary {
            background-color: #435ebe;
            color: white;
        }
        .custom-file-button {
            position: relative;
            overflow: hidden;
        }
        .custom-file-button input[type=file] {
            position: absolute;
            top: 0;
            right: 0;
            min-width: 100%;
            min-height: 100%;
            font-size: 100px;
            text-align: right;
            filter: alpha(opacity=0);
            opacity: 0;
            outline: none;
            background: white;
            cursor: pointer;
            display: block;
        }
        /* LOGO KÜÇÜLTME VE DÜZENLEME CSS */
        .project-img-wrapper {
            width: 100%;
            height: 180px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: rgba(0,0,0,0.02);
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #f1f1f1;
        }
        .project-img-wrapper img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            padding: 5px;
        }
        #current_image img {
            max-height: 120px;
            width: auto;
            object-fit: contain;
        }
    </style>
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
                        <div class="theme-toggle d-flex gap-2  align-items-center mt-2">
                            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
                                aria-hidden="true" role="img" class="iconify iconify--system-uicons" width="20"
                                height="20" preserveAspectRatio="xMidYMid meet" viewBox="0 0 21 21">
                                <g fill="none" fill-rule="evenodd" stroke="currentColor" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <path
                                        d="M10.5 14.5c2.219 0 4-1.763 4-3.982a4.003 4.003 0 0 0-4-4.018c-2.219 0-4 1.781-4 4c0 2.219 1.781 4 4 4zM4.136 4.136L5.55 5.55m9.9 9.9l1.414 1.414M1.5 10.5h2m14 0h2M4.135 16.863L5.55 15.45m9.899-9.9l1.414-1.415M10.5 19.5v-2m0-14v-2"
                                        opacity=".3"></path>
                                    <g transform="translate(-210 -1)">
                                        <path d="M220.5 2.5v2m6.5.5l-1.5 1.5"></path>
                                        <circle cx="220.5" cy="11.5" r="4"></circle>
                                        <path d="m214 5l1.5 1.5m5 14v-2m6.5-.5l-1.5-1.5M214 18l1.5-1.5m-4-5h2m14 0h2">
                                        </path>
                                    </g>
                                </g>
                            </svg>
                            <div class="form-check form-switch fs-6">
                                <input class="form-check-input  me-0" type="checkbox" id="toggle-dark"
                                    style="cursor: pointer">
                                <label class="form-check-label"></label>
                            </div>
                            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
                                aria-hidden="true" role="img" class="iconify iconify--mdi" width="20" height="20"
                                preserveAspectRatio="xMidYMid meet" viewBox="0 0 24 24">
                                <path fill="currentColor"
                                    d="m17.75 4.09l-2.53 1.94l.91 3.06l-2.63-1.81l-2.63 1.81l.91-3.06l-2.53-1.94L12.44 4l1.06-3l1.06 3l3.19.09m3.5 6.91l-1.64 1.25l.59 1.98l-1.7-1.17l-1.7 1.17l.59-1.98L15.75 11l2.06-.05L18.5 9l.69 1.95l2.06.05m-2.28 4.95c.83-.08 1.72 1.1 1.19 1.85c-.32.45-.66.87-1.08 1.27C15.17 23 8.84 23 4.94 19.07c-3.91-3.9-3.91-10.24 0-14.14c.4-.4.82-.76 1.27-1.08c.75-.53 1.93.36 1.85 1.19c-.27 2.86.69 5.83 2.89 8.02a9.96 9.96 0 0 0 8.02 2.89m-1.64 2.02a12.08 12.08 0 0 1-7.8-3.47c-2.17-2.19-3.33-5-3.49-7.82c-2.81 3.14-2.7 7.96.31 10.98c3.02 3.01 7.84 3.12 10.98.31Z">
                                </path>
                            </svg>
                        </div>
                        <div class="sidebar-toggler  x">
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

                        <li class="sidebar-item active">
                            <a href="projects.php" class='sidebar-link'>
                                <i class="bi bi-stack"></i>
                                <span>Projelerim</span>
                            </a>
                        </li>

                        <li class="sidebar-item has-sub">
                            <a href="#" class='sidebar-link'>
                                <i class="bi bi-funnel-fill"></i>
                                <span>Kategoriler</span>
                            </a>
                            <ul class="submenu">
                                <li class="submenu-item">
                                    <a href="projects.php?category=artado_eklenti" class="submenu-link">Artado Eklentileri</a>
                                </li>
                                <li class="submenu-item">
                                    <a href="projects.php?category=artado_tema" class="submenu-link">Artado Temaları</a>
                                </li>
                                <li class="submenu-item">
                                    <a href="projects.php?category=ana_sayfa" class="submenu-link">Ana Sayfa Temaları</a>
                                </li>
                                <li class="submenu-item">
                                    <a href="projects.php?category=pc_uygulama" class="submenu-link">PC Uygulamaları</a>
                                </li>
                                <li class="submenu-item">
                                    <a href="projects.php?category=mobil_uygulama" class="submenu-link">Mobil Uygulamalar</a>
                                </li>
                                <li class="submenu-item">
                                    <a href="projects.php?category=pc_oyun" class="submenu-link">PC Oyunları</a>
                                </li>
                                <li class="submenu-item">
                                    <a href="projects.php?category=artado_logo" class="submenu-link">Artado Logolar</a>
                                </li>
                            </ul>
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

                        <li class="sidebar-item has-sub">
                            <a href="#" class='sidebar-link'>
                                <i class="bi bi-hexagon-fill"></i>
                                <span>Proje Oluştur</span>
                            </a>
                            <ul class="submenu">
                                <li class="submenu-item">
                                    <a href="create-eklenti.php" class="submenu-link">Eklenti</a>
                                </li>
                                <li class="submenu-item">
                                    <a href="create-logo.php" class="submenu-link">Logo</a>
                                </li>
                                <li class="submenu-item">
                                    <a href="create-tema.php" class="submenu-link">Tema</a>
                                </li>
                                <li class="submenu-item">
                                    <a href="create-uyg-mobil.php" class="submenu-link">Mobil Uygulama</a>
                                </li>
                                <li class="submenu-item">
                                    <a href="create-uyg-pc.php" class="submenu-link">PC Uygulama</a>
                                </li>
                                <li class="submenu-item">
                                    <a href="create-game-pc.php" class="submenu-link">PC Oyun</a>
                                </li>
                                <li class="submenu-item">
                                    <a href="create-game-mobil.php" class="submenu-link">Mobil Oyun</a>
                                </li>
                            </ul>
                        </li>

                        <li class="sidebar-item">
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
                            <h3>Projelerim</h3>
                            <p class="text-subtitle text-muted">Projelerinizi buradan yönetebilirsiniz.</p>
                        </div>
                        
                        <div class="col-12 col-md-6 order-md-2 order-first">
                            <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="index.php">Ana Sayfa</a></li>
                                    <li class="breadcrumb-item active" aria-current="page">Projelerim</li>
                                </ol>
                            </nav>
                        </div>
                    </div>
                </div>

                <section class="section">
                    <?php if (count($projects) > 0): ?>
                        <?php foreach ($projects as $project): 
                            $profile_photo = get_user_avatar($project['profile_photo'] ?? null, true);
                            
                            $db_img_path = $project['image_path'] ?? null;
                            if ($db_img_path) {
                                $project_image_path = '../' . preg_replace('/^(\.\.\/|\.\/)/', '', $db_img_path);
                            } else {
                                $project_image_path = null;
                            }
                            $project_image_exists = $project_image_path && file_exists($project_image_path);
                        ?>
                        <div class="row">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar avatar-lg me-3">
                                                <img src="<?php echo htmlspecialchars($profile_photo); ?>" alt="User Avatar">
                                            </div>
                                            <div class="flex-grow-1">
                                                <h4 class="mb-0"><?php echo htmlspecialchars($project['username']); ?></h4>
                                                <small class="text-muted"><?php echo date('d-m-Y H:i', strtotime($project['upload_date'])); ?></small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-content">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-3">
                                                    <div class="project-img-wrapper">
                                                        <?php if ($project_image_exists): ?>
                                                            <img src="<?php echo htmlspecialchars($project_image_path); ?>" 
                                                                 alt="<?php echo htmlspecialchars($project['title']); ?>">
                                                        <?php else: ?>
                                                            <img src="<?php echo htmlspecialchars($profile_photo); ?>" 
                                                                 alt="Profil Fotoğrafı">
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <div class="col-md-9">
                                                    <div class="d-flex align-items-center mb-2">
                                                        <h4 class="card-title mb-0"><?php echo htmlspecialchars($project['title']); ?></h4>
                                                        <span class="badge bg-primary ms-2">
                                                            <?php echo getCategoryName($project['category']); ?>
                                                        </span>
                                                    </div>
                                                    <p class="card-text">
                                                        <?php echo nl2br(htmlspecialchars($project['description'])); ?>
                                                    </p>
                                                    <?php if (!empty($project['features'])): ?>
                                                        <p class="mt-3">
                                                            <strong>Özellikler:</strong><br>
                                                            <?php echo nl2br(htmlspecialchars($project['features'])); ?>
                                                        </p>
                                                    <?php endif; ?>
                                                    <div class="mt-4">
                                                        <button type="button" 
                                                           class="btn btn-primary me-2"
                                                           onclick="openEditModal(<?php echo htmlspecialchars(json_encode($project)); ?>)">Düzenle</button>
                                                        <a href="delete_project.php?id=<?php echo $project['id']; ?>" 
                                                           class="btn btn-danger" 
                                                           onclick="return confirm('Bu projeyi silmek istediğinizden emin misiniz?')">Sil</a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="row">
                            <div class="col-12">
                                <div class="alert alert-info">
                                    Henüz hiç projeniz bulunmuyor. Yeni bir proje eklemek için "Proje Oluştur" menüsünü kullanabilirsiniz.
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </section>
            </div>

            <footer>
                <div class="footer clearfix mb-0 text-muted">
                    <div class="float-start">
                        <p>2025 &copy; Artado Software</p>
                        <div class="categories mt-2">
                            <small>Kategoriler: 
                                <a href="projects.php?category=artado_eklenti">Artado Eklentileri</a> | 
                                <a href="projects.php?category=artado_tema">Artado Temaları</a> | 
                                <a href="projects.php?category=ana_sayfa">Ana Sayfa Temaları</a> | 
                                <a href="projects.php?category=pc_uygulama">PC Uygulamaları</a> | 
                                <a href="projects.php?category=mobil_uygulama">Mobil Uygulamalar</a> | 
                                <a href="projects.php?category=pc_oyun">PC Oyunları</a> | 
                                <a href="projects.php?category=artado_logo">Artado Logolar</a>
                            </small>
                        </div>
                    </div>
                    <div class="float-end">
                        <p>Sxinar Tarafından <span class="text-danger"><i class="bi bi-heart-fill icon-mid"></i></span>
                            ile <a href="https://sxi.is-a.dev">Sxinar</a></p>
                    </div>
                </div>
            </footer>
        </div>
    </div>
    
    <div class="modal fade" id="editProjectModal" tabindex="-1" aria-labelledby="editProjectModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header modal-header-primary">
                    <h5 class="modal-title" id="editProjectModalLabel">Proje Düzenle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editProjectForm" method="POST" action="update_project.php" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" id="edit_project_id" name="project_id">
                        
                        <div class="mb-3">
                            <label for="edit_title" class="form-label">Proje Başlığı</label>
                            <input type="text" class="form-control" id="edit_title" name="title" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_category" class="form-label">Kategori</label>
                            <select class="form-select" id="edit_category" name="category" required>
                                <option value="pc_oyun">PC Oyunu</option>
                                <option value="mobil_uygulama">Mobil Uygulama</option>
                                <option value="pc_uygulama">PC Uygulaması</option>
                                <option value="artado_eklenti">Artado Eklentisi</option>
                                <option value="artado_tema">Artado Teması</option>
                                <option value="artado_logo">Artado Logo</option>
                                <option value="ana_sayfa">Ana Sayfa Teması</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_description" class="form-label">Açıklama</label>
                            <textarea class="form-control" id="edit_description" name="description" rows="4" required></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_features" class="form-label">Özellikler</label>
                            <textarea class="form-control" id="edit_features" name="features" rows="4"></textarea>
                            <small class="text-muted">Her özelliği yeni bir satıra yazın</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_image" class="form-label">Proje Resmi</label>
                            <div class="input-group">
                                <input type="file" class="form-control" id="edit_image" name="image" accept="image/*">
                                <label class="input-group-text" for="edit_image">Resim Seç</label>
                            </div>
                            <small class="text-muted">Yeni bir resim seçmezseniz mevcut resim korunacaktır</small>
                        </div>
                        
                        <div id="current_image" class="mb-3">
                            <label class="form-label">Mevcut/Önizleme Resmi</label>
                            <div class="text-center p-3 border rounded">
                                <img src="" alt="Mevcut Proje Resmi" class="img-fluid rounded">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="edit_project_file" class="form-label">Proje Dosyası</label>
                            <div class="input-group">
                                <input type="file" class="form-control" id="edit_project_file" name="project_file">
                                <label class="input-group-text" for="edit_project_file">Dosya Seç</label>
                            </div>
                            <small class="text-muted">Desteklenen formatlar: ZIP, RAR, 7Z (Max: 100MB)</small>
                        </div>

                        <div id="current_file" class="mb-3">
                            <label class="form-label">Mevcut Dosya</label>
                            <div class="p-3 border rounded">
                                <p class="mb-0" id="current_file_name">Henüz dosya yüklenmemiş</p>
                                <small class="text-muted" id="current_file_info"></small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" class="btn btn-primary">Kaydet</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    function openEditModal(project) {
        // Modal alanlarını doldur
        document.getElementById('edit_project_id').value = project.id;
        document.getElementById('edit_title').value = project.title;
        document.getElementById('edit_category').value = project.category;
        document.getElementById('edit_description').value = project.description;
        document.getElementById('edit_features').value = project.features;
        
        // Mevcut resmi göster
        const currentImage = document.querySelector('#current_image img');
        if (project.image_path) {
            currentImage.src = project.image_path;
            currentImage.parentElement.style.display = 'block';
        } else {
            currentImage.src = 'https://raw.githubusercontent.com/Artado-Project/devs/refs/heads/main/ArtadoDevs/images/favicon.ico';
            currentImage.parentElement.style.display = 'block';
        }

        // Mevcut dosya bilgilerini göster
        const currentFileName = document.getElementById('current_file_name');
        const currentFileInfo = document.getElementById('current_file_info');
        if (project.project_file) {
            currentFileName.textContent = project.project_file;
            currentFileInfo.textContent = 'Mevcut dosyayı değiştirmek için yeni bir dosya seçin';
        } else {
            currentFileName.textContent = 'Henüz dosya yüklenmemiş';
            currentFileInfo.textContent = '';
        }

        // Resim seçildiğinde önizleme göster
        document.getElementById('edit_image').addEventListener('change', function(e) {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    currentImage.src = e.target.result;
                    currentImage.parentElement.style.display = 'block';
                };
                reader.readAsDataURL(this.files[0]);
            }
        });

        // Dosya seçildiğinde bilgileri göster
        document.getElementById('edit_project_file').addEventListener('change', function(e) {
            if (this.files && this.files[0]) {
                const file = this.files[0];
                currentFileName.textContent = file.name;
                currentFileInfo.textContent = `Boyut: ${(file.size / (1024*1024)).toFixed(2)} MB`;
            }
        });
        
        // Modalı aç
        new bootstrap.Modal(document.getElementById('editProjectModal')).show();
    }
    </script>
    
    <script src="assets/static/js/components/dark.js"></script>
    <script src="assets/extensions/perfect-scrollbar/perfect-scrollbar.min.js"></script>
    <script src="assets/compiled/js/app.js"></script>
</body>
</html>