<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

function sendPasswordResetEmail($email, $token) {
    $mail = new PHPMailer(true);
    
    try {
        $mail->isSMTP();
        
        // Brevo SMTP Ayarları
        $mail->Host       = 'mail.artadosearch.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'sxi@artadosearch.com';
        $mail->Password   = 'Semih+8589';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
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

        $mail->setFrom('sxi@artadosearch.com', 'Artado Developers');
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
