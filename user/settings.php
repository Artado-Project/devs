<?php
require_once '../includes/database.php';
require_once '../includes/auth.php';

// Başarı ve hata mesajlarını tanımla
$success_message = '';
$error_message = '';

// Kullanıcının giriş yapmış olması gerekiyor
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Kullanıcı oturum kontrolü
$user_id = $_SESSION['user_id'] ?? null;

// Varsayılan profil fotoğrafı URL'si
$default_profile_photo = 'https://artado.xyz/assest/img/artado-yeni.png';

// Kullanıcı bilgilerini çek
if ($user_id) {
    $stmt = $db->prepare("SELECT * FROM users WHERE id = :user_id");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        $user = [
            'username' => '',
            'email' => '',
            'phone' => '',
            'birthday' => '',
            'title' => 'Artado Geliştirici',
            'profile_photo' => $default_profile_photo
        ];
    }
    
    // $profile_photo değişkenini HTML src için hazırla
    require_once '../includes/functions.php';
    $profile_photo = get_user_avatar($user['profile_photo'] ?? null, true);
} else {
    header("Location: ../login.php");
    exit();
}

// Profil güncelleme formu gönderildiyse
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $birthday = trim($_POST['birthday'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    
    // Validasyon
    if (empty($name) || empty($email)) {
        $error_message = "Ad ve e-posta alanları zorunludur.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Geçerli bir e-posta adresi girin.";
    } else {
        try {
            // E-posta değişiyorsa, başka kullanıcı tarafından kullanılıp kullanılmadığını kontrol et
            if ($email !== $user['email']) {
                $stmt = $db->prepare("SELECT id FROM users WHERE email = :email AND id != :user_id");
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':user_id', $user_id);
                $stmt->execute();
                
                if ($stmt->fetch()) {
                    $error_message = "Bu e-posta adresi zaten kullanılıyor.";
                }
            }
            
            if (!$error_message) {
                // Profil bilgilerini güncelle
                $stmt = $db->prepare("
                    UPDATE users 
                    SET username = :name, email = :email, phone = :phone, 
                        birthday = :birthday, title = :title, bio = :bio 
                    WHERE id = :user_id
                ");
                
                $stmt->bindParam(':name', $name);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':phone', $phone);
                $stmt->bindParam(':birthday', $birthday);
                $stmt->bindParam(':title', $title);
                $stmt->bindParam(':bio', $bio);
                $stmt->bindParam(':user_id', $user_id);
                
                if ($stmt->execute()) {
                    $success_message = "Profil bilgileriniz başarıyla güncellendi!";
                    
                    // Session'daki kullanıcı bilgilerini güncelle
                    $_SESSION['username'] = $name;
                    $_SESSION['email'] = $email;
                    
                    // Kullanıcı bilgilerini yeniden çek
                    $stmt = $db->prepare("SELECT * FROM users WHERE id = :user_id");
                    $stmt->bindParam(':user_id', $user_id);
                    $stmt->execute();
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                } else {
                    $error_message = "Profil güncellenirken bir hata oluştu.";
                }
            }
        } catch (PDOException $e) {
            $error_message = "Veritabanı hatası: " . $e->getMessage();
        }
    }
}

// Profil fotoğrafı yükleme
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_photo'])) {
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($_FILES['profile_photo']['type'], $allowed_types)) {
            $error_message = "Sadece JPEG, PNG, GIF ve WebP formatları kabul edilir.";
        } elseif ($_FILES['profile_photo']['size'] > $max_size) {
            $error_message = "Dosya boyutu 5MB'dan küçük olmalıdır.";
        } else {
            // Dosya yükleme işlemi
            require_once '../includes/file_upload_helper.php';
            
            $upload_result = secure_image_upload($_FILES['profile_photo'], '../public/uploads/avatars/', 'profile_');
            
            if ($upload_result['success']) {
                // Veritabanını güncelle
                $stmt = $db->prepare("UPDATE users SET profile_photo = :profile_photo WHERE id = :user_id");
                $stmt->bindParam(':profile_photo', $upload_result['relative_path']);
                $stmt->bindParam(':user_id', $user_id);
                
                if ($stmt->execute()) {
                    $success_message = "Profil fotoğrafınız başarıyla güncellendi!";
                    $profile_photo = '../' . $upload_result['relative_path'];
                    $user['profile_photo'] = $upload_result['relative_path'];
                } else {
                    $error_message = "Profil fotoğrafı kaydedilirken bir hata oluştu.";
                }
            } else {
                $error_message = "Fotoğraf yüklenirken hata: " . $upload_result['error'];
            }
        }
    } else {
        $error_message = "Lütfen bir fotoğraf seçin.";
    }
}

