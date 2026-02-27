<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Paneli - Artado Devs</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Animation for popup opening */
        @keyframes popup-enter {
            0% {
                transform: scale(0.9) translateX(-20px);
                opacity: 0;
            }
            100% {
                transform: scale(1) translateX(0);
                opacity: 1;
            }
        }

        .popup-enter {
            animation: popup-enter 0.3s ease-out forwards;
        }

        /* Optional: smooth transition for closing */
        .popup-exit {
            animation: fadeOut 0.3s ease-out forwards;
        }

        @keyframes fadeOut {
            0% {
                opacity: 1;
            }
            100% {
                opacity: 0;
            }
        }

        /* Sidebar animations */
        .sidebar-item {
            transition: all 0.3s ease;
        }
        
        .sidebar-item:hover {
            transform: translateX(5px);
        }

        /* Header shadow */
        .admin-header {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        /* Active menu item */
        .active-menu {
            background: linear-gradient(90deg, #4f46e5 0%, #6366f1 100%);
            color: white;
        }
    </style>
</head>
<body class="bg-gray-50">

<?php
require_once '../includes/database.php';
require_once '../includes/auth.php';

// 1. ADIM: Sitenin ana yolunu tanımla (Burayı kendi domainine göre düzenle)
// Eğer localhostta çalışıyorsan: 'http://localhost/proje_adin/' yazmalısın.
define('SITE_URL', 'https://devs.artado.xyz/'); 

// Kullanıcı ID'si, oturumdan alınıyor
$user_id = $_SESSION['user_id'] ?? null;

// Varsayılan profil fotoğrafı (Zaten tam adres olduğu için sorun çıkarmaz)
$default_profile_photo = '../logo.png';

// Eğer kullanıcı giriş yaptıysa
if ($user_id) {
    $stmt = $db->prepare("SELECT * FROM users WHERE id = :user_id");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && $user['profile_photo']) {
        // Eğer veritabanındaki veri 'uploads/resim.jpg' gibi geliyorsa başına SITE_URL ekliyoruz
        // Eğer veritabanında zaten tam URL (http...) kayıtlıysa kontrol edip eklemelisin.
        $profile_photo = (strpos($user['profile_photo'], 'http') === 0) 
                         ? $user['profile_photo'] 
                         : SITE_URL . $user['profile_photo'];
    } else {
        $profile_photo = $default_profile_photo;
    }
} else {
    $profile_photo = $default_profile_photo;
}

// Menü aktifliği için sayfa adını al
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- Admin Header -->
<link rel="icon" type="image/png" href="../logo.png">
<header class="admin-header bg-gradient-to-r from-indigo-600 to-purple-600 text-white sticky top-0 z-50">
    <div class="container mx-auto px-6 py-4">
        <div class="flex items-center justify-between">
            <!-- Logo and Title -->
            <div class="flex items-center space-x-4">
                <div class="w-10 h-10 bg-white/20 backdrop-blur-lg rounded-lg flex items-center justify-center">
                    <i class="fas fa-shield-alt text-xl"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold">Admin Paneli</h1>
                    <p class="text-indigo-200 text-sm">Artado Devs Yönetim Sistemi</p>
                </div>
            </div>

            <!-- Navigation and Profile -->
            <div class="flex items-center space-x-6">
                <!-- Quick Actions -->
                <div class="hidden md:flex items-center space-x-4">
                    <a href="../user" class="bg-white/10 hover:bg-white/20 px-4 py-2 rounded-lg transition-all duration-200 flex items-center space-x-2">
                        <i class="fas fa-user"></i>
                        <span>Kullanıcı Konsolu</span>
                    </a>
                    <a href="../index.php" class="bg-white/10 hover:bg-white/20 px-4 py-2 rounded-lg transition-all duration-200 flex items-center space-x-2">
                        <i class="fas fa-home"></i>
                        <span>Ana Sayfa</span>
                    </a>
                </div>

                <!-- Profile Dropdown -->
                <div class="relative group">
                    <button class="flex items-center space-x-3 p-2 rounded-lg hover:bg-white/10 transition-colors">
                        <img src="<?php echo $profile_photo; ?>" alt="Profil Fotoğrafı" class="w-10 h-10 rounded-full border-2 border-white/50">
                        <div class="hidden md:block text-left">
                            <p class="font-semibold"><?php echo $user['username']; ?></p>
                            <p class="text-xs text-indigo-200">Administrator</p>
                        </div>
                        <i class="fas fa-chevron-down text-sm"></i>
                    </button>
                    
                    <!-- Dropdown Menu -->
                    <div class="absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-xl border border-gray-100 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50">
                        <div class="p-4 border-b border-gray-100">
                            <div class="flex items-center space-x-3">
                                <img src="<?php echo $profile_photo; ?>" alt="Profil Fotoğrafı" class="w-12 h-12 rounded-full border-2 border-gray-200">
                                <div>
                                    <p class="font-semibold text-gray-800"><?php echo $user['username']; ?></p>
                                    <p class="text-sm text-gray-500">admin@artado.xyz</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="py-2">
                            <a href="../user" class="flex items-center space-x-3 px-4 py-3 hover:bg-gray-50 transition-colors text-gray-700">
                                <i class="fas fa-user-circle text-gray-400"></i>
                                <span>Kullanıcı Profili</span>
                            </a>
                            <a href="../user" class="flex items-center space-x-3 px-4 py-3 hover:bg-gray-50 transition-colors text-gray-700">
                                <i class="fas fa-cog text-gray-400"></i>
                                <span>Ayarlar</span>
                            </a>
                            <a href="statistics.php" class="flex items-center space-x-3 px-4 py-3 hover:bg-gray-50 transition-colors text-gray-700">
                                <i class="fas fa-chart-bar text-gray-400"></i>
                                <span>İstatistikler</span>
                            </a>
                        </div>
                        
                        <div class="border-t border-gray-100 py-2">
                            <a href="../logout.php" class="flex items-center space-x-3 px-4 py-3 hover:bg-red-50 transition-colors text-red-600">
                                <i class="fas fa-sign-out-alt"></i>
                                <span>Çıkış Yap</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Mobile Menu Toggle -->
                <button id="mobile-menu-toggle" class="md:hidden p-2 rounded-lg hover:bg-white/10 transition-colors">
                    <i class="fas fa-bars text-xl"></i>
                </button>
            </div>
        </div>
    </div>
</header>

<!-- Admin Sidebar Navigation -->
<div class="flex">
    <!-- Sidebar -->
    <aside class="w-64 bg-white shadow-lg min-h-screen sticky top-16">
        <nav class="p-4">
            <div class="space-y-2">
                <!-- Dashboard -->
                <a href="index.php" class="sidebar-item flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700 hover:bg-indigo-50 <?php echo $current_page == 'index.php' ? 'active-menu' : ''; ?>">
                    <i class="fas fa-tachometer-alt w-5"></i>
                    <span>Dashboard</span>
                </a>

                <!-- Users Management -->
                <a href="users.php" class="sidebar-item flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700 hover:bg-indigo-50 <?php echo $current_page == 'users.php' ? 'active-menu' : ''; ?>">
                    <i class="fas fa-users w-5"></i>
                    <span>Kullanıcı Yönetimi</span>
                </a>

                <!-- Projects Management -->
                <a href="projects.php" class="sidebar-item flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700 hover:bg-indigo-50 <?php echo $current_page == 'projects.php' ? 'active-menu' : ''; ?>">
                    <i class="fas fa-folder-open w-5"></i>
                    <span>Proje Yönetimi</span>
                </a>

                <!-- Announcements -->
                <a href="duyuru.php" class="sidebar-item flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700 hover:bg-indigo-50 <?php echo $current_page == 'duyuru.php' ? 'active-menu' : ''; ?>">
                    <i class="fas fa-bullhorn w-5"></i>
                    <span>Duyurular</span>
                </a>

                <!-- Comments Management -->
                <a href="comments.php" class="sidebar-item flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700 hover:bg-indigo-50 <?php echo $current_page == 'comments.php' ? 'active-menu' : ''; ?>">
                    <i class="fas fa-comments w-5"></i>
                    <span>Yorum Yönetimi</span>
                </a>

                <!-- Categories -->
                <a href="categories.php" class="sidebar-item flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700 hover:bg-indigo-50 <?php echo $current_page == 'categories.php' ? 'active-menu' : ''; ?>">
                    <i class="fas fa-tags w-5"></i>
                    <span>Kategoriler</span>
                </a>

                <!-- Statistics -->
                <a href="statistics.php" class="sidebar-item flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700 hover:bg-indigo-50 <?php echo $current_page == 'statistics.php' ? 'active-menu' : ''; ?>">
                    <i class="fas fa-chart-pie w-5"></i>
                    <span>İstatistikler</span>
                </a>

                <!-- Divider -->
                <div class="border-t border-gray-200 my-4"></div>

                <!-- External Links -->
                <a href="../user" target="_blank" class="sidebar-item flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700 hover:bg-green-50">
                    <i class="fas fa-user-circle w-5 text-green-600"></i>
                    <span>Kullanıcı Konsolu</span>
                    <i class="fas fa-external-link-alt text-xs text-gray-400 ml-auto"></i>
                </a>

                <a href="../Workshop" target="_blank" class="sidebar-item flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700 hover:bg-purple-50">
                    <i class="fas fa-tools w-5 text-purple-600"></i>
                    <span>Workshop</span>
                    <i class="fas fa-external-link-alt text-xs text-gray-400 ml-auto"></i>
                </a>

                <a href="https://forum.artado.xyz" target="_blank" class="sidebar-item flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700 hover:bg-blue-50">
                    <i class="fas fa-comments w-5 text-blue-600"></i>
                    <span>Forum</span>
                    <i class="fas fa-external-link-alt text-xs text-gray-400 ml-auto"></i>
                </a>

                <a href="https://matrix.to/#/#artadoproject:matrix.org" target="_blank" class="sidebar-item flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700 hover:bg-gray-100">
                    <i class="fas fa-matrix-org w-5 text-gray-600"></i>
                    <span>Matrix</span>
                    <i class="fas fa-external-link-alt text-xs text-gray-400 ml-auto"></i>
                </a>
            </div>
        </nav>
    </aside>

    <!-- Main Content Area -->
    <main class="flex-1 p-6">
        <!-- Breadcrumb -->
        <div class="mb-6">
            <nav class="flex items-center space-x-2 text-sm text-gray-500">
                <a href="index.php" class="hover:text-indigo-600 transition-colors">
                    <i class="fas fa-home"></i>
                </a>
                <i class="fas fa-chevron-right text-xs"></i>
                <span class="text-gray-700 font-medium">
                    <?php
                    $page_titles = [
                        'index.php' => 'Dashboard',
                        'users.php' => 'Kullanıcı Yönetimi',
                        'projects.php' => 'Proje Yönetimi',
                        'duyuru.php' => 'Duyurular',
                        'comments.php' => 'Yorum Yönetimi',
                        'privacy_requests.php' => 'Gizlilik İstekleri',
                        'statistics.php' => 'İstatistikler',
                        'edit_user.php' => 'Kullanıcı Düzenle',
                        'edit_project.php' => 'Proje Düzenle',
                        'add_project.php' => 'Proje Ekle'
                    ];
                    echo $page_titles[$current_page] ?? 'Admin Panel';
                    ?>
                </span>
            </nav>
        </div>

<script>
    // Mobile menu toggle
    const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
    const sidebar = document.querySelector('aside');
    
    if (mobileMenuToggle && sidebar) {
        mobileMenuToggle.addEventListener('click', () => {
            sidebar.classList.toggle('hidden');
        });
    }

    // Close mobile menu when clicking outside
    document.addEventListener('click', (event) => {
        if (window.innerWidth < 768) {
            if (!sidebar.contains(event.target) && !mobileMenuToggle.contains(event.target)) {
                sidebar.classList.add('hidden');
            }
        }
    });

    // Responsive sidebar
    function handleResize() {
        if (window.innerWidth >= 768) {
            sidebar.classList.remove('hidden');
        }
    }
    
    window.addEventListener('resize', handleResize);
    handleResize(); // Initial check
</script>

</body>
</html>
