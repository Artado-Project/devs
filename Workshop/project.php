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

$project_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
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

if ($project_id === 0) { 
    header('Location: index.php'); 
    exit; 
}

try {
    $stmt = $db->prepare("
        SELECT p.*, pi.image_path, u.username AS uploader_username, u.avatar AS uploader_avatar 
        FROM projects p 
        LEFT JOIN project_images pi ON p.id = pi.project_id 
        LEFT JOIN users u ON p.user_id = u.id 
        WHERE p.id = :id
    ");
    $stmt->bindParam(':id', $project_id, PDO::PARAM_INT);
    $stmt->execute();
    $project = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$project) { 
        header('Location: index.php?error=notfound'); 
        exit; 
    }
} catch (PDOException $e) { 
    die("Hata: " . $e->getMessage()); 
}

$file_download_path = !empty($project['file_path']) ? '../' . ltrim($project['file_path'], './') : null;
$image_display_path = !empty($project['image_path']) ? '../' . ltrim($project['image_path'], './') : null;
$image_exists = $image_display_path && file_exists($image_display_path);

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
    <title><?= htmlspecialchars($project['title']) ?> — Artado Workshop</title>
    <meta name="description" content="<?= htmlspecialchars(mb_substr(strip_tags($project['description'] ?? ''), 0, 160)) ?>">
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
            background-image: radial-gradient(circle at 50% -20%, rgba(37, 99, 235, 0.1) 0%, transparent 60%);
            background-repeat: no-repeat;
        }

        .surface-card {
            background: var(--surface-1);
            border: 1px solid var(--border-subtle);
        }

        .image-modal-backdrop {
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(12px);
        }
    </style>
