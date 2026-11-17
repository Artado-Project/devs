<?php

require_once '../includes/database.php';
require_once '../includes/auth.php';
require_once '../includes/session_start.php';

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
    header('Location: auth-login.php');
    exit();
}

// Eğer kullanıcı zaten giriş yapmışsa ana sayfaya yönlendir
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Eğer kullanıcı zaten giriş yaptıysa, yönlendirme yap.
if (isset($_SESSION['user_email'])) {
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

            if ($user_role === 'admin') {
                $_SESSION['success'] = "Admin girişi başarılı!";
                header("Location: ../admin");
            } else {
                $_SESSION['success'] = "Giriş başarılı!";
                header("Location: ../user");
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
    <link rel="shortcut icon" href="https://raw.githubusercontent.com/Artado-Project/devs/refs/heads/main/ArtadoDevs/images/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="https://raw.githubusercontent.com/Artado-Project/devs/refs/heads/main/ArtadoDevs/images/favicon.ico" type="image/png">
    <link rel="stylesheet" crossorigin href="./assets/compiled/css/app.css">
    <link rel="stylesheet" crossorigin href="./assets/compiled/css/app-dark.css">
    <link rel="stylesheet" crossorigin href="./assets/compiled/css/auth.css">
</head>

<body>
    <script src="assets/static/js/initTheme.js"></script>
    <div id="auth">
        
<div class="row h-100">
    <div class="col-lg-5 col-12">
        <div id="auth-left">
            <div class="auth-logo">
                <a href="index.php"><img src="https://raw.githubusercontent.com/Artado-Project/devs/refs/heads/main/ArtadoDevs/images/favicon.ico" alt="Logo"></a>
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

            <form method="post">
                <div class="form-group position-relative has-icon-left mb-4">
                    <input type="email" name="email" class="form-control form-control-xl" placeholder="E-posta" required>
                    <div class="form-control-icon">
                        <i class="bi bi-person"></i>
                    </div>
                </div>
                <div class="form-group position-relative mb-4">
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="bi bi-shield-lock"></i>
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
                <p class="text-gray-600">Hesabınız yok mu? <a href="auth-register.php" class="font-bold">Kayıt Ol</a></p>
                <p><a class="font-bold" href="auth-forgot-password.php">Şifremi Unuttum</a></p>
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
</script>