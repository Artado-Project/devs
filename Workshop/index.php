<?php
session_start();
require '../config.php'; 

if (!defined('BASE_URL')) {
    define('BASE_URL', '/'); 
}

// Filtreleme ve Arama Parametreleri
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? trim($_GET['category']) : '';

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
        // SQL'de "Tema" araması "Temalar" ile eşleşsin diye LIKE kullandık
        $where_clauses[] = "p.category LIKE :category";
        $params[':category'] = '%' . $category_filter . '%';
    }

    if (!empty($where_clauses)) {
        $sql .= " WHERE " . implode(" AND ", $where_clauses);
    }

    $sql .= " GROUP BY p.id, p.title, p.description, p.upload_date, p.category, u.username ORDER BY p.upload_date DESC";
    
    $stmt = $db->prepare($sql);
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->execute();
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { die("Veritabanı hatası: " . $e->getMessage()); }
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Artado Workshop</title>
    <link rel="icon" type="image/x-icon" href="https://raw.githubusercontent.com/Artado-Project/devs/refs/heads/main/ArtadoDevs/images/favicon.ico">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        html, body { height: 100%; margin: 0; }
        body { display: flex; flex-direction: column; background-color: #f9fafb; font-family: sans-serif; }
        main { flex: 1 0 auto; }
        footer { flex-shrink: 0; }

        .project-card {
            display: flex; flex-direction: column; height: 100%;
            background: white; border-radius: 1.25rem; overflow: hidden;
            transition: all 0.3s ease; border: 1px solid #f1f5f9;
        }
        .project-card:hover { transform: translateY(-8px); box-shadow: 0 12px 24px -8px rgba(0,0,0,0.15); }

        .img-container {
            width: 100%; aspect-ratio: 16 / 10; overflow: hidden;
            background: #f8fafc; display: flex; align-items: center; justify-content: center; padding: 10px;
        }
        .img-container img { max-width: 100%; max-height: 100%; object-fit: contain; border-radius: 8px; }

        .hero-gradient { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }

        .ultra-compact-footer { background: #fff; padding: 25px 0; border-top: 1px solid #f1f5f9; color: #64748b; }
        .footer-wrap { max-width: 1200px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; padding: 0 20px; flex-wrap: wrap; gap: 20px; }
        .footer-left { display: flex; align-items: center; gap: 12px; }
        .footer-left img { height: 28px; width: auto; }
        .brand-name { font-weight: 700; color: #1e293b; font-size: 1.1rem; }
        
        .footer-nav-compact { display: flex; gap: 20px; }
        .footer-nav-compact a { color: #64748b; font-size: 14px; transition: color 0.2s; text-decoration: none; }
        .footer-nav-compact a:hover { color: #764ba2; }

        .compact-socials a { margin-left: 12px; color: #94a3b8; transition: color 0.2s; font-size: 1rem; }
        .compact-socials a:hover { color: #1e293b; }

        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

        #backToTop {
            position: fixed; bottom: 30px; right: 30px; background: #764ba2; color: white;
            width: 45px; height: 45px; border-radius: 12px; border: none; cursor: pointer; 
            display: none; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(118, 75, 162, 0.3);
            z-index: 100;
        }
    </style>
</head>
<body>

    <main>
        <section class="hero-gradient text-white py-16">
            <div class="container mx-auto px-4">
                <div class="max-w-2xl">
                    <h1 class="text-5xl font-bold mb-4">Artado Workshop</h1>
                    <p class="text-xl text-purple-100 opacity-90">Açık Kaynak Proje Platformu</p>
                    <div class="mt-8 flex gap-4">
                        <a href="#projects" class="bg-white text-purple-700 px-8 py-3 rounded-xl font-bold hover:bg-purple-50 transition-all text-decoration-none">Keşfet</a>
                        <?php if($is_logged_in): ?>
                            <a href="../user/create-project.php" class="bg-white/20 backdrop-blur-md text-white border border-white/20 px-8 py-3 rounded-xl font-bold hover:bg-white/30 transition-all text-decoration-none">Proje Ekle</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>

        <section class="container mx-auto px-4 -mt-10 mb-12">
            <div class="bg-white p-2 rounded-2xl shadow-xl border border-slate-100">
                <div class="flex flex-col md:flex-row items-center gap-2">
                    <div class="flex-1 relative w-full group">
                        <form method="GET" action="index.php" id="searchForm">
                            <input type="text" name="search" placeholder="Proje ara..." value="<?=htmlspecialchars($search_term)?>" class="w-full pl-12 pr-4 py-4 rounded-xl border-none focus:ring-0 text-slate-600 bg-transparent outline-none">
                            <i class="fas fa-search absolute left-5 top-1/2 -translate-y-1/2 text-slate-300"></i>
                            <?php if(!empty($category_filter)): ?>
                                <input type="hidden" name="category" value="<?=htmlspecialchars($category_filter)?>">
                            <?php endif; ?>
                        </form>
                    </div>
                    
                    <div class="flex items-center gap-4 px-4 h-12">
                        <?php if ($is_logged_in): ?>
                            <div class="flex items-center gap-3 bg-slate-50/50 pr-3 pl-1 py-1 rounded-full border border-slate-100">
                                <div class="w-8 h-8 rounded-full bg-purple-600 flex items-center justify-center text-white text-xs font-bold uppercase overflow-hidden">
                                    <?php if(!empty($user_avatar)): ?>
                                        <img src="../<?=$user_avatar?>" class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <?=substr($user_username, 0, 2)?>
                                    <?php endif; ?>
                                </div>
                                <span class="font-bold text-slate-700 text-sm"><?=$user_username?></span>
                            </div>
                        <?php else: ?>
                            <a href="https://devs.artado.xyz/login.php" class="bg-purple-600 text-white px-6 py-2 rounded-lg font-bold text-sm">Giriş</a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="flex items-center gap-2 p-2 overflow-x-auto mt-1 no-scrollbar">
                    <?php
                    // Görseldeki isimlere göre güncellendi
                    $categories = [
                        '' => 'Hepsi',
                        'Eklenti' => 'Eklentiler',
                        'Tema' => 'Temalar',
                        'Logo' => 'Logolar',
                        'Yazılım' => 'Yazılımlar'
                    ];
                    foreach ($categories as $val => $label):
                        $isActive = ($category_filter === $val);
                        $params = [];
                        if(!empty($search_term)) $params['search'] = $search_term;
                        if($val !== '') $params['category'] = $val;
                        $url = "index.php?" . http_build_query($params);
                    ?>
                        <a href="<?=$url?>" class="px-5 py-2 rounded-full text-xs font-bold transition-all <?=$isActive ? 'bg-purple-600 text-white' : 'bg-slate-50 text-slate-500 hover:bg-slate-100'?>">
                            <?=$label?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section id="projects" class="container mx-auto px-4 pb-20">
            <?php if (empty($projects)): ?>
                <div class="bg-white rounded-[2rem] border border-dashed border-slate-200 py-24 flex flex-col items-center justify-center text-center">
                    <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mb-6">
                        <i class="fas fa-search text-3xl text-slate-200"></i>
                    </div>
                    <p class="text-slate-400 font-medium">Aradığınız kriterlere uygun proje bulunamadı.</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
                    <?php foreach ($projects as $project): ?>
                        <div class="project-card">
                            <div class="img-container">
                                <?php 
                                $img = $project['image_path'];
                                $display_img = (!empty($img) && file_exists('../'.$img)) ? '../'.$img : $img;
                                ?>
                                <img src="<?=!empty($display_img) ? $display_img : 'https://via.placeholder.com/400x250?text=Görsel+Yok'?>" alt="Proje">
                            </div>
                            <div class="p-6 flex-1 flex flex-col">
                                <span class="bg-purple-50 text-purple-600 text-[10px] font-black px-2 py-1 rounded-md uppercase tracking-wider w-fit mb-3">
                                    <?=htmlspecialchars($project['category'] ?: 'Genel')?>
                                </span>
                                <h3 class="font-bold text-slate-800 text-lg mb-2 line-clamp-1"><?=htmlspecialchars($project['title'])?></h3>
                                <p class="text-slate-500 text-sm mb-6 line-clamp-2 leading-relaxed"><?=htmlspecialchars($project['description'])?></p>
                                
                                <div class="mt-auto pt-4 border-t border-slate-50 flex justify-between items-center text-[11px] font-medium text-slate-400">
                                    <span class="flex items-center gap-1.5"><i class="fas fa-user-circle text-slate-300"></i><?=htmlspecialchars($project['username'])?></span>
                                    <span><?=date('d.m.Y', strtotime($project['upload_date']))?></span>
                                </div>
                                <a href="project.php?id=<?=$project['id']?>" class="mt-4 bg-slate-900 text-white text-center py-2.5 rounded-xl font-bold hover:bg-purple-600 transition-colors text-decoration-none">Detayları Gör</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <footer class="ultra-compact-footer">
        <div class="footer-wrap">
            <div class="footer-left">
                <img src="https://artado.xyz/blog/wp-content/uploads/2025/09/artado-1.png" alt="Logo">
                <span class="brand-name">Artado</span>
                <div class="compact-socials">
                    <a href="https://discord.com/invite/WXCsr8zTN6"><i class="fab fa-discord"></i></a>
                    <a href="https://github.com/Artado-Project"><i class="fab fa-github"></i></a>
                </div>
            </div>
            
            <div class="footer-center hidden md:flex">
                <nav class="footer-nav-compact">
                    <a href="<?php echo BASE_URL; ?>">Ana Sayfa</a>
                    <a href="<?php echo BASE_URL; ?>katki.php">Destekçiler</a>
                    <a href="https://forum.artado.xyz">Forum</a>
                    <a href="https://myacc.artado.xyz/privacy">Gizlilik</a>
                </nav>
            </div>
            
            <div class="footer-right">
                <div class="right-info text-xs">
                   <span>&copy; <?php echo date('Y'); ?> Artado</span>
                   <span class="mx-1 opacity-30">|</span>
                   <span class="font-bold text-slate-600">Oyunlayıcı</span>
                </div>
            </div>
        </div>
    </footer>

    <button id="backToTop"><i class="fas fa-arrow-up"></i></button>

    <script>
        const btt = document.getElementById('backToTop');
        window.onscroll = () => {
            if(btt) btt.style.display = window.scrollY > 400 ? 'flex' : 'none';
        };
        if(btt) btt.onclick = () => window.scrollTo({top: 0, behavior: 'smooth'});
    </script>
</body>
</html>