<?php
require_once '../includes/database.php';
require_once '../includes/auth.php';

// Başarı ve hata mesajlarını tanımla - en başta boş olarak tanımla
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
$default_profile_photo = '../logo.png'; // URL olarak kalsın

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
    $country_code = trim($_POST['country_code'] ?? '+90');
    $phone_number = trim($_POST['phone'] ?? '');
    $phone = $country_code . ' ' . $phone_number;
    $birthday = !empty($_POST['birthday']) ? $_POST['birthday'] : null;

    // Validasyon kontrolleri
    if (empty($name)) {
        $error_message = "İsim alanı boş bırakılamaz.";
    } elseif (empty($email)) {
        $error_message = "E-posta alanı boş bırakılamaz.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Geçerli bir e-posta adresi giriniz.";
    } elseif (!empty($phone_number) && !preg_match('/^[0-9]{10}$/', $phone_number)) {
        $error_message = "Geçerli bir telefon numarası giriniz (10 haneli).";
    } else {
        try {
            // E-posta adresi başka bir kullanıcı tarafından kullanılıyor mu kontrol et
            $stmt = $db->prepare("SELECT id FROM users WHERE email = :email AND id != :user_id");
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $error_message = "Bu e-posta adresi başka bir kullanıcı tarafından kullanılıyor.";
            } else {
                // Profil bilgilerini güncelle
                $stmt = $db->prepare("UPDATE users SET username = :username, email = :email, phone = :phone, birthday = :birthday WHERE id = :user_id");
                $stmt->bindParam(':username', $name);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':phone', $phone);
                $stmt->bindParam(':birthday', $birthday, PDO::PARAM_NULL);
                $stmt->bindParam(':user_id', $user_id);

                if ($stmt->execute()) {
                    $success_message = "Profil bilgileriniz başarıyla güncellendi.";
                } else {
                    $error_message = "Profil güncellenirken bir hata oluştu.";
                }
            }
        } catch (PDOException $e) {
            $error_message = "Veritabanı hatası: " . $e->getMessage();
        }
    }
}

// Profil fotoğrafı yükleme işlemi
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['profile_photo'])) {
    $file = $_FILES['profile_photo'];

    if ($file['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $max_size = 10 * 1024 * 1024; // 10MB
        
        // Mime type kontrolü (daha güvenli)
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime_type, $allowed_types)) {
             $error_message = "Sadece resim dosyaları (JPG, PNG, GIF, WEBP) yükleyebilirsiniz.";
        } elseif ($file['size'] > $max_size) {
             $error_message = "Dosya boyutu en fazla 10MB olabilir.";
        } else {
            // Dizin ayarları
            $project_root = dirname(__DIR__); // /opt/lampp/htdocs/devs
            $upload_relative_path = 'public/uploads/avatars/';
            $upload_full_path = $project_root . '/' . $upload_relative_path;

            // Klasör yoksa oluştur
            if (!file_exists($upload_full_path)) {
                if (!mkdir($upload_full_path, 0755, true)) {
                    $error_message = "Yükleme dizini oluşturulamadı. Yazma izinlerini kontrol edin.";
                }
            }

            if (empty($error_message)) {
                // Benzersiz dosya adı oluştur
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                if (empty($extension)) {
                    // Mime type'dan uzantı bulmaya çalış
                    $extensions = [
                        'image/jpeg' => 'jpg',
                        'image/png' => 'png',
                        'image/gif' => 'gif',
                        'image/webp' => 'webp'
                    ];
                    $extension = $extensions[$mime_type] ?? 'jpg';
                }
                
                $new_filename = 'avatar_' . $user_id . '_' . time() . '.' . $extension;
                $target_file = $upload_full_path . $new_filename;
                $db_path = $upload_relative_path . $new_filename;

                if (move_uploaded_file($file['tmp_name'], $target_file)) {
                    // Eski fotoğrafı sil (eğer varsayılan değilse ve dosya varsa)
                    $old_photo = $user['profile_photo'] ?? null;
                    if ($old_photo && !filter_var($old_photo, FILTER_VALIDATE_URL)) {
                        $old_photo_path = $project_root . '/' . ltrim($old_photo, './');
                        if (file_exists($old_photo_path) && is_file($old_photo_path)) {
                            unlink($old_photo_path);
                        }
                    }

                    // Veritabanını güncelle
                    $stmt = $db->prepare("UPDATE users SET profile_photo = :photo WHERE id = :user_id");
                    $stmt->bindParam(':photo', $db_path);
                    $stmt->bindParam(':user_id', $user_id);

                    if ($stmt->execute()) {
                         $_SESSION['success_message'] = "Profil fotoğrafınız başarıyla güncellendi.";
                         header("Location: account-profile.php?updated=true");
                         exit();
                    } else {
                        $error_message = "Veritabanı güncellenemedi.";
                    }
                } else {
                    $error_message = "Dosya yüklenirken sunucu hatası oluştu. (move_uploaded_file failed)";
                }
            }
        }
    } elseif ($file['error'] !== UPLOAD_ERR_NO_FILE) {
         // Diğer upload hataları
         switch ($file['error']) {
            case UPLOAD_ERR_INI_SIZE:
                $error_message = "Dosya boyutu sunucu limitini aşıyor. (php.ini upload_max_filesize)";
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $error_message = "Dosya boyutu form limitini aşıyor.";
                break;
            case UPLOAD_ERR_PARTIAL:
                $error_message = "Dosya sadece kısmen yüklendi.";
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $error_message = "Geçici klasör eksik.";
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $error_message = "Dosya diske yazılamadı.";
                break;
            case UPLOAD_ERR_EXTENSION:
                $error_message = "Bir PHP eklentisi dosya yüklemesini durdurdu.";
                break;
            default:
                 $error_message = "Bilinmeyen bir yükleme hatası oluştu: " . $file['error'];
         }
    }
}

