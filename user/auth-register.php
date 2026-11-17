<?php
require_once '../includes/database.php';
// Başarı ve hata mesajlarını tanımla
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['cpass'] ?? '';
    $website = $_POST['website'] ?? '';

    // Boş alan kontrolü
    if (empty($username) || empty($email) || empty($password) || empty($password_confirm)) {
        $error_message = "Lütfen tüm zorunlu alanları doldurunuz.";
    }
    // Şifre uzunluk kontrolü
    elseif (strlen($password) < 6) {
        $error_message = "Şifre en az 6 karakter uzunluğunda olmalıdır.";
    }
    // Şifre eşleşme kontrolü
    elseif ($password !== $password_confirm) {
        $error_message = "Şifreler eşleşmiyor.";
    }
    // E-posta formatı kontrolü
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Geçerli bir e-posta adresi giriniz.";
    } else {
        // Kullanıcıyı veritabanına kaydet
        try {
            $stmt = $db->prepare("INSERT INTO users (username, email, password, website) VALUES (:username, :email, :password, :website)");
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $email);  

            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt->bindParam(':password', $hashed_password);
            $stmt->bindParam(':website', $website);
            
            $stmt->execute();  

            $success_message = "Kayıt başarılı! Giriş yapabilirsiniz.";
            header("Location: auth-login.php?registered=true");
            exit();
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $error_message = "Bu kullanıcı adı veya e-posta adresi zaten kullanılıyor.";
            } else {
                // Hata mesajını detaylı göster
                $error_message = "Kayıt sırasında bir hata oluştu: " . $e->getMessage();
                // Geliştirme ortamında hata detaylarını görmek için:
                error_log("Database Error: " . $e->getMessage());
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Mazer Admin Dashboard</title>
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
                <a href="index.html"><img src="https://raw.githubusercontent.com/Artado-Project/devs/refs/heads/main/ArtadoDevs/images/favicon.ico" alt="Logo"></a>
            </div>
            <h1 class="auth-title">Kayıt Ol</h1>
            <p class="auth-subtitle mb-5">Hesap oluşturmak için bilgilerinizi giriniz.</p>

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
                    <input type="email" name="email" class="form-control form-control-xl" placeholder="E-posta" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    <div class="form-control-icon">
                        <i class="bi bi-envelope"></i>
                    </div>
                </div>
                <div class="form-group position-relative has-icon-left mb-4">
                    <input type="text" name="username" class="form-control form-control-xl" placeholder="Kullanıcı Adı" required value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                    <div class="form-control-icon">
                        <i class="bi bi-person"></i>
                    </div>
                </div>
                <div class="form-group position-relative has-icon-left mb-4">
                    <div class="input-group">
                        <span class="input-group-text" style="background: transparent; border-right: 0;">
                            <i class="bi bi-shield-lock"></i>
                        </span>
                        <input type="password" name="password" id="password" class="form-control form-control-xl" placeholder="Şifre" required>
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
                        <input type="password" name="cpass" id="cpass" class="form-control form-control-xl" placeholder="Şifre Tekrar" required>
                        <button type="button" class="btn btn-outline-secondary" id="toggleCPassword">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>
                <div class="form-group position-relative has-icon-left mb-4">
                    <input type="url" name="website" class="form-control form-control-xl" placeholder="Website (İsteğe bağlı)" value="<?php echo htmlspecialchars($_POST['website'] ?? ''); ?>">
                    <div class="form-control-icon">
                        <i class="bi bi-globe"></i>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-block btn-lg shadow-lg mt-5">Kayıt Ol</button>
            </form>
            <div class="text-center mt-5 text-lg fs-4">
                <p class='text-gray-600'>Zaten hesabınız var mı? <a href="auth-login.php" class="font-bold">Giriş Yap</a></p>
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

document.getElementById('toggleCPassword').addEventListener('click', function() {
    const passwordInput = document.getElementById('cpass');
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