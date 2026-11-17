<?php
require_once 'includes/database.php';
require_once 'includes/auth.php';

// Başarı ve hata mesajlarını tanımla
$success_message = '';
$error_message = '';

// Kullanıcının giriş yapmış olması gerekiyor
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Kullanıcı bilgilerini çek
$stmt = $db->prepare("SELECT * FROM users WHERE id = :user_id");
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Katkı formu gönderildiyse
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_contribution'])) {
    $contribution_type = trim($_POST['contribution_type'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $github_link = trim($_POST['github_link'] ?? '');
    $contact_email = trim($_POST['contact_email'] ?? $user['email']);
    
    // Validasyon
    if (empty($contribution_type) || empty($title) || empty($description)) {
        $error_message = "Lütfen zorunlu alanları doldurun.";
    } else {
        try {
            // Katkıyı veritabanına kaydet
            $stmt = $db->prepare("
                INSERT INTO contributions (user_id, contribution_type, title, description, github_link, contact_email, status, created_at) 
                VALUES (:user_id, :contribution_type, :title, :description, :github_link, :contact_email, 'pending', NOW())
            ");
            
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':contribution_type', $contribution_type);
            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':github_link', $github_link);
            $stmt->bindParam(':contact_email', $contact_email);
            
            if ($stmt->execute()) {
                $success_message = "Katkı başvurunuz başarıyla gönderildi! En kısa sürede incelenecektir.";
            } else {
                $error_message = "Katkı gönderilirken bir hata oluştu.";
            }
        } catch (PDOException $e) {
            $error_message = "Veritabanı hatası: " . $e->getMessage();
        }
    }
}

