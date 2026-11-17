<?php
require_once '../config.php';

function sendProjectApprovalEmail($project_id, $project_title, $project_category, $username) {
    global $db;
    
    try {
        // Admin email adreslerini al
        $admin_emails = ['sxi@artadosearch.com', 'arda@artadosearch.com'];
        
        $to = implode(',', $admin_emails);
        $subject = "Yeni Proje Onayı İsteği - " . $project_title;
        
        $message = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; padding: 20px; text-align: center; }
                .content { background-color: #f4f4f4; padding: 20px; }
                .info-box { background-color: #e8f4fd; border-left: 4px solid #007bff; padding: 15px; margin: 20px 0; }
                .button { display: inline-block; padding: 10px 20px; background-color: #435ebe; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🚀 Yeni Proje Onayı İsteği</h1>
                </div>
                <div class='content'>
                    <p>Merhaba Admin,</p>
                    <p><strong>$username</strong> yeni bir proje yükledi ve onayınızı bekliyor.</p>
                    
                    <div class='info-box'>
                        <p><strong>Proje Bilgileri:</strong></p>
                        <ul>
                            <li><strong>Başlık:</strong> $project_title</li>
                            <li><strong>Kategori:</strong> $project_category</li>
                            <li><strong>Proje ID:</strong> $project_id</li>
                        </ul>
                    </div>
                    
                    <p style='text-align: center;'>
                        <a href='https://devs.artado.xyz/admin/projects.php' class='button'>
                            Projeyi İncele ve Onayla
                        </a>
                    </p>
                    
                    <p>Bu proje şu an onay bekliyor. Proje detaylarını inceleyip onaylayabilir veya reddedebilirsiniz.</p>
                </div>
                <div class='footer'>
                    <p>&copy; 2026 Artado Developers. Tüm hakları saklıdır.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: noreply@artadosearch.com" . "\r\n";
        
        return mail($to, $subject, $message, $headers);
        
    } catch (Exception $e) {
        error_log("Project approval email error: " . $e->getMessage());
        return false;
    }
}
?>
