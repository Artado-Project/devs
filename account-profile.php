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
$default_profile_photo = 'logo.png';

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
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Ayarları - Artado Developers</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .profile-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-bottom: 30px;
        }
        
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px;
            text-align: center;
            color: white;
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 4px solid white;
            margin-bottom: 20px;
            object-fit: cover;
        }
        
        .profile-name {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .profile-title {
            font-size: 18px;
            opacity: 0.9;
        }
        
        .profile-content {
            padding: 40px;
        }
        
        .form-section {
            margin-bottom: 40px;
        }
        
        .section-title {
            font-size: 24px;
            font-weight: bold;
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            color: #555;
            margin-bottom: 8px;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        .btn-secondary {
            background: #6c757d;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
            box-shadow: 0 10px 20px rgba(108, 117, 125, 0.3);
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .photo-upload {
            text-align: center;
            padding: 30px;
            border: 2px dashed #e1e5e9;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .photo-upload input[type="file"] {
            display: none;
        }
        
        .photo-upload label {
            display: inline-block;
            padding: 12px 30px;
            background: #f8f9fa;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .photo-upload label:hover {
            background: #e9ecef;
            border-color: #667eea;
        }
        
        .current-photo {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .current-photo img {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #e1e5e9;
        }
        
        @media (max-width: 768px) {
            .profile-header {
                padding: 30px 20px;
            }
            
            .profile-content {
                padding: 20px;
            }
            
            .profile-name {
                font-size: 24px;
            }
            
            .section-title {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Profil Kartı -->
        <div class="profile-card">
            <div class="profile-header">
                <img src="<?php echo htmlspecialchars($profile_photo); ?>" alt="Profil Fotoğrafı" class="profile-avatar">
                <div class="profile-name"><?php echo htmlspecialchars($user['username']); ?></div>
                <div class="profile-title"><?php echo htmlspecialchars($user['title'] ?? 'Artado Geliştirici'); ?></div>
            </div>
            
            <div class="profile-content">
                <!-- Mesajlar -->
                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>
                
                <!-- Profil Bilgileri Formu -->
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-user"></i> Profil Bilgileri
                    </h3>
                    
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name">Ad Soyad</label>
                                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="email">E-posta</label>
                                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="phone">Telefon</label>
                                    <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="birthday">Doğum Tarihi</label>
                                    <input type="date" id="birthday" name="birthday" value="<?php echo htmlspecialchars($user['birthday'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="title">Başlık</label>
                            <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($user['title'] ?? ''); ?>" placeholder="Örn: Frontend Developer">
                        </div>
                        
                        <div class="form-group">
                            <label for="bio">Hakkımda</label>
                            <textarea id="bio" name="bio" placeholder="Kendinizden bahsedin..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                        </div>
                        
                        <button type="submit" name="update_profile" class="btn">
                            <i class="fas fa-save"></i> Profil Bilgilerini Güncelle
                        </button>
                    </form>
                </div>
                
                <!-- Profil Fotoğrafı -->
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-camera"></i> Profil Fotoğrafı
                    </h3>
                    
                    <div class="current-photo">
                        <img src="<?php echo htmlspecialchars($profile_photo); ?>" alt="Mevcut Profil Fotoğrafı">
                        <p style="margin-top: 10px; color: #666;">Mevcut Profil Fotoğrafı</p>
                    </div>
                    
                    <form method="POST" enctype="multipart/form-data">
                        <div class="photo-upload">
                            <input type="file" id="profile_photo" name="profile_photo" accept="image/*">
                            <label for="profile_photo">
                                <i class="fas fa-upload"></i> Yeni Fotoğraf Seç
                            </label>
                            <p style="margin-top: 10px; color: #666;">JPEG, PNG, GIF veya WebP formatları, maksimum 5MB</p>
                        </div>
                        
                        <button type="submit" name="upload_photo" class="btn">
                            <i class="fas fa-camera"></i> Fotoğraf Yükle
                        </button>
                    </form>
                </div>
                
                <!-- Şifre Değiştirme -->
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-lock"></i> Şifre Değiştirme
                    </h3>
                    
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="current_password">Mevcut Şifre</label>
                                    <input type="password" id="current_password" name="current_password" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="new_password">Yeni Şifre</label>
                                    <input type="password" id="new_password" name="new_password" required minlength="6">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="confirm_password">Yeni Şifre (Tekrar)</label>
                                    <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" name="change_password" class="btn btn-secondary">
                            <i class="fas fa-key"></i> Şifreyi Değiştir
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Fotoğraf önizleme
        document.getElementById('profile_photo').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    // Önizleme yapmak isterseniz buraya ekleyebilirsiniz
                };
                reader.readAsDataURL(file);
            }
        });
        
        // Form validasyonu
        document.querySelector('form[name="change_password"]').addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('Yeni şifreler eşleşmiyor!');
                return false;
            }
        });
    </script>
</body>
</html>