// Mevcut katkıları çek
try {
    $stmt = $db->prepare("
        SELECT c.*, u.username 
        FROM contributions c 
        JOIN users u ON c.user_id = u.id 
        WHERE c.user_id = :user_id 
        ORDER BY c.created_at DESC
    ");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $contributions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $contributions = [];
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Katkıda Bulun - Artado Developers</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .fade-in {
            animation: fadeIn 0.6s ease-out;
        }
        
        .contribution-card {
            transition: all 0.3s ease;
        }
        
        .contribution-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .glass-effect {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="gradient-bg text-white shadow-lg">
        <div class="container mx-auto px-4 py-4">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div class="flex items-center space-x-4 mb-4 md:mb-0">
                    <a href="index.php" class="flex items-center space-x-2">
                        <img src="homepage/images/logo.png" alt="Artado" class="w-10 h-10 rounded-lg">
                        <span class="text-xl font-bold">Artado Developers</span>
                    </a>
                </div>
                
                <nav class="flex flex-wrap items-center space-x-6">
                    <a href="index.php" class="hover:text-purple-200 transition-colors">
                        <i class="fas fa-home mr-2"></i>Ana Sayfa
                    </a>
                    <a href="Workshop" class="hover:text-purple-200 transition-colors">
                        <i class="fas fa-tools mr-2"></i>Workshop
                    </a>
                    <a href="user" class="hover:text-purple-200 transition-colors">
                        <i class="fas fa-user mr-2"></i>Profil
                    </a>
                    <a href="logout.php" class="bg-white/20 hover:bg-white/30 px-4 py-2 rounded-lg transition-colors">
                        <i class="fas fa-sign-out-alt mr-2"></i>Çıkış
                    </a>
                </nav>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-8">
        <!-- Page Header -->
        <div class="text-center mb-12 fade-in">
            <h1 class="text-4xl md:text-5xl font-bold text-gray-800 mb-4">
                <i class="fas fa-hands-helping text-purple-600 mr-4"></i>
                Katkıda Bulun
            </h1>
            <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                Artado projelerine katkıda bulunarak açık kaynak topluluğunun bir parçası olabilirsiniz. 
                Yaratıcılığınızı paylaşın, fark yaratın!
            </p>
        </div>

        <!-- Success/Error Messages -->
        <?php if ($success_message): ?>
            <div class="glass-effect border border-green-200 rounded-xl p-4 mb-8 fade-in">
                <div class="flex items-center space-x-3">
                    <i class="fas fa-check-circle text-green-600 text-xl"></i>
                    <p class="text-green-800 font-medium"><?php echo $success_message; ?></p>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="glass-effect border border-red-200 rounded-xl p-4 mb-8 fade-in">
                <div class="flex items-center space-x-3">
                    <i class="fas fa-exclamation-circle text-red-600 text-xl"></i>
                    <p class="text-red-800 font-medium"><?php echo $error_message; ?></p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Contribution Form -->
        <div class="glass-effect rounded-2xl shadow-xl p-6 md:p-8 mb-12 fade-in">
            <h2 class="text-2xl md:text-3xl font-bold text-gray-800 mb-6">
                <i class="fas fa-plus-circle text-purple-600 mr-3"></i>
                Yeni Katkı Başvurusu
            </h2>
            
            <form method="POST" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Contribution Type -->
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">
                            <i class="fas fa-tag text-purple-600 mr-2"></i>Katkı Türü <span class="text-red-500">*</span>
                        </label>
                        <select name="contribution_type" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all">
                            <option value="">Seçiniz...</option>
                            <option value="code">Kod Katkısı</option>
                            <option value="design">Tasarım</option>
                            <option value="documentation">Dokümantasyon</option>
                            <option value="translation">Çeviri</option>
                            <option value="testing">Test Etme</option>
                            <option value="bug_report">Hata Bildirme</option>
                            <option value="feature_request">Özellik Talebi</option>
                            <option value="other">Diğer</option>
                        </select>
                    </div>

                    <!-- Title -->
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">
                            <i class="fas fa-heading text-purple-600 mr-2"></i>Başlık <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="title" required
                               value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>"
                               placeholder="Katkı başlığını girin..."
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all">
                    </div>
                </div>

                <!-- Description -->
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">
                        <i class="fas fa-align-left text-purple-600 mr-2"></i>Açıklama <span class="text-red-500">*</span>
                    </label>
                    <textarea name="description" required rows="6"
                              placeholder="Katkınızın detaylı açıklamasını girin..."
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all resize-none"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- GitHub Link -->
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">
                            <i class="fab fa-github text-purple-600 mr-2"></i>GitHub Link (İsteğe Bağlı)
                        </label>
                        <input type="url" name="github_link"
                               value="<?php echo htmlspecialchars($_POST['github_link'] ?? ''); ?>"
                               placeholder="https://github.com/..."
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all">
                    </div>

                    <!-- Contact Email -->
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">
                            <i class="fas fa-envelope text-purple-600 mr-2"></i>İletişim E-postası
                        </label>
                        <input type="email" name="contact_email"
                               value="<?php echo htmlspecialchars($_POST['contact_email'] ?? $user['email']); ?>"
                               placeholder="ornek@email.com"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all">
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="flex flex-col sm:flex-row gap-4">
                    <button type="submit" name="submit_contribution"
                            class="flex-1 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white font-semibold py-3 px-6 rounded-lg transition-all transform hover:scale-105">
                        <i class="fas fa-paper-plane mr-2"></i>
                        Katkıyı Gönder
                    </button>
                    <a href="user" 
                       class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-3 px-6 rounded-lg transition-all text-center">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Profile Dön
                    </a>
                </div>
            </form>
        </div>

        <!-- Previous Contributions -->
        <?php if (!empty($contributions)): ?>
            <div class="fade-in">
                <h2 class="text-2xl md:text-3xl font-bold text-gray-800 mb-8 text-center">
                    <i class="fas fa-history text-purple-600 mr-3"></i>
                    Önceki Katkılarınız
                </h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($contributions as $contribution): ?>
                        <div class="contribution-card glass-effect rounded-xl p-6">
                            <div class="flex items-center justify-between mb-4">
                                <span class="px-3 py-1 text-xs rounded-full 
                                    <?php 
                                    $status_colors = [
                                        'pending' => 'bg-yellow-100 text-yellow-800',
                                        'approved' => 'bg-green-100 text-green-800',
                                        'rejected' => 'bg-red-100 text-red-800'
                                    ];
                                    echo $status_colors[$contribution['status']] ?? 'bg-gray-100 text-gray-800';
                                    ?>">
                                    <?php 
                                    $status_text = [
                                        'pending' => 'Beklemede',
                                        'approved' => 'Onaylandı',
                                        'rejected' => 'Reddedildi'
                                    ];
                                    echo $status_text[$contribution['status']] ?? 'Bilinmiyor';
                                    ?>
                                </span>
                                <span class="text-xs text-gray-500">
                                    <?php echo date('d.m.Y', strtotime($contribution['created_at'])); ?>
                                </span>
                            </div>
                            
                            <h3 class="text-lg font-semibold text-gray-800 mb-2">
                                <?php echo htmlspecialchars($contribution['title']); ?>
                            </h3>
                            
                            <p class="text-gray-600 text-sm mb-4 line-clamp-3">
                                <?php echo htmlspecialchars(substr($contribution['description'], 0, 100)) . '...'; ?>
                            </p>
                            
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-purple-600 font-medium">
                                    <?php 
                                    $types = [
                                        'code' => 'Kod',
                                        'design' => 'Tasarım',
                                        'documentation' => 'Dokümantasyon',
                                        'translation' => 'Çeviri',
                                        'testing' => 'Test',
                                        'bug_report' => 'Hata Bildirim',
                                        'feature_request' => 'Özellik Talebi',
                                        'other' => 'Diğer'
                                    ];
                                    echo $types[$contribution['contribution_type']] ?? $contribution['contribution_type'];
                                    ?>
                                </span>
                                <?php if (!empty($contribution['github_link'])): ?>
                                    <a href="<?php echo htmlspecialchars($contribution['github_link']); ?>" 
                                       target="_blank" 
                                       class="text-gray-600 hover:text-purple-600">
                                        <i class="fab fa-github"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <!-- Footer -->
    <footer class="gradient-bg text-white py-8 mt-16">
        <div class="container mx-auto px-4">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div class="mb-4 md:mb-0">
                    <p>&copy; 2024 Artado Developers. Tüm hakları saklıdır.</p>
                </div>
                <div class="flex space-x-6">
                    <a href="https://github.com/Artado-Project" target="_blank" class="hover:text-purple-200 transition-colors">
                        <i class="fab fa-github text-xl"></i>
                    </a>
                    <a href="https://x.com/ArtadoL" target="_blank" class="hover:text-purple-200 transition-colors">
                        <i class="fab fa-twitter text-xl"></i>
                    </a>
                    <a href="https://forum.artado.xyz" target="_blank" class="hover:text-purple-200 transition-colors">
                        <i class="fas fa-comments text-xl"></i>
                    </a>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
