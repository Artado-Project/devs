<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
session_start();
require '../config.php'; 

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

if ($project_id === 0) { header('Location: index.php'); exit; }

try {
    $stmt = $db->prepare("SELECT p.*, pi.image_path, u.username AS uploader_username FROM projects p LEFT JOIN project_images pi ON p.id = pi.project_id LEFT JOIN users u ON p.user_id = u.id WHERE p.id = :id");
    $stmt->bindParam(':id', $project_id, PDO::PARAM_INT);
    $stmt->execute();
    $project = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$project) { header('Location: index.php?error=notfound'); exit; }
} catch (PDOException $e) { die("Hata!"); }

$file_download_path = !empty($project['file_path']) ? '../' . ltrim($project['file_path'], './') : null;
$image_display_path = !empty($project['image_path']) ? '../' . ltrim($project['image_path'], './') : null;
$image_exists = $image_display_path && file_exists($image_display_path);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($project['title']) ?> - Artado Workshop</title>
    <link rel="icon" type="image/x-icon" href="../logo.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&display=swap');
        
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            display: flex; flex-direction: column; min-height: 100vh;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        }
        main { flex: 1; }
        
        .hero-gradient {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 25%, #a855f7 50%, #c084fc 75%, #e879f9 100%);
            background-size: 200% 200%;
            animation: gradientShift 15s ease infinite;
        }
        
        @keyframes gradientShift {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }
        
        .glass-card {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 
                0 25px 50px -12px rgba(0, 0, 0, 0.12),
                0 20px 40px -12px rgba(99, 102, 241, 0.15),
                0 0 0 1px rgba(99, 102, 241, 0.05);
            border-radius: 2rem;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .glass-card:hover {
            transform: translateY(-4px);
            box-shadow: 
                0 35px 60px -12px rgba(0, 0, 0, 0.15),
                0 25px 50px -12px rgba(99, 102, 241, 0.2),
                0 0 0 1px rgba(99, 102, 241, 0.1);
        }

        .img-display {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border-radius: 1.5rem;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        
        .img-display:hover { 
            transform: scale(1.03) rotate(1deg);
            box-shadow: inset 0 4px 8px rgba(0, 0, 0, 0.08);
        }

        .download-btn {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.4);
        }
        
        .download-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(99, 102, 241, 0.5);
        }

        .category-badge {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            background-size: 200% 200%;
            animation: gradientShift 8s ease infinite;
        }

        .action-btn {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .action-btn:hover {
            transform: translateY(-2px) scale(1.05);
        }

        .ultra-compact-footer { 
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            padding: 30px 0; 
            border-top: 1px solid rgba(0, 0, 0, 0.05); 
            color: #64748b; 
        }
        
        .footer-wrap { 
            max-width: 1200px; 
            margin: 0 auto; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            padding: 0 20px; 
            flex-wrap: wrap; 
            gap: 20px; 
        }
        
        .brand-name { 
            font-weight: 800; 
            color: #1e293b; 
            font-size: 1.1rem; 
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .footer-nav-compact a { 
            color: #64748b; 
            font-size: 14px; 
            text-decoration: none; 
            transition: all 0.3s ease; 
            font-weight: 600; 
        }
        
        .footer-nav-compact a:hover { 
            color: #8b5cf6;
            transform: translateY(-2px);
        }

        /* Comment styles */
        .comment-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border: 1px solid rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }
        
        .comment-card:hover {
            transform: translateX(4px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            border-color: rgba(99, 102, 241, 0.2);
        }

        /* Rating stars animation */
        .rating-star {
            transition: all 0.2s ease;
        }
        
        .rating-star:hover {
            transform: scale(1.2) rotate(10deg);
        }

        /* Input focus effects */
        textarea:focus, input:focus {
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
        }
    </style>
</head>
<body>

    <main>
        <header class="hero-gradient text-white pt-8 pb-24">
            <div class="container mx-auto px-6">
                <nav class="flex justify-between items-center mb-12">
                    <a href="index.php" class="flex items-center gap-3 group">
                        <div class="bg-white/20 p-2 rounded-xl backdrop-blur-md group-hover:bg-white/30 transition-all">
                            <img src="https://artado.xyz/blog/wp-content/uploads/2025/09/artado-1.png" class="h-8" alt="Logo">
                        </div>
                        <span class="text-2xl font-extrabold tracking-tight">Workshop</span>
                    </a>
                    <?php if ($is_logged_in): ?>
                        <div class="flex items-center gap-3 bg-white/10 backdrop-blur-md px-4 py-2 rounded-2xl border border-white/20">
                            <img src="<?= !empty($user_avatar) ? '../'.$user_avatar : 'https://ui-avatars.com/api/?background=fff&color=8b5cf6&name='.$user_username ?>" class="w-8 h-8 rounded-full border-2 border-white/50">
                            <span class="font-bold text-sm"><?= $user_username ?></span>
                        </div>
                    <?php endif; ?>
                </nav>
            </div>
        </header>

        <div class="container mx-auto px-6 -mt-16 mb-20">
            <div class="glass-card overflow-hidden">
                <div class="flex flex-col lg:flex-row">
                    
                    <div class="lg:w-5/12 p-8 lg:p-12 bg-slate-50/50">
                        <div class="img-display aspect-square shadow-inner flex items-center justify-center p-6 mb-8 border border-slate-200">
                            <?php if ($image_exists): ?>
                                <img src="<?= htmlspecialchars($image_display_path) ?>" class="max-w-full max-h-full object-contain drop-shadow-2xl">
                            <?php else: ?>
                                <div class="text-center text-slate-300">
                                    <i class="fas fa-cubes text-7xl mb-4"></i>
                                    <p class="text-sm font-bold uppercase tracking-widest">Önizleme Yok</p>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($file_download_path): ?>
                            <a href="<?= htmlspecialchars($file_download_path) ?>" download class="flex items-center justify-center gap-3 w-full bg-indigo-600 text-white py-4 px-6 rounded-2xl font-bold text-lg shadow-xl shadow-indigo-200 hover:bg-indigo-700 hover:-translate-y-1 transition-all duration-300">
                                <i class="fas fa-cloud-arrow-down text-xl"></i>
                                Projeyi İndir
                            </a>
                        <?php endif; ?>
                    </div>

                    <div class="lg:w-7/12 p-8 lg:p-12">
                        <div class="flex items-center gap-2 mb-4">
                            <span class="category-badge px-3 py-1 rounded-lg text-white text-[11px] font-black uppercase tracking-tighter">
                                <?= htmlspecialchars($project['category'] ?: 'Genel') ?>
                            </span>
                        </div>
                        
                        <h1 class="text-4xl lg:text-5xl font-extrabold text-slate-900 mb-6 leading-tight bg-gradient-to-r from-slate-900 to-slate-700 bg-clip-text text-transparent">
                            <?= htmlspecialchars($project['title']) ?>
                        </h1>

                        <div class="flex items-center gap-6 mb-10 text-sm font-semibold text-slate-500">
                            <span class="flex items-center gap-2"><i class="fas fa-user text-indigo-500"></i> <?= htmlspecialchars($project['uploader_username']) ?></span>
                            <span class="flex items-center gap-2"><i class="far fa-calendar-alt text-indigo-500"></i> <?= date('d.m.Y', strtotime($project['upload_date'])) ?></span>
                        </div>

                        <div class="prose prose-slate max-w-none mb-10">
                            <h3 class="text-lg font-bold text-slate-800 mb-3 flex items-center gap-2">
                                <span class="w-1 h-6 bg-gradient-to-b from-indigo-500 to-purple-500 rounded-full"></span>
                                Proje Hakkında
                            </h3>
                            <p class="text-slate-600 leading-relaxed text-lg whitespace-pre-line">
                                <?= nl2br(htmlspecialchars($project['description'])) ?>
                            </p>
                        </div>

                        <div class="pt-8 border-t border-slate-100 flex items-center justify-between">
                            <a href="index.php" class="font-bold text-indigo-600 hover:text-indigo-800 flex items-center gap-2 transition-all">
                                <i class="fas fa-chevron-left"></i>
                                Geri Dön
                            </a>
                            <div class="flex gap-3">
                                <button id="likeBtn" onclick="toggleLike(<?= $project_id ?>)" class="action-btn w-12 h-12 rounded-2xl bg-slate-100 text-slate-500 flex items-center justify-center hover:bg-red-50 hover:text-red-500">
                                    <i id="likeIcon" class="far fa-heart text-xl"></i>
                                </button>
                                <button onclick="shareProject()" class="action-btn w-12 h-12 rounded-2xl bg-slate-100 text-slate-500 flex items-center justify-center hover:bg-indigo-50 hover:text-indigo-600">
                                    <i class="fas fa-share-nodes text-xl"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </main>

    <!-- Comments Section -->
    <section class="max-w-6xl mx-auto px-4 py-8">
        <div class="bg-white rounded-2xl shadow-xl p-8 border border-gray-100">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h3 class="text-3xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">
                        Comments & Reviews
                    </h3>
                    <p class="text-gray-500 mt-1">Share your thoughts about this project</p>
                </div>
                <div class="flex items-center gap-2">
                    <div class="bg-gradient-to-r from-indigo-500 to-purple-500 text-white px-4 py-2 rounded-full text-sm font-semibold">
                        <i class="fas fa-comments mr-2"></i>
                        <span id="commentCount">0</span> Comments
                    </div>
                </div>
            </div>
            
            <?php if ($is_logged_in): ?>
                <!-- Comment Form -->
                <div class="mb-8 bg-gradient-to-br from-indigo-50 to-purple-50 rounded-2xl p-6 border border-indigo-100">
                    <h4 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-pen-fancy mr-2 text-indigo-500"></i>
                        Write a Review
                    </h4>
                    <form id="commentForm" class="space-y-4">
                        <input type="hidden" name="project_id" value="<?= $project_id ?>">
                        
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Your Comment</label>
                            <textarea name="comment" rows="4" required
                                    class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-4 focus:ring-indigo-200 focus:border-indigo-500 focus:outline-none transition-all resize-none"
                                    placeholder="Share your thoughts about this project..."></textarea>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Rating</label>
                            <div class="flex space-x-3">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <button type="button" onclick="setRating(<?= $i ?>)" 
                                            class="rating-star text-3xl text-gray-300 hover:text-yellow-400 transition-all duration-200 transform hover:scale-125"
                                            data-rating="<?= $i ?>">
                                        <i class="fas fa-star"></i>
                                    </button>
                                <?php endfor; ?>
                            </div>
                            <input type="hidden" name="rating" id="ratingInput" value="0">
                        </div>
                        
                        <button type="submit" 
                                class="download-btn text-white px-8 py-3 rounded-xl font-semibold transition-all duration-300 flex items-center justify-center">
                            <i class="fas fa-paper-plane mr-2"></i>Post Comment
                        </button>
                    </form>
                </div>
            <?php else: ?>
                <div class="bg-gradient-to-r from-gray-50 to-gray-100 border-2 border-gray-200 rounded-2xl p-6 mb-6 text-center">
                    <div class="text-5xl text-gray-300 mb-4">
                        <i class="fas fa-lock"></i>
                    </div>
                    <p class="text-gray-600 text-lg mb-4">
                        Please <a href="../login.php" class="text-indigo-600 hover:text-indigo-800 font-bold">Login</a> to post a comment
                    </p>
                    <a href="../login.php" class="download-btn text-white px-6 py-2 rounded-lg inline-block">
                        Login Now
                    </a>
                </div>
            <?php endif; ?>
            
            <!-- Comments List -->
            <div id="commentsList" class="space-y-4">
                <?php
                // Fetch comments for this project
                try {
                    $stmt = $db->prepare("
                        SELECT wc.*, u.username, u.profile_photo 
                        FROM workshop_comments wc 
                        LEFT JOIN users u ON wc.user_id = u.id 
                        WHERE wc.project_id = ? AND wc.status = 'approved' 
                        ORDER BY wc.created_at DESC
                    ");
                    $stmt->execute([$project_id]);
                    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    if (empty($comments)): ?>
                    <div class="text-center py-12 bg-gray-50 rounded-2xl">
                        <div class="text-6xl text-gray-300 mb-4">
                            <i class="fas fa-comments"></i>
                        </div>
                        <p class="text-gray-500 text-lg font-medium">No comments yet. Be the first to share your thoughts!</p>
                    </div>
                <?php endif;
                    
                    foreach ($comments as $comment):
                        $avatar = !empty($comment['profile_photo']) ? '../' . ltrim($comment['profile_photo'], './') : 'https://ui-avatars.com/api/?background=6366f1&color=fff&name=' . urlencode($comment['username']);
                ?>
                    <div class="comment-card bg-gradient-to-br from-white to-gray-50 border border-gray-200 rounded-2xl p-6 transition-all duration-300">
                        <div class="flex items-start space-x-4">
                            <div class="relative">
                                <img src="<?= $avatar ?>" alt="<?= htmlspecialchars($comment['username']) ?>" 
                                     class="w-14 h-14 rounded-full border-4 border-indigo-100 shadow-lg">
                                <div class="absolute -bottom-1 -right-1 bg-indigo-500 text-white text-xs px-2 py-1 rounded-full">
                                    <?= $comment['rating'] ?> <i class="fas fa-star text-yellow-300 text-xs"></i>
                                </div>
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center justify-between mb-3">
                                    <div>
                                        <span class="font-bold text-gray-800 text-lg"><?= htmlspecialchars($comment['username']) ?></span>
                                        <span class="text-sm text-gray-500 ml-3 bg-gray-100 px-3 py-1 rounded-full">
                                            <?= date('M j, Y H:i', strtotime($comment['created_at'])) ?>
                                        </span>
                                    </div>
                                    <?php if ($comment['rating']): ?>
                                        <div class="flex text-yellow-400 space-x-1">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star <?= $i <= $comment['rating'] ? '' : 'text-gray-300' ?>"></i>
                                            <?php endfor; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <p class="text-gray-700 leading-relaxed text-base"><?= nl2br(htmlspecialchars($comment['comment'])) ?></p>
                            </div>
                        </div>
                    </div>
                <?php 
                    endforeach;
                } catch (PDOException $e) {
                    echo '<div class="bg-red-50 border border-red-200 rounded-xl p-4 text-red-600 text-center">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        Error loading comments. Please try again later.
                    </div>';
                }
                ?>
            </div>
        </div>
    </section>

    <footer class="ultra-compact-footer">
        <div class="footer-wrap">
            <div class="footer-left flex items-center gap-4">
                <img src="https://artado.xyz/blog/wp-content/uploads/2025/09/artado-1.png" alt="Logo" class="h-8">
                <span class="brand-name">Artado</span>
            </div>
            <nav class="footer-nav-compact flex gap-6">
                <a href="index.php">Ana Sayfa</a>
                <a href="https://forum.artado.xyz">Forum</a>
                <a href="#">Destekçiler</a>
                <a href="#">Gizlilik</a>
            </nav>
            <div class="footer-right font-bold text-sm">
                <span>&copy; 2026 Artado</span>
                <span class="mx-2 opacity-20">|</span>
                <span>Oyunlayıcı</span>
            </div>
        </div>
    </footer>

<script>
// Sayfa yüklendiğinde beğeni kontrolü
document.addEventListener('DOMContentLoaded', function() {
    const projectId = <?= $project_id ?>;
    if (getCookie('liked_project_' + projectId)) {
        applyLikeStyles();
    }
});

// BEĞENİ SİSTEMİ
function toggleLike(id) {
    const cookieName = 'liked_project_' + id;
    if (getCookie(cookieName)) {
        deleteCookie(cookieName);
        removeLikeStyles();
    } else {
        setCookie(cookieName, 'true', 365);
        applyLikeStyles();
    }
}

function applyLikeStyles() {
    const btn = document.getElementById('likeBtn');
    const icon = document.getElementById('likeIcon');
    if (btn && icon) {
        btn.classList.add('bg-red-50', 'text-red-500');
        btn.classList.remove('bg-slate-100', 'text-slate-500');
        icon.classList.replace('far', 'fas');
    }
}

function removeLikeStyles() {
    const btn = document.getElementById('likeBtn');
    const icon = document.getElementById('likeIcon');
    if (btn && icon) {
        btn.classList.remove('bg-red-50', 'text-red-500');
        btn.classList.add('bg-slate-100', 'text-slate-500');
        icon.classList.replace('fas', 'far');
    }
}

// PAYLAŞMA SİSTEMİ
async function shareProject() {
    const shareData = {
        title: '<?= addslashes(htmlspecialchars($project['title'])) ?>',
        text: 'Artado Workshop üzerindeki bu projeyi incele!',
        url: window.location.href
    };

    try {
        if (navigator.share) {
            // Mobil ve destekleyen tarayıcılar için sistem paylaşımı
            await navigator.share(shareData);
        } else {
            // Masaüstü için panoya kopyalama
            await navigator.clipboard.writeText(window.location.href);
            alert('Proje bağlantısı panoya kopyalandı!');
        }
    } catch (err) {
        console.error('Paylaşım hatası:', err);
    }
}

// COMMENT SYSTEM
let currentRating = 0;

function setRating(rating) {
    currentRating = rating;
    document.getElementById('ratingInput').value = rating;
    
    // Update star display
    const stars = document.querySelectorAll('.rating-star');
    stars.forEach((star, index) => {
        if (index < rating) {
            star.classList.remove('text-gray-300');
            star.classList.add('text-yellow-400');
        } else {
            star.classList.remove('text-yellow-400');
            star.classList.add('text-gray-300');
        }
    });
}

// Handle comment form submission
document.addEventListener('DOMContentLoaded', function() {
    const commentForm = document.getElementById('commentForm');
    if (commentForm) {
        commentForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'submit_comment');
            
            fetch('comment_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Clear form
                    this.reset();
                    currentRating = 0;
                    document.getElementById('ratingInput').value = '0';
                    
                    // Reset stars
                    document.querySelectorAll('.rating-star').forEach(star => {
                        star.classList.remove('text-yellow-400');
                        star.classList.add('text-gray-300');
                    });
                    
                    // Show success message
                    alert('Comment submitted successfully! It will be visible after admin approval.');
                    
                    // Reload comments after a delay
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while submitting your comment.');
            });
        });
    }
});

// COOKIE YARDIMCILARI
function setCookie(name, value, days) {
    const d = new Date();
    d.setTime(d.getTime() + (days * 24 * 60 * 60 * 1000));
    document.cookie = name + "=" + value + ";expires=" + d.toUTCString() + ";path=/";
}

function getCookie(name) {
    const value = "; " + document.cookie;
    const parts = value.split("; " + name + "=");
    if (parts.length === 2) return parts.pop().split(";").shift();
}

function deleteCookie(name) {
    document.cookie = name + '=; Max-Age=-99999999;path=/;';
}
</script>
</body>
</html>