<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
function loadEnv($file) {
    if (!file_exists($file)) {
        return;
    }
    
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0) {
            continue;
        }
        
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            $_ENV[$key] = $value;
        }
    }
}

loadEnv(__DIR__ . '/../.env');

function sendPasswordResetEmail($email, $token) {
    $mail = new PHPMailer(true);
    
    try {
        $mail->isSMTP();
        
        // TLS SMTP Ayarları
        $mail->Host       = $_ENV['MAIL_HOST'] ?? 'localhost';
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['MAIL_USERNAME'] ?? '';
        $mail->Password   = $_ENV['MAIL_PASSWORD'] ?? '';
        $mail->SMTPSecure = $_ENV['MAIL_ENCRYPTION'] === 'smtps' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $_ENV['MAIL_PORT'] ?? 587;
        $mail->CharSet    = 'UTF-8';
        
        // Debug ve timeout ayarları
        $mail->SMTPDebug  = 0; // 0 = kapalı, 2 = detaylı debug
        $mail->Timeout    = 30; // 30 saniye timeout
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        $mail->setFrom($_ENV['MAIL_USERNAME'] ?? 'noreply@example.com', $_ENV['APP_NAME'] ?? 'Application');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = 'Şifre Sıfırlama Talebi - Artado Developers';
        
        // Dinamik proje yolu - REQUEST_URI'den proje klasörünü al
        $project_path = dirname(dirname($_SERVER['SCRIPT_NAME']));
        if ($project_path === '/' || $project_path === '\\') {
            $project_path = '';
        }
        $reset_link = "http://" . $_SERVER['HTTP_HOST'] . $project_path . "/reset-password.php?token=" . $token;
        
        $mail->Body = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #435ebe; color: white; padding: 20px; text-align: center; }
                .content { background-color: #f4f4f4; padding: 20px; }
                .button { display: inline-block; padding: 10px 20px; background-color: #435ebe; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Şifre Sıfırlama</h1>
                </div>
                <div class='content'>
                    <p>Merhaba,</p>
                    <p>Artado Developers hesabınız için şifre sıfırlama talebinde bulundunuz.</p>
                    <p>Şifrenizi sıfırlamak için aşağıdaki bağlantıya tıklayın:</p>
                    <p style='text-align: center;'>
                        <a href='{$reset_link}' class='button'>Şifremi Sıfırla</a>
                    </p>
                    <p>Veya aşağıdaki bağlantıyı tarayıcınıza kopyalayın:</p>
                    <p style='word-break: break-all;'>{$reset_link}</p>
                    <p><strong>Bu bağlantı 1 saat geçerlidir.</strong></p>
                    <p>Eğer bu talebi siz yapmadıysanız, bu e-postayı görmezden gelebilirsiniz.</p>
                </div>
                <div class='footer'>
                    <p>&copy; 2024 Artado Developers. Tüm hakları saklıdır.</p>
                </div>
            </div>
        </body>
        </html>
        ";

        $mail->AltBody = "Şifrenizi sıfırlamak için bu bağlantıya gidin: {$reset_link}\n\nBu bağlantı 1 saat geçerlidir.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mail gönderme hatası: {$mail->ErrorInfo}");
        return false;
    }
}