// Başarı mesajını session'dan al (sayfa yeniden yüklendikten sonra)
if (isset($_GET['updated']) && isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']); // Mesajı gösterdikten sonra temizle
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hesap Ayarları</title>
    
    <link rel="shortcut icon" href="data:image/svg+xml,%3csvg%20xmlns='http://www.w3.org/2000/svg'%20viewBox='0%200%2033%2034'%20fill-rule='evenodd'%20stroke-linejoin='round'%20stroke-miterlimit='2'%20xmlns:v='https://vecta.io/nano'%3e%3cpath%20d='M3%2027.472c0%204.409%206.18%205.552%2013.5%205.552%207.281%200%2013.5-1.103%2013.5-5.513s-6.179-5.552-13.5-5.552c-7.281%200-13.5%201.103-13.5%205.513z'%20fill='%23435ebe'%20fill-rule='nonzero'/%3e%3ccircle%20cx='16.5'%20cy='8.8'%20r='8.8'%20fill='%2341bbdd'/%3e%3c/svg%3e" type="image/x-icon">
    <link rel="shortcut icon" href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACEAAAAiCAYAAADRcLDBAAAEs2lUWHRYTUw6Y29tLmFkb2JlLnhtcAAAAAAAPD94cGFja2V0IGJlZ2luPSLvu78iIGlkPSJXNU0wTXBDZWhpSHpyZVN6TlRjemtjOWQiPz4KPHg6eG1wbWV0YSB4bWxuczp4PSJhZG9iZTpuczptZXRhLyIgeDp4bXB0az0iWE1QIENvcmUgNS41LjAiPgogPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4KICA8cmRmOkRlc2NyaXB0aW9uIHJkZjphYm91dD0iIgogICAgeG1sbnM6ZXhpZj0iaHR0cDovL25zLmFkb2JlLmNvbS9leGlmLzEuMC8iCiAgICB4bWxuczp0aWZmPSJodHRwOi8vbnMuYWRvYmUuY29tL3RpZmYvMS4wLyIKICAgIHhtbG5zOnBob3Rvc2hvcD0iaHR0cDovL25zLmFkb2JlLmNvbS9waG90b3Nob3AvMS4wLyIKICAgIHhtbG5zOnhtcD0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wLyIKICAgIHhtbG5zOnhtcE1NPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvbW0vIgogICAgeG1sbnM6c3RFdnQ9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZUV2ZW50IyIKICAgZXhpZjpQaXhlbFhEaW1lbnNpb249IjMzIgogICBleGlmOlBpeGVsWURpbWVuc2lvbj0iMzQiCiAgIGV4aWY6Q29sb3JTcGFjZT0iMSIKICAgdGlmZjpJbWFnZVdpZHRoPSIzMyIKICAgdGlmZjpJbWFnZUxlbmd0aD0iMzQiCiAgIHRpZmY6UmVzb2x1dGlvblVuaXQ9IjIiCiAgIHRpZmY6WFJlc29sdXRpb249Ijk2LjAiCiAgIHRpZmY6WVJlc29sdXRpb249Ijk2LjAiCiAgIHBob3Rvc2hvcDpDb2xvck1vZGU9IjMiCiAgIHBob3Rvc2hvcDpJQ0NQcm9maWxlPSJzUkdCIElFQzYxOTY2LTIuMSIKICAgeG1wOk1vZGlmeURhdGU9IjIwMjItMDMtMzFUMTA6NTA6MjMrMDI6MDAiCiAgIHhtcDpNZXRhZGF0YURhdGU9IjIwMjItMDMtMzFUMTA6NTA6MjMrMDI6MDAiPgogICA8eG1wTU06SGlzdG9yeT4KICAgIDxyZGY6U2VxPgogICAgIDxyZGY6bGkKICAgICAgc3RFdnQ6YWN0aW9uPSJwcm9kdWNlZCIKICAgICAgc3RFdnQ6c29mdHdhcmVBZ2VudD0iQWZmaW5pdHkgRGVzaWduZXIgMS4xMC4xIgogICAgICBzdEV2dDp3aGVuPSIyMDIyLTAzLTMxVDEwOjUwOjIzKzAyOjAwIi8+CiAgICA8L3JkZjpTZXE+CiAgIDwveG1wTU06SGlzdG9yeT4KICA8L3JkZjpEZXNjcmlwdGlvbj4KIDwvcmRmOlJERj4KPC94OnhtcG1ldGE+Cjw/eHBhY2tldCBlbmQ9InIiPz5V57uAAAABgmlDQ1BzUkdCIElFQzYxOTY2LTIuMQAAKJF1kc8rRFEUxz9maORHo1hYKC9hISNGTWwsRn4VFmOUX5uZZ36oeTOv954kW2WrKLHxa8FfwFZZK0WkZClrYoOe87ypmWTO7dzzud97z+nec8ETzaiaWd4NWtYyIiNhZWZ2TvE946WZSjqoj6mmPjE1HKWkfdxR5sSbgFOr9Ll/rXoxYapQVik8oOqGJTwqPL5i6Q5vCzeo6dii8KlwpyEXFL519LjLLw6nXP5y2IhGBsFTJ6ykijhexGra0ITl5bRqmWU1fx/nJTWJ7PSUxBbxJkwijBBGYYwhBgnRQ7/MIQIE6ZIVJfK7f/MnyUmuKrPOKgZLpEhj0SnqslRPSEyKnpCRYdXp/9++msneoFu9JgwVT7b91ga+LfjetO3PQ9v+PgLvI1xkC/m5A+h7F32zoLXug38dzi4LWnwHzjeg8UGPGbFfySvuSSbh9QRqZ6H+Gqrm3Z7l9zm+h+iafNUV7O5Bu5z3L/wAdthn7QIme0YAAAAJcEhZcwAADsQAAA7EAZUrDhsAAAJTSURBVFiF7Zi9axRBGIefEw2IdxFBRQsLWUTBaywSK4ubdSGVIY1Y6HZql8ZKCGIqwX/AYLmCgVQKfiDn7jZeEQMWfsSAHAiKqPiB5mIgELWYOW5vzc3O7niHhT/YZvY37/swM/vOzJbIqVq9uQ04CYwCI8AhYAlYAB4Dc7HnrOSJWcoJcBS4ARzQ2F4BZ2LPmTeNuykHwEWgkQGAet9QfiMZjUSt3hwD7psGTWgs9pwH1hC1enMYeA7sKwDxBqjGnvNdZzKZjqmCAKh+U1kmEwi3IEBbIsugnY5avTkEtIAtFhBrQCX2nLVehqyRqFoCAAwBh3WGLAhbgCRIYYinwLolwLqKUwwi9pxV4KUlxKKKUwxC6ZElRCPLYAJxGfhSEOCz6m8HEXvOB2CyIMSk6m8HoXQTmMkJcA2YNTHm3congOvATo3tE3A29pxbpnFzQSiQPcB55IFmFNgFfEQeahaAGZMpsIJIAZWAHcDX2HN+2cT6r39GxmvC9aPNwH5gO1BOPFuBVWAZue0vA9+A12EgjPadnhCuH1WAE8ivYAQ4ohKaagV4gvxi5oG7YSA2vApsCOH60WngKrA3R9IsvQUuhIGY00K4flQG7gHH/mLytB4C42EgfrQb0mV7us8AAMeBS8mGNMR4nwHamtBB7B4QRNdaS0M8GxDEog7iyoAguvJ0QYSBuAOcAt71Kfl7wA8DcTvZ2KtOlJEr+ByyQtqqhTyHTIeB+ONeqi3brh+VgIN0fohUgWGggizZFTplu12yW8iy/YLOGWMpDMTPXnl+Az9vj2HERYqPAAAAAElFTkSuQmCC" type="image/png">
    
  <link rel="stylesheet" crossorigin href="./assets/compiled/css/app.css">
  <link rel="stylesheet" crossorigin href="./assets/compiled/css/app-dark.css">
</head>

<body>
    <script src="assets/static/js/initTheme.js"></script>
    <div id="app">
        <div id="sidebar">
            <div class="sidebar-wrapper active">
    <div class="sidebar-header position-relative">
        <div class="d-flex justify-content-between align-items-center">
            <div class="logo">
                <a href="index.php"><img src="data:image/svg+xml,%3csvg%20xmlns='http://www.w3.org/2000/svg'%20viewBox='0%200%20152%2034'%20fill-rule='evenodd'%20stroke-linejoin='round'%20stroke-miterlimit='2'%3e%3cpath%20d='M0%2027.472c0%204.409%206.18%205.552%2013.5%205.552%207.281%200%2013.5-1.103%2013.5-5.513s-6.179-5.552-13.5-5.552c-7.281%200-13.5%201.103-13.5%205.513z'%20fill='%23435ebe'%20fill-rule='nonzero'/%3e%3ccircle%20cx='13.5'%20cy='8.8'%20r='8.8'%20fill='%2341bbdd'/%3e%3cpath%20d='M71.676%203.22c.709%200%201.279.228%201.71.684.431.431.646%201.013.646%201.748v22.496c0%20.709-.203%201.267-.608%201.672s-.937.608-1.596.608-1.178-.203-1.558-.608-.57-.963-.57-1.672V12.492l-6.46%2012.236c-.304.557-.633.975-.988%201.254-.355.253-.773.38-1.254.38s-.899-.127-1.254-.38-.684-.671-.988-1.254l-6.498-12.046v15.466c0%20.684-.203%201.241-.608%201.672-.38.405-.899.608-1.558.608s-1.178-.203-1.558-.608-.57-.963-.57-1.672V5.652c0-.735.203-1.317.608-1.748.431-.456%201.001-.684%201.71-.684.988%200%201.761.545%202.318%201.634l8.436%2016.074%208.398-16.074c.557-1.089%201.305-1.634%202.242-1.634zm15.801%207.942c2.584%200%204.497.646%205.738%201.938%201.267%201.267%201.9%203.205%201.9%205.814v9.272c0%20.684-.203%201.229-.608%201.634-.405.38-.962.57-1.672.57-.658%200-1.203-.203-1.634-.608-.405-.405-.608-.937-.608-1.596v-.836c-.431.988-1.114%201.761-2.052%202.318-.912.557-1.976.836-3.192.836-1.241%200-2.368-.253-3.382-.76s-1.811-1.203-2.394-2.09-.874-1.875-.874-2.964c0-1.368.342-2.445%201.026-3.23.71-.785%201.85-1.355%203.42-1.71s3.737-.532%206.498-.532h.95v-.874c0-1.241-.266-2.141-.798-2.698-.532-.583-1.393-.874-2.584-.874a7.78%207.78%200%200%200-2.242.342c-.76.203-1.659.507-2.698.912-.658.329-1.14.494-1.444.494-.456%200-.836-.165-1.14-.494-.278-.329-.418-.76-.418-1.292%200-.431.102-.798.304-1.102.228-.329.596-.633%201.102-.912.887-.481%201.938-.861%203.154-1.14%201.242-.279%202.458-.418%203.648-.418zm-1.178%2015.922c1.267%200%202.293-.418%203.078-1.254.811-.861%201.216-1.963%201.216-3.306v-.798h-.684c-1.697%200-3.015.076-3.952.228s-1.608.418-2.014.798-.608.899-.608%201.558c0%20.811.279%201.482.836%202.014.583.507%201.292.76%202.128.76zm27.476-.456c1.418%200%202.128.595%202.128%201.786%200%20.557-.178%201.001-.532%201.33-.355.304-.887.456-1.596.456h-12.692c-.634%200-1.153-.203-1.558-.608a1.97%201.97%200%200%201-.608-1.444c0-.583.228-1.14.684-1.672l9.766-11.286h-8.474c-.71%200-1.242-.152-1.596-.456s-.532-.747-.532-1.33.177-1.026.532-1.33.886-.456%201.596-.456h12.274c.658%200%201.178.203%201.558.608.405.38.608.861.608%201.444%200%20.608-.216%201.165-.646%201.672l-9.804%2011.286h8.892zm19.762-1.52c.431%200%20.773.165%201.026.494.279.329.418.773.418%201.33%200%20.785-.468%201.444-1.406%201.976-.861.481-1.836.874-2.926%201.178-1.089.279-2.128.418-3.116.418-2.989%200-5.358-.861-7.106-2.584s-2.622-4.079-2.622-7.068c0-1.9.38-3.585%201.14-5.054s1.824-2.609%203.192-3.42c1.394-.811%202.964-1.216%204.712-1.216%201.672%200%203.129.367%204.37%201.102s2.204%201.773%202.888%203.116%201.026%202.926%201.026%204.75c0%201.089-.481%201.634-1.444%201.634h-11.21c.152%201.748.646%203.04%201.482%203.876.836.811%202.052%201.216%203.648%201.216.811%200%201.52-.101%202.128-.304.634-.203%201.343-.481%202.128-.836.76-.405%201.318-.608%201.672-.608zm-6.574-10.602c-1.292%200-2.33.405-3.116%201.216-.76.811-1.216%201.976-1.368%203.496h8.588c-.05-1.545-.43-2.711-1.14-3.496-.709-.811-1.697-1.216-2.964-1.216zm22.43-3.268c.658-.051%201.178.089%201.558.418s.57.823.57%201.482c0%20.684-.165%201.191-.494%201.52s-.925.545-1.786.646l-1.14.114c-1.495.152-2.597.659-3.306%201.52-.684.861-1.026%201.938-1.026%203.23v7.98c0%20.735-.228%201.305-.684%201.71-.456.38-1.026.57-1.71.57s-1.254-.19-1.71-.57c-.431-.405-.646-.975-.646-1.71V13.442c0-.709.215-1.254.646-1.634.456-.38%201.013-.57%201.672-.57s1.19.19%201.596.57c.405.355.608.874.608%201.558v1.52c.481-1.115%201.19-1.976%202.128-2.584.962-.608%202.026-.95%203.192-1.026l.532-.038z'%20fill='%23435ebe'%20fill-rule='nonzero'/%3e%3c/svg%3e" alt="Logo" srcset=""></a>
            </div>
            <div class="theme-toggle d-flex gap-2  align-items-center mt-2">
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" aria-hidden="true"
                    role="img" class="iconify iconify--system-uicons" width="20" height="20"
                    preserveAspectRatio="xMidYMid meet" viewBox="0 0 21 21">
                    <g fill="none" fill-rule="evenodd" stroke="currentColor" stroke-linecap="round"
                        stroke-linejoin="round">
                        <path
                            d="M10.5 14.5c2.219 0 4-1.763 4-3.982a4.003 4.003 0 0 0-4-4.018c-2.219 0-4 1.781-4 4c0 2.219 1.781 4 4 4zM4.136 4.136L5.55 5.55m9.9 9.9l1.414 1.414M1.5 10.5h2m14 0h2M4.135 16.863L5.55 15.45m9.899-9.9l1.414-1.415M10.5 19.5v-2m0-14v-2"
                            opacity=".3"></path>
                        <g transform="translate(-210 -1)">
                            <path d="M220.5 2.5v2m6.5.5l-1.5 1.5"></path>
                            <circle cx="220.5" cy="11.5" r="4"></circle>
                            <path d="m214 5l1.5 1.5m5 14v-2m6.5-.5l-1.5-1.5M214 18l1.5-1.5m-4-5h2m14 0h2"></path>
                        </g>
                    </g>
                </svg>
                <div class="form-check form-switch fs-6">
                    <input class="form-check-input  me-0" type="checkbox" id="toggle-dark" style="cursor: pointer">
                    <label class="form-check-label"></label>
                </div>
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" aria-hidden="true"
                    role="img" class="iconify iconify--mdi" width="20" height="20" preserveAspectRatio="xMidYMid meet"
                    viewBox="0 0 24 24">
                    <path fill="currentColor"
                        d="m17.75 4.09l-2.53 1.94l.91 3.06l-2.63-1.81l-2.63 1.81l.91-3.06l-2.53-1.94L12.44 4l1.06-3l1.06 3l3.19.09m3.5 6.91l-1.64 1.25l.59 1.98l-1.7-1.17l-1.7 1.17l.59-1.98L15.75 11l2.06-.05L18.5 9l.69 1.95l2.06.05m-2.28 4.95c.83-.08 1.72 1.1 1.19 1.85c-.32.45-.66.87-1.08 1.27C15.17 23 8.84 23 4.94 19.07c-3.91-3.9-3.91-10.24 0-14.14c.4-.4.82-.76 1.27-1.08c.75-.53 1.93.36 1.85 1.19c-.27 2.86.69 5.83 2.89 8.02a9.96 9.96 0 0 0 8.02 2.89m-1.64 2.02a12.08 12.08 0 0 1-7.8-3.47c-2.17-2.19-3.33-5-3.49-7.82c-2.81 3.14-2.7 7.96.31 10.98c3.02 3.01 7.84 3.12 10.98.31Z">
                    </path>
                </svg>
            </div>
            <div class="sidebar-toggler  x">
                <a href="#" class="sidebar-hide d-xl-none d-block"><i class="bi bi-x bi-middle"></i></a>
            </div>
        </div>
    </div>
    <div id="   ">
            <div class="sidebar-wrapper active">
                <div class="sidebar-header position-relative">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="logo">
                            <a href="index.php"><img
                                    src="https://raw.githubusercontent.com/Artado-Project/devs/refs/heads/main/ArtadoDevs/images/favicon.ico"
                                    alt="Logo" srcset=""></a>
                        </div>
                        <div class="theme-toggle d-flex gap-2  align-items-center mt-2">
                            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
                                aria-hidden="true" role="img" class="iconify iconify--system-uicons" width="20"
                                height="20" preserveAspectRatio="xMidYMid meet" viewBox="0 0 21 21">
                                <g fill="none" fill-rule="evenodd" stroke="currentColor" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <path
                                        d="M10.5 14.5c2.219 0 4-1.763 4-3.982a4.003 4.003 0 0 0-4-4.018c-2.219 0-4 1.781-4 4c0 2.219 1.781 4 4 4zM4.136 4.136L5.55 5.55m9.9 9.9l1.414 1.414M1.5 10.5h2m14 0h2M4.135 16.863L5.55 15.45m9.899-9.9l1.414-1.415M10.5 19.5v-2m0-14v-2"
                                        opacity=".3"></path>
                                    <g transform="translate(-210 -1)">
                                        <path d="M220.5 2.5v2m6.5.5l-1.5 1.5"></path>
                                        <circle cx="220.5" cy="11.5" r="4"></circle>
                                        <path d="m214 5l1.5 1.5m5 14v-2m6.5-.5l-1.5-1.5M214 18l1.5-1.5m-4-5h2m14 0h2">
                                        </path>
                                    </g>
                                </g>
                            </svg>
                            <div class="form-check form-switch fs-6">
                                <input class="form-check-input  me-0" type="checkbox" id="toggle-dark"
                                    style="cursor: pointer">
                                <label class="form-check-label"></label>
                            </div>
                            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
                                aria-hidden="true" role="img" class="iconify iconify--mdi" width="20" height="20"
                                preserveAspectRatio="xMidYMid meet" viewBox="0 0 24 24">
                                <path fill="currentColor"
                                    d="m17.75 4.09l-2.53 1.94l.91 3.06l-2.63-1.81l-2.63 1.81l.91-3.06l-2.53-1.94L12.44 4l1.06-3l1.06 3l3.19.09m3.5 6.91l-1.64 1.25l.59 1.98l-1.7-1.17l-1.7 1.17l.59-1.98L15.75 11l2.06-.05L18.5 9l.69 1.95l2.06.05m-2.28 4.95c.83-.08 1.72 1.1 1.19 1.85c-.32.45-.66.87-1.08 1.27C15.17 23 8.84 23 4.94 19.07c-3.91-3.9-3.91-10.24 0-14.14c.4-.4.82-.76 1.27-1.08c.75-.53 1.93.36 1.85 1.19c-.27 2.86.69 5.83 2.89 8.02a9.96 9.96 0 0 0 8.02 2.89m-1.64 2.02a12.08 12.08 0 0 1-7.8-3.47c-2.17-2.19-3.33-5-3.49-7.82c-2.81 3.14-2.7 7.96.31 10.98c3.02 3.01 7.84 3.12 10.98.31Z">
                                </path>
                            </svg>
                        </div>
                        <div class="sidebar-toggler  x">
                            <a href="#" class="sidebar-hide d-xl-none d-block"><i class="bi bi-x bi-middle"></i></a>
                        </div>
                    </div>
                </div>
                <div class="sidebar-menu">
                    <ul class="menu">
                        <li class="sidebar-title">Konsol</li>

                        <li class="sidebar-item">
                            <a href="index.php" class='sidebar-link'>
                                <i class="bi bi-grid-fill"></i>
                                <span>Ana Sayfa</span>
                            </a>
                        </li>

                        <li class="sidebar-item">
                            <a href="projects.php" class='sidebar-link'>
                                <i class="bi bi-stack"></i>
                                <span>Projelerim</span>
                            </a>
                        </li>

                        <li class="sidebar-item has-sub">
                            <a href="#" class='sidebar-link'>
                                <i class="bi bi-collection-fill"></i>
                                <span>Sosyal Medya</span>
                            </a>
                            <ul class="submenu">
                                <li class="submenu-item">
                                    <a href="https://artadosearch.com" class="submenu-link">Artado Search</a>
                                </li>
                                <li class="submenu-item">
                                    <a href="https://forum.artado.xyz" class="submenu-link">Forum</a>
                                </li>
                                <li class="submenu-item">
                                    <a href="https://matrix.to/#/#artadoproject:matrix.org" class="submenu-link">Matrix</a>
                                </li>
                                <li class="submenu-item">
                                    <a href="https://x.com/ArtadoL" class="submenu-link">Twitter</a>
                                </li>
                            </ul>
                        </li>

                        <li class="sidebar-item has-sub">
                            <a href="#" class='sidebar-link'>
                                <i class="bi bi-grid-1x2-fill"></i>
                                <span>Destekte Bulunun</span>
                            </a>
                            <ul class="submenu">
                                <li class="submenu-item">
                                    <a href="https://kreosus.com/artadosoft?hl=tr" target="_blank" class="submenu-link">Kreosus</a>
                                </li>
                            </ul>
                        </li>

                        <li class="sidebar-title">Çalışma Panelim</li>

                        <li class="sidebar-item has-sub">
                            <a href="#" class='sidebar-link'>
                                <i class="bi bi-hexagon-fill"></i>
                                <span>Proje Oluştur</span>
                            </a>
                            <ul class="submenu">
                                <li class="submenu-item">
                                    <a href="create-eklenti.php" class="submenu-link">Eklenti</a>
                                </li>
                                <li class="submenu-item">
                                    <a href="create-logo.php" class="submenu-link">Logo</a>
                                </li>
                                <li class="submenu-item">
                                    <a href="create-theme.php" class="submenu-link">Tema</a>
                                </li>
                                <li class="submenu-item">
                                    <a href="create-uyg-mobil.php" class="submenu-link">Mobil Uygulama</a>
                                </li>
                                <li class="submenu-item">
                                    <a href="create-uyg-pc.php" class="submenu-link">PC Uygulama</a>
                                </li>
                                <li class="submenu-item">
                                    <a href="create-game-pc.php" class="submenu-link">PC Oyun</a>
                                </li>
                                <li class="submenu-item">
                                    <a href="create-game-mobil.php" class="submenu-link">Mobil Oyun</a>
                                </li>
                            </ul>
                        </li>

                        <li class="sidebar-item">
                            <a href="todo-list.php" class='sidebar-link'>
                                <i class="bi bi-file-earmark-medical-fill"></i>
                                <span>Yapılacaklar Listesi</span>
                            </a>
                        </li>

                        <li class="sidebar-item">
                            <a href="announcements.php" class='sidebar-link'>
                                <i class="bi bi-journal-check"></i>
                                <span>Duyurular</span>
                            </a>
                        </li>

                        <li class="sidebar-title">Ayarlar</li>

                        <li class="sidebar-item has-sub active">
                            <a href="#" class='sidebar-link'>
                                <i class="bi bi-person-circle"></i>
                                <span>Hesap</span>
                            </a>
                            <ul class="submenu">
                                <li class="submenu-item active">
                                    <a href="account-profile.php" class="submenu-link">Profil</a>
                                </li>
                                <li class="submenu-item">
                                    <a href="account-security.php" class="submenu-link">Güvenlik</a>
                                </li>
                            </ul>
                        </li>

                        <li class="sidebar-item">
                            <a href="auth-login.php?logout=true" class='sidebar-link'>
                                <i class="bi bi-box-arrow-right"></i>
                                <span>Çıkış Yap</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        </div>
        <div id="main">
            <header class="mb-3">
                <a href="#" class="burger-btn d-block d-xl-none">
                    <i class="bi bi-justify fs-3"></i>
                </a>
            </header>
            
<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h3>Profil Ayarları</h3>
                <p class="text-subtitle text-muted">Profil bilgilerinizi buradan güncelleyebilirsiniz</p>
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Ana Sayfa</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Profil</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
    <section class="section">
        <div class="row">
            <div class="col-12 col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-center align-items-center flex-column">
                            <div class="avatar avatar-2xl">
                                <?php 
                                // Not: $profile_photo artık sayfanın başında doğru URL olarak hazırlanıyor.
                                // file_exists kontrolü kaldırıldı.
                                ?>
                                <img src="<?php echo htmlspecialchars($profile_photo); ?>" alt="Profil Fotoğrafı">
                            </div>
                            <form method="post" enctype="multipart/form-data" class="mt-3">
                                <div class="form-group">
                                    <input type="file" name="profile_photo" class="form-control" accept="image/*">
                                </div>
                                <button type="submit" class="btn btn-primary mt-2">Fotoğrafı Güncelle</button>
                            </form>
                            <h3 class="mt-3"><?php echo htmlspecialchars($user['name'] ?? ''); ?></h3>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-8">
                <div class="card">
                    <div class="card-body">
                        <!-- Başarı/Hata mesajları session'dan alınacak şekilde güncellendi -->
                        <?php if(!empty($success_message)): ?>
                            <div class="alert alert-success">
                                <?php echo $success_message; ?>
                            </div>
                        <?php endif; ?>

                        <?php if(!empty($error_message)): ?>
                            <div class="alert alert-danger">
                                <?php echo $error_message; ?>
                            </div>
                        <?php endif; ?>

                        <form method="post">
                            <div class="form-group">
                                <label for="name" class="form-label">İsim</label>
                                <input type="text" name="name" id="name" class="form-control" placeholder="İsminiz" 
                                    value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="email" class="form-label">E-posta</label>
                                <input type="email" name="email" id="email" class="form-control" placeholder="E-posta adresiniz" 
                                    value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="phone" class="form-label">Telefon</label>
                                <div class="input-group">
                                    <select class="form-select" name="country_code" style="max-width: 150px;">
                                        <option value="+90" <?php echo (strpos($user['phone'] ?? '', '+90') === 0) ? 'selected' : ''; ?>>Türkiye (+90)</option>
                                        <option value="+1" <?php echo (strpos($user['phone'] ?? '', '+1') === 0) ? 'selected' : ''; ?>>ABD/Kanada (+1)</option>
                                        <option value="+44" <?php echo (strpos($user['phone'] ?? '', '+44') === 0) ? 'selected' : ''; ?>>Birleşik Krallık (+44)</option>
                                        <option value="+49" <?php echo (strpos($user['phone'] ?? '', '+49') === 0) ? 'selected' : ''; ?>>Almanya (+49)</option>
                                        <option value="+33" <?php echo (strpos($user['phone'] ?? '', '+33') === 0) ? 'selected' : ''; ?>>Fransa (+33)</option>
                                        <option value="+39" <?php echo (strpos($user['phone'] ?? '', '+39') === 0) ? 'selected' : ''; ?>>İtalya (+39)</option>
                                        <option value="+31" <?php echo (strpos($user['phone'] ?? '', '+31') === 0) ? 'selected' : ''; ?>>Hollanda (+31)</option>
                                        <option value="+34" <?php echo (strpos($user['phone'] ?? '', '+34') === 0) ? 'selected' : ''; ?>>İspanya (+34)</option>
                                        <option value="+7" <?php echo (strpos($user['phone'] ?? '', '+7') === 0) ? 'selected' : ''; ?>>Rusya (+7)</option>
                                        <option value="+86" <?php echo (strpos($user['phone'] ?? '', '+86') === 0) ? 'selected' : ''; ?>>Çin (+86)</option>
                                    </select>
                                    <input type="tel" name="phone" id="phone" class="form-control" 
                                        placeholder="5XX XXX XX XX" 
                                        value="<?php 
                                            $phone = $user['phone'] ?? '';
                                            // Ülke kodunu kaldır
                                            echo htmlspecialchars(preg_replace('/^\+\d+\s*/', '', $phone)); 
                                        ?>">
                                </div>
                                <small class="text-muted">Örnek: 5XX XXX XX XX</small>

                            <div class="form-group">
                                <button type="submit" name="update_profile" class="btn btn-primary">Değişiklikleri Kaydet</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

            <footer>
    <div class="footer clearfix mb-0 text-muted">
        <div class="float-start">
            <p>2025 &copy; Artado Software</p>
        </div>
        <div class="float-end">
            <p>Sxinar Tarafından <span class="text-danger"><i class="bi bi-heart-fill icon-mid"></i></span>
                ile <a href="https://sxi.is-a.dev">Sxinar</a></p>
        </div>
    </div>
</footer>
        </div>
    </div>
    <script src="assets/static/js/components/dark.js"></script>
    <script src="assets/extensions/perfect-scrollbar/perfect-scrollbar.min.js"></script>
    
    
    <script src="assets/compiled/js/app.js"></script>
    

    
</body>

</html>