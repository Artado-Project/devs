<?php
session_start();

if (file_exists('../config.php')) {
    require '../config.php';
} elseif (file_exists('config.php')) {
    require 'config.php';
}

if (!defined('BASE_URL')) {
    define('BASE_URL', '/'); 
}

$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? trim($_GET['category']) : '';
$sort = isset($_GET['sort']) ? trim($_GET['sort']) : 'latest';

$is_logged_in = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
$user_username = $is_logged_in ? ($_SESSION['username'] ?? 'Kullanıcı') : '';
$user_avatar = '';

if ($is_logged_in) {
    try {
        $stmt = $db->prepare("SELECT avatar FROM users WHERE id = :id");
        $stmt->bindParam(':id', $_SESSION['user_id'], PDO::PARAM_INT);
        $stmt->execute();
        $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
        $user_avatar = $user_data['avatar'] ?? '';
    } catch (PDOException $e) { $user_avatar = ''; }
}

// Kategoriler ve ikonlar
$categories = [
    '' => ['name' => 'Tümü', 'icon' => 'fa-layer-group'],
    'Eklenti' => ['name' => 'Eklentiler', 'icon' => 'fa-plug'],
    'Tema' => ['name' => 'Temalar', 'icon' => 'fa-brush'],
    'Logo' => ['name' => 'Logolar', 'icon' => 'fa-icons'],
    'Yazılım' => ['name' => 'Yazılımlar', 'icon' => 'fa-terminal']
];

