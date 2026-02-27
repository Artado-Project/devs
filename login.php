<?php

require_once 'includes/database.php';
require_once 'includes/auth.php';
require_once 'includes/session_start.php';

// Başarı ve hata mesajlarını tanımla - en başta boş olarak tanımla
$success_message = '';
$error_message = '';

// Çıkış yapma işlemi
if (isset($_GET['logout'])) {
    // Oturumu sonlandır
    session_destroy();
    
    // Çerezleri temizle
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time()-3600, '/');
    }
    
    // Giriş sayfasına yönlendir
    header('Location: login.php');
    exit();
}


// Eğer kullanıcı zaten giriş yaptıysa, yönlendirme yap.
if (isset($_SESSION['user_email'])) {
    // Rol seçim popup'ı gösterilmesi gerekiyorsa yönlendirme yapma
    if (isset($_GET['show_role_selection']) && $_GET['show_role_selection'] === '1') {
        // Popup'ı göstermek için sayfada kal
    } else {
        // Kullanıcının rolünü al
        $user_role = getUserRole($_SESSION['user_email'], $db);

        // Kullanıcı adminse admin sayfasına, kullanıcıysa user sayfasına yönlendir.
        if ($user_role === 'admin') {
            header("Location: admin");
        } else {
            header("Location: user");
        }
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // Boş alan kontrolü
    if (empty($email) || empty($password)) {
        $error_message = "Lütfen e-posta ve şifre alanlarını doldurunuz.";
    } else {
        if (login($email, $password, $db)) {
            // Kullanıcının rolünü veritabanından al
            $user_role = getUserRole($email, $db);

            // Kullanıcıyı oturum açarak yönlendir.
            $_SESSION['user_email'] = $email;
            $_SESSION['user_role'] = $user_role;

            if ($user_role === 'admin') {
                $_SESSION['success'] = "Admin girişi başarılı!";
                // Admin için rol seçim popup'ı göster
                header("Location: login.php?show_role_selection=1");
            } else {
                $_SESSION['success'] = "Giriş başarılı!";
                header("Location: user");
            }
            exit();
        } else {
            $error_message = "Geçersiz e-posta veya şifre.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş Yap - Artado Developers</title>
    <link rel="shortcut icon" href="logo.png" type="image/x-icon">
    <link rel="shortcut icon" href="logo.png" type="image/png">
    <link rel="stylesheet" crossorigin href="user/assets/compiled/css/app.css">
    <link rel="stylesheet" crossorigin href="user/assets/compiled/css/app-dark.css">
    <link rel="stylesheet" crossorigin href="user/assets/compiled/css/auth.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <script src="user/assets/static/js/initTheme.js"></script>
    <div id="auth">
        
<div class="row h-100">
    <div class="col-lg-5 col-12">
        <div id="auth-left">
            <div class="auth-logo">
                <a href="index.php"><img src="logo.png" alt="Logo"></a>
            </div>
            <h1 class="auth-title">Giriş Yap</h1>
            <p class="auth-subtitle mb-5">Kayıt olurken kullandığınız bilgileriniz ile giriş yapın.</p>

            <!-- Hata ve başarı mesajları için alert -->
            <?php if($error_message): ?>
                <div class="alert alert-danger">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <?php if($success_message): ?>
                <div class="alert alert-success">
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>

            <?php if(isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>

            <form method="post">
                <div class="form-group position-relative has-icon-left mb-4">
                    <input type="email" name="email" class="form-control form-control-xl" placeholder="E-posta" required>
                    <div class="form-control-icon">
                        <i class="bi bi-envelope-heart-fill text-primary"></i>
                    </div>
                </div>
                <div class="form-group position-relative has-icon-left mb-4">
                    <div class="input-group">
                        <span class="input-group-text" style="background: transparent; border-right: 0;">
                            <i class="bi bi-shield-lock-fill text-primary"></i>
                        </span>
                        <input type="password" name="password" id="password" class="form-control form-control-xl" placeholder="Şifre" required>
                        <button type="button" class="btn btn-outline-secondary" id="togglePassword">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>
                <div class="form-check form-check-lg d-flex align-items-end">
                    <input class="form-check-input me-2" type="checkbox" value="" id="flexCheckDefault">
                    <label class="form-check-label text-gray-600" for="flexCheckDefault">
                        Beni hatırla
                    </label>
                </div>
                <button type="submit" class="btn btn-primary btn-block btn-lg shadow-lg mt-5">Giriş Yap</button>
            </form>
            <div class="text-center mt-5 text-lg fs-4">
                <p class="text-gray-600">Hesabınız yok mu? <a href="register" class="font-bold">Kayıt Ol</a></p>
                <p><a class="font-bold" href="auth-forgot-password">Şifremi Unuttum</a></p>
            </div>
        </div>
    </div>
    <div class="col-lg-7 d-none d-lg-block">
        <div id="auth-right">

        </div>
    </div>
</div>

    </div>
</body>

</html>

<!-- Şifre gösterme/gizleme için JavaScript -->
<script>
document.getElementById('togglePassword').addEventListener('click', function() {
    const passwordInput = document.getElementById('password');
    const icon = this.querySelector('i');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
    } else {
        passwordInput.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
    }
});

// Rol seçim popup'ı
document.addEventListener('DOMContentLoaded', function() {
    // URL'de show_role_selection parametresi varsa popup'ı göster
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('show_role_selection') === '1') {
        showRoleSelectionPopup();
    }
});

function showRoleSelectionPopup() {
    // Popup HTML'i oluştur
    const popupHTML = `
        <div id="roleSelectionPopup" class="role-selection-popup">
            <div class="role-selection-overlay"></div>
            <div class="role-selection-modal">
                <div class="role-selection-header">
                    <div class="role-selection-icon">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <h2>Hoş Geldiniz!</h2>
                    <p>Admin hesabınızla giriş yaptınız. Nereye gitmek istersiniz?</p>
                </div>
                
                <div class="role-selection-options">
                    <button onclick="goToPanel('admin')" class="role-option admin-option">
                        <div class="role-option-icon">
                            <i class="fas fa-cogs"></i>
                        </div>
                        <div class="role-option-content">
                            <h3>Admin Paneli</h3>
                            <p>Kullanıcıları ve projeleri yönetin</p>
                        </div>
                        <div class="role-option-arrow">
                            <i class="fas fa-arrow-right"></i>
                        </div>
                    </button>
                    
                    <button onclick="goToPanel('user')" class="role-option user-option">
                        <div class="role-option-icon">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="role-option-content">
                            <h3>Kullanıcı Paneli</h3>
                            <p>Projenizi oluşturun ve yönetin</p>
                        </div>
                        <div class="role-option-arrow">
                            <i class="fas fa-arrow-right"></i>
                        </div>
                    </button>
                </div>
                
                            </div>
        </div>
    `;
    
    // Popup'ı sayfaya ekle
    document.body.insertAdjacentHTML('beforeend', popupHTML);
    
    // CSS stillerini ekle
    const popupCSS = `
        <style>
        .role-selection-popup {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.3s ease-out;
        }
        
        .role-selection-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(8px);
        }
        
        .role-selection-modal {
            position: relative;
            background: #1f2937;
            border: 1px solid #374151;
            border-radius: 20px;
            padding: 40px;
            max-width: 500px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            animation: slideUp 0.3s ease-out;
        }
        
        .role-selection-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .role-selection-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: white;
            font-size: 32px;
            box-shadow: 0 10px 25px -5px rgba(139, 92, 246, 0.3);
        }
        
        .role-selection-header h2 {
            font-size: 28px;
            font-weight: 700;
            color: #f9fafb;
            margin-bottom: 10px;
        }
        
        .role-selection-header p {
            color: #9ca3af;
            font-size: 16px;
            line-height: 1.5;
        }
        
        .role-selection-options {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .role-option {
            display: flex;
            align-items: center;
            padding: 20px;
            border: 2px solid #374151;
            border-radius: 15px;
            background: #111827;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: left;
            width: 100%;
        }
        
        .role-option:hover {
            border-color: #8b5cf6;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(139, 92, 246, 0.2);
        }
        
        .admin-option:hover {
            background: linear-gradient(135deg, #1f2937 0%, #451a03 100%);
            border-color: #dc2626;
        }
        
        .user-option:hover {
            background: linear-gradient(135deg, #1f2937 0%, #1e3a8a 100%);
            border-color: #3b82f6;
        }
        
        .role-option-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 20px;
        }
        
        .admin-option .role-option-icon {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            color: white;
            box-shadow: 0 4px 15px -3px rgba(220, 38, 38, 0.3);
        }
        
        .user-option .role-option-icon {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            box-shadow: 0 4px 15px -3px rgba(59, 130, 246, 0.3);
        }
        
        .role-option-content {
            flex: 1;
        }
        
        .role-option-content h3 {
            font-size: 18px;
            font-weight: 600;
            color: #f9fafb;
            margin-bottom: 5px;
        }
        
        .role-option-content p {
            font-size: 14px;
            color: #9ca3af;
            margin: 0;
        }
        
        .role-option-arrow {
            color: #6b7280;
            font-size: 16px;
            transition: transform 0.3s ease;
        }
        
        .role-option:hover .role-option-arrow {
            transform: translateX(5px);
            color: #8b5cf6;
        }
        
        .role-selection-footer {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid #374151;
        }
        
                
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideUp {
            from { 
                opacity: 0;
                transform: translateY(30px);
            }
            to { 
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @media (max-width: 640px) {
            .role-selection-modal {
                padding: 30px 20px;
                margin: 20px;
                background: #1f2937;
            }
            
            .role-selection-header h2 {
                font-size: 24px;
            }
            
            .role-option {
                padding: 15px;
                background: #111827;
            }
            
            .role-option-icon {
                width: 40px;
                height: 40px;
                font-size: 16px;
                margin-right: 12px;
            }
            
            .role-option-content h3 {
                font-size: 16px;
            }
            
            .role-option-content p {
                font-size: 13px;
            }
        }
        </style>
    `;
    
    document.head.insertAdjacentHTML('beforeend', popupCSS);
}

function closeRoleSelectionPopup() {
    const popup = document.getElementById('roleSelectionPopup');
    if (popup) {
        popup.remove();
    }
}

function goToPanel(panel) {
    // Popup'ı kapat
    closeRoleSelectionPopup();
    
    // Seçilen panele yönlendir
    if (panel === 'admin') {
        window.location.href = 'admin';
    } else {
        window.location.href = 'user';
    }
}

</script>