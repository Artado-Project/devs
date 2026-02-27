<?php
// Hata raporlamayı etkinleştir
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>

<?php
require_once '../includes/database.php';
require_once '../includes/auth.php';

// Kullanıcının giriş yapmış olması gerekiyor
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Ziyaret kaydı ekle (Dashboard sayfası için)
$visitor_ip = $_SERVER['REMOTE_ADDR'] ?: 'unknown';
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$log_stmt = $db->prepare("INSERT INTO visit_logs (page_type, target_id, visitor_ip, user_agent) VALUES ('dashboard', :user_id, :ip, :ua)");
$log_stmt->execute([':user_id' => $_SESSION['user_id'], ':ip' => $visitor_ip, ':ua' => $user_agent]);

// Toplam kullanıcı sayısını al
$stmt = $db->query("SELECT COUNT(*) FROM users");
$total_users = $stmt->fetchColumn();

// Toplam duyuru sayısını al
$stmt = $db->query("SELECT COUNT(*) FROM announcements");
$total_announcements = $stmt->fetchColumn();

// Toplam proje sayısını al
$stmt = $db->query("SELECT COUNT(*) FROM projects");
$total_projects = $stmt->fetchColumn();

// Kullanıcı oturum kontrolü
$user_id = $_SESSION['user_id'] ?? null;

// Kullanıcı bilgilerini çek
if ($user_id) {
    $stmt = $db->prepare("SELECT * FROM users WHERE id = :user_id");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    require_once '../includes/functions.php';
    $profile_photo = get_user_avatar($user['profile_photo'] ?? null, true);
} else {
    require_once '../includes/functions.php';
    $profile_photo = get_user_avatar(null, true);
}