try {
    $sql = "SELECT p.id, p.title, p.description, p.upload_date, p.category, 
            MAX(pi.image_path) as image_path, u.username 
            FROM projects p
            LEFT JOIN project_images pi ON p.id = pi.project_id
            LEFT JOIN users u ON p.user_id = u.id";
    
    $where_clauses = [];
    $params = [];

    if (!empty($search_term)) {
        $where_clauses[] = "(p.title LIKE :search OR p.description LIKE :search)";
        $params[':search'] = '%' . $search_term . '%';
    }

    if (!empty($category_filter)) {
        $where_clauses[] = "p.category LIKE :category";
        $params[':category'] = '%' . $category_filter . '%';
    }

    if (!empty($where_clauses)) {
        $sql .= " WHERE " . implode(" AND ", $where_clauses);
    }

    $sql .= " GROUP BY p.id, p.title, p.description, p.upload_date, p.category, u.username";

    switch ($sort) {
        case 'oldest':
            $sql .= " ORDER BY p.upload_date ASC";
            break;
        case 'alpha_asc':
            $sql .= " ORDER BY p.title ASC";
            break;
        case 'alpha_desc':
            $sql .= " ORDER BY p.title DESC";
            break;
        case 'latest':
        default:
            $sql .= " ORDER BY p.upload_date DESC";
            break;
    }
    
    $stmt = $db->prepare($sql);
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->execute();
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { 
    die("Veritabanı hatası: " . $e->getMessage()); 
}

$total_projects = count($projects);

function timeAgo($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;
    if ($diff < 60) return 'Az önce';
    if ($diff < 3600) return round($diff / 60) . ' dk önce';
    if ($diff < 86400) return round($diff / 3600) . ' saat önce';
    if ($diff < 2592000) return round($diff / 86400) . ' gün önce';
    return date('d.m.Y', $time);
}
?>
<!DOCTYPE html>
<html lang="tr" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Workshop — Artado Developers</title>
    <meta name="description" content="Artado ekosistemi için eklentiler, temalar ve açık kaynaklı topluluk projeleri.">
    <link rel="icon" type="image/png" href="https://devs.artado.xyz/homepage/images/logo.png">
    
    <!-- Typography -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', '-apple-system', 'BlinkMacSystemFont', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50: '#f0f7ff',
                            500: '#2563eb',
                            600: '#1d4ed8',
                            glow: 'rgba(37, 99, 235, 0.15)'
                        }
                    }
                }
            }
        }
    </script>
    
    <style>
        :root {
            --bg-base: #090a0f;
            --surface-1: #0f1219;
            --surface-2: #161b26;
            --border-subtle: rgba(255, 255, 255, 0.07);
            --border-hover: rgba(255, 255, 255, 0.15);
        }
        
        body {
            background-color: var(--bg-base);
            color: #e2e8f0;
            font-family: 'Inter', -apple-system, sans-serif;
            background-image: radial-gradient(circle at 50% -20%, rgba(37, 99, 235, 0.12) 0%, transparent 60%);
            background-repeat: no-repeat;
        }

        .surface-card {
            background: var(--surface-1);
            border: 1px solid var(--border-subtle);
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .surface-card:hover {
            border-color: var(--border-hover);
            transform: translateY(-2px);
            box-shadow: 0 16px 32px -12px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(255, 255, 255, 0.05);
        }

        .category-pill {
            transition: all 0.15s ease;
        }
        .category-pill.active {
            background: #ffffff;
            color: #090a0f;
            font-weight: 600;
        }
        .category-pill:not(.active) {
            color: #94a3b8;
            border: 1px solid var(--border-subtle);
        }
        .category-pill:not(.active):hover {
            color: #ffffff;
            background: var(--surface-2);
            border-color: var(--border-hover);
        }

        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="min-h-screen flex flex-col antialiased selection:bg-blue-600 selection:text-white">

    <!-- Üst Gezinme Çubuğu (Global Header) -->
    <header class="sticky top-0 z-50 border-b border-white/[0.07] bg-[#090a0f]/80 backdrop-blur-xl">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between gap-4">
            
            <!-- Logo & Marka -->
            <div class="flex items-center gap-6">
                <a href="index.php" class="flex items-center gap-3 group">
                    <img src="https://devs.artado.xyz/homepage/images/logo.png" alt="Artado" class="w-7 h-7 object-contain transition-transform group-hover:scale-105">
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-semibold text-white tracking-tight">Artado</span>
                        <span class="text-xs text-slate-500 font-mono">/</span>
                        <span class="text-xs font-medium text-slate-300">Workshop</span>
                    </div>
                </a>

                <nav class="hidden md:flex items-center gap-1 text-xs font-medium text-slate-400">
                    <a href="index.php" class="px-3 py-1.5 rounded-md text-white bg-white/[0.06] transition-colors">Keşfet</a>
                    <a href="https://forum.artado.xyz" target="_blank" rel="noopener" class="px-3 py-1.5 rounded-md hover:text-white hover:bg-white/[0.04] transition-colors">Forum</a>
                    <a href="https://devs.artado.xyz" target="_blank" rel="noopener" class="px-3 py-1.5 rounded-md hover:text-white hover:bg-white/[0.04] transition-colors">Dokümantasyon</a>
                </nav>
            </div>

            <!-- Sağ Aksiyonlar -->
            <div class="flex items-center gap-3">
                <?php if ($is_logged_in): ?>
                    <a href="../user/create-project.php" class="hidden sm:inline-flex items-center gap-2 px-3.5 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-500 text-white text-xs font-medium transition-all shadow-sm shadow-blue-600/20">
                        <i class="fas fa-plus text-[10px]"></i> Proje Paylaş
                    </a>
                    <div class="flex items-center gap-2 pl-2 border-l border-white/[0.08]">
                        <div class="w-8 h-8 rounded-full bg-slate-800 border border-white/10 flex items-center justify-center text-xs font-semibold text-white overflow-hidden">
                            <?= !empty($user_avatar) ? '<img src="../'.$user_avatar.'" class="w-full h-full object-cover">' : substr($user_username, 0, 2) ?>
                        </div>
                        <span class="hidden sm:inline text-xs font-medium text-slate-300"><?=$user_username?></span>
                    </div>
                <?php else: ?>
                    <a href="https://devs.artado.xyz/login.php" class="px-3.5 py-1.5 rounded-lg bg-white/[0.08] hover:bg-white/[0.12] text-white text-xs font-medium border border-white/[0.08] transition-all">
                        Giriş Yap
                    </a>
                <?php endif; ?>
            </div>

        </div>
    </header>

    <!-- Ana Gövde -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 py-8 sm:py-12 space-y-8">

        <!-- Hero & Arama Başlığı -->
        <section class="max-w-3xl space-y-4">
            <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full border border-blue-500/20 bg-blue-500/10 text-blue-400 text-[11px] font-medium">
                <span class="w-1.5 h-1.5 rounded-full bg-blue-400 animate-pulse"></span> Topluluk Havuzu
            </div>
            <h1 class="text-3xl sm:text-4xl font-bold tracking-tight text-white">
                Artado için geliştirilen çalışmaları keşfedin.
            </h1>
            <p class="text-sm text-slate-400 leading-relaxed">
                Geliştiriciler tarafından paylaşılan eklentiler, özel temalar, grafik varlıkları ve açık kaynaklı araçlar.
            </p>

            <!-- Arama & Filtre Çubuğu -->
            <form method="GET" action="index.php" class="pt-2">
                <?php if(!empty($category_filter)): ?>
                    <input type="hidden" name="category" value="<?=htmlspecialchars($category_filter)?>">
                <?php endif; ?>
                <div class="relative flex items-center">
                    <i class="fas fa-search absolute left-4 text-slate-500 text-sm"></i>
                    <input 
                        type="text" 
                        name="search" 
                        id="mainSearchInput"
                        value="<?=htmlspecialchars($search_term)?>" 
                        placeholder="Başlık veya açıklamayla ara... (/)" 
                        class="w-full bg-[#0f1219] border border-white/[0.1] text-sm text-slate-200 pl-11 pr-24 py-3.5 rounded-xl focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-all placeholder-slate-500 shadow-inner"
                    >
                    <div class="absolute right-3 flex items-center gap-1.5">
                        <?php if(!empty($search_term)): ?>
                            <a href="index.php<?=!empty($category_filter)?'?category='.urlencode($category_filter):''?>" class="text-xs text-slate-400 hover:text-white px-2 py-1" title="Temizle">
                                <i class="fas fa-times"></i>
                            </a>
                        <?php endif; ?>
                        <kbd class="hidden sm:inline-block px-2 py-0.5 text-[10px] font-mono text-slate-400 bg-white/[0.05] border border-white/[0.08] rounded">⌘K</kbd>
                    </div>
                </div>
            </form>
        </section>

        <!-- Kategori Sekmeleri & Sıralama Barı -->
        <section class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-white/[0.07] pb-4">
            
            <!-- Kategori Butonları -->
            <div class="flex items-center gap-2 overflow-x-auto no-scrollbar w-full sm:w-auto py-1">
                <?php foreach ($categories as $catKey => $catMeta): 
                    $isActive = ($category_filter === $catKey);
                    $query = [];
                    if (!empty($search_term)) $query['search'] = $search_term;
                    if (!empty($catKey)) $query['category'] = $catKey;
                    if (!empty($sort) && $sort !== 'latest') $query['sort'] = $sort;
                    $catUrl = "index.php" . (!empty($query) ? '?' . http_build_query($query) : '');
                ?>
                    <a href="<?=$catUrl?>" class="category-pill whitespace-nowrap px-3.5 py-1.5 rounded-lg text-xs flex items-center gap-2 <?=$isActive ? 'active' : ''?>">
                        <i class="fas <?=$catMeta['icon']?> text-[11px] opacity-80"></i>
                        <span><?=$catMeta['name']?></span>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- Sıralama Dropdown -->
            <div class="flex items-center gap-2 self-end sm:self-auto text-xs text-slate-400">
                <span>Sırala:</span>
                <form method="GET" action="index.php" id="sortForm">
                    <?php if(!empty($search_term)): ?><input type="hidden" name="search" value="<?=htmlspecialchars($search_term)?>"><?php endif; ?>
                    <?php if(!empty($category_filter)): ?><input type="hidden" name="category" value="<?=htmlspecialchars($category_filter)?>"><?php endif; ?>
                    <select name="sort" onchange="this.form.submit()" class="bg-[#0f1219] border border-white/[0.08] text-slate-200 rounded-lg px-2.5 py-1.5 text-xs font-medium focus:outline-none focus:border-white/20 cursor-pointer">
                        <option value="latest" <?=$sort === 'latest' ? 'selected' : ''?>>En Yeni</option>
                        <option value="oldest" <?=$sort === 'oldest' ? 'selected' : ''?>>En Eski</option>
                        <option value="alpha_asc" <?=$sort === 'alpha_asc' ? 'selected' : ''?>>A &rarr; Z</option>
                        <option value="alpha_desc" <?=$sort === 'alpha_desc' ? 'selected' : ''?>>Z &rarr; A</option>
                    </select>
                </form>
            </div>
        </section>

        <!-- Proje Kartları Izgarası -->
        <section>
            <?php if (empty($projects)): ?>
                <div class="surface-card rounded-2xl py-16 px-6 text-center flex flex-col items-center justify-center space-y-3">
                    <div class="w-12 h-12 rounded-xl bg-white/[0.04] border border-white/[0.08] flex items-center justify-center text-slate-400 text-lg">
                        <i class="fas fa-inbox"></i>
                    </div>
                    <div class="space-y-1">
                        <h3 class="text-sm font-semibold text-white">Eşleşen çalışma bulunamadı</h3>
                        <p class="text-xs text-slate-400">Arama teriminizi değiştirmeyi veya filtreleri temizlemeyi deneyin.</p>
                    </div>
                    <a href="index.php" class="mt-2 px-3.5 py-1.5 rounded-lg bg-white/[0.08] hover:bg-white/[0.12] text-xs font-medium text-white transition-colors">
                        Tümünü Göster
                    </a>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                    <?php foreach ($projects as $project): 
                        $img_path = !empty($project['image_path']) ? '../' . ltrim($project['image_path'], './') : null;
                        $has_img = $img_path && file_exists($img_path);
                    ?>
                        <a href="project.php?id=<?=$project['id']?>" class="surface-card rounded-xl overflow-hidden flex flex-col justify-between group">
                            
                            <!-- Kart Görseli -->
                            <div class="w-full aspect-[16/10] bg-[#07090e] border-b border-white/[0.06] relative overflow-hidden flex items-center justify-center">
                                <?php if ($has_img): ?>
                                    <img src="<?= htmlspecialchars($img_path) ?>" alt="<?= htmlspecialchars($project['title']) ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                <?php else: ?>
                                    <!-- Minimalist Sanatsal Geometrik Yer Tutucu -->
                                    <div class="w-full h-full flex flex-col items-center justify-center bg-gradient-to-br from-slate-900 via-[#0d1117] to-slate-950 p-6 text-center relative">
                                        <div class="w-10 h-10 rounded-lg bg-white/[0.05] border border-white/[0.08] flex items-center justify-center text-slate-400 group-hover:text-blue-400 transition-colors">
                                            <i class="fas fa-layer-group text-sm"></i>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Kategori Etiketi -->
                                <span class="absolute top-2.5 left-2.5 px-2 py-0.5 rounded-md text-[10px] font-medium tracking-wide bg-black/60 backdrop-blur-md border border-white/10 text-slate-300">
                                    <?=htmlspecialchars($project['category'] ?: 'Genel')?>
                                </span>
                            </div>

                            <!-- Kart Gövdesi -->
                            <div class="p-4 flex-1 flex flex-col justify-between space-y-3">
                                <div>
                                    <h2 class="text-sm font-semibold text-white group-hover:text-blue-400 transition-colors line-clamp-1">
                                        <?=htmlspecialchars($project['title'])?>
                                    </h2>
                                    <p class="text-xs text-slate-400 line-clamp-2 mt-1 leading-relaxed">
                                        <?=htmlspecialchars($project['description'] ?: 'Açıklama belirtilmemiş.')?>
                                    </p>
                                </div>

                                <!-- Alt Bilgi -->
                                <div class="pt-3 border-t border-white/[0.06] flex items-center justify-between text-[11px] text-slate-400">
                                    <div class="flex items-center gap-1.5 truncate">
                                        <span class="text-slate-500">@</span>
                                        <span class="text-slate-300 font-medium truncate"><?=htmlspecialchars($project['username'] ?? 'Anonim')?></span>
                                    </div>
                                    <span class="text-slate-500 font-mono text-[10px]"><?=timeAgo($project['upload_date'])?></span>
                                </div>
                            </div>

                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

    </main>

    <?php include 'footer.php'; ?>

    <script>
        // Kısayol: "/" tuşuna basıldığında aramaya odaklan
        document.addEventListener('keydown', (e) => {
            if (e.key === '/' && document.activeElement.tagName !== 'INPUT' && document.activeElement.tagName !== 'TEXTAREA') {
                e.preventDefault();
                const search = document.getElementById('mainSearchInput');
                if (search) search.focus();
            }
        });
    </script>
</body>
</html>