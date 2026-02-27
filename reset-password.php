<?php
require_once 'includes/database.php';
require_once 'includes/session_start.php';

$success_message = '';
$error_message = '';
$token_valid = false;
$token = '';

// Token kontrolü
if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    try {
        // Token'ın geçerli olup olmadığını kontrol et
        $stmt = $db->prepare("SELECT * FROM password_resets WHERE token = :token AND used = 0 AND expires_at > NOW()");
        $stmt->bindParam(':token', $token);
        $stmt->execute();
        $reset_request = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($reset_request) {
            $token_valid = true;
        } else {
            $error_message = "Geçersiz veya süresi dolmuş şifre sıfırlama bağlantısı.";
        }
    } catch (PDOException $e) {
        error_log("Token validation error: " . $e->getMessage());
        $error_message = "Bir hata oluştu. Lütfen daha sonra tekrar deneyin.";
    }
} else {
    $error_message = "Geçersiz şifre sıfırlama bağlantısı.";
}

// Şifre sıfırlama işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_token = $_POST['token'] ?? '';
    $new_password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // POST'tan gelen token'ı kontrol et
    if (!empty($post_token)) {
        try {
            $stmt = $db->prepare("SELECT * FROM password_resets WHERE token = :token AND used = 0 AND expires_at > NOW()");
            $stmt->bindParam(':token', $post_token);
            $stmt->execute();
            $reset_request = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$reset_request) {
                $error_message = "Geçersiz veya süresi dolmuş şifre sıfırlama bağlantısı.";
            } elseif (empty($new_password) || empty($confirm_password)) {
                $error_message = "Lütfen tüm alanları doldurunuz.";
                $token_valid = true;
            } elseif (strlen($new_password) < 6) {
                $error_message = "Şifre en az 6 karakter uzunluğunda olmalıdır.";
                $token_valid = true;
            } elseif ($new_password !== $confirm_password) {
                $error_message = "Şifreler eşleşmiyor.";
                $token_valid = true;
            } else {
                // Şifreyi güncelle
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE users SET password = :password WHERE email = :email");
                $stmt->bindParam(':password', $hashed_password);
                $stmt->bindParam(':email', $reset_request['email']);
                $stmt->execute();
                
                // Token'ı kullanılmış olarak işaretle
                $stmt = $db->prepare("UPDATE password_resets SET used = 1 WHERE token = :token");
                $stmt->bindParam(':token', $post_token);
                $stmt->execute();
                
                // Başarılı mesajla birlikte login sayfasına yönlendir
                $_SESSION['success'] = "Şifreniz başarıyla güncellendi. Giriş yapabilirsiniz.";
                header("Location: login.php");
                exit();
            }
        } catch (PDOException $e) {
            error_log("Password update error: " . $e->getMessage());
            $error_message = "Şifre güncellenirken bir hata oluştu. Lütfen daha sonra tekrar deneyin.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Şifre Sıfırla - Artado Developers</title>
    <link rel="shortcut icon" href="https://raw.githubusercontent.com/Artado-Project/devs/refs/heads/main/ArtadoDevs/images/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="https://raw.githubusercontent.com/Artado-Project/devs/refs/heads/main/ArtadoDevs/images/favicon.ico" type="image/png">
    <link rel="stylesheet" crossorigin href="user/assets/compiled/css/app.css">
    <link rel="stylesheet" crossorigin href="user/assets/compiled/css/app-dark.css">
    <link rel="stylesheet" crossorigin href="user/assets/compiled/css/auth.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>

<body>
    <script src="user/assets/static/js/initTheme.js"></script>
    <div id="auth">
        
<div class="row h-100">
    <div class="col-lg-5 col-12">
        <div id="auth-left">
            <div class="auth-logo">
                <a href="index.php"><img src="https://raw.githubusercontent.com/Artado-Project/devs/refs/heads/main/ArtadoDevs/images/favicon.ico" alt="Logo"></a>
            </div>
            <h1 class="auth-title">Şifre Sıfırla</h1>
            <p class="auth-subtitle mb-5">Yeni şifrenizi belirleyin.</p>

            <?php if($error_message): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <?php if($success_message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $success_message; ?>
                </div>
                <div class="text-center mt-4">
                    <a href="login" class="btn btn-primary btn-lg">Giriş Yap</a>
                </div>
            <?php endif; ?>

            <?php if($token_valid && !$success_message): ?>
            <form method="post">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                
                <div class="form-group position-relative has-icon-left mb-4">
                    <div class="input-group">
                        <span class="input-group-text" style="background: transparent; border-right: 0;">
                            <i class="bi bi-shield-lock"></i>
                        </span>
                        <input type="password" name="password" id="password" class="form-control form-control-xl" placeholder="Yeni Şifre" required>
                        <button type="button" class="btn btn-outline-secondary" id="togglePassword">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="form-group position-relative has-icon-left mb-4">
                    <div class="input-group">
                        <span class="input-group-text" style="background: transparent; border-right: 0;">
                            <i class="bi bi-shield-lock"></i>
                        </span>
                        <input type="password" name="confirm_password" id="confirm_password" class="form-control form-control-xl" placeholder="Yeni Şifre Tekrar" required>
                        <button type="button" class="btn btn-outline-secondary" id="toggleConfirmPassword">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block btn-lg shadow-lg mt-5">Şifreyi Güncelle</button>
            </form>
            <?php endif; ?>
            
            <div class="text-center mt-5 text-lg fs-4">
                <p class='text-gray-600'><a href="login" class="font-bold">Giriş Sayfasına Dön</a></p>
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

<script>
document.getElementById('togglePassword')?.addEventListener('click', function() {
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

document.getElementById('toggleConfirmPassword')?.addEventListener('click', function() {
    const passwordInput = document.getElementById('confirm_password');
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

</html>
