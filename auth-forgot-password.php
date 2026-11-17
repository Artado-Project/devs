<?php
require_once 'includes/database.php';
require_once 'includes/session_start.php';

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    
    if (empty($email)) {
        $error_message = "Lütfen e-posta adresinizi giriniz.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Geçerli bir e-posta adresi giriniz.";
    } else {
        try {
            // Kullanıcının var olup olmadığını kontrol et
            $stmt = $db->prepare("SELECT id, email FROM users WHERE email = :email");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                // Token oluştur
                $token = bin2hex(random_bytes(32));
                $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                // Token'ı veritabanına kaydet
                $stmt = $db->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (:email, :token, :expires_at)");
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':token', $token);
                $stmt->bindParam(':expires_at', $expires_at);
                $stmt->execute();
                
                // Mail gönderme işlemi
                require_once 'includes/mailer.php';
                $mail_sent = sendPasswordResetEmail($email, $token);
                
                if ($mail_sent) {
                    $success_message = "Şifre sıfırlama bağlantısı e-posta adresinize gönderildi.";
                } else {
                    // Mail gönderilemezse, linki direkt göster (geliştirme ortamı için)
                    $project_path = dirname($_SERVER['SCRIPT_NAME']);
                    if ($project_path === '/' || $project_path === '\\') {
                        $project_path = '';
                    }
                    $reset_link = "http://" . $_SERVER['HTTP_HOST'] . $project_path . "/reset-password.php?token=" . $token;
                    $success_message = "E-posta gönderilirken bir sorun oluştu. Şifre sıfırlama bağlantınız: <br><a href='$reset_link' class='text-primary'>$reset_link</a>";
                }
                
            } else {
                // Güvenlik için kullanıcı bulunamasa bile başarılı mesajı göster
                $success_message = "Eğer bu e-posta adresine kayıtlı bir hesap varsa, şifre sıfırlama bağlantısı gönderildi.";
            }
        } catch (PDOException $e) {
            error_log("Password reset error: " . $e->getMessage());
            $error_message = "Bir hata oluştu. Lütfen daha sonra tekrar deneyin.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Şifremi Unuttum - Artado Developers</title>
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
                <a href="index.html"><img src="https://raw.githubusercontent.com/Artado-Project/devs/refs/heads/main/ArtadoDevs/images/favicon.ico" alt="Logo"></a>
            </div>
            <h1 class="auth-title">Şifremi Unuttum</h1>
            <p class="auth-subtitle mb-5">E-posta adresinizi girin, size şifre sıfırlama bağlantısı gönderelim.</p>

            <?php if($error_message): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <?php if($success_message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
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
                <button type="submit" class="btn btn-primary btn-block btn-lg shadow-lg mt-5">Gönder</button>
            </form>
            <div class="text-center mt-5 text-lg fs-4">
                <p class='text-gray-600'>Hesabınızı hatırladınız mı? <a href="login" class="font-bold">Giriş Yap</a>
                </p>
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