</head>
<body class="min-h-screen flex flex-col antialiased selection:bg-blue-600 selection:text-white">

    <!-- Global Header -->
    <header class="sticky top-0 z-50 border-b border-white/[0.07] bg-[#090a0f]/80 backdrop-blur-xl">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between gap-4">
            
            <div class="flex items-center gap-6">
                <a href="index.php" class="flex items-center gap-3 group">
                    <img src="https://devs.artado.xyz/homepage/images/logo.png" alt="Artado" class="w-7 h-7 object-contain transition-transform group-hover:scale-105">
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-semibold text-white tracking-tight">Artado</span>
                        <span class="text-xs text-slate-500 font-mono">/</span>
                        <span class="text-xs font-medium text-slate-300">Workshop</span>
                    </div>
                </a>
            </div>

            <div class="flex items-center gap-3">
                <a href="index.php" class="px-3 py-1.5 rounded-lg bg-white/[0.06] hover:bg-white/[0.1] text-slate-300 hover:text-white text-xs font-medium border border-white/[0.06] transition-all flex items-center gap-2">
                    <i class="fas fa-arrow-left text-[10px]"></i> Tüm Projeler
                </a>
            </div>

        </div>
    </header>

    <!-- Ana İçerik -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 py-8 space-y-8">

        <!-- Proje Başlık Bölümü -->
        <section class="border-b border-white/[0.07] pb-8 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="space-y-3">
                <div class="flex items-center gap-2.5 text-xs text-slate-400">
                    <a href="index.php?category=<?=urlencode($project['category'] ?? '')?>" class="px-2.5 py-1 rounded-md bg-white/[0.05] border border-white/[0.08] text-slate-300 hover:text-white transition-colors font-medium">
                        <?=htmlspecialchars($project['category'] ?: 'Genel')?>
                    </a>
                    <span class="text-slate-600">•</span>
                    <span><?=date('d.m.Y', strtotime($project['upload_date']))?></span>
                </div>

                <h1 class="text-2xl sm:text-3xl font-bold text-white tracking-tight">
                    <?=htmlspecialchars($project['title'])?>
                </h1>

                <div class="flex items-center gap-2 text-xs text-slate-400">
                    <div class="w-5 h-5 rounded-full bg-slate-800 border border-white/10 flex items-center justify-center text-[10px] font-semibold text-white">
                        <?=substr($project['uploader_username'] ?? 'A', 0, 1)?>
                    </div>
                    <span>Geliştirici: <strong class="text-slate-200"><?=htmlspecialchars($project['uploader_username'] ?? 'Anonim')?></strong></span>
                </div>
            </div>

            <!-- Eylemler -->
            <div class="flex items-center gap-3 self-start md:self-auto flex-wrap">
                <?php if ($file_download_path): ?>
                    <a href="<?=htmlspecialchars($file_download_path)?>" download class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs font-semibold flex items-center gap-2 shadow-lg shadow-blue-600/20 transition-all">
                        <i class="fas fa-download text-xs"></i> Projeyi İndir
                    </a>
                <?php endif; ?>

                <button id="likeBtn" class="w-9 h-9 rounded-xl bg-white/[0.06] hover:bg-white/[0.1] border border-white/[0.08] text-slate-400 flex items-center justify-center text-xs transition-all hover:text-rose-400" title="Beğen">
                    <i id="likeIcon" class="far fa-heart"></i>
                </button>

                <button id="shareBtn" class="px-3 py-2 rounded-xl bg-white/[0.06] hover:bg-white/[0.1] border border-white/[0.08] text-slate-300 text-xs font-medium flex items-center gap-1.5 transition-all" title="Bağlantıyı Kopyala">
                    <i class="fas fa-link text-[11px]"></i> Paylaş
                </button>
            </div>
        </section>

        <!-- 2 Sütunlu Detay Düzeni -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
            
            <!-- Sol / Ana Bölüm (2 Sütun) -->
            <div class="lg:col-span-2 space-y-8">
                
                <!-- Görsel Vitrini -->
                <?php if ($image_exists): ?>
                    <div class="surface-card rounded-2xl overflow-hidden group cursor-pointer relative" id="previewImageWrapper">
                        <img src="<?=htmlspecialchars($image_display_path)?>" alt="<?=htmlspecialchars($project['title'])?>" class="w-full max-h-[460px] object-contain bg-[#07090e] mx-auto transition-transform duration-300 group-hover:scale-[1.01]">
                        <div class="absolute bottom-3 right-3 px-2.5 py-1 rounded-lg bg-black/60 backdrop-blur-md border border-white/10 text-[11px] text-slate-300 opacity-0 group-hover:opacity-100 transition-opacity flex items-center gap-1.5">
                            <i class="fas fa-expand text-[10px]"></i> Büyüt
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Proje Açıklaması -->
                <div class="surface-card rounded-2xl p-6 sm:p-7 space-y-4">
                    <h2 class="text-sm font-semibold uppercase tracking-wider text-slate-400">Çalışma Hakkında</h2>
                    <div class="text-sm text-slate-300 leading-relaxed whitespace-pre-line">
                        <?=htmlspecialchars($project['description'] ?: 'Bu çalışma için özel bir detay açıklaması belirtilmemiş.')?>
                    </div>
                </div>

                <!-- Değerlendirmeler & Yorumlar -->
                <div class="surface-card rounded-2xl p-6 sm:p-7 space-y-6">
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-semibold uppercase tracking-wider text-slate-400">Değerlendirmeler</h2>
                    </div>

                    <?php if ($is_logged_in): ?>
                        <!-- Yorum Formu -->
                        <form id="commentForm" class="space-y-4 bg-white/[0.02] border border-white/[0.06] p-4 rounded-xl">
                            <input type="hidden" name="action" value="submit_comment">
                            <input type="hidden" name="project_id" value="<?=$project_id?>">
                            <input type="hidden" name="rating" id="ratingInput" value="5">

                            <div class="flex items-center justify-between">
                                <span class="text-xs text-slate-400 font-medium">Puan Verin:</span>
                                <div class="flex items-center gap-1" id="starContainer">
                                    <?php for($i = 1; $i <= 5; $i++): ?>
                                        <button type="button" data-val="<?=$i?>" class="star-btn text-amber-400 hover:scale-110 text-xs transition-transform focus:outline-none">
                                            <i class="fas fa-star"></i>
                                        </button>
                                    <?php endfor; ?>
                                </div>
                            </div>

                            <textarea name="comment" rows="3" required placeholder="Görüşlerinizi ve incelemenizi paylaşın..." class="w-full bg-[#090a0f] border border-white/[0.08] rounded-lg p-3 text-xs text-slate-200 placeholder-slate-500 focus:outline-none focus:border-white/20 transition-all resize-none"></textarea>

                            <div class="flex justify-end">
                                <button type="submit" id="submitBtn" class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white text-xs font-semibold rounded-lg transition-all shadow-sm">
                                    Yorumu İlet
                                </button>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="p-4 rounded-xl bg-white/[0.02] border border-white/[0.06] flex items-center justify-between gap-4 text-xs text-slate-400">
                            <span>Değerlendirme bırakmak için lütfen oturum açın.</span>
                            <a href="https://devs.artado.xyz/login.php" class="px-3 py-1.5 bg-white/[0.08] hover:bg-white/[0.12] text-white font-medium rounded-lg transition-colors">Giriş</a>
                        </div>
                    <?php endif; ?>

                    <!-- Yorum Akışı -->
                    <div id="commentsFlow" class="space-y-3 pt-2">
                        <?php
                        try {
                            $stmt = $db->prepare("
                                SELECT wc.*, u.username 
                                FROM workshop_comments wc 
                                LEFT JOIN users u ON wc.user_id = u.id 
                                WHERE wc.project_id = ? AND wc.status = 'approved' 
                                ORDER BY wc.created_at DESC
                            ");
                            $stmt->execute([$project_id]);
                            $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

                            if (empty($comments)):
                        ?>
                            <div id="noCommentsBox" class="py-6 text-center text-xs text-slate-500 italic">
                                Henüz bir değerlendirme yapılmamış.
                            </div>
                        <?php
                            else:
                                foreach ($comments as $cm):
                                    $cmName = htmlspecialchars($cm['username'] ?? 'Anonim');
                                    $rating = (int)($cm['rating'] ?? 5);
                        ?>
                            <div class="p-3.5 rounded-xl bg-white/[0.02] border border-white/[0.05] space-y-2 text-xs">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <span class="font-semibold text-slate-200"><?=$cmName?></span>
                                        <span class="text-amber-400 text-[10px]">
                                            <?php for($s=1; $s<=5; $s++): ?>
                                                <i class="<?=$s<=$rating?'fas':'far'?> fa-star"></i>
                                            <?php endfor; ?>
                                        </span>
                                    </div>
                                    <span class="text-[10px] text-slate-500 font-mono"><?=date('d.m.Y', strtotime($cm['created_at']))?></span>
                                </div>
                                <p class="text-slate-300 leading-relaxed"><?=nl2br(htmlspecialchars($cm['comment']))?></p>
                            </div>
                        <?php
                                endforeach;
                            endif;
                        } catch (Exception $e) {
                            echo '<p class="text-xs text-rose-400">Yorumlar yüklenemedi.</p>';
                        }
                        ?>
                    </div>
                </div>

            </div>

            <!-- Sağ / Meta Sidebar (1 Sütun) -->
            <div class="space-y-6">
                
                <div class="surface-card rounded-2xl p-5 space-y-4">
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-400">Proje Detayları</h3>
                    
                    <dl class="divide-y divide-white/[0.06] text-xs">
                        <div class="py-2.5 flex items-center justify-between">
                            <dt class="text-slate-400">Kategori</dt>
                            <dd class="text-slate-200 font-medium"><?=htmlspecialchars($project['category'] ?: 'Genel')?></dd>
                        </div>
                        <div class="py-2.5 flex items-center justify-between">
                            <dt class="text-slate-400">Yüklenme Tarihi</dt>
                            <dd class="text-slate-200 font-mono"><?=date('d.m.Y', strtotime($project['upload_date']))?></dd>
                        </div>
                        <div class="py-2.5 flex items-center justify-between">
                            <dt class="text-slate-400">Geliştirici</dt>
                            <dd class="text-slate-200 font-medium">@<?=htmlspecialchars($project['uploader_username'] ?? 'Anonim')?></dd>
                        </div>
                        <div class="py-2.5 flex items-center justify-between">
                            <dt class="text-slate-400">Durum</dt>
                            <dd class="text-emerald-400 font-medium flex items-center gap-1.5">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span> Yayında
                            </dd>
                        </div>
                    </dl>

                    <?php if ($file_download_path): ?>
                        <div class="pt-2">
                            <a href="<?=htmlspecialchars($file_download_path)?>" download class="w-full py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs font-semibold flex items-center justify-center gap-2 transition-all shadow-md shadow-blue-600/20">
                                <i class="fas fa-download text-xs"></i> İndir
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

            </div>

        </div>

    </main>

    <!-- Lightbox Modal -->
    <?php if ($image_exists): ?>
        <div id="imageModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 image-modal-backdrop opacity-0 pointer-events-none transition-opacity duration-200">
            <div class="relative max-w-5xl max-h-[90vh]">
                <button id="closeModalBtn" class="absolute -top-10 right-0 w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition-colors">
                    <i class="fas fa-times text-xs"></i>
                </button>
                <img src="<?=htmlspecialchars($image_display_path)?>" alt="Preview" class="max-w-full max-h-[85vh] object-contain rounded-xl shadow-2xl border border-white/10">
            </div>
        </div>
    <?php endif; ?>

    <?php include 'footer.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const projectId = <?=$project_id?>;
            const cookieName = 'liked_project_' + projectId;
            const likeIcon = document.getElementById('likeIcon');
            const likeBtn = document.getElementById('likeBtn');
            const shareBtn = document.getElementById('shareBtn');

            // Beğeni
            if (getCookie(cookieName)) {
                likeIcon.classList.remove('far');
                likeIcon.classList.add('fas', 'text-rose-500');
            }

            if(likeBtn) {
                likeBtn.addEventListener('click', function() {
                    if (getCookie(cookieName)) {
                        deleteCookie(cookieName);
                        likeIcon.classList.remove('fas', 'text-rose-500');
                        likeIcon.classList.add('far');
                        if(typeof showToast === 'function') showToast('Beğeni geri alındı', 'info');
                    } else {
                        setCookie(cookieName, 'true', 365);
                        likeIcon.classList.remove('far');
                        likeIcon.classList.add('fas', 'text-rose-500');
                        if(typeof showToast === 'function') showToast('Proje beğenildi!', 'success');
                    }
                });
            }

            // Paylaş
            if(shareBtn) {
                shareBtn.addEventListener('click', function() {
                    if (navigator.clipboard) {
                        navigator.clipboard.writeText(window.location.href).then(() => {
                            if(typeof showToast === 'function') showToast('Bağlantı kopyalandı', 'success');
                        });
                    }
                });
            }

            // Yıldız Seçimi
            const stars = document.querySelectorAll('.star-btn');
            const ratingInput = document.getElementById('ratingInput');
            stars.forEach(btn => {
                btn.addEventListener('click', function() {
                    const val = parseInt(this.getAttribute('data-val'));
                    if(ratingInput) ratingInput.value = val;
                    stars.forEach(s => {
                        const sVal = parseInt(s.getAttribute('data-val'));
                        if(sVal <= val) {
                            s.classList.remove('text-slate-600');
                            s.classList.add('text-amber-400');
                        } else {
                            s.classList.remove('text-amber-400');
                            s.classList.add('text-slate-600');
                        }
                    });
                });
            });

            // Yorum Gönderme
            const commentForm = document.getElementById('commentForm');
            const submitBtn = document.getElementById('submitBtn');
            if (commentForm) {
                commentForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    if(submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.textContent = 'İletiliyor...';
                    }

                    const formData = new FormData(this);
                    fetch('comment_handler.php', { method: 'POST', body: formData })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            if(typeof showToast === 'function') showToast(data.message, 'success');
                            commentForm.reset();
                            const flow = document.getElementById('commentsFlow');
                            const placeholder = document.getElementById('noCommentsBox');
                            if (placeholder) placeholder.remove();

                            if (flow && data.comment) {
                                const newEl = document.createElement('div');
                                newEl.className = "p-3.5 rounded-xl bg-white/[0.02] border border-white/[0.05] space-y-2 text-xs";
                                let starIcons = '';
                                for(let s=1; s<=5; s++) starIcons += `<i class="${s <= data.comment.rating ? 'fas' : 'far'} fa-star"></i>`;
                                newEl.innerHTML = `
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <span class="font-semibold text-slate-200">${data.comment.username}</span>
                                            <span class="text-amber-400 text-[10px]">${starIcons}</span>
                                        </div>
                                        <span class="text-[10px] text-slate-500 font-mono">${data.comment.created_at}</span>
                                    </div>
                                    <p class="text-slate-300 leading-relaxed">${data.comment.comment}</p>
                                `;
                                flow.insertBefore(newEl, flow.firstChild);
                            }
                        } else {
                            if(typeof showToast === 'function') showToast(data.message, 'error');
                        }
                    })
                    .catch(() => {
                        if(typeof showToast === 'function') showToast('İletim hatası oluştu', 'error');
                    })
                    .finally(() => {
                        if(submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.textContent = 'Yorumu İlet';
                        }
                    });
                });
            }

            // Lightbox
            const imgWrapper = document.getElementById('previewImageWrapper');
            const imgModal = document.getElementById('imageModal');
            const closeModalBtn = document.getElementById('closeModalBtn');

            if(imgWrapper && imgModal) {
                imgWrapper.addEventListener('click', () => {
                    imgModal.classList.remove('opacity-0', 'pointer-events-none');
                    imgModal.classList.add('opacity-100');
                });
            }
            if(closeModalBtn && imgModal) {
                closeModalBtn.addEventListener('click', () => {
                    imgModal.classList.remove('opacity-100');
                    imgModal.classList.add('opacity-0', 'pointer-events-none');
                });
            }
            if(imgModal) {
                imgModal.addEventListener('click', (e) => {
                    if (e.target === imgModal) {
                        imgModal.classList.remove('opacity-100');
                        imgModal.classList.add('opacity-0', 'pointer-events-none');
                    }
                });
            }
        });

        function setCookie(name, value, days) {
            const d = new Date(); d.setTime(d.getTime() + (days * 24 * 60 * 60 * 1000));
            document.cookie = name + "=" + value + ";expires=" + d.toUTCString() + ";path=/";
        }
        function getCookie(name) {
            const value = "; " + document.cookie; const parts = value.split("; " + name + "=");
            if (parts.length === 2) return parts.pop().split(";").shift();
        }
        function deleteCookie(name) { document.cookie = name + '=;expires=Thu, 01 Jan 1970 00:00:01 GMT;path=/;'; }
    </script>
</body>
</html>