// İstatistik verilerini hazırla (Son 12 ayın proje sayıları)
$stats_stmt = $db->query("
    SELECT 
        DATE_FORMAT(dates.date, '%b') as month,
        dates.date as sort_date,
        IFNULL(COUNT(p.id), 0) as count
    FROM (
        SELECT LAST_DAY(CURRENT_DATE - INTERVAL 11 MONTH) + INTERVAL 1 DAY as date UNION ALL
        SELECT LAST_DAY(CURRENT_DATE - INTERVAL 10 MONTH) + INTERVAL 1 DAY UNION ALL
        SELECT LAST_DAY(CURRENT_DATE - INTERVAL 9 MONTH) + INTERVAL 1 DAY UNION ALL
        SELECT LAST_DAY(CURRENT_DATE - INTERVAL 8 MONTH) + INTERVAL 1 DAY UNION ALL
        SELECT LAST_DAY(CURRENT_DATE - INTERVAL 7 MONTH) + INTERVAL 1 DAY UNION ALL
        SELECT LAST_DAY(CURRENT_DATE - INTERVAL 6 MONTH) + INTERVAL 1 DAY UNION ALL
        SELECT LAST_DAY(CURRENT_DATE - INTERVAL 5 MONTH) + INTERVAL 1 DAY UNION ALL
        SELECT LAST_DAY(CURRENT_DATE - INTERVAL 4 MONTH) + INTERVAL 1 DAY UNION ALL
        SELECT LAST_DAY(CURRENT_DATE - INTERVAL 3 MONTH) + INTERVAL 1 DAY UNION ALL
        SELECT LAST_DAY(CURRENT_DATE - INTERVAL 2 MONTH) + INTERVAL 1 DAY UNION ALL
        SELECT LAST_DAY(CURRENT_DATE - INTERVAL 1 MONTH) + INTERVAL 1 DAY UNION ALL
        SELECT LAST_DAY(CURRENT_DATE) + INTERVAL 1 DAY
    ) as dates
    LEFT JOIN projects p ON DATE_FORMAT(p.created_at, '%Y-%m') = DATE_FORMAT(dates.date, '%Y-%m')
    GROUP BY dates.date, month
    ORDER BY sort_date
");
$stats_data = $stats_stmt->fetchAll(PDO::FETCH_ASSOC);
$chart_months = json_encode(array_column($stats_data, 'month'));
$chart_counts = json_encode(array_column($stats_data, 'count'));

// Son duyuruları al
$ann_stmt = $db->query("
    SELECT a.*, u.username, u.profile_photo 
    FROM announcements a 
    LEFT JOIN users u ON a.user_id = u.id 
    ORDER BY a.created_at DESC LIMIT 4
");
$latest_announcements = $ann_stmt->fetchAll(PDO::FETCH_ASSOC);

// Workshop son 2 projeyi çek
function getWorkshopProjects() {
    global $db;
    
    // Önce veritabanından son 2 projeyi çek
    try {
        $stmt = $db->query("
            SELECT p.title, u.username as author, p.file_path, p.created_at, p.category,
                   COUNT(pi.id) as image_count
            FROM projects p 
            JOIN users u ON p.user_id = u.id 
            LEFT JOIN project_images pi ON p.id = pi.project_id
            GROUP BY p.id, p.title, u.username, p.file_path, p.created_at, p.category
            ORDER BY p.created_at DESC 
            LIMIT 2
        ");
        $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($projects)) {
            $normalized = [];
            foreach ($projects as $project) {
                $normalized[] = [
                    'title' => $project['title'],
                    'author' => $project['author'],
                    'link' => 'https://devs.artado.xyz/workshop',
                    'image' => $project['image_count'] > 0 ? 'https://raw.githubusercontent.com/Artado-Project/devs/main/ArtadoDevs/images/favicon.ico' : null,
                    'category' => getCategoryName($project['category'])
                ];
            }
            return $normalized;
        }
    } catch (Exception $e) {
        error_log("Database query failed: " . $e->getMessage());
    }
    
    // Veritabanı da boşsa örnek veri döndür
    return [
        [
            'title' => 'Artado Search',
            'author' => 'Artado Team',
            'link' => 'https://devs.artado.xyz/workshop',
            'image' => 'https://raw.githubusercontent.com/Artado-Project/devs/main/ArtadoDevs/images/favicon.ico',
            'category' => 'Arama Motoru'
        ],
        [
            'title' => 'Artado Browser',
            'author' => 'Artado Team', 
            'link' => 'https://devs.artado.xyz/workshop',
            'image' => 'https://raw.githubusercontent.com/Artado-Project/devs/main/ArtadoDevs/images/favicon.ico',
            'category' => 'Web Tarayıcı'
        ]
    ];
}

// Kategori adını Türkçe'ye çevir
function getCategoryName($category) {
    $categories = [
        'artado_logo' => 'Logo',
        'artado_extension' => 'Eklenti',
        'artado_theme' => 'Tema',
        'artado_pc_app' => 'PC Uygulaması',
        'artado_mobile_app' => 'Mobil Uygulama',
        'artado_pc_game' => 'PC Oyunu',
        'artado_mobile_game' => 'Mobil Oyun',
        'general' => 'Genel',
        'work' => 'İş',
        'personal' => 'Kişisel',
        'urgent' => 'Acil',
        'study' => 'Çalışma',
        'health' => 'Sağlık',
        'shopping' => 'Alışveriş'
    ];
    
    return $categories[$category] ?? ucfirst($category);
}

// Workshop verisini normalize et
function normalizeWorkshopData($projects) {
    $normalized = [];
    
    foreach ($projects as $project) {
        // Veri formatını kontrol et ve gerekli alanları ekle
        $normalized_project = [
            'title' => $project['title'] ?? $project['name'] ?? 'Bilinmeyen Proje',
            'author' => $project['author'] ?? $project['developer'] ?? $project['username'] ?? 'Bilinmeyen',
            'link' => $project['link'] ?? $project['url'] ?? $project['download_url'] ?? 'https://devs.artado.xyz/workshop',
            'image' => $project['image'] ?? $project['thumbnail'] ?? $project['icon'] ?? null,
            'downloads' => $project['downloads'] ?? $project['download_count'] ?? 0
        ];
        
        $normalized[] = $normalized_project;
    }
    
    return array_slice($normalized, 0, 2); // Sadece ilk 2 proje
}

// WordPress blog son 3 post'u çek
function getBlogPosts() {
    $blog_url = 'https://artado.xyz/blog/wp-json/wp/v2/posts?per_page=3&_embed';
    
    $context = stream_context_create([
        'http' => [
            'timeout' => 5,
            'user_agent' => 'Artado-Developers/1.0'
        ]
    ]);
    
    $response = @file_get_contents($blog_url, false, $context);
    
    if ($response === false) {
        return [];
    }
    
    $posts = json_decode($response, true);
    $formatted_posts = [];
    
    foreach ($posts as $post) {
        $formatted_posts[] = [
            'title' => $post['title']['rendered'],
            'excerpt' => wp_trim_words(strip_tags($post['excerpt']['rendered']), 15),
            'link' => $post['link'],
            'date' => date('d.m.Y', strtotime($post['date'])),
            'featured_image' => isset($post['_embedded']['wp:featuredmedia'][0]['source_url']) 
                ? $post['_embedded']['wp:featuredmedia'][0]['source_url'] 
                : null
        ];
    }
    
    return $formatted_posts;
}

// WordPress trim words fonksiyonu
function wp_trim_words($text, $num_words = 55, $more = null) {
    if (null === $more) {
        $more = '...';
    }
    $text = strip_tags($text);
    $words_array = preg_split('/[\s\n\r]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
    if (count($words_array) > $num_words) {
        $words_array = array_slice($words_array, 0, $num_words);
        $text = implode(' ', $words_array);
        $text = $text . $more;
    } else {
        $text = implode(' ', $words_array);
    }
    return $text;
}

// Verileri çek
$workshop_projects = getWorkshopProjects();
$blog_posts = getBlogPosts();

// Visitors Profile (Ziyaretçi istatistikleri - Örnek veri veya loglardan çekme)
$visit_stats_stmt = $db->query("SELECT page_type, COUNT(*) as count FROM visit_logs GROUP BY page_type");
$visit_stats = $visit_stats_stmt->fetchAll(PDO::FETCH_KEY_PAIR) ?: ['home' => 0, 'project' => 0];
$visit_series = json_encode(array_values($visit_stats));
$visit_labels = json_encode(array_keys($visit_stats));
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ana Sayfa - Artado Developers</title>
    <link rel="shortcut icon" href="../logo.png" type="image/x-icon">
    <link rel="shortcut icon" href="../logo.png" type="image/png">
    <link rel="stylesheet" crossorigin href="./assets/compiled/css/app.css">
    <link rel="stylesheet" crossorigin href="./assets/compiled/css/app-dark.css">
    <link rel="stylesheet" crossorigin href="./assets/compiled/css/iconly.css">
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
                            <svg xmlns="https://www.w3.org/2000/svg" xmlns:xlink="https://www.w3.org/1999/xlink"
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
                            <svg xmlns="https://www.w3.org/2000/svg" xmlns:xlink="https://www.w3.org/1999/xlink"
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

                        <li class="sidebar-item active">
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
                <h3>Önizleme</h3>
            </div>
            <div class="page-content">
                <section class="row">
                    <div class="col-12 col-lg-9">
                        <div class="row">
                            <div class="col-6 col-lg-3 col-md-6">
                                <div class="card">
                                    <div class="card-body px-4 py-4-5">
                                        <div class="row">
                                            <div
                                                class="col-md-4 col-lg-12 col-xl-12 col-xxl-5 d-flex justify-content-start ">
                                                <div class="stats-icon purple mb-2">
                                                    <i class="iconly-boldShow"></i>
                                                </div>
                                            </div>
                                            <div class="col-md-8 col-lg-12 col-xl-12 col-xxl-7">
                                                <h6 class="text-muted font-semibold">Toplam Proje Sayısı</h6>
                                                <h6 class="font-extrabold mb-0"><?php echo $total_projects; ?></h6>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6 col-lg-3 col-md-6">
                                <div class="card">
                                    <div class="card-body px-4 py-4-5">
                                        <div class="row">
                                            <div
                                                class="col-md-4 col-lg-12 col-xl-12 col-xxl-5 d-flex justify-content-start ">
                                                <div class="stats-icon blue mb-2">
                                                    <i class="iconly-boldProfile"></i>
                                                </div>
                                            </div>
                                            <div class="col-md-8 col-lg-12 col-xl-12 col-xxl-7">
                                                <h6 class="text-muted font-semibold">Toplam Kullanıcı Sayısı</h6>
                                                <h6 class="font-extrabold mb-0"><?php echo $total_users; ?></h6>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6 col-lg-3 col-md-6">
                                <div class="card">
                                    <div class="card-body px-4 py-4-5">
                                        <div class="row">
                                            <div
                                                class="col-md-4 col-lg-12 col-xl-12 col-xxl-5 d-flex justify-content-start ">
                                                <div class="stats-icon green mb-2">
                                                    <i class="iconly-boldAdd-User"></i>
                                                </div>
                                            </div>
                                            <div class="col-md-8 col-lg-12 col-xl-12 col-xxl-7">
                                                <h6 class="text-muted font-semibold">Duyurular</h6>
                                                <h6 class="font-extrabold mb-0"><?php echo $total_announcements; ?></h6>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6 col-lg-3 col-md-6">
                                <div class="card">
                                    <div class="card-body px-4 py-4-5">
                                        <div class="row">
                                            <div
                                                class="col-md-4 col-lg-12 col-xl-12 col-xxl-5 d-flex justify-content-start ">
                                                <div class="stats-icon red mb-2">
                                                    <i class="iconly-boldBookmark"></i>
                                                </div>
                                            </div>
                                            <div class="col-md-8 col-lg-12 col-xl-12 col-xxl-7">
                                                <h6 class="text-muted font-semibold">Proje görüntülemem</h6>
                                                <h6 class="font-extrabold mb-0">None</h6>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h4>Son Eklenen Projeler</h4>
                                    </div>
                                    <div class="card-body">
                                        <section class="project-section">

                                            <?php
                                            // Projeleri alalım
                                            $stmt = $db->query("SELECT p.title, u.username FROM projects p JOIN users u ON p.user_id = u.id ORDER BY p.created_at DESC LIMIT 5");
                                            $recent_projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                            ?>

                                            <ul>
                                                <?php foreach ($recent_projects as $project): ?>
                                                    <li><?php echo $project['title']; ?> <span>(Yükleyen:
                                                            @<?php echo $project['username']; ?>)</span></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </section>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12 col-xl-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h4>Workshop Son Projeler</h4>
                                    </div>
                                    <div class="card-body">
                                        <?php if (!empty($workshop_projects)): ?>
                                            <?php foreach ($workshop_projects as $project): ?>
                                                <div class="d-flex align-items-start mb-3 pb-3 border-bottom">
                                                    <?php if (!empty($project['image'])): ?>
                                                        <img src="<?= htmlspecialchars($project['image']) ?>" 
                                                             class="me-3" style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px;" 
                                                             alt="<?= htmlspecialchars($project['title']) ?>">
                                                    <?php else: ?>
                                                        <div class="me-3 bg-secondary d-flex align-items-center justify-content-center" 
                                                             style="width: 60px; height: 60px; border-radius: 8px;">
                                                            <i class="bi bi-image text-white"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div class="flex-grow-1">
                                                        <h6 class="mb-1">
                                                            <a href="<?= htmlspecialchars($project['link'] ?? '#') ?>" 
                                                               target="_blank" class="text-decoration-none">
                                                                <?= htmlspecialchars($project['title']) ?>
                                                            </a>
                                                        </h6>
                                                        <p class="text-muted small mb-1">
                                                            <i class="bi bi-person me-1"></i>
                                                            <?= htmlspecialchars($project['author'] ?? 'Bilinmeyen') ?>
                                                        </p>
                                                        <p class="text-muted small mb-0">
                                                            <i class="bi bi-tag me-1"></i>
                                                            <?= htmlspecialchars($project['category']) ?>
                                                        </p>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div class="text-center py-3">
                                                <i class="bi bi-exclamation-triangle text-warning fs-4"></i>
                                                <p class="text-muted small mt-2">Workshop projeleri yüklenemedi</p>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="mt-3">
                                            <a href="https://devs.artado.xyz/workshop" target="_blank" 
                                               class="btn btn-sm btn-outline-primary w-100">
                                                <i class="bi bi-box-arrow-up-right me-1"></i>
                                                Tüm Projeler
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-xl-8">
                                <div class="card">
                                    <div class="card-header">
                                        <h4>Son duyurular</h4>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-hover table-lg">
                                                <thead>
                                                    <tr>
                                                        <th>İsim</th>
                                                        <th>İçeriği</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($latest_announcements as $ann): ?>
                                                    <tr>
                                                        <td class="col-3">
                                                            <div class="d-flex align-items-center">
                                                                <div class="avatar avatar-md">
                                                                    <img src="<?php echo get_user_avatar($ann['profile_photo'] ?? null, true); ?>">
                                                                </div>
                                                                <p class="font-bold ms-3 mb-0"><?php echo htmlspecialchars($ann['username'] ?: 'Artado'); ?></p>
                                                            </div>
                                                        </td>
                                                        <td class="col-auto">
                                                            <p class=" mb-0"><strong><?php echo htmlspecialchars($ann['title']); ?>:</strong> <?php echo mb_strimwidth(strip_tags($ann['description']), 0, 100, "..."); ?></p>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                    <?php if (empty($latest_announcements)): ?>
                                                    <tr><td colspan="2" class="text-center">Henüz duyuru bulunmuyor.</td></tr>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-lg-3">
                        <div class="card">
                            <div class="card-body py-4 px-4">
                                <div class="d-flex align-items-center">
                                    <div class="avatar avatar-xl">
                                        <img src="<?php echo $profile_photo; ?>" class="profile-photo">
                                    </div>
                                    <div class="ms-3 name">
                                        <h5 class="font-bold"><?php echo $user['username']; ?></h5>
                                        <h6 class="text-muted mb-0"><?php echo $user['email']; ?></h6>
                                    </div>
                                </div>
                                

                            </div>
                        </div>
                        <div class="card">
                            <div class="card-header">
                                <h4>Son Proje Yükleyenler</h4>
                            </div>
                            <div class="card-content pb-4">
                                    <div class="name ms-4 w-100">
                                        <?php 
                                        $uploader_stmt = $db->query("
                                            SELECT u.username, u.profile_photo, MAX(p.created_at) as last_upload
                                            FROM projects p 
                                            JOIN users u ON p.user_id = u.id 
                                            GROUP BY u.id, u.username, u.profile_photo
                                            ORDER BY last_upload DESC 
                                            LIMIT 5
                                        ");
                                        $uploaders = $uploader_stmt->fetchAll(PDO::FETCH_ASSOC);
                                        foreach ($uploaders as $uploader): ?>
                                            <div class="d-flex align-items-center mb-2">
                                                <div class="avatar avatar-sm me-2">
                                                    <img src="<?php echo get_user_avatar($uploader['profile_photo'], true); ?>">
                                                </div>
                                                <span class="font-bold">@<?php echo htmlspecialchars($uploader['username']); ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>


                                <div class="px-4">
                                    <button class='btn btn-block btn-xl btn-outline-primary font-bold mt-3'><a href="https://devs.artado.xyz/workshop">Projeleri
                                        İncele<a></button>
                                </div>
                            </div>
                        </div>
                        <div class="card">
                            <div class="card-header">
                                <h4>Artado Blog</h4>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($blog_posts)): ?>
                                    <?php foreach ($blog_posts as $post): ?>
                                        <div class="mb-3 pb-3 border-bottom">
                                            <?php if ($post['featured_image']): ?>
                                                <img src="<?= htmlspecialchars($post['featured_image']) ?>" 
                                                     class="w-100 mb-2" style="height: 120px; object-fit: cover; border-radius: 8px;" 
                                                     alt="<?= htmlspecialchars($post['title']) ?>">
                                            <?php endif; ?>
                                            
                                            <h6 class="mb-2">
                                                <a href="<?= htmlspecialchars($post['link']) ?>" 
                                                   target="_blank" class="text-decoration-none">
                                                    <?= html_entity_decode($post['title']) ?>
                                                </a>
                                            </h6>
                                            
                                            <p class="text-muted small mb-2">
                                                <?= $post['excerpt'] ?>
                                            </p>
                                            
                                            <div class="d-flex justify-content-between align-items-center">
                                                <small class="text-muted">
                                                    <i class="bi bi-calendar me-1"></i>
                                                    <?= $post['date'] ?>
                                                </small>
                                                <a href="<?= htmlspecialchars($post['link']) ?>" 
                                                   target="_blank" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-arrow-right"></i>
                                                </a>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="text-center py-4">
                                        <i class="bi bi-newspaper text-muted fs-3"></i>
                                        <p class="text-muted mt-2">Blog yazıları yüklenemedi</p>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="mt-3">
                                    <a href="https://artado.xyz/blog" target="_blank" 
                                       class="btn btn-sm btn-outline-primary w-100">
                                        <i class="bi bi-journal-text me-1"></i>
                                        Tüm Yazılar
                                    </a>
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
                        <p>Sxinar tarafından <span class="text-danger"><i class="bi bi-heart-fill icon-mid"></i></span>
                            by <a href="https://sxi.is-a.dev">Sxinar</a></p>
                    </div>
                </div>
            </footer>
        </div>
    </div>
    <script src="assets/static/js/components/dark.js"></script>
    <script src="assets/extensions/perfect-scrollbar/perfect-scrollbar.min.js"></script>


    <script src="assets/compiled/js/app.js"></script>



    <!-- Need: Apexcharts -->
    <script src="assets/extensions/apexcharts/apexcharts.min.js"></script>
    <script src="assets/static/js/pages/dashboard.js"></script>

</body>

</html>