// Şifre değiştirme
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error_message = "Tüm şifre alanları zorunludur.";
    } elseif (strlen($new_password) < 6) {
        $error_message = "Yeni şifre en az 6 karakter olmalıdır.";
    } elseif ($new_password !== $confirm_password) {
        $error_message = "Yeni şifreler eşleşmiyor.";
    } else {
        // Mevcut şifreyi kontrol et
        if (password_verify($current_password, $user['password'])) {
            // Yeni şifreyi hash'le ve güncelle
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            $stmt = $db->prepare("UPDATE users SET password = :password WHERE id = :user_id");
            $stmt->bindParam(':password', $hashed_password);
            $stmt->bindParam(':user_id', $user_id);
            
            if ($stmt->execute()) {
                $success_message = "Şifreniz başarıyla değiştirildi!";
            } else {
                $error_message = "Şifre değiştirilirken bir hata oluştu.";
            }
        } else {
            $error_message = "Mevcut şifreniz yanlış.";
        }
    }
}

// Kullanıcının projelerini çek
try {
    $stmt = $db->prepare("SELECT * FROM projects WHERE user_id = :user_id ORDER BY created_at DESC");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $user_projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $user_projects = [];
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ayarlar - Artado Developers</title>
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
        
        .settings-card {
            transition: all 0.3s ease;
        }
        
        .settings-card:hover {
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
        
        .tab-button {
            transition: all 0.3s ease;
        }
        
        .tab-button:hover {
            transform: translateY(-2px);
        }
        
        .tab-button.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        @media (max-width: 768px) {
            .mobile-menu {
                display: none;
            }
            
            .mobile-menu.active {
                display: block;
            }
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="gradient-bg text-white shadow-lg">
        <div class="container mx-auto px-4 py-4">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div class="flex items-center space-x-4 mb-4 md:mb-0">
                    <a href="../index.php" class="flex items-center space-x-2">
                        <img src="../homepage/images/logo.png" alt="Artado" class="w-10 h-10 rounded-lg">
                        <span class="text-xl font-bold">Artado Developers</span>
                    </a>
                </div>
                
                <!-- Mobile Menu Toggle -->
                <button id="mobile-menu-toggle" class="md:hidden text-white">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                
                <!-- Navigation -->
                <nav id="mobile-menu" class="mobile-menu flex flex-col md:flex-row items-center space-y-4 md:space-y-0 md:space-x-6 w-full md:w-auto">
                    <a href="../index.php" class="hover:text-purple-200 transition-colors">
                        <i class="fas fa-home mr-2"></i>Ana Sayfa
                    </a>
                    <a href="../Workshop" class="hover:text-purple-200 transition-colors">
                        <i class="fas fa-tools mr-2"></i>Workshop
                    </a>
                    <a href="index.php" class="hover:text-purple-200 transition-colors">
                        <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
                    </a>
                    <a href="../katki.php" class="hover:text-purple-200 transition-colors">
                        <i class="fas fa-hands-helping mr-2"></i>Katkı
                    </a>
                    <a href="../logout.php" class="bg-white/20 hover:bg-white/30 px-4 py-2 rounded-lg transition-colors">
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
                <i class="fas fa-cog text-purple-600 mr-4"></i>
                Ayarlar
            </h1>
            <p class="text-xl text-gray-600">Hesap ayarlarınızı yönetin</p>
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

        <!-- Profile Overview Card -->
        <div class="glass-effect rounded-2xl shadow-xl p-6 md:p-8 mb-12 fade-in">
            <div class="flex flex-col md:flex-row items-center md:items-start space-y-6 md:space-y-0 md:space-x-8">
                <!-- Profile Photo -->
                <div class="flex-shrink-0">
                    <div class="relative">
                        <img src="<?php echo htmlspecialchars($profile_photo); ?>" 
                             alt="Profil Fotoğrafı" 
                             class="w-32 h-32 md:w-40 md:h-40 rounded-full border-4 border-white shadow-xl">
                        <div class="absolute bottom-0 right-0 bg-purple-600 text-white rounded-full p-2 shadow-lg">
                            <i class="fas fa-camera"></i>
                        </div>
                    </div>
                </div>
                
                <!-- Profile Info -->
                <div class="flex-1 text-center md:text-left">
                    <h2 class="text-3xl font-bold text-gray-800 mb-2"><?php echo htmlspecialchars($user['username']); ?></h2>
                    <p class="text-lg text-purple-600 mb-4"><?php echo htmlspecialchars($user['title'] ?? 'Artado Geliştirici'); ?></p>
                    
                    <div class="flex flex-wrap justify-center md:justify-start gap-4 mb-6">
                        <div class="flex items-center space-x-2 text-gray-600">
                            <i class="fas fa-envelope"></i>
                            <span><?php echo htmlspecialchars($user['email']); ?></span>
                        </div>
                        <?php if (!empty($user['phone'])): ?>
                            <div class="flex items-center space-x-2 text-gray-600">
                                <i class="fas fa-phone"></i>
                                <span><?php echo htmlspecialchars($user['phone']); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!empty($user['bio'])): ?>
                        <p class="text-gray-700 mb-6"><?php echo htmlspecialchars($user['bio']); ?></p>
                    <?php endif; ?>
                    
                    <div class="flex flex-wrap gap-4 justify-center md:justify-start">
                        <div class="bg-purple-100 rounded-lg px-4 py-2">
                            <span class="text-purple-800 font-semibold"><?php echo count($user_projects); ?></span>
                            <span class="text-purple-600 ml-1">Proje</span>
                        </div>
                        <div class="bg-green-100 rounded-lg px-4 py-2">
                            <span class="text-green-800 font-semibold">Aktif</span>
                            <span class="text-green-600 ml-1">Kullanıcı</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs Navigation -->
        <div class="flex flex-wrap justify-center mb-8 gap-2 md:gap-4">
            <button onclick="showTab('profile')" class="tab-button active px-6 py-3 rounded-lg font-semibold transition-all">
                <i class="fas fa-user mr-2"></i>Profil Bilgileri
            </button>
            <button onclick="showTab('photo')" class="tab-button px-6 py-3 rounded-lg font-semibold bg-white shadow-md hover:shadow-lg transition-all">
                <i class="fas fa-camera mr-2"></i>Fotoğraf
            </button>
            <button onclick="showTab('password')" class="tab-button px-6 py-3 rounded-lg font-semibold bg-white shadow-md hover:shadow-lg transition-all">
                <i class="fas fa-lock mr-2"></i>Şifre
            </button>
            <button onclick="showTab('projects')" class="tab-button px-6 py-3 rounded-lg font-semibold bg-white shadow-md hover:shadow-lg transition-all">
                <i class="fas fa-folder mr-2"></i>Projelerim
            </button>
        </div>

        <!-- Profile Tab -->
        <div id="profile-tab" class="tab-content fade-in">
            <div class="glass-effect rounded-2xl shadow-xl p-6 md:p-8">
                <h3 class="text-2xl font-bold text-gray-800 mb-6">
                    <i class="fas fa-user-edit text-purple-600 mr-3"></i>
                    Profil Bilgilerini Düzenle
                </h3>
                
                <form method="POST" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Name -->
                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">
                                <i class="fas fa-user text-purple-600 mr-2"></i>Ad Soyad <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="name" required
                                   value="<?php echo htmlspecialchars($user['username']); ?>"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all">
                        </div>

                        <!-- Email -->
                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">
                                <i class="fas fa-envelope text-purple-600 mr-2"></i>E-posta <span class="text-red-500">*</span>
                            </label>
                            <input type="email" name="email" required
                                   value="<?php echo htmlspecialchars($user['email']); ?>"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all">
                        </div>

                        <!-- Phone -->
                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">
                                <i class="fas fa-phone text-purple-600 mr-2"></i>Telefon
                            </label>
                            <input type="tel" name="phone"
                                   value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"
                                   placeholder="+90 555 123 4567"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all">
                        </div>

                        <!-- Birthday -->
                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">
                                <i class="fas fa-birthday-cake text-purple-600 mr-2"></i>Doğum Tarihi
                            </label>
                            <input type="date" name="birthday"
                                   value="<?php echo htmlspecialchars($user['birthday'] ?? ''); ?>"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all">
                        </div>

                        <!-- Title -->
                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">
                                <i class="fas fa-briefcase text-purple-600 mr-2"></i>Ünvan
                            </label>
                            <input type="text" name="title"
                                   value="<?php echo htmlspecialchars($user['title'] ?? 'Artado Geliştirici'); ?>"
                                   placeholder="Örn: Frontend Developer"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all">
                        </div>
                    </div>

                    <!-- Bio -->
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">
                            <i class="fas fa-align-left text-purple-600 mr-2"></i>Hakkımda
                        </label>
                        <textarea name="bio" rows="4"
                                  placeholder="Kendinizden bahsedin..."
                                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all resize-none"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex flex-col sm:flex-row gap-4">
                        <button type="submit" name="update_profile"
                                class="flex-1 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white font-semibold py-3 px-6 rounded-lg transition-all transform hover:scale-105">
                            <i class="fas fa-save mr-2"></i>
                            Bilgileri Güncelle
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Photo Tab -->
        <div id="photo-tab" class="tab-content hidden">
            <div class="glass-effect rounded-2xl shadow-xl p-6 md:p-8">
                <h3 class="text-2xl font-bold text-gray-800 mb-6">
                    <i class="fas fa-camera text-purple-600 mr-3"></i>
                    Profil Fotoğrafı
                </h3>
                
                <div class="text-center">
                    <div class="mb-8">
                        <img src="<?php echo htmlspecialchars($profile_photo); ?>" 
                             alt="Mevcut Profil Fotoğrafı" 
                             class="w-48 h-48 md:w-64 md:h-64 rounded-full mx-auto border-4 border-white shadow-xl">
                    </div>
                    
                    <form method="POST" enctype="multipart/form-data" class="space-y-6">
                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">
                                <i class="fas fa-upload text-purple-600 mr-2"></i>Yeni Fotoğraf Yükle
                            </label>
                            <input type="file" name="profile_photo" accept="image/*"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all">
                            <p class="text-sm text-gray-500 mt-2">JPEG, PNG, GIF veya WebP formatları, maksimum 5MB</p>
                        </div>
                        
                        <div class="flex flex-col sm:flex-row gap-4">
                            <button type="submit" name="upload_photo"
                                    class="flex-1 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white font-semibold py-3 px-6 rounded-lg transition-all transform hover:scale-105">
                                <i class="fas fa-upload mr-2"></i>
                                Fotoğraf Yükle
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Password Tab -->
        <div id="password-tab" class="tab-content hidden">
            <div class="glass-effect rounded-2xl shadow-xl p-6 md:p-8">
                <h3 class="text-2xl font-bold text-gray-800 mb-6">
                    <i class="fas fa-lock text-purple-600 mr-3"></i>
                    Şifre Değiştir
                </h3>
                
                <form method="POST" class="space-y-6">
                    <!-- Current Password -->
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">
                            <i class="fas fa-key text-purple-600 mr-2"></i>Mevcut Şifre <span class="text-red-500">*</span>
                        </label>
                        <input type="password" name="current_password" required
                               placeholder="Mevcut şifrenizi girin"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all">
                    </div>

                    <!-- New Password -->
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">
                            <i class="fas fa-lock text-purple-600 mr-2"></i>Yeni Şifre <span class="text-red-500">*</span>
                        </label>
                        <input type="password" name="new_password" required
                               placeholder="Yeni şifrenizi girin (en az 6 karakter)"
                               minlength="6"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all">
                    </div>

                    <!-- Confirm Password -->
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">
                            <i class="fas fa-lock text-purple-600 mr-2"></i>Yeni Şifre (Tekrar) <span class="text-red-500">*</span>
                        </label>
                        <input type="password" name="confirm_password" required
                               placeholder="Yeni şifrenizi tekrar girin"
                               minlength="6"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all">
                    </div>

                    <!-- Submit Button -->
                    <div class="flex flex-col sm:flex-row gap-4">
                        <button type="submit" name="change_password"
                                class="flex-1 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white font-semibold py-3 px-6 rounded-lg transition-all transform hover:scale-105">
                            <i class="fas fa-key mr-2"></i>
                            Şifreyi Değiştir
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Projects Tab -->
        <div id="projects-tab" class="tab-content hidden">
            <div class="glass-effect rounded-2xl shadow-xl p-6 md:p-8">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-2xl font-bold text-gray-800">
                        <i class="fas fa-folder text-purple-600 mr-3"></i>
                        Projelerim
                    </h3>
                    <a href="create-eklenti.php" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg transition-colors">
                        <i class="fas fa-plus mr-2"></i>Yeni Proje
                    </a>
                </div>
                
                <?php if (empty($user_projects)): ?>
                    <div class="text-center py-12">
                        <i class="fas fa-folder-open text-6xl text-gray-300 mb-4"></i>
                        <h4 class="text-xl font-semibold text-gray-600 mb-2">Henüz projeniz yok</h4>
                        <p class="text-gray-500 mb-6">İlk projenizi oluşturarak başlayın</p>
                        <a href="create-eklenti.php" class="bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white font-semibold py-3 px-6 rounded-lg transition-all transform hover:scale-105">
                            <i class="fas fa-plus mr-2"></i>
                            İlk Projemi Oluştur
                        </a>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php foreach ($user_projects as $project): ?>
                            <div class="settings-card bg-white rounded-xl shadow-lg p-6 border border-gray-200">
                                <div class="flex items-center justify-between mb-4">
                                    <span class="px-3 py-1 text-xs rounded-full bg-purple-100 text-purple-800">
                                        <?php echo htmlspecialchars($project['category']); ?>
                                    </span>
                                    <span class="text-xs text-gray-500">
                                        <?php echo date('d.m.Y', strtotime($project['created_at'])); ?>
                                    </span>
                                </div>
                                
                                <h4 class="text-lg font-semibold text-gray-800 mb-2">
                                    <?php echo htmlspecialchars($project['title']); ?>
                                </h4>
                                
                                <p class="text-gray-600 text-sm mb-4 line-clamp-3">
                                    <?php echo htmlspecialchars(substr($project['description'], 0, 100)) . '...'; ?>
                                </p>
                                
                                <div class="flex space-x-2">
                                    <a href="../Workshop/project.php?id=<?php echo $project['id']; ?>" 
                                       target="_blank"
                                       class="flex-1 bg-blue-100 hover:bg-blue-200 text-blue-700 py-2 px-3 rounded-lg transition-colors text-center text-sm">
                                        <i class="fas fa-eye mr-1"></i>Görüntüle
                                    </a>
                                    <a href="update_project.php?id=<?php echo $project['id']; ?>" 
                                       class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 py-2 px-3 rounded-lg transition-colors text-center text-sm">
                                        <i class="fas fa-edit mr-1"></i>Düzenle
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
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

    <script>
        // Tab functionality
        function showTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.add('hidden');
            });
            
            // Remove active class from all buttons
            document.querySelectorAll('.tab-button').forEach(button => {
                button.classList.remove('active');
                button.classList.add('bg-white', 'shadow-md', 'hover:shadow-lg');
            });
            
            // Show selected tab
            document.getElementById(tabName + '-tab').classList.remove('hidden');
            
            // Add active class to clicked button
            event.target.classList.add('active');
            event.target.classList.remove('bg-white', 'shadow-md', 'hover:shadow-lg');
        }
        
        // Mobile menu toggle
        document.getElementById('mobile-menu-toggle').addEventListener('click', function() {
            document.getElementById('mobile-menu').classList.toggle('active');
        });
        
        // Close mobile menu when clicking outside
        document.addEventListener('click', function(event) {
            const mobileMenu = document.getElementById('mobile-menu');
            const toggle = document.getElementById('mobile-menu-toggle');
            
            if (!mobileMenu.contains(event.target) && !toggle.contains(event.target)) {
                mobileMenu.classList.remove('active');
            }
        });
        
        // Auto-hide success messages
        setTimeout(() => {
            const successMessages = document.querySelectorAll('.border-green-200');
            successMessages.forEach(msg => {
                msg.style.transition = 'opacity 0.5s';
                msg.style.opacity = '0';
                setTimeout(() => msg.remove(), 500);
            });
        }, 5000);
    </script>
</body>
</html>
